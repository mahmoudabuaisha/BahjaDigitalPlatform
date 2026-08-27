<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Enums\UserRole;
use App\Models\Area;
use App\Models\Category;
use App\Models\Event;
use App\Models\ShelterCenter;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\AreaSeeder;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * بند الخطة التحذيري: تنبيه — لا منع — عند تداخل موعدين لنفس الفريق
 * أو نفس مركز الإيواء، وسطر «آخر تحديث» في صفحة الفعالية.
 */
class EventConflictWarningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AreaSeeder::class);
        $this->seed(CategorySeeder::class);
    }

    private function manager(): User
    {
        return User::factory()->create([
            'role' => UserRole::TeamManager,
            'team_id' => Team::factory()->create()->id,
        ]);
    }

    /** @return array<string, mixed> */
    private function payload(User $manager, array $overrides = []): array
    {
        return array_merge([
            'title' => 'فعالية جديدة',
            'category_id' => Category::first()->id,
            'audience' => 'all',
            'description' => 'أنشطة ترفيهية متنوعة للأطفال.',
            'start_date' => today()->addWeek()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '12:00',
            'area_id' => Area::first()->id,
            'expected_children' => 30,
            'registration_mode' => 'direct',
            'action' => 'publish',
        ], $overrides);
    }

    public function test_a_second_event_for_the_same_team_at_the_same_time_gets_a_warning(): void
    {
        $manager = $this->manager();

        Event::factory()->approved()->create([
            'team_id' => $manager->team_id,
            'title' => 'يوم الدمى',
            'start_date' => today()->addWeek(),
            'start_time' => '11:00',
            'end_time' => '13:00',
        ]);

        $this->actingAs($manager)
            ->post(route('organizer.events.store'), $this->payload($manager))
            ->assertSessionHas('event_warning', fn (array $warnings) => str_contains(implode(' ', $warnings), 'يوم الدمى'));
    }

    public function test_back_to_back_events_do_not_warn_because_intervals_are_half_open(): void
    {
        $manager = $this->manager();

        Event::factory()->approved()->create([
            'team_id' => $manager->team_id,
            'start_date' => today()->addWeek(),
            'start_time' => '12:00',
            'end_time' => '14:00',
        ]);

        // تنتهي 12:00 حيث تبدأ التالية — ليس تعارضاً
        $this->actingAs($manager)
            ->post(route('organizer.events.store'), $this->payload($manager))
            ->assertSessionHas('event_warning', []);
    }

    public function test_a_busy_shelter_center_warns_even_for_another_team(): void
    {
        $manager = $this->manager();
        $center = ShelterCenter::create(['name' => 'مركز الأمل', 'area_id' => Area::first()->id]);

        Event::factory()->approved()->create([
            'title' => 'مسرح العرائس',
            'shelter_center_id' => $center->id,
            'start_date' => today()->addWeek(),
            'start_time' => '10:30',
        ]);

        $this->actingAs($manager)
            ->post(route('organizer.events.store'), $this->payload($manager, ['shelter_center_id' => $center->id]))
            ->assertSessionHas('event_warning', fn (array $warnings) => str_contains(implode(' ', $warnings), 'مسرح العرائس')
                && str_contains(implode(' ', $warnings), 'لفريق آخر'));
    }

    public function test_a_cancelled_event_never_triggers_the_warning(): void
    {
        $manager = $this->manager();

        Event::factory()->create([
            'team_id' => $manager->team_id,
            'status' => EventStatus::Cancelled,
            'start_date' => today()->addWeek(),
            'start_time' => '10:00',
            'end_time' => '12:00',
        ]);

        $this->actingAs($manager)
            ->post(route('organizer.events.store'), $this->payload($manager))
            ->assertSessionHas('event_warning', []);
    }

    public function test_the_event_page_shows_when_its_details_were_last_updated(): void
    {
        $event = Event::factory()->approved()->create();

        $this->get(route('events.show', $event))
            ->assertOk()
            ->assertSee('آخر تحديث لمعلومات الفعالية');
    }
}
