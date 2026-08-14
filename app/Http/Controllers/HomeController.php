<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Category;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(Request $request): View
    {
        // فعاليات 14 يوماً قادمة، مجمعة حسب اليوم — تُعرض SSR ويفلترها Alpine محلياً
        $events = Event::query()
            ->publiclyVisible()
            ->with(['team:id,name,slug', 'category:id,name,color,icon', 'area:id,name,slug', 'shelterCenter:id,name'])
            ->whereBetween('start_date', [today()->toDateString(), today()->addDays(14)->toDateString()])
            ->orderBy('start_date')
            ->orderBy('start_time')
            ->get();

        $eventsByDay = $events->groupBy(fn (Event $event) => $event->start_date->toDateString());

        $areas = Area::orderBy('sort_order')
            ->with(['shelterCenters' => fn ($q) => $q->where('is_active', true)->orderBy('name')])
            ->get();

        $categories = Category::orderBy('sort_order')->get();

        // الافتراضي هو "اليوم"، إلا إن خلا اليوم من الفعاليات — عندها لا نستقبل
        // العائلة بشاشة فارغة، بل بكل الأيام القادمة
        $defaultDay = $events->contains(fn (Event $event) => $event->start_date->isToday())
            ? 'today'
            : 'all';

        return view('pages.home', [
            'eventsByDay' => $eventsByDay,
            'areas' => $areas,
            'categories' => $categories,
            'initialFilters' => [
                'area' => (string) $request->query('area', ''),
                'center' => (string) $request->query('center', ''),
                'category' => (string) $request->query('cat', ''),
                'day' => (string) $request->query('day', $defaultDay),
            ],
        ]);
    }
}
