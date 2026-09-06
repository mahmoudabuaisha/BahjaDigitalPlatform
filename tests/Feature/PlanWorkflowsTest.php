<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Enums\OrgType;
use App\Enums\TeamApplicationStatus;
use App\Enums\UserRole;
use App\Models\Area;
use App\Models\Category;
use App\Models\Event;
use App\Models\EventSeries;
use App\Models\Team;
use App\Models\TeamApplication;
use App\Models\User;
use App\Services\TeamApplicationService;
use Database\Seeders\AreaSeeder;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** طلبات الانضمام، التكرار الأسبوعي، وتسجيل الحضور — وفق خطة الإنتاج */
class PlanWorkflowsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AreaSeeder::class);
        $this->seed(CategorySeeder::class);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::SuperAdmin, 'team_id' => null]);
    }

    // ── طلبات انضمام الفرق ──

    public function test_a_team_application_is_submitted_from_the_public_site(): void
    {
        $this->get(route('teams.join'))->assertOk()->assertSee('انضمّوا كفريق تطوعي');

        $this->post(route('teams.join.store'), [
            'team_name' => 'فريق الياسمين',
            'org_type' => 'volunteer_team',
            'area_id' => Area::first()->id,
            'base_location' => 'رفح — حي الجنينة',
            'contact_name' => 'أبو خليل',
            'contact_email' => 'yasmeen@example.com',
            'contact_phone' => '0599111222',
            'emergency_phone' => '0568222333',
            'description' => 'أنشطة ترفيهية ودعم نفسي في مراكز إيواء رفح.',
            'geographic_scope' => 'مراكز الإيواء الغربية في رفح',
            'coverage_areas' => [Area::first()->id],
            'activities' => ['games', 'storytelling'],
            'volunteers_count' => 12,
            'capacity_per_event' => 60,
            'terms' => '1',
        ])->assertSessionHas('application_sent');

        $application = TeamApplication::first();
        $this->assertSame(TeamApplicationStatus::Pending, $application->status);
        $this->assertSame(OrgType::VolunteerTeam, $application->org_type);
        $this->assertSame(['games', 'storytelling'], $application->activities);
        $this->assertSame(12, $application->volunteers_count);
        $this->assertNotNull($application->pledge_accepted_at);
    }

    public function test_the_honeypot_swallows_spam_without_saving(): void
    {
        $this->post(route('teams.join.store'), [
            'website' => 'http://spam.example',
            'team_name' => 'سبام',
        ])->assertSessionHas('application_sent');

        $this->assertSame(0, TeamApplication::count());
    }

    public function test_approving_an_application_creates_the_team_and_its_manager(): void
    {
        $application = TeamApplication::create([
            'team_name' => 'فريق الياسمين',
            'org_type' => 'initiative',
            'contact_name' => 'أبو خليل',
            'contact_email' => 'yasmeen@example.com',
            'contact_phone' => '0599111222',
            'emergency_phone' => '0568222333',
            'description' => 'أنشطة ترفيهية.',
            'activities' => ['theater', 'music'],
            'volunteers_count' => 8,
            'capacity_per_event' => 40,
            'status' => TeamApplicationStatus::Pending,
        ]);

        $this->actingAs($this->admin());
        $result = app(TeamApplicationService::class)->approve($application);

        $this->assertTrue($result['team']->is_active);
        $this->assertSame(UserRole::TeamManager, $result['manager']->role);
        $this->assertSame($result['team']->id, $result['manager']->team_id);
        $this->assertSame(TeamApplicationStatus::Approved, $application->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'application.approved']);

        // بيانات الطلب انتقلت كاملة إلى ملف الفريق — لا إعادة إدخال
        $this->assertSame(OrgType::Initiative, $result['team']->org_type);
        $this->assertSame(['theater', 'music'], $result['team']->activities);
        $this->assertSame('0568222333', $result['team']->emergency_phone);
        $this->assertSame(40, $result['team']->capacity_per_event);

        // كلمة المرور المولَّدة تعمل
        $this->post(route('logout'));
        $this->post('/login', [
            'email' => 'yasmeen@example.com',
            'password' => $result['password'],
        ])->assertRedirect(route('organizer.dashboard'));
    }

    public function test_suspending_a_team_locks_out_its_members(): void
    {
        $team = Team::factory()->create();
        $manager = User::factory()->create(['role' => UserRole::TeamManager, 'team_id' => $team->id]);

        $this->actingAs($this->admin());
        app(TeamApplicationService::class)->suspend($team, 'مخالفة سياسة الصور');

        $this->assertFalse($team->fresh()->is_active);
        $this->assertFalse($manager->fresh()->is_active);
        $this->assertDatabaseHas('audit_logs', ['action' => 'application.suspended']);
    }

    // ── التكرار الأسبوعي ──

    public function test_weekly_recurrence_creates_independent_occurrences(): void
    {
        $team = Team::factory()->create();
        $manager = User::factory()->create(['role' => UserRole::TeamManager, 'team_id' => $team->id]);

        $this->actingAs($manager)->post(route('organizer.events.store'), [
            'title' => 'حلقة حكايات أسبوعية',
            'category_id' => Category::first()->id,
            'audience' => 'all',
            'description' => 'قصص وحكايات كل أسبوع.',
            'start_date' => today()->addDays(3)->toDateString(),
            'start_time' => '16:00',
            'area_id' => Area::first()->id,
            'expected_children' => 30,
            'registration_mode' => 'approval',
            'repeat_weekly' => '1',
            'repeat_count' => 4,
            'action' => 'publish',
        ])->assertSessionHas('event_saved');

        $series = EventSeries::first();
        $events = Event::where('series_id', $series->id)->orderBy('start_date')->get();

        $this->assertSame(4, $events->count());
        $this->assertSame(4, (int) $series->occurrence_count);

        // أسبوع كامل بين كل موعدين
        $this->assertSame(
            today()->addDays(3)->addWeeks(3)->toDateString(),
            $events->last()->start_date->toDateString(),
        );

        // كل مرة مستقلة: إلغاء واحدة لا يمسّ أخواتها
        $events->first()->update(['status' => EventStatus::Cancelled]);
        $this->assertSame(3, Event::where('series_id', $series->id)->where('status', '!=', EventStatus::Cancelled)->count());
    }

    // ── الحضور الفعلي ──

    public function test_attendance_opens_only_after_the_event_ends(): void
    {
        $team = Team::factory()->create();
        $manager = User::factory()->create(['role' => UserRole::TeamManager, 'team_id' => $team->id]);

        $future = Event::factory()->approved()->create([
            'team_id' => $team->id,
            'start_date' => today()->addDays(3),
        ]);

        $this->actingAs($manager)
            ->get(route('organizer.events.attendance', $future))
            ->assertForbidden();
    }

    public function test_attendance_is_recorded_and_feeds_the_event_totals(): void
    {
        $team = Team::factory()->create();
        $manager = User::factory()->create(['role' => UserRole::TeamManager, 'team_id' => $team->id]);

        $event = Event::factory()->approved()->create([
            'team_id' => $team->id,
            'start_date' => today()->subDay(),
            'start_time' => '10:00',
        ]);

        $this->actingAs($manager);
        $this->get(route('organizer.events.attendance', $event))->assertOk();

        $this->post(route('organizer.events.attendance.store', $event), [
            'children_actual' => 42,
            'guardians_actual' => 15,
            'notes_private' => 'نقص في أدوات الرسم.',
        ])->assertSessionHas('event_saved');

        $event->refresh();
        $this->assertSame(EventStatus::Completed, $event->status);
        $this->assertSame(42, $event->actual_children);
        $this->assertSame(42, $event->attendanceReport->children_actual);
        $this->assertNull($event->attendanceReport->verified_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'attendance.submitted']);
    }

    public function test_a_manager_cannot_record_attendance_for_another_team(): void
    {
        $event = Event::factory()->approved()->create(['start_date' => today()->subDay()]);
        $intruder = User::factory()->create([
            'role' => UserRole::TeamManager,
            'team_id' => Team::factory()->create()->id,
        ]);

        $this->actingAs($intruder)
            ->post(route('organizer.events.attendance.store', $event), [
                'children_actual' => 10,
                'guardians_actual' => 2,
            ])
            ->assertForbidden();
    }
}
