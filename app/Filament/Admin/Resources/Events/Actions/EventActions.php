<?php

namespace App\Filament\Admin\Resources\Events\Actions;

use App\Enums\EventStatus;
use App\Models\AuditLog;
use App\Models\Event;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;

/**
 * إجراءات الإشراف على الفعاليات — تُستخدم في جدول القائمة وصفحة العرض معاً.
 */
class EventActions
{
    public static function approve(): Action
    {
        return Action::make('approve')
            ->label('اعتماد')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->visible(fn (Event $record): bool => $record->status === EventStatus::Pending)
            ->modalHeading('اعتماد الفعالية')
            ->modalDescription('تظهر للعائلات فوراً — أو في الموعد المجدول إن حدّدتموه.')
            ->schema([
                DateTimePicker::make('publish_at')
                    ->label('نشر مؤجَّل (اختياري)')
                    ->helperText('اتركوه فارغاً للنشر الفوري. عند تحديد موعدٍ تبقى الفعالية معتمدةً مخفيّة ويصدر إعلانها لحظة النشر.')
                    ->seconds(false)
                    ->minDate(now()),
            ])
            ->action(function (Event $record, array $data): void {
                $publishAt = filled($data['publish_at'] ?? null) ? Carbon::parse($data['publish_at']) : null;

                $record->forceFill([
                    'status' => EventStatus::Approved,
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                    'publish_at' => $publishAt?->isFuture() ? $publishAt : null,
                    'rejection_reason' => null,
                ])->save();

                AuditLog::record('event.approved', $record, after: $record->publish_at
                    ? ['publish_at' => $record->publish_at->toDateTimeString()]
                    : null);

                Notification::make()
                    ->title($record->publish_at
                        ? 'اعتُمدت — وتُنشر '.$record->publish_at->translatedFormat('l j F الساعة H:i')
                        : 'تم اعتماد الفعالية')
                    ->success()
                    ->send();
            });
    }

    public static function reject(): Action
    {
        return Action::make('reject')
            ->label('رفض')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(fn (Event $record): bool => $record->status === EventStatus::Pending)
            ->modalHeading('رفض الفعالية')
            ->schema([
                Textarea::make('rejection_reason')
                    ->label('سبب الرفض (سيظهر للفريق)')
                    ->required()
                    ->maxLength(500)
                    ->rows(3),
            ])
            ->action(function (Event $record, array $data): void {
                $record->forceFill([
                    'status' => EventStatus::Rejected,
                    'rejection_reason' => $data['rejection_reason'],
                    'approved_by' => null,
                    'approved_at' => null,
                ])->save();

                AuditLog::record('event.rejected', $record, reason: $data['rejection_reason']);

                Notification::make()
                    ->title('تم رفض الفعالية')
                    ->body('سيرى الفريق سبب الرفض في لوحته.')
                    ->warning()
                    ->send();
            });
    }

    public static function qrCode(): Action
    {
        return Action::make('qr')
            ->label('رمز QR')
            ->icon('heroicon-o-qr-code')
            ->color('gray')
            ->modalHeading(fn (Event $record): string => 'رمز QR — '.$record->title)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('إغلاق')
            ->modalContent(function (Event $record): HtmlString {
                $url = $record->shortUrl();

                $svg = (new Builder(
                    writer: new SvgWriter,
                    data: $url,
                    size: 280,
                    margin: 12,
                ))->build()->getString();

                $base64 = base64_encode($svg);
                $fileName = 'bahja-event-'.$record->id.'-qr.svg';

                return new HtmlString(<<<HTML
                    <div style="display:flex;flex-direction:column;align-items:center;gap:1rem;padding:.5rem">
                        <div style="background:#fff;border-radius:.75rem;padding:.75rem;border:1px solid #e5e7eb">{$svg}</div>
                        <code dir="ltr" style="font-size:.8rem;user-select:all">{$url}</code>
                        <a download="{$fileName}"
                           href="data:image/svg+xml;base64,{$base64}"
                           style="display:inline-flex;align-items:center;gap:.4rem;font-weight:600;color:#d97706">
                            تنزيل الرمز للطباعة (SVG)
                        </a>
                    </div>
                    HTML);
            });
    }
}
