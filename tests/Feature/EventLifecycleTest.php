<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\AreaSeeder::class);
        $this->seed(\Database\Seeders\CategorySeeder::class);
    }

    public function test_team_manager_edit_of_approved_event_reverts_to_pending(): void
    {
        $team = Team::factory()->create();
        $manager = User::factory()->managerOf($team)->create();
        $event = Event::factory()->approved()->create(['team_id' => $team->id]);

        $this->actingAs($manager);

        $event->update(['title' => 'عنوان معدل']);

        $this->assertSame(EventStatus::Pending, $event->fresh()->status);
        $this->assertNull($event->fresh()->approved_by);
    }

    public function test_admin_edit_of_approved_event_keeps_it_approved(): void
    {
        $admin = User::factory()->admin()->create();
        $event = Event::factory()->approved()->create();

        $this->actingAs($admin);

        $event->update(['title' => 'عنوان معدل من الإدارة']);

        $this->assertSame(EventStatus::Approved, $event->fresh()->status);
    }

    public function test_attendance_recording_does_not_revert_approved_status(): void
    {
        $team = Team::factory()->create();
        $manager = User::factory()->managerOf($team)->create();
        $event = Event::factory()->approved()->create(['team_id' => $team->id]);

        $this->actingAs($manager);

        $event->update(['actual_children' => 55, 'actual_caregivers' => 20]);

        $this->assertSame(EventStatus::Approved, $event->fresh()->status);
    }

    public function test_mark_completed_command_converts_past_approved_events(): void
    {
        $past = Event::factory()->approved()->create(['start_date' => today()->subDays(2)]);
        $future = Event::factory()->approved()->create(['start_date' => today()->addDay()]);

        $this->artisan('events:mark-completed')->assertSuccessful();

        $this->assertSame(EventStatus::Completed, $past->fresh()->status);
        $this->assertSame(EventStatus::Approved, $future->fresh()->status);
    }

    public function test_publicly_visible_scope_excludes_hidden_statuses(): void
    {
        Event::factory()->approved()->create();
        Event::factory()->completed()->create();
        Event::factory()->draft()->create();
        Event::factory()->pending()->create();
        Event::factory()->create(['status' => EventStatus::Rejected]);
        Event::factory()->create(['status' => EventStatus::Cancelled]);

        $this->assertSame(2, Event::publiclyVisible()->count());
    }
}
