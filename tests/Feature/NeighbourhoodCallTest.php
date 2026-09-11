<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Area;
use App\Models\Event;
use App\Models\NeighbourhoodCall;
use App\Models\PlaceLink;
use App\Models\ShelterCenter;
use App\Models\Team;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\NeighbourhoodDemandService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * المرحلة د: عائلة لا تجد فعالية قريبة ترفع يدها، والنداءات تُجمَّع
 * فتعرف الفرق أين ينتظر الأطفال — بلا أسماء ولا مواقع.
 */
class NeighbourhoodCallTest extends TestCase
{
    use RefreshDatabase;

    private Area $home;

    private ShelterCenter $homeCenter;

    private ShelterCenter $nextDoor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\AreaSeeder::class);
        $this->seed(\Database\Seeders\CategorySeeder::class);

        $this->home = Area::first();
        $this->homeCenter = $this->center('مركز مدرسة الشاطئ');
        $this->nextDoor = $this->center('مركز مدرسة النصر');
    }

    private function center(string $name): ShelterCenter
    {
        $center = new ShelterCenter;
        $center->forceFill([
            'area_id' => $this->home->id,
            'name' => $name,
            'is_active' => true,
        ])->save();

        return $center;
    }

    private function family(?ShelterCenter $center = null): User
    {
        return User::factory()->create([
            'role' => UserRole::Family,
            'team_id' => null,
            'area_id' => $this->home->id,
            'shelter_center_id' => ($center ?? $this->homeCenter)->id,
        ]);
    }

    public function test_a_family_with_nothing_nearby_can_raise_its_hand(): void
    {
        $family = $this->family();

        $this->actingAs($family)->get(route('home'))
            ->assertOk()
            ->assertSee('ارفعوا أيديكم');

        $this->actingAs($family)->post(route('calls.store'), [
            'age_band' => '3-5',
            'children_count' => 3,
            'note' => 'أغلب الأطفال هنا دون السادسة',
        ])->assertSessionHas('call_sent');

        $call = NeighbourhoodCall::sole();

        $this->assertSame($family->id, $call->user_id);
        $this->assertSame($this->homeCenter->id, $call->shelter_center_id);
        $this->assertSame($this->home->id, $call->area_id);
        $this->assertSame(3, $call->children_count);
        $this->assertNull($call->answered_at);
    }

    public function test_a_far_away_event_does_not_count_as_nearby(): void
    {
        $family = $this->family();
        $far = Area::skip(1)->first();

        Event::factory()->approved()->create([
            'area_id' => $far->id,
            'shelter_center_id' => null,
            'title' => 'فعالية في الطرف الآخر من القطاع',
        ]);

        // ترتيبٌ وحده لا يكفي: قسم «قرب مكانكم» لا يفتح على فعالية بعيدة،
        // بل يفسح المكان للنداء
        $this->actingAs($family)->get(route('home'))
            ->assertOk()
            ->assertDontSee('قرب مكانكم')
            ->assertSee('ارفعوا أيديكم');

        $this->assertTrue(
            Event::query()->publiclyVisible()->upcoming()->nearTo($family)->doesntExist(),
        );
    }

    public function test_one_standing_call_is_enough(): void
    {
        $family = $this->family();

        $this->actingAs($family)->post(route('calls.store'))->assertSessionHas('call_sent');
        $this->actingAs($family)->post(route('calls.store'))->assertSessionHas('call_sent');

        $this->assertSame(1, NeighbourhoodCall::count());
    }

    public function test_a_family_without_an_anchor_is_asked_for_its_place_first(): void
    {
        $family = User::factory()->create([
            'role' => UserRole::Family,
            'team_id' => null,
            'area_id' => null,
            'shelter_center_id' => null,
        ]);

        $this->actingAs($family)->post(route('calls.store'))
            ->assertRedirect(route('account.profile'));

        $this->assertSame(0, NeighbourhoodCall::count());
    }

    public function test_a_new_event_answers_the_call_and_says_so(): void
    {
        $family = $this->family();

        $this->actingAs($family)->post(route('calls.store'));

        $event = Event::factory()->approved()->create([
            'area_id' => $this->home->id,
            'shelter_center_id' => $this->homeCenter->id,
            'title' => 'يوم ألعاب في مدرسة الشاطئ',
        ]);

        $call = NeighbourhoodCall::sole();

        $this->assertNotNull($call->answered_at);
        $this->assertSame($event->id, $call->answered_event_id);

        $notifications = UserNotification::where('user_id', $family->id)->get();

        // جواب واحد دقيق، لا إعلان محافظة فوقه
        $this->assertSame(['call_answered'], $notifications->pluck('type')->all());
        $this->assertStringContainsString('سمعنا نداءكم', $notifications->first()->title);
    }

    public function test_an_event_a_short_walk_away_answers_the_call_too(): void
    {
        $family = $this->family();

        PlaceLink::create([
            'from_center_id' => $this->homeCenter->id,
            'to_center_id' => $this->nextDoor->id,
            'walk_minutes' => 12,
        ]);

        $this->actingAs($family)->post(route('calls.store'));

        Event::factory()->approved()->create([
            'area_id' => $this->home->id,
            'shelter_center_id' => $this->nextDoor->id,
        ]);

        $this->assertNotNull(NeighbourhoodCall::sole()->answered_at);
    }

    public function test_an_event_too_far_to_walk_leaves_the_call_standing(): void
    {
        $family = $this->family();

        PlaceLink::create([
            'from_center_id' => $this->homeCenter->id,
            'to_center_id' => $this->nextDoor->id,
            'walk_minutes' => 55,
        ]);

        $this->actingAs($family)->post(route('calls.store'));

        Event::factory()->approved()->create([
            'area_id' => $this->home->id,
            'shelter_center_id' => $this->nextDoor->id,
        ]);

        $this->assertNull(NeighbourhoodCall::sole()->answered_at);
    }

    public function test_teams_see_a_place_only_once_enough_families_called(): void
    {
        $demand = app(NeighbourhoodDemandService::class);

        foreach (range(1, NeighbourhoodCall::TEAM_VISIBILITY_FLOOR - 1) as $ignored) {
            $this->actingAs($this->family())->post(route('calls.store'));
        }

        $this->assertTrue($demand->byPlace(forTeams: true)->isEmpty());
        $this->assertFalse($demand->byPlace(forTeams: false)->isEmpty());

        // النداء الذي يبلغ العتبة يكشف المكان للفرق
        $this->actingAs($this->family())->post(route('calls.store'));

        $visible = $demand->byPlace(forTeams: true);

        $this->assertCount(1, $visible);
        $this->assertSame($this->homeCenter->id, $visible->first()->center->id);
        $this->assertSame(NeighbourhoodCall::TEAM_VISIBILITY_FLOOR, $visible->first()->calls);
    }

    public function test_the_team_dashboard_points_to_where_children_wait(): void
    {
        foreach (range(1, NeighbourhoodCall::TEAM_VISIBILITY_FLOOR) as $ignored) {
            $this->actingAs($this->family())->post(route('calls.store'));
        }

        $team = Team::factory()->create();
        $manager = User::factory()->create([
            'role' => UserRole::TeamManager,
            'team_id' => $team->id,
        ]);

        $this->actingAs($manager)->get(route('organizer.dashboard'))
            ->assertOk()
            ->assertSee('أين ينتظركم الأطفال')
            ->assertSee('مركز مدرسة الشاطئ');
    }

    public function test_a_lone_family_is_never_named_to_teams(): void
    {
        $family = $this->family();

        $this->actingAs($family)->post(route('calls.store'));

        $demand = app(NeighbourhoodDemandService::class);

        $this->assertSame(0, $demand->companionsFor($family->fresh()));
        $this->assertTrue($demand->byPlace(forTeams: true)->isEmpty());

        $team = Team::factory()->create();
        $manager = User::factory()->create(['role' => UserRole::TeamManager, 'team_id' => $team->id]);

        $this->actingAs($manager)->get(route('organizer.dashboard'))
            ->assertOk()
            ->assertDontSee('مركز مدرسة الشاطئ');
    }

    public function test_a_family_can_withdraw_its_call(): void
    {
        $family = $this->family();

        $this->actingAs($family)->post(route('calls.store'));
        $this->assertSame(1, NeighbourhoodCall::count());

        $this->actingAs($family)->delete(route('calls.destroy'))->assertSessionHas('call_sent');
        $this->assertSame(0, NeighbourhoodCall::count());
    }
}
