<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\EventStatus;
use App\Models\Event;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class EventsPerWeekChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'الفعاليات أسبوعياً (آخر 8 أسابيع)';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $labels = [];
        $data = [];

        $weekStart = Carbon::today()->startOfWeek()->subWeeks(7);

        for ($i = 0; $i < 8; $i++) {
            $from = $weekStart->copy()->addWeeks($i);
            $to = $from->copy()->endOfWeek();

            $labels[] = $from->format('m/d');
            $data[] = Event::whereIn('status', [EventStatus::Approved, EventStatus::Completed])
                ->whereBetween('start_date', [$from->toDateString(), $to->toDateString()])
                ->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'عدد الفعاليات',
                    'data' => $data,
                    'tension' => 0.3,
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
