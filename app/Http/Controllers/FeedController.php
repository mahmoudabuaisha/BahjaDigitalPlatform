<?php

namespace App\Http\Controllers;

use App\Enums\Audience;
use App\Enums\EventStatus;
use App\Enums\LocationVisibility;
use App\Enums\RegistrationMode;
use App\Models\Area;
use App\Models\Category;
use App\Models\Event;
use App\Models\ShelterCenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * تغذية JSON مضغوطة بفعاليات أسبوعين + جداول مرجعية —
 * تُخزَّن في جهاز العائلة لتصفح الروزنامة دون إنترنت.
 *
 * المخطط 3 يحمل تفاصيل كل فعالية لا عنوانها فقط: الوصف وطريق الوصول
 * والأعمار والرسوم. بذلك تُقرأ صفحة أي فعالية والشبكة مقطوعة، حتى لو
 * لم تُفتح تلك الصفحة من قبل — وهذا هو الفرق بين روزنامة محفوظة فعلاً
 * وقائمة عناوين لا تُفتح. المفاتيح مختصرة لأن الحمولة تُنزَّل على 2G.
 */
class FeedController extends Controller
{
    /** المفتاح يحمل رقم المخطط: ترقيةٌ لا تُقدِّم حمولة قديمة الشكل */
    public const CACHE_KEY = 'events_feed_v3';

    /** سقف الوصف والشروط بالأحرف: الحمولة تُنزَّل على شبكة ضعيفة */
    private const TEXT_LIMIT = 700;

    public function __invoke(Request $request): JsonResponse|Response
    {
        $payload = Cache::remember(self::CACHE_KEY, 300, function (): array {
            $events = Event::query()
                ->publiclyVisible()
                ->with(['team:id,name', 'shelterCenter:id,visibility'])
                ->whereBetween('start_date', [today()->toDateString(), today()->addDays(14)->toDateString()])
                ->orderBy('start_date')
                ->orderBy('start_time')
                ->get();

            return [
                // عقد الأوفلاين (القسم 11.3): إصدار وتوقيتات وصلاحية وبصمة
                'schema' => 3,
                'v' => now()->toIso8601String(),
                'generated_at' => now()->toIso8601String(),
                'expires_at' => now()->addDay()->toIso8601String(),
                'areas' => Area::orderBy('sort_order')
                    ->get(['id', 'name', 'slug'])
                    ->map(fn (Area $a) => ['id' => $a->id, 'n' => $a->name, 's' => $a->slug])
                    ->all(),
                'cats' => Category::orderBy('sort_order')
                    ->get(['id', 'name', 'color'])
                    ->map(fn (Category $c) => ['id' => $c->id, 'n' => $c->name, 'c' => $c->color])
                    ->all(),
                'centers' => ShelterCenter::where('is_active', true)
                    ->where('visibility', '!=', LocationVisibility::Hidden->value)
                    ->get(['id', 'area_id', 'name'])
                    ->map(fn (ShelterCenter $s) => ['id' => $s->id, 'a' => $s->area_id, 'n' => $s->name])
                    ->all(),
                // الإلغاءات لها الأولوية عند كل اتصال — كي لا يظهر ملغى كأنه قائم
                'cancelled' => Event::query()
                    ->where('status', EventStatus::Cancelled)
                    ->whereDate('start_date', '>=', today()->subDays(3))
                    ->pluck('id')
                    ->all(),
                'events' => $events->map(fn (Event $e) => array_filter([
                    'id' => $e->id,
                    't' => $e->title,
                    'd' => $e->start_date->toDateString(),
                    's' => substr($e->start_time, 0, 5),
                    'e' => $e->end_time ? substr($e->end_time, 0, 5) : null,
                    'a' => $e->area_id,
                    'sc' => ($e->shelterCenter?->visibility?->showsName() ?? true) ? $e->shelter_center_id : null,
                    'c' => $e->category_id,
                    'tm' => $e->team?->name,
                    'loc' => $e->publicLocationDetails(),
                    // ما يجعل الصفحة تُقرأ كاملةً دون شبكة
                    'de' => Str::limit((string) $e->description, self::TEXT_LIMIT) ?: null,
                    'di' => Str::limit((string) $e->directions, self::TEXT_LIMIT) ?: null,
                    'tr' => Str::limit((string) $e->terms, self::TEXT_LIMIT) ?: null,
                    'ag' => $e->ageLabel(),
                    'fe' => $e->fee > 0 ? $e->feeLabel() : null,
                    'au' => $e->audience !== Audience::All ? $e->audience->getLabel() : null,
                    'rm' => $e->registration_mode === RegistrationMode::Approval ? 'approval' : 'direct',
                    'ec' => $e->expected_children,
                    'im' => $e->imageCardUrl(),
                    'pu' => $e->public_id,
                    'u' => $e->updated_at?->toIso8601String(),
                ], fn ($value) => $value !== null))->all(),
            ];
        });

        // البصمة من محتوى الفعاليات نفسه: تغيّر المحتوى = بصمة جديدة
        $checksum = md5(json_encode([$payload['events'], $payload['cancelled']], JSON_UNESCAPED_UNICODE));
        $payload['checksum'] = $checksum;

        $etag = '"'.$checksum.'"';

        // على شبكة 2G: طلب إعادة التحقق يكلف صفر بايتات تقريباً
        if ($request->header('If-None-Match') === $etag) {
            return response(null, 304)->header('ETag', $etag);
        }

        return response()->json($payload, 200, [
            'ETag' => $etag,
            'Last-Modified' => now()->toRfc7231String(),
            'Cache-Control' => 'public, max-age=120, stale-while-revalidate=600',
        ], JSON_UNESCAPED_UNICODE);
    }
}
