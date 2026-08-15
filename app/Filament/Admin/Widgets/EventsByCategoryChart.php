<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Category;
use Filament\Widgets\ChartWidget;

class EventsByCategoryChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'الفعاليات حسب الفئة';

    protected int|string|array $columnSpan = 1;

    /** ألوان الفئات في الموقع العام — لتتطابق اللوحة مع ما تراه العائلات */
    private const PALETTE = ['#7c5cff', '#10b981', '#f59e0b', '#f43f5e', '#0ea5e9', '#f97316'];

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $categories = Category::withCount('events')
            ->orderByDesc('events_count')
            ->get()
            ->filter(fn (Category $category) => $category->events_count > 0);

        return [
            'datasets' => [[
                'label' => 'فعالية',
                'data' => $categories->pluck('events_count')->all(),
                'backgroundColor' => $categories->values()
                    ->map(fn ($category, $index) => self::PALETTE[$index % count(self::PALETTE)])
                    ->all(),
                'borderWidth' => 0,
            ]],
            'labels' => $categories->pluck('name')->all(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'cutout' => '68%',
            'plugins' => [
                'legend' => [
                    'position' => 'right',
                    'labels' => ['usePointStyle' => true, 'boxWidth' => 8, 'padding' => 14],
                ],
            ],
        ];
    }
}
