<?php

namespace App\Filament\Admin\Resources\NewsletterSubscribers\Pages;

use App\Filament\Admin\Resources\NewsletterSubscribers\NewsletterSubscriberResource;
use App\Models\AuditLog;
use App\Models\NewsletterSubscriber;
use App\Services\NewsletterDigest;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListNewsletterSubscribers extends ListRecords
{
    protected static string $resource = NewsletterSubscriberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendDigest')
                ->label('إرسال نشرة الأسبوع الآن')
                ->icon('heroicon-o-paper-airplane')
                ->requiresConfirmation()
                ->modalHeading('إرسال نشرة الأسبوع')
                ->modalDescription(fn (): string => sprintf(
                    'تصل فعاليات الأيام السبعة القادمة (%d فعالية) إلى %d مشتركاً مؤكَّداً. من وصلته نشرة اليوم لا تُرسل له ثانية.',
                    app(NewsletterDigest::class)->events()->count(),
                    NewsletterSubscriber::active()->count(),
                ))
                ->modalSubmitActionLabel('أرسلوا')
                ->action(function (): void {
                    $result = app(NewsletterDigest::class)->send();

                    AuditLog::record('newsletter.sent', null, after: $result);

                    Notification::make()
                        ->title("أُرسلت النشرة إلى {$result['sent']} مشتركاً")
                        ->body($result['skipped'] ? "تُخطّي {$result['skipped']}: وصلتهم نشرة اليوم أو لا فعاليات في محافظتهم." : null)
                        ->success()
                        ->send();
                }),
            Action::make('downloadCsv')
                ->label('تنزيل CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function (): StreamedResponse {
                    AuditLog::record('export.csv', null, after: ['type' => 'newsletter']);

                    return response()->streamDownload(function (): void {
                        $handle = fopen('php://output', 'w');

                        // BOM كي يفتح الملف في Excel بعربية سليمة
                        fwrite($handle, "\xEF\xBB\xBF");
                        fputcsv($handle, ['البريد', 'المحافظة', 'الحالة', 'اشترك في', 'آخر نشرة']);

                        NewsletterSubscriber::with('area:id,name')
                            ->orderBy('created_at')
                            ->lazy()
                            ->each(function (NewsletterSubscriber $subscriber) use ($handle): void {
                                fputcsv($handle, [
                                    $subscriber->email,
                                    $subscriber->area?->name ?? 'كل المحافظات',
                                    $subscriber->statusLabel(),
                                    $subscriber->created_at->format('Y-m-d H:i'),
                                    $subscriber->last_sent_at?->format('Y-m-d H:i'),
                                ]);
                            });

                        fclose($handle);
                    }, 'bahja-newsletter-'.now()->format('Y-m-d').'.csv', [
                        'Content-Type' => 'text/csv; charset=UTF-8',
                    ]);
                }),
        ];
    }

    public function getTabs(): array
    {
        return [
            'active' => Tab::make('مؤكَّدون')
                ->badge(NewsletterSubscriber::active()->count() ?: null)
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->active()),
            'pending' => Tab::make('بانتظار التأكيد')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('confirmed_at')->whereNull('unsubscribed_at')),
            'unsubscribed' => Tab::make('ملغون')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('unsubscribed_at')),
            'all' => Tab::make('الكل'),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'active';
    }
}
