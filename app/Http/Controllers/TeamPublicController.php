<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Models\Team;
use Illuminate\View\View;

class TeamPublicController extends Controller
{
    public function show(Team $team): View
    {
        abort_unless($team->is_active, 404);

        $upcoming = $team->upcomingEvents()
            ->with(['category:id,name,color,icon', 'area:id,name,slug', 'shelterCenter:id,name', 'team:id,name,slug'])
            ->limit(12)
            ->get();

        $completedCount = $team->events()
            ->where('status', \App\Enums\EventStatus::Completed)
            ->count();

        $childrenReached = (int) $team->events()
            ->where('status', \App\Enums\EventStatus::Completed)
            ->sum('actual_children');

        // متوسط تقييم العائلات لفعاليات هذا الفريق — يُعرض فقط إن وُجد تقييم
        $averageRating = Feedback::query()
            ->whereNotNull('rating')
            ->whereHas('event', fn ($query) => $query->where('team_id', $team->id))
            ->avg('rating');

        return view('pages.team-show', [
            'team' => $team,
            'upcoming' => $upcoming,
            'completedCount' => $completedCount,
            'childrenReached' => $childrenReached,
            'averageRating' => $averageRating ? (float) $averageRating : null,
        ]);
    }
}
