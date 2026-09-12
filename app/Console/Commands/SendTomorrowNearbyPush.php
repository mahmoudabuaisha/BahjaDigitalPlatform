<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Jobs\SendPushNotification;
use App\Models\Event;
use App\Models\PlaceLink;
use App\Models\User;
use App\Services\Push\WebPushSender;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * «غداً في حيّكم» — جوهر المنصّة في جملة واحدة تصل الهاتف مساءً.
 *
 * العائلة لا تبحث: يصلها أن فعالية ستُقام غداً على بُعد دقائق من بيتها.
 * ولا يُرسَل شيء لمن لم تحدّد مكانها أو لم تأذن بالإشعارات.
 */
class SendTomorrowNearbyPush extends Command
{
    protected $signature = 'push:tomorrow {--force : تجاهل الحماية من التكرار}';

    protected $description = 'إشعار مسائي لكل عائلة بفعاليات الغد القريبة من مكانها';

    public function handle(WebPushSender $sender): int
    {
        if (! $sender->isConfigured()) {
            $this->warn('مفاتيح VAPID غير مضبوطة — لا إشعارات تُرسل.');

            return self::SUCCESS;
        }

        $tomorrow = today()->addDay();

        $events = Event::query()
            ->publiclyVisible()
            ->whereDate('start_date', $tomorrow)
            ->with(['area:id,name', 'shelterCenter:id,name'])
            ->orderBy('start_time')
            ->get();

        if ($events->isEmpty()) {
            $this->info('لا فعاليات غداً — لا شيء يُرسل.');

            return self::SUCCESS;
        }

        $families = User::query()
            ->where('role', UserRole::Family)
            ->where('is_active', true)
            ->whereNotNull('area_id')
            ->whereExists(fn ($query) => $query
                ->selectRaw('1')
                ->from('push_subscriptions')
                ->whereColumn('push_subscriptions.user_id', 'users.id'))
            ->get();

        $walkableCache = [];
        $sent = 0;

        foreach ($families as $family) {
            $centerId = $family->shelter_center_id;

            $walkable = $walkableCache[$centerId] ??= PlaceLink::withinWalk($centerId, Event::NEARBY_WALK_MINUTES);

            $near = $events->filter(fn (Event $event): bool => $event->area_id === $family->area_id
                || ($event->shelter_center_id !== null && in_array($event->shelter_center_id, $walkable, true)));

            if ($near->isEmpty()) {
                continue;
            }

            $guard = 'push:tomorrow:'.$family->id.':'.$tomorrow->toDateString();

            // التشغيل اليدوي أو كرون مكرَّر لا يوقظ البيت مرتين
            if (! $this->option('force') && ! Cache::add($guard, true, now()->addDays(2))) {
                continue;
            }

            SendPushNotification::dispatch($family->id, $this->payload($near, $family));

            $sent++;
        }

        $this->info("أُرسل {$sent} إشعاراً لعائلات تنتظر فعاليات الغد.");

        return self::SUCCESS;
    }

    /**
     * @param  Collection<int, Event>  $near
     * @return array<string, mixed>
     */
    private function payload(Collection $near, User $family): array
    {
        // الأقرب مكاناً يتصدّر الرسالة، لا الأسبق موعداً
        $first = $near->sortBy(fn (Event $event) => [$event->proximityRank($family), $event->start_time])->first();

        $distance = $first->proximityLabel($family);
        $time = substr($first->start_time, 0, 5);

        $body = $first->title.' — الساعة '.$time.($distance ? ' · '.$distance : '');

        if ($near->count() > 1) {
            $body .= "\nو".($near->count() - 1 === 1 ? 'فعالية أخرى' : ($near->count() - 1).' فعاليات أخرى')
                .' قريبة منكم غداً.';
        }

        return [
            'title' => 'غداً في حيّكم',
            'body' => $body,
            'url' => $near->count() > 1
                ? route('events.index', ['sort' => 'near'])
                : route('events.show', $first),
            'tag' => 'bahja-tomorrow',
        ];
    }
}
