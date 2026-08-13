<?php

namespace App\Console\Commands;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Observers\EventObserver;
use Illuminate\Console\Command;

class MarkCompletedEvents extends Command
{
    protected $signature = 'events:mark-completed';

    protected $description = 'تحويل الفعاليات المعتمدة التي مضى تاريخها إلى "منفَّذة" كي تسجل الفرق حضورها';

    public function handle(): int
    {
        $count = Event::query()
            ->where('status', EventStatus::Approved)
            ->whereDate('start_date', '<', today())
            ->update(['status' => EventStatus::Completed->value]);

        if ($count > 0) {
            EventObserver::bustFeedCache();
        }

        $this->info("تم تحويل {$count} فعالية إلى منفَّذة.");

        return self::SUCCESS;
    }
}
