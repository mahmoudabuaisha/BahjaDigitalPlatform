<?php

namespace Tests\Feature;

use App\Enums\TeamApplicationStatus;
use App\Enums\UserRole;
use App\Mail\TeamApprovedMail;
use App\Models\Area;
use App\Models\Team;
use App\Models\TeamApplication;
use App\Models\User;
use App\Services\TeamApplicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * مسار الفرق بعد التوحيد: طلب واحد يمرّ باعتماد الإدارة، بريد ترحيب
 * ببيانات الدخول، ولوحة واحدة يدير منها الفريق ملفه وفعالياته.
 */
class UnifiedTeamPathTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\AreaSeeder::class);
        $this->seed(\Database\Seeders\CategorySeeder::class);
    }

    public function test_approving_an_application_emails_the_team_its_login_details(): void
    {
        Mail::fake();

        $application = TeamApplication::create([
            'team_name' => 'فريق نور الأمل',
            'contact_name' => 'أبو سامي',
            'contact_email' => 'noor@example.com',
            'contact_phone' => '0599111222',
            'area_id' => Area::first()->id,
            'description' => 'فريق تطوعي يقدّم أنشطة ترفيهية لأطفال مراكز الإيواء.',
            'activities' => ['coloring'],
            'status' => TeamApplicationStatus::Pending,
            'pledge_accepted_at' => now(),
        ]);

        $result = app(TeamApplicationService::class)->approve($application);

        Mail::assertQueued(
            TeamApprovedMail::class,
            fn (TeamApprovedMail $mail): bool => $mail->hasTo('noor@example.com')
                && $mail->password === $result['password']
                && $mail->team->is($result['team']),
        );

        // البريد يُبنى فعلاً دون أخطاء ويحمل بيانات الدخول
        $rendered = (new TeamApprovedMail($result['team'], $result['manager'], $result['password']))->render();

        $this->assertStringContainsString('noor@example.com', $rendered);
        $this->assertStringContainsString($result['password'], $rendered);
    }

    public function test_a_team_edits_its_own_profile_and_logo_without_admin(): void
    {
        Storage::fake('public');

        $team = Team::factory()->create(['name' => 'فريق قديم']);
        $manager = User::factory()->create([
            'role' => UserRole::TeamManager,
            'team_id' => $team->id,
        ]);

        $this->actingAs($manager)->get(route('organizer.profile'))->assertOk()->assertSee('ملف الفريق');

        $this->actingAs($manager)->put(route('organizer.profile.update'), [
            'name' => 'فريق بسمة الغد',
            'description' => 'نقدّم أنشطة ترفيهية ودعماً نفسياً للأطفال.',
            'logo' => UploadedFile::fake()->image('logo.jpg', 600, 600),
            'base_location' => 'مركز إيواء الشمال',
            'activities' => ['coloring', 'games'],
            'coverage_areas' => [Area::first()->id],
            'volunteers_count' => 12,
        ])->assertRedirect(route('organizer.profile'));

        $team->refresh();

        $this->assertSame('فريق بسمة الغد', $team->name);
        $this->assertSame(['coloring', 'games'], $team->activities);
        $this->assertSame(12, $team->volunteers_count);
        $this->assertNotNull($team->logo_path);
        Storage::disk('public')->assertExists($team->logo_path);
    }

    public function test_a_family_cannot_edit_a_team_profile(): void
    {
        $family = User::factory()->create(['role' => UserRole::Family, 'team_id' => null]);

        $this->actingAs($family)->get(route('organizer.profile'))->assertForbidden();
    }
}
