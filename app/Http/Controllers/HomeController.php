<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Models\Area;
use App\Models\Category;
use App\Models\Event;
use App\Models\Team;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $upcoming = Event::query()
            ->publiclyVisible()
            ->upcoming()
            ->with(['team:id,name,slug', 'category', 'area:id,name,slug', 'shelterCenter:id,name'])
            ->orderBy('start_date')
            ->orderBy('start_time')
            ->limit(6)
            ->get();

        // عدّاد الفعاليات القادمة لكل فئة — يظهر على بطاقات الفئات
        $categories = Category::query()
            ->orderBy('sort_order')
            ->withCount(['events' => fn ($query) => $query->publiclyVisible()->upcoming()])
            ->get();

        // فعاليات قرب مكان العائلة — تُرتَّب بالجيرة ثم بالأسبق موعداً
        $family = auth()->user();

        $nearby = $family?->hasLocationAnchor()
            ? Event::query()
                ->publiclyVisible()
                ->upcoming()
                ->with(['team:id,name,slug', 'category', 'area:id,name,slug', 'shelterCenter:id,name'])
                ->nearestTo($family)
                ->orderBy('start_date')
                ->orderBy('start_time')
                ->limit(3)
                ->get()
            : null;

        return view('pages.home', [
            'upcoming' => $upcoming,
            'nearby' => $nearby,
            'family' => $family,
            'categories' => $categories,
            'areas' => Area::orderBy('sort_order')
                ->withCount(['events' => fn ($query) => $query->publiclyVisible()->upcoming()])
                ->get(),
            'stats' => [
                'upcoming' => Event::publiclyVisible()->upcoming()->count(),
                'completed' => Event::where('status', EventStatus::Completed)->count(),
                'children' => (int) Event::where('status', EventStatus::Completed)->sum('actual_children'),
                'teams' => Team::where('is_active', true)->count(),
            ],
        ]);
    }
}
