<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * الاختبار الحرج: عزل بيانات الفرق عن بعضها وعن لوحة الإدارة،
 * وأن مسار الفرق موحَّد على لوحة واحدة (/organizer).
 */
class TeamPanelScopingTest extends TestCase
{
    use RefreshDatabase;

    private Team $teamA;

    private Team $teamB;

    private User $managerA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\AreaSeeder::class);
        $this->seed(\Database\Seeders\CategorySeeder::class);

        $this->teamA = Team::factory()->create();
        $this->teamB = Team::factory()->create();
        $this->managerA = User::factory()->managerOf($this->teamA)->create();
    }

    public function test_manager_sees_only_own_team_events_in_dashboard(): void
    {
        $own = Event::factory()->approved()->create([
            'team_id' => $this->teamA->id,
            'title' => 'يوم ألعاب فريقنا',
        ]);
        $foreign = Event::factory()->approved()->create([
            'team_id' => $this->teamB->id,
            'title' => 'فعالية فريق آخر',
        ]);

        $this->actingAs($this->managerA)
            ->get(route('organizer.events'))
            ->assertOk()
            ->assertSee($own->title)
            ->assertDontSee($foreign->title);
    }

    public function test_manager_cannot_open_foreign_team_registrations(): void
    {
        $foreign = Event::factory()->approved()->create(['team_id' => $this->teamB->id]);

        $this->actingAs($this->managerA)
            ->get(route('organizer.events.registrations', $foreign))
            ->assertForbidden();
    }

    public function test_manager_cannot_access_admin_panel(): void
    {
        $this->actingAs($this->managerA)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_admin_cannot_access_team_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('organizer.dashboard'))
            ->assertForbidden();
    }

    public function test_manager_of_inactive_team_cannot_access_team_dashboard(): void
    {
        $pendingTeam = Team::factory()->pending()->create();
        $manager = User::factory()->managerOf($pendingTeam)->create();

        $this->actingAs($manager)
            ->get(route('organizer.dashboard'))
            ->assertForbidden();
    }

    public function test_manager_cannot_update_foreign_team_event_via_policy(): void
    {
        $foreignEvent = Event::factory()->approved()->create(['team_id' => $this->teamB->id]);

        $this->assertFalse($this->managerA->can('update', $foreignEvent));
        $this->assertFalse($this->managerA->can('view', $foreignEvent));
    }

    public function test_the_old_self_registration_path_leads_to_the_application_form(): void
    {
        // لا تسجيل ذاتي بعد اليوم — كل فريق يمرّ بنموذج الانضمام واعتماد الإدارة
        $this->get('/team/register')->assertRedirect('/join-team');

        $this->get('/join-team')
            ->assertOk()
            ->assertSee('بيانات الفريق');
    }

    public function test_the_old_team_panel_path_redirects_to_the_single_dashboard(): void
    {
        $this->actingAs($this->managerA)
            ->get('/team')
            ->assertRedirect(route('organizer.dashboard'));

        $this->actingAs($this->managerA)
            ->get('/team/events/5/edit')
            ->assertRedirect(route('organizer.dashboard'));
    }
}
