<?php

namespace Tests\Feature;

use App\Filament\Team\Resources\Events\Pages\ListEvents;
use App\Models\Event;
use App\Models\Team;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * الاختبار الحرج: عزل بيانات الفرق عن بعضها وعن لوحة الإدارة.
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

    public function test_manager_sees_only_own_team_events_in_panel_table(): void
    {
        $ownEvents = Event::factory()->count(2)->approved()->create(['team_id' => $this->teamA->id]);
        $foreignEvents = Event::factory()->count(3)->approved()->create(['team_id' => $this->teamB->id]);

        Filament::setCurrentPanel('team');

        Livewire::actingAs($this->managerA)
            ->test(ListEvents::class)
            ->assertCanSeeTableRecords($ownEvents)
            ->assertCanNotSeeTableRecords($foreignEvents);
    }

    public function test_manager_cannot_access_admin_panel(): void
    {
        $this->actingAs($this->managerA)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_admin_cannot_access_team_panel(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/team')
            ->assertForbidden();
    }

    public function test_manager_of_inactive_team_cannot_access_team_panel(): void
    {
        $pendingTeam = Team::factory()->pending()->create();
        $manager = User::factory()->managerOf($pendingTeam)->create();

        $this->actingAs($manager)
            ->get('/team')
            ->assertForbidden();
    }

    public function test_manager_cannot_update_foreign_team_event_via_policy(): void
    {
        $foreignEvent = Event::factory()->approved()->create(['team_id' => $this->teamB->id]);

        $this->assertFalse($this->managerA->can('update', $foreignEvent));
        $this->assertFalse($this->managerA->can('view', $foreignEvent));
    }

    public function test_team_registration_page_renders_for_guests(): void
    {
        $this->get('/team/register')
            ->assertOk()
            ->assertSee('انضمام فريق جديد')
            ->assertSee('بيانات الفريق');
    }
}
