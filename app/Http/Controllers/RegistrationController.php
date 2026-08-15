<?php

namespace App\Http\Controllers;

use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\Registration;
use App\Models\UserNotification;
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
            ->with(['event.category', 'event.area', 'event.shelterCenter', 'event.team', 'child'])
            ->get()
            ->sortBy(fn (Registration $registration) => $registration->event->start_date);

        return view('pages.my-events', [
            'upcoming' => $registrations->reject(fn ($registration) => $registration->event->hasEnded())->values(),
            'past' => $registrations->filter(fn ($registration) => $registration->event->hasEnded())->values(),
        ]);
    }

    public function store(Request $request, Event $event): RedirectResponse
    {
        abort_unless($event->status->isPubliclyVisible(), 404);

        $user = Auth::user();

        $data = $request->validate([
            'children' => ['required', 'array', 'min:1'],
            'children.*' => ['integer'],
            'note' => ['nullable', 'string', 'max:300'],
        ]);

        // أطفال وليّ الأمر وحدهم — لا يُحجز لطفل عائلة أخرى
        $children = $user->children()->whereIn('id', $data['children'])->get();

        if ($children->isEmpty()) {
            return back()->with('registration_error', 'اختاروا طفلاً واحداً على الأقل.');
        }

        if ($event->hasEnded()) {
            return back()->with('registration_error', 'انتهى موعد هذه الفعالية.');
        }

        // الأطفال المحجوزون مسبقاً لا يُحتسبون مرتين
        $alreadyBooked = $event->registrations()
            ->whereIn('child_id', $children->pluck('id'))
            ->holdingSeat()
            ->pluck('child_id');

        $newChildren = $children->reject(fn ($child) => $alreadyBooked->contains($child->id));

        if ($newChildren->isEmpty()) {
            return back()->with('registration_error', 'هؤلاء الأطفال محجوزون في هذه الفعالية بالفعل.');
        }

        $remaining = $event->seatsRemaining();

        if ($remaining !== null && $newChildren->count() > $remaining) {
            return back()->with('registration_error', $remaining > 0
                ? 'لم يتبقَّ سوى '.$remaining.' مقعد — اختاروا عدداً أقل من الأطفال.'
                : 'اكتمل العدد في هذه الفعالية.');
        }

        foreach ($newChildren as $child) {
            $event->registrations()->updateOrCreate(
                ['child_id' => $child->id],
                [
                    'user_id' => $user->id,
                    'children_count' => 1,
                    'note' => $data['note'] ?? null,
                    'status' => RegistrationStatus::Pending,
                    'review_note' => null,
                    'reviewed_at' => null,
                ],
            );
        }

        UserNotification::send(
            $user,
            'registration_submitted',
            'وصل طلب حجزكم — بانتظار موافقة الفريق',
            $event->title.' · '.$newChildren->count().' من الأطفال',
            route('events.show', $event),
        );

        // الفريق يرى الطلب في لوحته، ويصله إشعار كي لا يتأخّر الردّ
        foreach ($event->team?->users ?? [] as $manager) {
            UserNotification::send(
                $manager,
                'registration_received',
                'طلب حجز جديد على «'.$event->title.'»',
                $user->name.' — '.$newChildren->count().' من الأطفال',
            );
        }

        return back()->with('registration_done', true);
    }

    public function destroy(Registration $registration): RedirectResponse
    {
        abort_unless($registration->user_id === Auth::id(), 403);

        if (! $registration->isCancellable()) {
            return back()->with('registration_error',
                'لا يمكن الإلغاء قبل أقل من 24 ساعة من الموعد — تواصلوا مع الفريق.');
        }

        $registration->update(['status' => RegistrationStatus::Cancelled]);

        return back()->with('registration_cancelled', true);
    }
}
