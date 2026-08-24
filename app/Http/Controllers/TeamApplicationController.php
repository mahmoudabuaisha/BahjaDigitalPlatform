<?php

namespace App\Http\Controllers;

use App\Enums\TeamApplicationStatus;
use App\Enums\UserRole;
use App\Models\TeamApplication;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * طلب انضمام فريق تطوعي (القسم 5.2): يُقدَّم من الموقع العام
 * ولا يبدأ الفريق التشغيل قبل اعتماد الإدارة.
 */
class TeamApplicationController extends Controller
{
    public function create(): View
    {
        return view('pages.join-team');
    }

    public function store(Request $request): RedirectResponse
    {
        // مصيدة السبام: حقل مخفي لا يملؤه البشر
        if ($request->filled('website')) {
            return redirect()->route('teams.join')->with('application_sent', true);
        }

        $data = $request->validate([
            'team_name' => ['required', 'string', 'max:120'],
            'contact_name' => ['required', 'string', 'max:120'],
            'contact_email' => ['required', 'email', 'max:255'],
            'contact_phone' => ['required', 'string', 'max:30'],
            'description' => ['required', 'string', 'max:1000'],
            'geographic_scope' => ['nullable', 'string', 'max:160'],
            'terms' => ['accepted'],
        ], [], [
            'team_name' => 'اسم الفريق',
            'contact_name' => 'اسم المسؤول',
            'contact_email' => 'البريد الإلكتروني',
            'contact_phone' => 'رقم الجوال',
            'description' => 'وصف النشاط',
            'terms' => 'الموافقة على الشروط',
        ]);

        unset($data['terms']);

        $application = TeamApplication::create($data + ['status' => TeamApplicationStatus::Pending]);

        User::query()
            ->whereIn('role', [UserRole::SuperAdmin, UserRole::Admin])
            ->pluck('id')
            ->each(fn (int $adminId) => UserNotification::send(
                $adminId,
                'team_application',
                'طلب انضمام فريق جديد: '.$application->team_name,
                $application->contact_name.' — '.$application->geographic_scope,
            ));

        return redirect()->route('teams.join')->with('application_sent', true);
    }
}
