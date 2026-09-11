<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Enums\RegistrationStatus;
use App\Enums\UserRole;
use App\Models\Child;
use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use App\Models\UserNotification;
use Database\Seeders\AreaSeeder;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FamilyRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AreaSeeder::class);
        $this->seed(CategorySeeder::class);
    }

    private function family(int $childrenCount = 1): User
    {
        $user = User::factory()->create([
            'role' => UserRole::Family,
            'team_id' => null,
            'is_active' => true,
        ]);

        Child::factory()->count($childrenCount)->create(['user_id' => $user->id]);

        return $user;
    }

    private function upcomingEvent(?int $capacity = 10): Event
    {
        return Event::factory()->approved()->create([
            'start_date' => today()->addDays(3),
            'start_time' => '10:00',
            'expected_children' => $capacity,
        ]);
    }

    public function test_visitor_can_register_an_account_and_lands_on_the_dashboard(): void
    {
        $this->post('/register', [
            'name' => 'أم محمّد',
            'email' => 'om@example.com',
            'phone' => '0599000000',
            'password' => 'kalimat-sirr',
            'password_confirmation' => 'kalimat-sirr',
        ])->assertRedirect(route('account'));

        $this->assertAuthenticated();
        $this->assertSame(UserRole::Family, User::where('email', 'om@example.com')->first()->role);
    }

    public function test_family_user_cannot_reach_admin_or_team_panels(): void
    {
        $this->actingAs($this->family());

        $this->get('/admin')->assertForbidden();
        $this->get('/team')->assertForbidden();
    }

    public function test_guest_is_sent_to_login_before_booking(): void
    {
        $event = $this->upcomingEvent();

        $this->post(route('registrations.store', $event), ['children' => [1]])
            ->assertRedirect(route('login'));
    }

    public function test_booking_starts_pending_and_notifies_the_family(): void
    {
        $event = $this->upcomingEvent();
        $family = $this->family();

        $this->actingAs($family);

        $this->post(route('registrations.store', $event), [
            'children' => $family->children->pluck('id')->all(),
        ])->assertSessionHas('registration_done');

        $registration = Registration::first();

        $this->assertSame(RegistrationStatus::Pending, $registration->status);
        $this->assertSame($family->children->first()->id, $registration->child_id);
        $this->assertSame(1, $event->fresh()->seatsTaken());
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $family->id,
            'type' => 'registration_submitted',
        ]);
    }

    public function test_family_cannot_book_a_child_of_another_family(): void
    {
        $event = $this->upcomingEvent();
        $otherChild = $this->family()->children->first();

        $this->actingAs($this->family());

        $this->post(route('registrations.store', $event), ['children' => [$otherChild->id]])
            ->assertSessionHas('registration_error');

        $this->assertSame(0, Registration::count());
    }

    public function test_booking_is_refused_when_it_exceeds_remaining_seats(): void
    {
        $event = $this->upcomingEvent(capacity: 1);
        $family = $this->family(childrenCount: 2);

        $this->actingAs($family);

        $this->post(route('registrations.store', $event), [
            'children' => $family->children->pluck('id')->all(),
        ])->assertSessionHas('registration_error');

        $this->assertSame(0, Registration::count());
    }

    public function test_booking_a_past_event_is_refused(): void
    {
        $event = Event::factory()->approved()->create(['start_date' => today()->subWeek()]);
        $family = $this->family();

        $this->actingAs($family);

        $this->post(route('registrations.store', $event), ['children' => $family->children->pluck('id')->all()])
            ->assertSessionHas('registration_error');

        $this->assertSame(0, Registration::count());
    }

    public function test_family_can_cancel_more_than_a_day_before_but_not_after(): void
    {
        $family = $this->family();
        $this->actingAs($family);

        $far = $this->upcomingEvent();
        $this->post(route('registrations.store', $far), ['children' => $family->children->pluck('id')->all()]);

        $registration = Registration::first();
        $this->delete(route('registrations.destroy', $registration))->assertSessionHas('registration_cancelled');
        $this->assertSame(RegistrationStatus::Cancelled, $registration->fresh()->status);

        // فعالية بعد ساعات: الإلغاء مغلق
        $soon = Event::factory()->approved()->create([
            'start_date' => today(),
            'start_time' => now()->addHours(3)->format('H:i:s'),
            'expected_children' => 10,
        ]);

        $imminent = Registration::create([
            'event_id' => $soon->id,
            'user_id' => $family->id,
            'child_id' => $family->children->first()->id,
            'children_count' => 1,
            'status' => RegistrationStatus::Pending,
        ]);

        $this->delete(route('registrations.destroy', $imminent))->assertSessionHas('registration_error');
        $this->assertSame(RegistrationStatus::Pending, $imminent->fresh()->status);
    }

    public function test_family_cannot_cancel_a_registration_of_another_family(): void
    {
        $event = $this->upcomingEvent();
        $other = $this->family();

        $registration = Registration::create([
            'event_id' => $event->id,
            'user_id' => $other->id,
            'child_id' => $other->children->first()->id,
            'children_count' => 1,
            'status' => RegistrationStatus::Pending,
        ]);

        $this->actingAs($this->family());

        $this->delete(route('registrations.destroy', $registration))->assertForbidden();
    }

    public function test_children_and_profile_are_managed_from_the_account(): void
    {
        $family = $this->family(childrenCount: 0);
        $this->actingAs($family);

        $this->post(route('children.store'), [
            'name' => 'أحمد',
            'birth_date' => today()->subYears(8)->toDateString(),
            'gender' => 'male',
        ])->assertSessionHas('child_saved');

        $child = Child::where('user_id', $family->id)->first();
        $this->assertSame('أحمد', $child->name);
        $this->assertSame(8, $child->age());

        $this->put(route('account.profile.update'), [
            'name' => 'أم أحمد',
            'email' => $family->email,
            'address' => 'مركز إيواء — خيمة 12',
        ])->assertSessionHas('profile_saved');

        $this->assertSame('أم أحمد', $family->fresh()->name);
    }

    public function test_event_page_in_both_panels_loads_with_the_registrations_tab(): void
    {
        $event = $this->upcomingEvent();

        $admin = User::factory()->create(['role' => UserRole::SuperAdmin, 'team_id' => null]);
        $this->actingAs($admin)->get('/admin/events/'.$event->id.'/edit')->assertOk();

        $manager = User::factory()->create([
            'role' => UserRole::TeamManager,
            'team_id' => $event->team_id,
        ]);
        $this->actingAs($manager)->get('/team/events/'.$event->id.'/edit')->assertOk();
    }

    public function test_organizer_pages_are_limited_to_active_team_managers(): void
    {
        $event = $this->upcomingEvent();

        // وليّ أمر: ممنوع
        $this->actingAs($this->family());
        $this->get(route('organizer.dashboard'))->assertForbidden();

        // مسؤول فريق نشط: مسموح، ويرى فعالياته وحدها
        $manager = User::factory()->create([
            'role' => UserRole::TeamManager,
            'team_id' => $event->team_id,
        ]);

        $this->actingAs($manager);
        $this->get(route('organizer.dashboard'))->assertOk()->assertSee($event->title);
        $this->get(route('organizer.events'))->assertOk()->assertSee($event->title);

        $otherEvent = Event::factory()->approved()->create(['title' => 'فعالية فريق آخر']);
        $this->get(route('organizer.events'))->assertDontSee('فعالية فريق آخر');
    }

    public function test_families_are_notified_when_an_event_is_rescheduled_or_cancelled(): void
    {
        $event = $this->upcomingEvent();
        $family = $this->family();

        $this->actingAs($family);
        $this->post(route('registrations.store', $event), ['children' => $family->children->pluck('id')->all()]);

        $event->update(['start_date' => today()->addDays(9)]);
        $this->assertDatabaseHas('user_notifications', ['user_id' => $family->id, 'type' => 'event_changed']);

        $event->update(['status' => EventStatus::Cancelled]);
        $this->assertDatabaseHas('user_notifications', ['user_id' => $family->id, 'type' => 'event_cancelled']);
    }

    public function test_reminder_command_notifies_families_of_tomorrow_events(): void
    {
        $event = Event::factory()->approved()->create([
            'start_date' => today()->addDay(),
            'start_time' => '10:00',
            'expected_children' => 10,
        ]);

        $family = $this->family();

        Registration::create([
            'event_id' => $event->id,
            'user_id' => $family->id,
            'child_id' => $family->children->first()->id,
            'children_count' => 1,
            'status' => RegistrationStatus::Pending,
        ]);

        $this->artisan('registrations:remind')->assertSuccessful();

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $family->id,
            'type' => 'event_reminder',
        ]);
    }

    public function test_notification_tabs_filter_by_group(): void
    {
        $family = $this->family();
        UserNotification::send($family, 'admin_message', 'رسالة من الإدارة');
        UserNotification::send($family, 'registration_accepted', 'قُبل حجزكم');

        $this->actingAs($family);

        $this->get(route('notifications', ['tab' => 'messages']))
            ->assertOk()
            ->assertSee('رسالة من الإدارة')
            ->assertDontSee('قُبل حجزكم');

        $this->delete(route('notifications.clear'))->assertSessionHas('notifications_cleared');
        $this->assertSame(0, $family->notifications()->count());
    }

    public function test_notifications_can_be_marked_read(): void
    {
        $family = $this->family();
        UserNotification::send($family, 'test', 'إشعار تجريبي');

        $this->actingAs($family);
        $this->assertSame(1, $family->unreadNotificationsCount());

        $this->post(route('notifications.read'))->assertSessionHas('notifications_read');
        $this->assertSame(0, $family->fresh()->unreadNotificationsCount());
    }
}
