<?php

namespace App\Http\Controllers;

use App\Enums\RegistrationStatus;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    /** «فعالياتي» — حجوزات وليّ الأمر، القادمة أولاً */
    public function index(): View
    {
        $registrations = Auth::user()->registrations()
            ->with(['event.category', 'event.area', 'event.shelterCenter', 'event.team'])
            ->join('events', 'events.id', '=', 'registrations.event_id')
            ->orderBy('events.start_date')
            ->orderBy('events.start_time')
            ->select('registrations.*')
            ->get();

        return view('pages.my-events', [
            'upcoming' => $registrations->filter(fn ($registration) => ! $registration->event->hasEnded()),
            'past' => $registrations->filter(fn ($registration) => $registration->event->hasEnded()),
        ]);
    }

    public function store(Request $request, Event $event): RedirectResponse
    {
        abort_unless($event->status->isPubliclyVisible(), 404);

        $data = $request->validate([
            'children_count' => ['required', 'integer', 'min:1', 'max:10'],
            'note' => ['nullable', 'string', 'max:300'],
        ]);

        if ($event->hasEnded()) {
            return back()->with('registration_error', 'انتهى موعد هذه الفعالية.');
        }

        $remaining = $event->seatsRemaining();

        if ($remaining !== null && $data['children_count'] > $remaining) {
            return back()->with('registration_error', $remaining > 0
                ? 'لم يتبقَّ سوى '.$remaining.' مقعد — عدّلوا عدد الأطفال.'
                : 'اكتمل العدد في هذه الفعالية.');
        }

        // حجز واحد لكل عائلة: إعادة الحجز تُحدّث القائم بدل أن تُنشئ ثانياً
        $event->registrations()->updateOrCreate(
            ['user_id' => Auth::id()],
            [...$data, 'status' => RegistrationStatus::Confirmed],
        );

        return back()->with('registration_done', true);
    }

    public function destroy(Event $event): RedirectResponse
    {
        $registration = $event->registrations()
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $registration->update(['status' => RegistrationStatus::Cancelled]);

        return back()->with('registration_cancelled', true);
    }
}
