<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EventController extends Controller
{
    public function show(Event $event): View
    {
        abort_unless($event->status->isPubliclyVisible(), 404);

        $event->load(['team', 'category', 'area', 'shelterCenter']);

        // فعاليات أخرى قريبة في نفس المنطقة
        $related = Event::query()
            ->publiclyVisible()
            ->upcoming()
            ->where('id', '!=', $event->id)
            ->where('area_id', $event->area_id)
            ->with(['team:id,name,slug', 'category:id,name,color,icon', 'area:id,name,slug', 'shelterCenter:id,name'])
            ->orderBy('start_date')
            ->limit(4)
            ->get();

        return view('pages.event-show', [
            'event' => $event,
            'related' => $related,
        ]);
    }

    /** الرابط القصير المستخدم في رموز QR المطبوعة */
    public function short(Event $event): RedirectResponse
    {
        return redirect()->route('events.show', $event);
    }
}
