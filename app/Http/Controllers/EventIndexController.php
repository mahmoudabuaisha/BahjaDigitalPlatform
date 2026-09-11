<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Category;
use App\Models\Event;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventIndexController extends Controller
{
    /** شرائح عمرية تُعرض للأهالي بدل إدخال رقم — القيمة "من-إلى" */
    public const AGE_BUCKETS = [
        '3-5' => 'من 3 إلى 5 سنوات',
        '6-9' => 'من 6 إلى 9 سنوات',
        '10-14' => 'من 10 إلى 14 سنة',
    ];

    public function __invoke(Request $request): View
    {
        $viewer = $request->user();

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'cat' => (string) $request->query('cat', ''),
            'area' => (string) $request->query('area', ''),
            'age' => (string) $request->query('age', ''),
            'when' => (string) $request->query('when', ''),
            // الترتيب: 'near' يقدّم الأقرب لمرساة مكان العائلة، وإلا فالأسبق موعداً
            'sort' => $request->query('sort') === 'near' ? 'near' : '',
        ];

        $events = Event::query()
            ->publiclyVisible()
            ->upcoming()
            ->with(['team:id,name,slug', 'category', 'area:id,name,slug', 'shelterCenter:id,name'])
            ->when($filters['q'] !== '', function (Builder $query) use ($filters) {
                $term = '%'.$filters['q'].'%';

                $query->where(fn (Builder $inner) => $inner
                    ->where('title', 'like', $term)
                    ->orWhere('description', 'like', $term)
                    ->orWhere('location_details', 'like', $term));
            })
            ->when($filters['cat'] !== '', fn (Builder $query) => $query
                ->whereRelation('category', 'slug', $filters['cat']))
            ->when($filters['area'] !== '', fn (Builder $query) => $query
                ->whereRelation('area', 'slug', $filters['area']))
            ->when($filters['when'] === 'today', fn (Builder $query) => $query
                ->whereDate('start_date', today()))
            ->when($filters['when'] === 'week', fn (Builder $query) => $query
                ->whereBetween('start_date', [today()->toDateString(), today()->addDays(7)->toDateString()]))
            ->when(array_key_exists($filters['age'], self::AGE_BUCKETS), function (Builder $query) use ($filters) {
                [$from, $to] = array_map('intval', explode('-', $filters['age']));

                // تقاطع المدى المطلوب مع مدى الفعالية؛ الفعاليات بلا فئة عمرية
                // محدَّدة تُستثنى كي لا نَعِد الأهل بما لم يصرّح به الفريق
                $query->whereNotNull('age_min')
                    ->whereNotNull('age_max')
                    ->where('age_min', '<=', $to)
                    ->where('age_max', '>=', $from);
            })
            ->when($filters['sort'] === 'near', fn ($query) => $query->nearestTo($viewer))
            ->orderBy('start_date')
            ->orderBy('start_time')
            ->paginate(9)
            ->withQueryString();

        return view('pages.events-index', [
            'events' => $events,
            'viewer' => $viewer,
            'filters' => $filters,
            'categories' => Category::orderBy('sort_order')
                ->withCount(['events' => fn ($query) => $query->publiclyVisible()->upcoming()])
                ->get(),
            'areas' => Area::orderBy('sort_order')->get(),
            'ageBuckets' => self::AGE_BUCKETS,
        ]);
    }
}
