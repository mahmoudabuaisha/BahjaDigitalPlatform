<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Category;
use App\Models\Event;
use App\Models\ShelterCenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * تغذية JSON مضغوطة بفعاليات 30 يوماً + جداول مرجعية —
 * تُخزَّن في الـ Service Worker لتصفح الروزنامة دون إنترنت.
 */
class FeedController extends Controller
{
    public function __invoke(Request $request): JsonResponse|Response
    {
        $payload = Cache::remember('events_feed', 300, function (): array {
            $events = Event::query()
                ->publiclyVisible()
                ->with('team:id,name')
                ->whereBetween('start_date', [today()->toDateString(), today()->addDays(30)->toDateString()])
                ->orderBy('start_date')
                ->orderBy('start_time')
                ->get();

            return [
                'v' => now()->toIso8601String(),
                'areas' => Area::orderBy('sort_order')
                    ->get(['id', 'name', 'slug'])
                    ->map(fn (Area $a) => ['id' => $a->id, 'n' => $a->name, 's' => $a->slug])
                    ->all(),
                'cats' => Category::orderBy('sort_order')
                    ->get(['id', 'name', 'color'])
                    ->map(fn (Category $c) => ['id' => $c->id, 'n' => $c->name, 'c' => $c->color])
                    ->all(),
                'centers' => ShelterCenter::where('is_active', true)
                    ->get(['id', 'area_id', 'name'])
                    ->map(fn (ShelterCenter $s) => ['id' => $s->id, 'a' => $s->area_id, 'n' => $s->name])
                    ->all(),
                'events' => $events->map(fn (Event $e) => array_filter([
                    'id' => $e->id,
                    't' => $e->title,
                    'd' => $e->start_date->toDateString(),
                    's' => substr($e->start_time, 0, 5),
                    'e' => $e->end_time ? substr($e->end_time, 0, 5) : null,
                    'a' => $e->area_id,
                    'sc' => $e->shelter_center_id,
                    'c' => $e->category_id,
                    'tm' => $e->team?->name,
                    'loc' => $e->location_details,
                ], fn ($value) => $value !== null))->all(),
            ];
        });

        $etag = '"'.md5($payload['v'].count($payload['events'])).'"';

        // على شبكة 2G: طلب إعادة التحقق يكلف صفر بايتات تقريباً
        if ($request->header('If-None-Match') === $etag) {
            return response(null, 304)->header('ETag', $etag);
        }

        return response()->json($payload, 200, [
            'ETag' => $etag,
            'Cache-Control' => 'public, max-age=300',
        ], JSON_UNESCAPED_UNICODE);
    }
}
