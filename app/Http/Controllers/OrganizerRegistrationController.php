<?php

namespace App\Http\Controllers;

use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\Registration;
use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * تسجيلات فعالية الفريق: يقبل الفريق الحجوزات أو يعتذر عنها بسبب يصل
 * وليّ الأمر كما هو — بلا وساطة من الإدارة.
 */
class OrganizerRegistrationController extends Controller
{
    public function index(Request $request, Event $event): View
    {
        $this->authorizeTeam($request, $event);

        $registrations = $event->registrations()
            ->with(['user:id,name,phone,email', 'child:id,name,birth_year'])
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'accepted' THEN 1 ELSE 2 END")
            ->latest('created_at')
            ->get();

        return view('pages.organizer.registrations', [
            'event' => $event,
            'registrations' => $registrations,
            'acceptedChildren' => $registrations
                ->where('status', RegistrationStatus::Accepted)
                ->sum(fn (Registration $registration): int => max(1, (int) $registration->children_count)),
        ]);
    }

    public function accept(Request $request, Event $event, Registration $registration): RedirectResponse
    {
        $this->authorizeTeam($request, $event);
        $this->ensureBelongs($event, $registration);

        if ($registration->status === RegistrationStatus::Pending) {
            $registration->update([
                'status' => RegistrationStatus::Accepted,
                'reviewed_at' => now(),
            ]);

            UserNotification::send(
                $registration->user_id,
                'registration_accepted',
                'تم قبول حجزكم في «'.$event->title.'»',
                $registration->child?->name.' — '.$event->start_date->translatedFormat('l j F').' الساعة '.substr($event->start_time, 0, 5),
                route('events.show', $event),
            );
        }

        return back()->with('status', 'قُبل الحجز ووصل إشعار وليّ الأمر.');
    }

    public function reject(Request $request, Event $event, Registration $registration): RedirectResponse
    {
        $this->authorizeTeam($request, $event);
        $this->ensureBelongs($event, $registration);

        $data = $request->validate([
            'review_note' => ['required', 'string', 'max:300'],
        ]);

        if ($registration->status === RegistrationStatus::Pending) {
            $registration->update([
                'status' => RegistrationStatus::Rejected,
                'review_note' => $data['review_note'],
                'reviewed_at' => now(),
            ]);

            UserNotification::send(
                $registration->user_id,
                'registration_rejected',
                'اعتذر الفريق عن حجزكم في «'.$event->title.'»',
                $data['review_note'],
                route('events.show', $event),
            );
        }

        return back()->with('status', 'أُرسل الاعتذار مع السبب إلى وليّ الأمر.');
    }

    private function authorizeTeam(Request $request, Event $event): void
    {
        abort_unless($event->team_id === $request->user()->team_id, 403);
    }

    private function ensureBelongs(Event $event, Registration $registration): void
    {
        abort_unless($registration->event_id === $event->id, 404);
    }
}
