<?php

namespace Tests\Feature;

use App\Enums\RegistrationStatus;
use App\Enums\UserRole;
use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
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

    private function family(): User
    {
        return User::factory()->create([
            'role' => UserRole::Family,
            'team_id' => null,
            'is_active' => true,
        ]);
    }

    public function test_visitor_can_register_an_account_and_lands_on_my_events(): void
    {
        $this->post('/register', [
            'name' => 'أم محمّد',
            'email' => 'om@example.com',
            'phone' => '0599000000',
            'password' => 'kalimat-sirr',
            'password_confirmation' => 'kalimat-sirr',
        ])->assertRedirect(route('my-events'));

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
        $event = Event::factory()->approved()->create(['start_date' => today()->addDays(3)]);

        $this->post(route('registrations.store', $event), ['children_count' => 2])
            ->assertRedirect(route('login'));
    }

    public function test_family_can_book_a_seat_and_cancel_it(): void
    {
        $event = Event::factory()->approved()->create([
            'start_date' => today()->addDays(3),
            'expected_children' => 10,
        ]);

        $this->actingAs($this->family());

        $this->post(route('registrations.store', $event), ['children_count' => 3])
            ->assertSessionHas('registration_done');

        $this->assertSame(3, $event->fresh()->seatsTaken());
        $this->assertSame(7, $event->fresh()->seatsRemaining());

        $this->delete(route('registrations.destroy', $event))
            ->assertSessionHas('registration_cancelled');

        $this->assertSame(0, $event->fresh()->seatsTaken());
    }

    public function test_booking_is_refused_when_it_exceeds_remaining_seats(): void
    {
        $event = Event::factory()->approved()->create([
            'start_date' => today()->addDays(3),
            'expected_children' => 4,
        ]);

        Registration::create([
            'event_id' => $event->id,
            'user_id' => $this->family()->id,
            'children_count' => 3,
            'status' => RegistrationStatus::Confirmed,
        ]);

        $this->actingAs($this->family());

        $this->post(route('registrations.store', $event), ['children_count' => 2])
            ->assertSessionHas('registration_error');

        $this->assertSame(3, $event->fresh()->seatsTaken());
    }

    public function test_booking_a_past_event_is_refused(): void
    {
        $event = Event::factory()->approved()->create(['start_date' => today()->subWeek()]);

        $this->actingAs($this->family());

        $this->post(route('registrations.store', $event), ['children_count' => 1])
            ->assertSessionHas('registration_error');

        $this->assertSame(0, $event->fresh()->seatsTaken());
    }

    public function test_rebooking_updates_the_existing_registration_instead_of_duplicating(): void
    {
        $event = Event::factory()->approved()->create([
            'start_date' => today()->addDays(3),
            'expected_children' => 20,
        ]);

        $this->actingAs($family = $this->family());

        $this->post(route('registrations.store', $event), ['children_count' => 2]);
        $this->post(route('registrations.store', $event), ['children_count' => 5]);

        $this->assertSame(1, Registration::where('user_id', $family->id)->count());
        $this->assertSame(5, $event->fresh()->seatsTaken());
    }
}
