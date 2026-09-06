<?php

namespace App\Http\Controllers;

use App\Enums\OrgType;
use App\Enums\TeamApplicationStatus;
use App\Enums\UserRole;
use App\Models\Area;
use App\Models\TeamApplication;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * طلب انضمام فريق تطوعي (القسم 5.2): يُقدَّم من الموقع العام
 * ولا يبدأ الفريق التشغيل قبل اعتماد الإدارة.
 */
class TeamApplicationController extends Controller
{
    public function create(): View
    {
        return view('pages.join-team', [
            'areas' => Area::orderBy('name')->get(['id', 'name']),
            'orgTypes' => OrgType::options(),
            'activityOptions' => TeamApplication::ACTIVITIES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // مصيدة السبام: حقل مخفي لا يملؤه البشر
        if ($request->filled('website')) {
            return redirect()->route('teams.join')->with('application_sent', true);
        }

        $data = $request->validate([
            'team_name' => ['required', 'string', 'max:120'],
            'org_type' => ['required', Rule::enum(OrgType::class)],
            'area_id' => ['required', Rule::exists('areas', 'id')],
            'base_location' => ['nullable', 'string', 'max:160'],
            'contact_name' => ['required', 'string', 'max:120'],
            'contact_email' => ['required', 'email', 'max:255'],
            'contact_phone' => ['required', 'string', 'max:30'],
            'emergency_phone' => ['nullable', 'string', 'max:30'],
            'description' => ['required', 'string', 'max:1000'],
            'geographic_scope' => ['nullable', 'string', 'max:160'],
            'coverage_areas' => ['nullable', 'array'],
            'coverage_areas.*' => [Rule::exists('areas', 'id')],
            'activities' => ['required', 'array', 'min:1'],
            'activities.*' => [Rule::in(array_keys(TeamApplication::ACTIVITIES))],
            'volunteers_count' => ['required', 'integer', 'min:1', 'max:5000'],
            'capacity_per_event' => ['required', 'integer', 'min:1', 'max:5000'],
            'terms' => ['accepted'],
        ], [], [
            'team_name' => 'اسم الفريق',
            'org_type' => 'نوع الجهة',
            'area_id' => 'المحافظة',
            'contact_name' => 'اسم المسؤول',
            'contact_email' => 'البريد الإلكتروني',
            'contact_phone' => 'رقم الواتساب',
            'emergency_phone' => 'رقم الطوارئ',
            'description' => 'وصف النشاط',
            'activities' => 'الأنشطة المتقنة',
            'volunteers_count' => 'عدد المتطوعين',
            'capacity_per_event' => 'الاستيعاب في النشاط الواحد',
            'terms' => 'تعهد سلامة الأطفال',
        ]);

        unset($data['terms']);

        $application = TeamApplication::create($data + [
            'status' => TeamApplicationStatus::Pending,
            // ختم زمني للتعهد — لا يصل الطلب أصلاً دون قبوله
            'pledge_accepted_at' => now(),
        ]);

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
