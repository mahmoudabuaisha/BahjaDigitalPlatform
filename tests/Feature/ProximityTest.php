<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Area;
use App\Models\Event;
use App\Models\ShelterCenter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * القرب بالجيرة لا بالمسافة: لا إحداثيات ولا GPS — مرساة مكان تختارها
 * العائلة من قائمة، فتُرتَّب الفعاليات بالأقرب إليها.
 */
class ProximityTest extends TestCase
{
    use RefreshDatabase;

    private Area $home;

    private Area $other;

    private ShelterCenter $homeCenter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\AreaSeeder::class);
        $this->seed(\Database\Seeders\CategorySeeder::class);

        $this->home = Area::first();
        $this->other = Area::skip(1)->first();
        $this->homeCenter = ShelterCenter::create([
            'area_id' => $this->home->id,
            'name' => 'مركز إيواء مدرسة الشاطئ',
            'is_active' => true,
        ]);
    }

    private function family(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role' => UserRole::Family,
            'team_id' => null,
        ], $overrides));
    }

    public function test_events_are_ranked_by_neighbourhood_not_distance(): void
    {
        $family = $this->family([
            'area_id' => $this->home->id,
            'shelter_center_id' => $this->homeCenter->id,
        ]);

        $samePlace = Event::factory()->approved()->create([
            'area_id' => $this->home->id,
            'shelter_center_id' => $this->homeCenter->id,
            'start_date' => today()->addWeeks(3),
        ]);
        $sameArea = Event::factory()->approved()->create([
            'area_id' => $this->home->id,
            'shelter_center_id' => null,
            'start_date' => today()->addWeeks(2),
        ]);
        $farAway = Event::factory()->approved()->create([
            'area_id' => $this->other->id,
            'shelter_center_id' => null,
            'start_date' => today()->addDay(),
        ]);

        $ordered = Event::query()->publiclyVisible()->upcoming()
            ->nearestTo($family)
            ->orderBy('start_date')
            ->pluck('id')
            ->all();

        // الأقرب مكاناً يتقدّم رغم أن الأبعد أسبق موعداً
        $this->assertSame([$samePlace->id, $sameArea->id, $farAway->id], $ordered);

        $this->assertSame('في مكانكم نفسه', $samePlace->proximityLabel($family));
        $this->assertSame('في محافظتكم', $sameArea->proximityLabel($family));
        $this->assertNull($farAway->proximityLabel($family));
    }

    public function test_without_an_anchor_the_order_stays_by_date(): void
    {
        $guest = $this->family(['area_id' => null, 'shelter_center_id' => null]);

        $later = Event::factory()->approved()->create([
            'area_id' => $this->home->id,
            'start_date' => today()->addWeeks(2),
        ]);
        $sooner = Event::factory()->approved()->create([
            'area_id' => $this->other->id,
            'start_date' => today()->addDay(),
        ]);

        $ordered = Event::query()->publiclyVisible()->upcoming()
            ->nearestTo($guest)
            ->orderBy('start_date')
            ->pluck('id')
            ->all();

        $this->assertSame([$sooner->id, $later->id], $ordered);
        $this->assertNull($sooner->proximityLabel($guest));
    }

    public function test_a_family_sets_its_anchor_from_the_profile(): void
    {
        $family = $this->family(['area_id' => null]);

        $this->actingAs($family)->get(route('account.profile'))
            ->assertOk()
            ->assertSee('أين أنتم الآن؟');

        $this->actingAs($family)->put(route('account.profile.update'), [
            'name' => $family->name,
            'email' => $family->email,
            'area_id' => $this->home->id,
            'shelter_center_id' => $this->homeCenter->id,
        ])->assertSessionHas('profile_saved');

        $family->refresh();

        $this->assertSame($this->home->id, $family->area_id);
        $this->assertSame($this->homeCenter->id, $family->shelter_center_id);
        $this->assertNotNull($family->location_set_at);
        $this->assertStringContainsString('مدرسة الشاطئ', $family->locationLabel());
    }

    public function test_a_landmark_outside_the_chosen_area_is_ignored(): void
    {
        $family = $this->family();

        $this->actingAs($family)->put(route('account.profile.update'), [
            'name' => $family->name,
            'email' => $family->email,
            'area_id' => $this->other->id,
            'shelter_center_id' => $this->homeCenter->id, // معلم يتبع محافظة أخرى
        ])->assertSessionHas('profile_saved');

        $this->assertNull($family->fresh()->shelter_center_id);
    }

    public function test_the_home_page_invites_and_then_shows_nearby_events(): void
    {
        $family = $this->family(['area_id' => null]);

        $this->actingAs($family)->get(route('home'))
            ->assertOk()
            ->assertSee('حدّدوا مكانكم');

        $family->update(['area_id' => $this->home->id]);

        Event::factory()->approved()->create([
            'area_id' => $this->home->id,
            'title' => 'يوم ألعاب قرب المركز',
            'start_date' => today()->addDays(2),
        ]);

        $this->actingAs($family)->get(route('home'))
            ->assertOk()
            ->assertSee('قرب مكانكم')
            ->assertSee('يوم ألعاب قرب المركز');
    }
}
