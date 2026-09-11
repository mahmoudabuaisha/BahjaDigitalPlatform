<?php

namespace App\Services;

use App\Models\Area;
use App\Models\Event;
use App\Models\NeighbourhoodCall;
use App\Models\PlaceLink;
use App\Models\ShelterCenter;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * الطلب على الفعاليات كما تقوله العائلات نفسها.
 *
 * نداء واحد لا يُعرض لأحد خارج الإدارة؛ ما تراه الفرق تجميعٌ لا ينزل
 * عن عتبة العرض. والغاية عملية: أن يعرف الفريق أين ينتظره الأطفال.
 */
class NeighbourhoodDemandService
{
    /** مدى «القريب» الذي تلبّيه فعالية واحدة: ربع ساعة مشياً وزيادة */
    public const WALKABLE_MINUTES = 25;

    /**
     * فعالية جديدة تلبّي نداءات مكانها: تُغلَق وتُبلَّغ العائلات.
     * الدائرة تُغلق هنا — من نادى يسمع الجواب.
     *
     * @return array<int, int> معرّفات العائلات التي بلغها الجواب
     */
    public function answerWith(Event $event): array
    {
        $calls = NeighbourhoodCall::query()
            ->standing()
            ->where(function (Builder $query) use ($event): void {
                $query->where(fn (Builder $inner) => $inner
                    ->whereNull('shelter_center_id')
                    ->where('area_id', $event->area_id));

                $reachable = $this->placesReachableFrom($event->shelter_center_id);

                if ($reachable !== []) {
                    $query->orWhereIn('shelter_center_id', $reachable);
                } elseif ($event->shelter_center_id === null) {
                    // فعالية بلا مكان مدرج: أقرب ما نعرفه محافظتها
                    $query->orWhere('area_id', $event->area_id);
                }
            })
            ->with('user:id,shelter_center_id,area_id')
            ->get();

        foreach ($calls as $call) {
            $call->forceFill([
                'answered_event_id' => $event->id,
                'answered_at' => now(),
            ])->save();

            $distance = $event->proximityLabel($call->user);

            UserNotification::send(
                $call->user_id,
                'call_answered',
                'سمعنا نداءكم — فعالية قرب مكانكم',
                $event->title.' — '.$event->start_date->translatedFormat('l j F')
                    .($distance ? ' — '.$distance : ''),
                route('events.show', $event),
            );
        }

        return $calls->pluck('user_id')->unique()->values()->all();
    }

    /**
     * الأماكن التي تلبّيها فعالية في هذا المكان: هو نفسه وما يُمشى إليه.
     *
     * @return array<int, int>
     */
    public function placesReachableFrom(?int $centerId): array
    {
        return PlaceLink::withinWalk($centerId, self::WALKABLE_MINUTES);
    }

    /**
     * الطلب القائم لكل محافظة، مع عدد فعالياتها القادمة كي تُقرأ الفجوة.
     *
     * @return Collection<int, object>
     */
    public function byArea(): Collection
    {
        $demand = NeighbourhoodCall::query()
            ->standing()
            ->selectRaw('area_id, count(*) as calls, sum(children_count) as children')
            ->groupBy('area_id')
            ->get()
            ->keyBy('area_id');

        $events = Event::query()
            ->publiclyVisible()
            ->upcoming()
            ->selectRaw('area_id, count(*) as total')
            ->groupBy('area_id')
            ->pluck('total', 'area_id');

        return Area::query()
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug', 'sort_order'])
            ->map(fn (Area $area): object => (object) [
                'area' => $area,
                'calls' => (int) ($demand[$area->id]->calls ?? 0),
                'children' => (int) ($demand[$area->id]->children ?? 0),
                'events' => (int) ($events[$area->id] ?? 0),
            ]);
    }

    /**
     * الأماكن التي يكثر فيها النداء. للفرق: ما بلغ عتبة العرض فقط.
     *
     * @return Collection<int, object>
     */
    public function byPlace(bool $forTeams = true, int $limit = 10): Collection
    {
        $rows = NeighbourhoodCall::query()
            ->standing()
            ->whereNotNull('shelter_center_id')
            ->selectRaw('shelter_center_id, count(*) as calls, sum(children_count) as children, max(created_at) as latest')
            ->groupBy('shelter_center_id')
            ->when($forTeams, fn (Builder $query) => $query
                ->havingRaw('count(*) >= ?', [NeighbourhoodCall::TEAM_VISIBILITY_FLOOR]))
            ->orderByDesc(DB::raw('count(*)'))
            ->limit($limit)
            ->get();

        $centers = ShelterCenter::query()
            ->with('area:id,name,slug')
            ->whereIn('id', $rows->pluck('shelter_center_id'))
            ->get(['id', 'name', 'area_id'])
            ->keyBy('id');

        return $rows
            ->map(fn ($row): ?object => isset($centers[$row->shelter_center_id]) ? (object) [
                'center' => $centers[$row->shelter_center_id],
                'calls' => (int) $row->calls,
                'children' => (int) $row->children,
                'latest' => $row->latest,
            ] : null)
            ->filter()
            ->values();
    }

    /**
     * عدد العائلات المنادية من مكان هذه العائلة — تُطمأَن أنها ليست وحدها.
     * يعود صفراً إن لم يبلغ العدد عتبة العرض.
     */
    public function companionsFor(User $user): int
    {
        if (! $user->hasLocationAnchor()) {
            return 0;
        }

        $count = NeighbourhoodCall::query()
            ->standing()
            ->when(
                $user->shelter_center_id,
                fn (Builder $query) => $query->where('shelter_center_id', $user->shelter_center_id),
                fn (Builder $query) => $query->where('area_id', $user->area_id)->whereNull('shelter_center_id'),
            )
            ->count();

        return $count >= NeighbourhoodCall::TEAM_VISIBILITY_FLOOR ? $count : 0;
    }

    /** النداء القائم لهذه العائلة، إن وُجد */
    public function standingCallOf(User $user): ?NeighbourhoodCall
    {
        return NeighbourhoodCall::query()
            ->standing()
            ->where('user_id', $user->id)
            ->latest()
            ->first();
    }
}
