<?php

namespace App\Console\Commands;

use App\Enums\EventStatus;
use App\Models\AuditLog;
use App\Models\Event;
use App\Observers\EventObserver;
use Illuminate\Console\Command;

/**
 * لحظة النشر المجدول (القسم 6.1): فعالية معتمدة بموعد نشر حان وقته
 * تصبح ظاهرة (الحقل يُصفَّر) ويُبثّ إعلانها لعائلات المحافظة الآن لا وقت الاعتماد.
 */
class ReleaseScheduledEvents extends Command
{
    protected $signature = 'events:release-scheduled';

    protected $description = 'إظهار الفعاليات المعتمدة التي حان موعد نشرها المجدول';

    public function handle(): int
    {
        $due = Event::query()
            ->where('status', EventStatus::Approved)
            ->whereNotNull('publish_at')
            ->where('publish_at', '<=', now())
            ->get();

        foreach ($due as $event) {
            $event->forceFill(['publish_at' => null])->save();

            (new EventObserver)->announceToArea($event);

            AuditLog::record('event.published', $event);
        }

        if ($due->isNotEmpty()) {
            $this->info('نُشرت '.$due->count().' فعالية مجدولة.');
        }

        return self::SUCCESS;
    }
}
