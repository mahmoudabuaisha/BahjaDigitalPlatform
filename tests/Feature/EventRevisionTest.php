<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Enums\RevisionStatus;
use App\Enums\UserRole;
use App\Models\Event;
use App\Models\EventRevision;
use App\Models\User;
use App\Services\RevisionService;
use Database\Seeders\AreaSeeder;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** قاعدة الخطة 6.3: تبقى آخر نسخة معتمدة منشورة حتى اعتماد التعديل */
class EventRevisionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AreaSeeder::class);
        $this->seed(CategorySeeder::class);
    }

    private function managerFor(Event $event): User
    {
        return User::factory()->create([
            'role' => UserRole::TeamManager,
            'team_id' => $event->team_id,
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::SuperAdmin, 'team_id' => null]);
    }

    public function test_a_manager_edit_on_a_published_event_does_not_change_what_families_see(): void
    {
        $event = Event::factory()->approved()->create(['title' => 'العنوان المنشور']);
        $this->actingAs($this->managerFor($event));

        $event->update(['title' => 'عنوان مقترح']);

        $event->refresh();
        $this->assertSame('العنوان المنشور', $event->title);
        $this->assertSame(EventStatus::Approved, $event->status);
        $this->assertSame(1, $event->revisions()->count());

        // زائر يفتح الصفحة: يرى العنوان المنشور لا المقترح
        $this->post(route('logout'));
        $this->get(route('events.show', $event))
            ->assertOk()
            ->assertSee('العنوان المنشور')
            ->assertDontSee('عنوان مقترح');
    }

    public function test_a_newer_edit_replaces_the_previous_pending_revision(): void
    {
        $event = Event::factory()->approved()->create();
        $this->actingAs($this->managerFor($event));

        $event->update(['title' => 'اقتراح أول']);
        $event->refresh()->update(['title' => 'اقتراح ثانٍ']);

        $this->assertSame(1, EventRevision::pending()->count());
        $this->assertSame('اقتراح ثانٍ', $event->pendingRevision()->first()->payload['title']);
    }

    public function test_approving_a_revision_applies_it_and_logs_the_decision(): void
    {
        $event = Event::factory()->approved()->create(['title' => 'قبل التعديل']);
        $this->actingAs($this->managerFor($event));
        $event->update(['title' => 'بعد التعديل']);

        $this->actingAs($this->admin());
        app(RevisionService::class)->approve($event->pendingRevision()->first());

        $event->refresh();
        $this->assertSame('بعد التعديل', $event->title);
        $this->assertSame(EventStatus::Approved, $event->status);
        $this->assertSame(RevisionStatus::Approved, $event->revisions()->first()->status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'revision.approved',
            'subject_id' => $event->id,
        ]);
    }

    public function test_rejecting_a_revision_keeps_the_published_version_and_notifies_the_team(): void
    {
        $event = Event::factory()->approved()->create(['title' => 'الأصل']);
        $manager = $this->managerFor($event);
        $this->actingAs($manager);
        $event->update(['title' => 'مقترح مرفوض']);

        $this->actingAs($this->admin());
        app(RevisionService::class)->reject($event->pendingRevision()->first(), 'الوصف غير مكتمل');

        $event->refresh();
        $this->assertSame('الأصل', $event->title);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $manager->id,
            'type' => 'revision_rejected',
        ]);
    }

    public function test_admins_edit_published_events_directly_without_revisions(): void
    {
        $event = Event::factory()->approved()->create(['title' => 'قبل']);

        $this->actingAs($this->admin());
        $event->update(['title' => 'بعد — تعديل إداري']);

        $this->assertSame('بعد — تعديل إداري', $event->fresh()->title);
        $this->assertSame(0, EventRevision::count());
    }

    public function test_every_event_gets_a_stable_public_link(): void
    {
        $event = Event::factory()->approved()->create();

        $this->assertNotNull($event->public_id);

        $this->get('/r/'.$event->public_id)
            ->assertRedirect(route('events.show', $event));
    }

    public function test_the_stable_link_of_another_teams_event_is_not_guessable_by_id(): void
    {
        $event = Event::factory()->approved()->create();

        $this->get('/r/'.$event->id)->assertNotFound();
    }
}
