<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\Registration;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * لوحة الفريق المنظِّم داخل الموقع — عرض فقط.
 * الإنشاء والتعديل وتسجيل الحضور تبقى في لوحة Filament بكل تحققاتها،
 * وأزرار هذه الصفحات تفتحها مباشرة.
 */
class OrganizerDashboardController extends Controller
{
    public function dashboard(): View
    {
        $team = Auth::user()->team;

        $events = Event::query()->where('team_id', $team->id);

        return view('pages.organizer.dashboard', [
            'team' => $team,
            'stats' => [
                'total' => (clone $events)->count(),
                'upcoming' => (clone $events)->publiclyVisible()->upcoming()->count(),
                'registrations' => Registration::whereRelation('event', 'team_id', $team->id)
                    ->holdingSeat()->count(),
                'completed' => (clone $events)->where('status', EventStatus::Completed)->count(),
            ],
            'pendingRegistrations' => Registration::query()
                ->whereRelation('event', 'team_id', $team->id)
                ->where('status', RegistrationStatus::Pending)
                ->count(),
            'recent' => (clone $events)
                ->with(['category', 'area', 'shelterCenter'])
                ->withCount(['registrations as seats_taken' => fn ($query) => $query->holdingSeat()])
                ->orderByDesc('start_date')
                ->limit(5)
                ->get(),
        ]);
    }

    public function events(Request $request): View
    {
        $team = Auth::user()->team;
        $filter = (string) $request->query('status', '');
        $search = trim((string) $request->query('q', ''));

        $base = Event::query()->where('team_id', $team->id);

        $events = (clone $base)
            ->with(['category', 'area', 'shelterCenter'])
            ->withCount(['registrations as seats_taken' => fn ($query) => $query->holdingSeat()])
            ->when($search !== '', fn (Builder $query) => $query->where('title', 'like', '%'.$search.'%'))
            ->when($filter !== '', function (Builder $query) use ($filter) {
                $statuses = match ($filter) {
                    'published' => [EventStatus::Approved],
                    'pending' => [EventStatus::Pending, EventStatus::Draft],
                    'completed' => [EventStatus::Completed],
                    'cancelled' => [EventStatus::Cancelled, EventStatus::Rejected],
                    default => [],
                };

                return $statuses ? $query->whereIn('status', $statuses) : $query;
            })
            ->orderByDesc('start_date')
            ->paginate(10)
            ->withQueryString();

        return view('pages.organizer.events', [
            'team' => $team,
            'events' => $events,
            'filter' => $filter,
            'search' => $search,
            'stats' => [
                'total' => (clone $base)->count(),
                'published' => (clone $base)->where('status', EventStatus::Approved)->count(),
                'pending' => (clone $base)->whereIn('status', [EventStatus::Pending, EventStatus::Draft])->count(),
                'cancelled' => (clone $base)->whereIn('status', [EventStatus::Cancelled, EventStatus::Rejected])->count(),
            ],
        ]);
    }
}
