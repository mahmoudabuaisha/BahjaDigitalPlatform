<?php

namespace App\Http\Controllers;

use App\Enums\RegistrationStatus;
use App\Models\Area;
use App\Models\ShelterCenter;
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
            'user' => Auth::user()->load(['area', 'shelterCenter']),
            'children' => Auth::user()->children()->orderBy('birth_date')->get(),
            'areas' => Area::orderBy('sort_order')->get(),
            // المعالم مجمَّعة بمحافظتها كي يصفّيها المتصفّح بلا طلب شبكة
            'centersByArea' => ShelterCenter::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'area_id', 'name'])
                ->groupBy('area_id'),
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
            'shelter_center_id' => ['nullable', 'exists:shelter_centers,id'],
            'address' => ['nullable', 'string', 'max:200'],
        ]);

        // المعلم يتبع محافظته: تغيير المحافظة يلغي معلماً لا ينتمي إليها
        if ($data['shelter_center_id'] ?? null) {
            $belongs = ShelterCenter::whereKey($data['shelter_center_id'])
                ->where('area_id', $data['area_id'] ?? 0)
                ->exists();

            if (! $belongs) {
                $data['shelter_center_id'] = null;
            }
        }

        if (($data['area_id'] ?? null) !== $user->area_id
            || ($data['shelter_center_id'] ?? null) !== $user->shelter_center_id) {
            $data['location_set_at'] = now();
        }

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
