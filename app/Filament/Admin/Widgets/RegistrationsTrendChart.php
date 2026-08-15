<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Registration;
use Filament\Widgets\ChartWidget;

class RegistrationsTrendChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'التسجيلات في الفعاليات';

    protected int|string|array $columnSpan = 1;

    public ?string $filter = '7';

    protected function getFilters(): ?array
    {
        return [
            '7' => 'آخر 7 أيام',
            '30' => 'آخر 30 يوماً',
            '90' => 'آخر 3 أشهر',
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $days = (int) ($this->filter ?? 7);
        $step = $days > 30 ? 7 : 1;

        $labels = [];
        $data = [];

        for ($offset = $days - 1; $offset >= 0; $offset -= $step) {
            $from = today()->subDays($offset);
            $to = $from->copy()->addDays($step - 1);

            $labels[] = $step === 1
                ? $from->translatedFormat('j M')
                : $from->format('m/d');

            $data[] = Registration::whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])->count();
        }

        return [
            'datasets' => [[
                'label' => 'تسجيل',
                'data' => $data,
                'borderColor' => '#7c5cff',
                'backgroundColor' => 'rgba(124, 92, 255, .14)',
                'tension' => 0.4,
                'fill' => true,
                'pointRadius' => 3,
                'pointBackgroundColor' => '#7c5cff',
            ]],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => false]],
            'scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]],
        ];
    }
}
