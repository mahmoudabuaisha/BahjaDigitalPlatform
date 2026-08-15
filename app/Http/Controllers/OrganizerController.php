<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Models\Category;
use App\Models\Feedback;
use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrganizerController extends Controller
{
    public function __invoke(Request $request): View
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'cat' => (string) $request->query('cat', ''),
        ];

        $teams = Team::query()
            ->where('is_active', true)
            ->when($filters['q'] !== '', fn (Builder $query) => $query
                ->where('name', 'like', '%'.$filters['q'].'%'))
            ->when($filters['cat'] !== '', fn (Builder $query) => $query
                ->whereHas('events', fn (Builder $inner) => $inner
                    ->publiclyVisible()
                    ->whereRelation('category', 'slug', $filters['cat'])))
            ->withCount([
                'events as upcoming_count' => fn (Builder $query) => $query->publiclyVisible()->upcoming(),
                'events as completed_count' => fn (Builder $query) => $query->where('status', EventStatus::Completed),
            ])
            ->orderByDesc('upcoming_count')
            ->orderBy('name')
            ->paginate(9)
            ->withQueryString();

        // متوسط تقييم العائلات لكل فريق — استعلام واحد بدل استعلام لكل بطاقة
        $ratings = Feedback::query()
            ->whereNotNull('rating')
            ->join('events', 'events.id', '=', 'feedback.event_id')
            ->whereIn('events.team_id', $teams->pluck('id'))
            ->groupBy('events.team_id')
            ->selectRaw('events.team_id, AVG(feedback.rating) as average')
            ->pluck('average', 'team_id');

        // الفئة الغالبة لكل فريق — تُعرض شارةً على البطاقة
        $mainCategories = Team::query()
            ->whereIn('teams.id', $teams->pluck('id'))
            ->join('events', 'events.team_id', '=', 'teams.id')
            ->whereNotNull('events.category_id')
            ->groupBy('teams.id', 'events.category_id')
            ->selectRaw('teams.id as team_id, events.category_id, COUNT(*) as total')
            ->orderByDesc('total')
            ->get()
            ->unique('team_id')
            ->pluck('category_id', 'team_id');

        return view('pages.organizers', [
            'teams' => $teams,
            'filters' => $filters,
            'ratings' => $ratings,
            'mainCategories' => $mainCategories,
            'categories' => Category::orderBy('sort_order')->get()->keyBy('id'),
            'categoryList' => Category::orderBy('sort_order')->get(),
        ]);
    }
}
