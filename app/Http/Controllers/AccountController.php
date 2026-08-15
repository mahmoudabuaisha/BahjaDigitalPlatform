<?php

namespace App\Http\Controllers;

use App\Enums\RegistrationStatus;
use App\Models\Area;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function dashboard(): View
    {
        $user = Auth::user();

        $upcoming = $user->registrations()
            ->with(['event.category', 'event.area', 'event.shelterCenter', 'child'])
            ->whereRelation('event', 'start_date', '>=', today())
            ->get()
            ->sortBy(fn ($registration) => $registration->event->start_date);

        return view('pages.account.dashboard', [
            'upcoming' => $upcoming,
            'stats' => [
                'upcoming' => $upcoming->whereIn('status', RegistrationStatus::holdingSeat())->count(),
                'pending' => $user->registrations()->where('status', RegistrationStatus::Pending)->count(),
                'children' => $user->children()->count(),
                'attended' => $user->registrations()
                    ->where('status', RegistrationStatus::Accepted)
                    ->whereRelation('event', 'start_date', '<', today())
                    ->count(),
            ],
            'notifications' => $user->notifications()->limit(4)->get(),
        ]);
    }

    public function profile(): View
    {
        return view('pages.account.profile', [
            'user' => Auth::user()->load('area'),
            'children' => Auth::user()->children()->orderBy('birth_date')->get(),
            'areas' => Area::orderBy('sort_order')->get(),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'in:male,female'],
            'area_id' => ['nullable', 'exists:areas,id'],
            'address' => ['nullable', 'string', 'max:200'],
        ]);

        $user->update($data);

        return back()->with('profile_saved', true);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($data['current_password'], Auth::user()->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'كلمة المرور الحالية غير صحيحة.',
            ]);
        }

        Auth::user()->update(['password' => $data['password']]);

        return back()->with('password_saved', true);
    }
}
