<?php

namespace App\Filament\Admin\Resources\Events\Pages;

use App\Enums\EventStatus;
use App\Filament\Admin\Resources\Events\EventResource;
use App\Models\AuditLog;
use App\Models\Event;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListEvents extends ListRecords
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('فعالية جديدة'),
            Action::make('exportCsv')
                ->label('تصدير CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function (): StreamedResponse {
                    AuditLog::record('export.csv', null, after: ['type' => 'events']);

                    return response()->streamDownload(function (): void {
                        $handle = fopen('php://output', 'w');

                        // BOM كي يفتح الملف في Excel بعربية سليمة (القسم 14.2)
                        fwrite($handle, "\xEF\xBB\xBF");
                        fputcsv($handle, ['المعرف', 'الفعالية', 'الفريق', 'المحافظة', 'الفئة', 'التاريخ', 'الحالة', 'المتوقع', 'الأطفال الفعلي', 'المرافقون الفعلي', 'تحقق الإدارة']);

                        Event::with(['team:id,name', 'area:id,name', 'category:id,name', 'attendanceReport'])
                            ->orderBy('start_date')
                            ->lazy()
                            ->each(function (Event $event) use ($handle): void {
                                fputcsv($handle, [
                                    $event->public_id,
                                    $event->title,
                                    $event->team?->name,
                                    $event->area?->name,
                                    $event->category?->name,
                                    $event->start_date->toDateString(),
                                    $event->status->getLabel(),
                                    $event->expected_children,
                                    $event->attendanceReport?->children_actual,
                                    $event->attendanceReport?->guardians_actual,
                                    $event->attendanceReport?->verified_at ? 'نعم' : 'لا',
                                ]);
                            });

                        fclose($handle);
                    }, 'bahja-events-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
                }),
        ];
    }

    public function getTabs(): array
    {
        $countByStatus = Event::query()
            ->selectRaw('status, COUNT(*) AS total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'pending' => Tab::make('بانتظار الاعتماد')
                ->badge($countByStatus[EventStatus::Pending->value] ?? null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', EventStatus::Pending)),
            'approved' => Tab::make('معتمدة')
                ->badge($countByStatus[EventStatus::Approved->value] ?? null)
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', EventStatus::Approved)),
            'completed' => Tab::make('منفَّذة')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', EventStatus::Completed)),
            'rejected' => Tab::make('مرفوضة')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', EventStatus::Rejected)),
            'all' => Tab::make('الكل'),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'pending';
    }
}
