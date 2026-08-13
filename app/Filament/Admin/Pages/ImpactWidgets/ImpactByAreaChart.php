<?php

namespace App\Filament\Admin\Pages\ImpactWidgets;

use App\Filament\Admin\Pages\ImpactReport;
use App\Models\Area;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class ImpactByAreaChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'الأطفال المستفيدون حسب المنطقة';

    protected int|string|array $columnSpan = 'full';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $totals = ImpactReport::filteredQuery($this->pageFilters)
            ->selectRaw('area_id, SUM(COALESCE(actual_children, 0)) AS children')
            ->groupBy('area_id')
            ->pluck('children', 'area_id');

        $areas = Area::orderBy('sort_order')->get();

        return [
            'datasets' => [
                [
                    'label' => 'عدد الأطفال',
                    'data' => $areas->map(fn (Area $area) => (int) ($totals[$area->id] ?? 0))->all(),
                ],
            ],
            'labels' => $areas->pluck('name')->all(),
        ];
    }
}
