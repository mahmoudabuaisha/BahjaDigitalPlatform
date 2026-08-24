<?php

namespace App\Console\Commands;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Observers\EventObserver;
use Illuminate\Console\Command;

/**
 * الأرشفة اليومية (القسم 6.2): الفعالية المعتمدة التي مضى موعدها
 * تصير «منفَّذة» ليفتح تسجيل الحضور، وبعد ستين يوماً تخرج من العرض
 * التشغيلي إلى الأرشيف — وتبقى للتقارير والتدقيق، فالأرشفة ليست حذفاً.
 */
class ArchivePastEvents extends Command
{
    protected $signature = 'events:archive';

    protected $description = 'تحويل الفعاليات المنتهية إلى منفَّذة، وأرشفة القديمة منها';

    public function handle(): int
    {
        $completed = Event::query()
            ->where('status', EventStatus::Approved)
            ->whereDate('start_date', '<', today())
            ->update(['status' => EventStatus::Completed->value]);

        $archived = Event::query()
            ->whereIn('status', [EventStatus::Completed, EventStatus::Cancelled, EventStatus::Rejected])
            ->whereDate('start_date', '<', today()->subDays(60))
            ->whereNull('archived_at')
            ->update([
                'status' => EventStatus::Archived->value,
                'archived_at' => now(),
            ]);

        EventObserver::bustFeedCache();

        cache()->forever('events_archive_last_run', now()->toIso8601String());

        $this->info("منفَّذة: {$completed} — مؤرشفة: {$archived}");

        return self::SUCCESS;
    }
}
