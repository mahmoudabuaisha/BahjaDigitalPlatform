<?php

namespace App\Services;

use App\Mail\NewsletterDigestMail;
use App\Models\Event;
use App\Models\NewsletterSubscriber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

/**
 * نشرة الأسبوع: فعاليات الأيام السبعة القادمة كما نشرتها الفرق، تصل كل
 * مشترك مؤكَّد مرة واحدة في اليوم، محصورةً في محافظته إن اختارها.
 */
class NewsletterDigest
{
    /** الأيام التي تغطّيها النشرة ابتداءً من اليوم */
    public const DAYS = 7;

    /** @return Collection<int, Event> */
    public function events(?int $areaId = null): Collection
    {
        return Event::query()
            ->publiclyVisible()
            ->whereBetween('start_date', [today()->toDateString(), today()->addDays(self::DAYS - 1)->toDateString()])
            ->when($areaId, fn (Builder $query) => $query->where('area_id', $areaId))
            ->with(['team:id,name,slug', 'category', 'area:id,name,slug', 'shelterCenter'])
            ->orderBy('start_date')
            ->orderBy('start_time')
            ->get();
    }

    /**
     * الفعاليات مجمَّعة باليوم، مفتاح كل مجموعة تاريخه.
     *
     * @return BaseCollection<string, Collection<int, Event>>
     */
    public function eventsByDay(?int $areaId = null): BaseCollection
    {
        return $this->events($areaId)->groupBy(fn (Event $event): string => $event->start_date->toDateString());
    }

    /**
     * @return array{sent: int, skipped: int}
     */
    public function send(bool $force = false): array
    {
        $all = $this->events();
        $sent = 0;
        $skipped = 0;
        $dayKey = today()->toDateString();

        NewsletterSubscriber::query()
            ->active()
            ->with('area:id,name,slug')
            ->orderBy('id')
            ->chunkById(200, function (Collection $subscribers) use ($all, $force, $dayKey, &$sent, &$skipped): void {
                foreach ($subscribers as $subscriber) {
                    $events = $subscriber->area_id
                        ? $all->where('area_id', $subscriber->area_id)->values()
                        : $all;

                    // لا فعاليات في محافظته هذا الأسبوع: لا رسالة فارغة
                    if ($events->isEmpty()) {
                        $skipped++;

                        continue;
                    }

                    // كرون مكرَّر أو إرسال يدوي بعد المجدول لا يرسل اليوم نفسه مرتين
                    $guard = 'newsletter:digest:'.$subscriber->id.':'.$dayKey;

                    if (! $force && ! Cache::add($guard, true, now()->addDays(2))) {
                        $skipped++;

                        continue;
                    }

                    Mail::to($subscriber->email)->queue(new NewsletterDigestMail($subscriber, $events));
                    $subscriber->forceFill(['last_sent_at' => now()])->save();
                    $sent++;
                }
            });

        return ['sent' => $sent, 'skipped' => $skipped];
    }
}
