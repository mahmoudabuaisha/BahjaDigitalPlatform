<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Area;
use App\Models\Event;
use App\Models\PlaceLink;
use App\Models\ShelterCenter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * المرحلة ج: القرب يُقاس بدقائق المشي التي يعرفها أهل المكان، لا بمسافة
 * على خريطة. والصلة متماثلة — الطريق نفسه ذهاباً وإياباً.
 */
class WalkingMinutesTest extends TestCase
{
    use RefreshDatabase;

    private Area $home;

    private ShelterCenter $homeCenter;

    private ShelterCenter $nextDoor;

    private ShelterCenter $acrossTown;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\AreaSeeder::class);
        $this->seed(\Database\Seeders\CategorySeeder::class);

        $this->home = Area::first();
        $this->homeCenter = $this->center('مركز مدرسة الشاطئ');
        $this->nextDoor = $this->center('مركز مدرسة النصر');
        $this->acrossTown = $this->center('مركز مدرسة الرمال');
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

    private function family(): User
    {
        return User::factory()->create([
            'role' => UserRole::Family,
            'team_id' => null,
            'area_id' => $this->home->id,
            'shelter_center_id' => $this->homeCenter->id,
        ]);
    }

    public function test_a_walk_of_ten_minutes_beats_a_place_with_no_link(): void
    {
        $family = $this->family();

        PlaceLink::create([
            'from_center_id' => $this->homeCenter->id,
            'to_center_id' => $this->nextDoor->id,
            'walk_minutes' => 10,
        ]);

        $unlinked = Event::factory()->approved()->create([
            'area_id' => $this->home->id,
            'shelter_center_id' => $this->acrossTown->id,
            'start_date' => today()->addDay(),
        ]);
        $walkable = Event::factory()->approved()->create([
            'area_id' => $this->home->id,
            'shelter_center_id' => $this->nextDoor->id,
            'start_date' => today()->addWeeks(3),
        ]);

        $ordered = Event::query()->publiclyVisible()->upcoming()
            ->nearestTo($family)
            ->orderBy('start_date')
            ->pluck('events.id')
            ->all();

        $this->assertSame([$walkable->id, $unlinked->id], $ordered);
        $this->assertSame('على بُعد 10 دقائق مشياً', $walkable->proximityLabel($family));
        $this->assertSame('في محافظتكم', $unlinked->proximityLabel($family));
    }

    public function test_the_link_reads_the_same_in_both_directions(): void
    {
        // مسجَّلة من الجار إلى البيت — والقراءة يجب أن تنجح بالعكس أيضاً
        PlaceLink::create([
            'from_center_id' => $this->nextDoor->id,
            'to_center_id' => $this->homeCenter->id,
            'walk_minutes' => 7,
        ]);

        $this->assertSame(7, PlaceLink::minutesBetween($this->homeCenter->id, $this->nextDoor->id));
        $this->assertSame(7, PlaceLink::minutesBetween($this->nextDoor->id, $this->homeCenter->id));
        $this->assertSame(0, PlaceLink::minutesBetween($this->homeCenter->id, $this->homeCenter->id));
        $this->assertNull(PlaceLink::minutesBetween($this->homeCenter->id, $this->acrossTown->id));
    }

    public function test_the_shorter_walk_comes_first(): void
    {
        $family = $this->family();

        PlaceLink::create([
            'from_center_id' => $this->homeCenter->id,
            'to_center_id' => $this->nextDoor->id,
            'walk_minutes' => 6,
        ]);
        PlaceLink::create([
            'from_center_id' => $this->homeCenter->id,
            'to_center_id' => $this->acrossTown->id,
            'walk_minutes' => 25,
        ]);

        $far = Event::factory()->approved()->create([
            'area_id' => $this->home->id,
            'shelter_center_id' => $this->acrossTown->id,
            'start_date' => today()->addDay(),
        ]);
        $near = Event::factory()->approved()->create([
            'area_id' => $this->home->id,
            'shelter_center_id' => $this->nextDoor->id,
            'start_date' => today()->addWeeks(2),
        ]);

        $ordered = Event::query()->publiclyVisible()->upcoming()
            ->nearestTo($family)
            ->orderBy('start_date')
            ->pluck('events.id')
            ->all();

        $this->assertSame([$near->id, $far->id], $ordered);
    }

    public function test_the_pair_is_stored_once_whichever_way_it_is_entered(): void
    {
        $link = PlaceLink::create([
            'from_center_id' => $this->nextDoor->id,
            'to_center_id' => $this->homeCenter->id,
            'walk_minutes' => 9,
        ]);

        // يُخزَّن بالمعرّف الأصغر أولاً ليحرس الفهرس الفريد الزوج فعلياً
        $this->assertSame(min($this->homeCenter->id, $this->nextDoor->id), $link->fresh()->from_center_id);
    }

    public function test_the_event_page_shows_the_way_there(): void
    {
        $family = $this->family();

        PlaceLink::create([
            'from_center_id' => $this->homeCenter->id,
            'to_center_id' => $this->nextDoor->id,
            'walk_minutes' => 12,
        ]);

        $event = Event::factory()->approved()->create([
            'area_id' => $this->home->id,
            'shelter_center_id' => $this->nextDoor->id,
            'directions' => 'من دوّار الكتيبة غرباً — المدرسة على يمينكم',
        ]);

        $this->actingAs($family)->get(route('events.show', $event))
            ->assertOk()
            ->assertSee('كيف تصلون؟')
            ->assertSee('من دوّار الكتيبة غرباً — المدرسة على يمينكم')
            ->assertSee('على بُعد 12 دقيقة مشياً');
    }

    public function test_a_visitor_without_an_anchor_still_reads_the_directions(): void
    {
        $event = Event::factory()->approved()->create([
            'area_id' => $this->home->id,
            'shelter_center_id' => $this->nextDoor->id,
            'directions' => 'خلف مسجد الحيّ مباشرة',
        ]);

        $this->get(route('events.show', $event))
            ->assertOk()
            ->assertSee('كيف تصلون؟')
            ->assertSee('خلف مسجد الحيّ مباشرة');
    }
}
