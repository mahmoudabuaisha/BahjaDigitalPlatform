<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Enums\RegistrationMode;
use App\Enums\RegistrationStatus;
use App\Enums\UserRole;
use App\Models\Area;
use App\Models\Category;
use App\Models\Child;
use App\Models\Event;
use App\Models\Registration;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\AreaSeeder;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrganizerEventFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AreaSeeder::class);
        $this->seed(CategorySeeder::class);
    }

    private function manager(?Event $event = null): User
    {
        return User::factory()->create([
            'role' => UserRole::TeamManager,
            'team_id' => $event?->team_id ?? Team::factory()->create()->id,
        ]);
    }

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'يوم ترفيهي للأطفال',
            'category_id' => Category::first()->id,
            'audience' => 'all',
            'description' => 'ألعاب وأنشطة ورسم في ساحة المركز.',
            'start_date' => today()->addWeek()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '13:00',
            'area_id' => Area::first()->id,
            'location_details' => 'شارع الجلاء — بجانب المدرسة',
            'age_range' => '6-9',
            'expected_children' => 40,
            'registration_mode' => RegistrationMode::Approval->value,
            'action' => 'publish',
        ], $overrides);
    }

    public function test_a_family_user_cannot_open_the_event_form(): void
    {
        $family = User::factory()->create(['role' => UserRole::Family, 'team_id' => null]);

        $this->actingAs($family)->get(route('organizer.events.create'))->assertForbidden();
    }

    public function test_manager_creates_an_event_that_waits_for_approval(): void
    {
        $manager = $this->manager();

        $this->actingAs($manager)->get(route('organizer.events.create'))->assertOk();

        $this->actingAs($manager)
            ->post(route('organizer.events.store'), $this->payload())
            ->assertRedirect(route('organizer.events'))
            ->assertSessionHas('event_saved');

        $event = Event::firstWhere('title', 'يوم ترفيهي للأطفال');

        $this->assertSame($manager->team_id, $event->team_id);
        $this->assertSame(EventStatus::Pending, $event->status);
        $this->assertSame(6, $event->age_min);
        $this->assertSame(9, $event->age_max);
        $this->assertSame(40, $event->expected_children);
    }

    public function test_saving_as_draft_keeps_the_event_out_of_review(): void
    {
        $manager = $this->manager();

        $this->actingAs($manager)->post(route('organizer.events.store'), $this->payload(['action' => 'draft']));

        $this->assertSame(EventStatus::Draft, Event::first()->status);
    }

    public function test_the_form_refuses_a_past_date_and_an_end_before_the_start(): void
    {
        $manager = $this->manager();

        $this->actingAs($manager)
            ->post(route('organizer.events.store'), $this->payload([
                'start_date' => today()->subDay()->toDateString(),
            ]))
            ->assertSessionHasErrors('start_date');

        $this->actingAs($manager)
            ->post(route('organizer.events.store'), $this->payload([
                'start_time' => '12:00',
                'end_time' => '11:00',
            ]))
            ->assertSessionHasErrors('end_time');

        $this->assertSame(0, Event::count());
    }

    public function test_the_uploaded_image_is_stored_and_replaced_on_edit(): void
    {
        Storage::fake('public');

        $manager = $this->manager();

        $this->actingAs($manager)->post(route('organizer.events.store'), $this->payload([
            'image' => UploadedFile::fake()->image('event.jpg'),
        ]));

        $event = Event::first();
        Storage::disk('public')->assertExists($event->image_path);

        $first = $event->image_path;

        $this->actingAs($manager)->put(route('organizer.events.update', $event), $this->payload([
            'image' => UploadedFile::fake()->image('newer.jpg'),
        ]));

        $this->assertNotSame($first, $event->fresh()->image_path);
        Storage::disk('public')->assertMissing($first);
    }

    public function test_a_manager_cannot_touch_another_teams_event(): void
    {
        $event = Event::factory()->approved()->create();
        $intruder = $this->manager();

        $this->actingAs($intruder)->get(route('organizer.events.edit', $event))->assertForbidden();
        $this->actingAs($intruder)->put(route('organizer.events.update', $event), $this->payload())->assertForbidden();
        $this->actingAs($intruder)->delete(route('organizer.events.destroy', $event))->assertForbidden();
    }

    public function test_editing_an_approved_event_sends_it_back_for_review(): void
    {
        $event = Event::factory()->approved()->create();
        $manager = $this->manager($event);

        $this->actingAs($manager)->get(route('organizer.events.edit', $event))->assertOk();

        $this->actingAs($manager)
            ->put(route('organizer.events.update', $event), $this->payload(['title' => 'اسم بعد التعديل']))
            ->assertSessionHas('event_saved');

        $event->refresh();

        $this->assertSame('اسم بعد التعديل', $event->title);
        $this->assertSame(EventStatus::Pending, $event->status);
    }

    public function test_editing_only_the_seat_count_keeps_the_event_published(): void
    {
        $event = Event::factory()->approved()->create();
        $manager = $this->manager($event);

        $this->actingAs($manager)->put(route('organizer.events.update', $event), $this->payload([
            'title' => $event->title,
            'category_id' => $event->category_id,
            'description' => $event->description,
            'start_date' => $event->start_date->toDateString(),
            'start_time' => substr($event->start_time, 0, 5),
            'end_time' => $event->end_time ? substr($event->end_time, 0, 5) : null,
            'area_id' => $event->area_id,
            'location_details' => $event->location_details,
            'expected_children' => 120,
        ]));

        $event->refresh();

        $this->assertSame(120, $event->expected_children);
        $this->assertSame(EventStatus::Approved, $event->status);
    }

    public function test_an_event_with_bookings_is_cancelled_instead_of_deleted(): void
    {
        $event = Event::factory()->approved()->create(['start_date' => today()->addDays(5)]);
        $manager = $this->manager($event);

        $family = User::factory()->create(['role' => UserRole::Family, 'team_id' => null]);
        $child = Child::factory()->create(['user_id' => $family->id]);

        Registration::create([
            'event_id' => $event->id,
            'user_id' => $family->id,
            'child_id' => $child->id,
            'children_count' => 1,
            'status' => RegistrationStatus::Pending,
        ]);

        $this->actingAs($manager)->delete(route('organizer.events.destroy', $event));

        $this->assertSame(EventStatus::Cancelled, $event->fresh()->status);
        $this->assertDatabaseHas('user_notifications', ['user_id' => $family->id, 'type' => 'event_cancelled']);
    }

    public function test_an_empty_event_is_deleted(): void
    {
        $event = Event::factory()->approved()->create();
        $manager = $this->manager($event);

        $this->actingAs($manager)->delete(route('organizer.events.destroy', $event));

        $this->assertSoftDeleted($event);
    }

    public function test_direct_registration_mode_accepts_the_booking_immediately(): void
    {
        $event = Event::factory()->approved()->create([
            'start_date' => today()->addDays(3),
            'expected_children' => 10,
            'registration_mode' => RegistrationMode::Direct,
        ]);

        $family = User::factory()->create(['role' => UserRole::Family, 'team_id' => null]);
        $child = Child::factory()->create(['user_id' => $family->id]);

        $this->actingAs($family)->post(route('registrations.store', $event), ['children' => [$child->id]]);

        $this->assertSame(RegistrationStatus::Accepted, Registration::first()->status);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $family->id,
            'type' => 'registration_accepted',
        ]);
    }
}
