<?php

namespace App\Services;

use App\Enums\TeamApplicationStatus;
use App\Enums\UserRole;
use App\Mail\TeamApprovedMail;
use App\Models\AuditLog;
use App\Models\Team;
use App\Models\TeamApplication;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/** اعتماد طلب فريق: يُنشأ الفريق وحساب مديره بكلمة سرّ تُسلَّم يدوياً */
class TeamApplicationService
{
    /** @return array{team: Team, manager: User, password: string} */
    public function approve(TeamApplication $application): array
    {
        return DB::transaction(function () use ($application): array {
            // كل ما جمعه الطلب ينتقل إلى ملف الفريق — لا إعادة إدخال بعد الاعتماد
            $team = Team::create([
                'name' => $application->team_name,
                'org_type' => $application->org_type,
                'area_id' => $application->area_id,
                'base_location' => $application->base_location,
                'slug' => Team::uniqueSlugFromName($application->team_name),
                'description' => $application->description,
                'contact_name' => $application->contact_name,
                'whatsapp_phone' => $application->contact_phone,
                'emergency_phone' => $application->emergency_phone,
                'coverage_details' => $application->geographic_scope,
                'coverage_areas' => $application->coverage_areas,
                'activities' => $application->activities,
                'volunteers_count' => $application->volunteers_count,
                'capacity_per_event' => $application->capacity_per_event,
                'is_active' => true,
            ]);

            $password = Str::password(12, symbols: false);

            $manager = User::create([
                'name' => $application->contact_name,
                'email' => $application->contact_email,
                'phone' => $application->contact_phone,
                'password' => $password,
                'role' => UserRole::TeamManager,
                'team_id' => $team->id,
                'is_active' => true,
            ]);

            // الإدارة راجعت الطلب وتواصلت مع أصحابه — بريد الحساب يُعتبر مؤكَّداً
            $manager->forceFill(['email_verified_at' => now()])->save();

            $application->update([
                'status' => TeamApplicationStatus::Approved,
                'team_id' => $team->id,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);

            AuditLog::record('application.approved', $application, after: ['team_id' => $team->id]);

            // بريد الترحيب يحمل بيانات الدخول — فشل الإرسال لا يُبطل الاعتماد
            rescue(
                fn () => Mail::to($manager->email)->queue(new TeamApprovedMail($team, $manager, $password)),
                report: true,
            );

            return ['team' => $team, 'manager' => $manager, 'password' => $password];
        });
    }

    public function reject(TeamApplication $application, string $reason): void
    {
        $application->update([
            'status' => TeamApplicationStatus::Rejected,
            'decision_reason' => $reason,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        AuditLog::record('application.rejected', $application, reason: $reason);
    }

    /** تعليق فريق: يُمنع دخول أعضائه وتختفي فعالياته القادمة من العرض */
    public function suspend(Team $team, string $reason): void
    {
        DB::transaction(function () use ($team, $reason): void {
            $team->update(['is_active' => false]);
            $team->users()->update(['is_active' => false]);

            AuditLog::record('application.suspended', $team, reason: $reason);
        });
    }

    public function reactivate(Team $team): void
    {
        DB::transaction(function () use ($team): void {
            $team->update(['is_active' => true]);
            $team->users()->update(['is_active' => true]);

            AuditLog::record('application.reactivated', $team);
        });
    }
}
