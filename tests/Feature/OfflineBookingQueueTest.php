<?php

namespace Tests\Feature;

use App\Enums\RegistrationStatus;
use App\Enums\UserRole;
use App\Models\Child;
use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Database\Seeders\AreaSeeder;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * الحجز المحفوظ على الجهاز يُرسل عبر fetch حين تعود الشبكة،
 * فيجب أن يردّ السيرفر برسالة JSON يفهمها الطابور لا بتحويلة.
 */
class OfflineBookingQueueTest extends TestCase
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
        $user = User::factory()->create(['role' => UserRole::Family, 'team_id' => null, 'is_active' => true]);

        Child::factory()->create(['user_id' => $user->id]);

        return $user;
    }

    public function test_a_queued_booking_is_accepted_and_answered_with_json(): void
    {
        $event = Event::factory()->approved()->create([
            'start_date' => today()->addDays(4),
            'expected_children' => 10,
        ]);

        $family = $this->family();

        $this->actingAs($family)
            ->postJson(route('registrations.store', $event), [
                'children' => [$family->children->first()->id],
                'note' => 'وصل الطلب بعد عودة الشبكة',
            ])
            ->assertOk()
            ->assertJson(['ok' => true])
            ->assertJsonPath('message', fn (string $message): bool => str_contains($message, $event->title));

        $this->assertSame(RegistrationStatus::Pending, Registration::first()->status);
    }

    public function test_a_queued_booking_that_arrives_late_is_refused_with_a_readable_reason(): void
    {
        // اكتمل العدد بينما كان الطلب محفوظاً على الجهاز
        $event = Event::factory()->approved()->create([
            'start_date' => today()->addDays(4),
            'expected_children' => 1,
        ]);

        $other = $this->family();

        Registration::create([
            'event_id' => $event->id,
            'user_id' => $other->id,
            'child_id' => $other->children->first()->id,
            'children_count' => 1,
            'status' => RegistrationStatus::Pending,
        ]);

        $family = $this->family();

        $this->actingAs($family)
            ->postJson(route('registrations.store', $event), [
                'children' => [$family->children->first()->id],
            ])
            ->assertStatus(422)
            ->assertJson(['ok' => false, 'message' => 'اكتمل العدد في هذه الفعالية.']);

        $this->assertSame(1, Registration::count());
    }

    public function test_a_queued_booking_of_a_cancelled_event_is_refused(): void
    {
        $event = Event::factory()->approved()->create(['start_date' => today()->subWeek()]);
        $family = $this->family();

        $this->actingAs($family)
            ->postJson(route('registrations.store', $event), [
                'children' => [$family->children->first()->id],
            ])
            ->assertStatus(422)
            ->assertJson(['ok' => false]);

        $this->assertSame(0, Registration::count());
    }

    public function test_the_session_stays_alive_for_a_week(): void
    {
        $this->assertSame(10080, (int) config('session.lifetime'));
    }
}
