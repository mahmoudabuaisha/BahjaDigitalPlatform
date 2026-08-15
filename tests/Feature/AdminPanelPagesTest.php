<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Enums\UserRole;
use App\Filament\Admin\Pages\EventReview;
use App\Models\Event;
use App\Models\User;
use Database\Seeders\AreaSeeder;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPanelPagesTest extends TestCase
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

    public function test_the_review_page_is_closed_to_families(): void
    {
        $family = User::factory()->create(['role' => UserRole::Family, 'team_id' => null]);

        $this->actingAs($family)->get('/admin/event-review')->assertForbidden();
    }

    public function test_the_review_page_opens_on_the_pending_tab(): void
    {
        $pending = Event::factory()->create(['status' => EventStatus::Pending, 'title' => 'فعالية بانتظار المراجعة']);
        $approved = Event::factory()->approved()->create(['title' => 'فعالية معتمدة']);

        $this->actingAs($this->admin());

        $this->get('/admin/event-review')->assertOk();

        Livewire::test(EventReview::class)
            ->assertCanSeeTableRecords([$pending])
            ->assertCanNotSeeTableRecords([$approved])
            ->call('selectTab', 'approved')
            ->assertCanSeeTableRecords([$approved])
            ->assertCanNotSeeTableRecords([$pending]);
    }

    public function test_an_event_can_be_approved_from_the_review_page(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Pending]);

        $this->actingAs($this->admin());

        Livewire::test(EventReview::class)
            ->callTableAction('approve', $event)
            ->assertHasNoActionErrors();

        $this->assertSame(EventStatus::Approved, $event->fresh()->status);
    }

    public function test_the_review_tab_counts_follow_the_statuses(): void
    {
        Event::factory()->count(2)->create(['status' => EventStatus::Pending]);
        Event::factory()->approved()->create();
        Event::factory()->create(['status' => EventStatus::Rejected]);

        $this->actingAs($this->admin());

        $counts = Livewire::test(EventReview::class)->instance()->tabCounts();

        $this->assertSame(4, $counts['all']);
        $this->assertSame(2, $counts['pending']);
        $this->assertSame(1, $counts['approved']);
        $this->assertSame(1, $counts['rejected']);
    }

    public function test_the_users_page_lists_accounts_with_their_roles(): void
    {
        $manager = User::factory()->create(['role' => UserRole::TeamManager]);

        $this->actingAs($this->admin())
            ->get('/admin/users')
            ->assertOk()
            ->assertSee('إدارة المستخدمين')
            ->assertSee($manager->name);
    }
}
