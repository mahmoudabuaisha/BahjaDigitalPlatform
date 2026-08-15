<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * حسابات العائلات على الموقع العام — منفصلة عن دخول اللوحات:
 * وليّ الأمر لا يملك صلاحية أي لوحة (انظر User::canAccessPanel).
 */
class FamilyAuthController extends Controller
{
    public function showLogin(): View
    {
        return view('pages.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة.',
            ]);
        }

        if (! Auth::user()->is_active) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'هذا الحساب موقوف — تواصلوا مع إدارة المنصّة.',
            ]);
        }

        $request->session()->regenerate();

        // أعضاء الفرق والإدارة يدخلون من لوحاتهم، لا من هنا
        if (! Auth::user()->isFamily()) {
            $panel = Auth::user()->role->isAdministrative() ? '/admin' : '/team';

            return redirect()->to($panel);
        }

        return redirect()->intended(route('account'));
    }

    public function showRegister(): View
    {
        return view('pages.auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            ...$data,
            'role' => UserRole::Family,
            'is_active' => true,
        ]);

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->route('account')->with('welcome', true);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
