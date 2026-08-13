<?php

namespace App\Filament\Admin\Pages;

use App\Enums\EventStatus;
use App\Filament\Admin\Pages\ImpactWidgets\ImpactByAreaChart;
use App\Filament\Admin\Pages\ImpactWidgets\ImpactStatsWidget;
use App\Models\Event;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class ImpactReport extends Dashboard
{
    use HasFiltersForm;

    protected static string $routePath = '/impact-report';

    protected static ?string $title = 'تقرير الأثر';

    protected static ?string $navigationLabel = 'تقرير الأثر';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'إدارة المحتوى';

    protected static ?int $navigationSort = 4;

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('نطاق التقرير')
                    ->columnSpanFull()
                    ->columns(4)
                    ->schema([
                        DatePicker::make('from')
                            ->label('من تاريخ'),
                        DatePicker::make('until')
                            ->label('إلى تاريخ'),
                        Select::make('area_id')
                            ->label('المنطقة')
                            ->options(fn () => \App\Models\Area::orderBy('sort_order')->pluck('name', 'id')->all()),
                        Select::make('team_id')
                            ->label('الفريق')
                            ->options(fn () => \App\Models\Team::orderBy('name')->pluck('name', 'id')->all()),
                    ]),
            ]);
    }

    public function getWidgets(): array
    {
        return [
            ImpactStatsWidget::class,
            ImpactByAreaChart::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadCsv')
                ->label('تنزيل CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn (): StreamedResponse => $this->downloadCsv()),
        ];
    }

    /** استعلام الفعاليات المنفَّذة وفق فلاتر الصفحة — تستخدمه الـ widgets أيضاً */
    public static function filteredQuery(?array $filters): Builder
    {
        $filters ??= [];

        return Event::query()
            ->where('status', EventStatus::Completed)
            ->when($filters['from'] ?? null, fn (Builder $q, string $from) => $q->whereDate('start_date', '>=', $from))
            ->when($filters['until'] ?? null, fn (Builder $q, string $until) => $q->whereDate('start_date', '<=', $until))
            ->when($filters['area_id'] ?? null, fn (Builder $q, $areaId) => $q->where('area_id', $areaId))
            ->when($filters['team_id'] ?? null, fn (Builder $q, $teamId) => $q->where('team_id', $teamId));
    }

    protected function downloadCsv(): StreamedResponse
    {
        $events = static::filteredQuery($this->filters)
            ->with(['team', 'area', 'shelterCenter', 'category'])
            ->orderBy('start_date')
            ->get();

        return response()->streamDownload(function () use ($events): void {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM كي يفتح الملف بالعربية سليماً في Excel
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'المعرف', 'الفعالية', 'الفريق', 'المنطقة', 'المركز', 'التصنيف',
                'التاريخ', 'عدد الأطفال', 'عدد المرافقين', 'المشاهدات',
            ]);

            foreach ($events as $event) {
                fputcsv($handle, [
                    $event->id,
                    $event->title,
                    $event->team?->name,
                    $event->area?->name,
                    $event->shelterCenter?->name ?? 'موقع حر',
                    $event->category?->name,
                    $event->start_date->format('Y-m-d'),
                    $event->actual_children,
                    $event->actual_caregivers,
                    $event->views_count,
                ]);
            }

            fclose($handle);
        }, 'bahja-impact-report-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
