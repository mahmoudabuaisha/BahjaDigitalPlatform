<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Event;
use App\Models\EventSeries;
use App\Models\Team;
use App\Models\User;
use App\Models\UserNotification;
use App\Support\Settings;
use Database\Seeders\AreaSeeder;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * إكمال الجزئيات: نافذة النشر المؤجَّل (approved ≠ published)،
 * تعديل «هذه المرة والمواعيد القادمة» في السلسلة، ومحتوى الصفحات من اللوحة.
 */
class DeferredPublishAndSeriesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AreaSeeder::class);
        $this->seed(CategorySeeder::class);
    }

    public function test_an_approved_event_with_a_future_publish_time_stays_hidden_from_families(): void
    {
        $event = Event::factory()->approved()->create(['title' => 'مهرجان مؤجَّل النشر']);
        $event->forceFill(['publish_at' => now()->addDay()])->save();

        $this->get(route('events.index'))->assertDontSee('مهرجان مؤجَّل النشر');
        $this->get(route('api.events'))->assertDontSee('مهرجان مؤجَّل النشر');
        $this->assertFalse($event->fresh()->acceptsRegistrations());
    }

    public function test_the_release_command_publishes_due_events_and_announces_them(): void
    {
        $event = Event::factory()->approved()->create(['title' => 'مهرجان حان نشره']);
        $event->forceFill(['publish_at' => now()->subMinute()])->save();

        $family = User::factory()->create([
            'role' => UserRole::Family,
            'team_id' => null,
            'area_id' => $event->area_id,
        ]);

        $this->artisan('events:release-scheduled')->assertSuccessful();

        $this->assertNull($event->fresh()->publish_at);
        $this->get(route('events.index'))->assertSee('مهرجان حان نشره');

        $this->assertTrue(UserNotification::where('user_id', $family->id)->where('type', 'event_new')->exists());
        $this->assertDatabaseHas('audit_logs', ['action' => 'event.published']);
    }

    public function test_a_series_edit_can_apply_to_this_and_future_occurrences(): void
    {
        $team = Team::factory()->create();
        $manager = User::factory()->create(['role' => UserRole::TeamManager, 'team_id' => $team->id]);

        $series = EventSeries::create([
            'team_id' => $team->id,
            'title' => 'حلقة أسبوعية',
            'recurrence_rule' => 'weekly',
            'starts_on' => today()->addWeek(),
            'occurrence_count' => 3,
        ]);

        [$first, $second, $third] = collect(range(0, 2))->map(fn (int $week) => Event::factory()->approved()->create([
            'team_id' => $team->id,
            'series_id' => $series->id,
            'title' => 'حلقة أسبوعية',
            'start_date' => today()->addWeek()->addWeeks($week),
            'start_time' => '15:00',
        ]))->all();

        $payload = [
            'title' => 'حلقة أسبوعية بعنوان جديد',
            'category_id' => $first->category_id,
            'audience' => 'all',
            'description' => 'وصف محدَّث للنشاط الأسبوعي.',
            'start_date' => $first->start_date->toDateString(),
            'start_time' => '16:00',
            'area_id' => $first->area_id,
            'expected_children' => 25,
            'registration_mode' => 'direct',
            'action' => 'publish',
            'apply_to_future' => 1,
        ];

        $this->actingAs($manager)
            ->put(route('organizer.events.update', $first), $payload)
            ->assertSessionHas('event_saved', fn (string $message) => str_contains($message, 'وسرى التعديل على 2'));

        // الموعدان القادمان معتمدان — فتعديلهما الجوهري نسخة معلّقة لكلٍّ منهما،
        // وتاريخ كل موعد يبقى كما هو
        $this->assertTrue($second->fresh()->pendingRevision()->exists());
        $this->assertSame('حلقة أسبوعية بعنوان جديد', $third->fresh()->pendingRevision->payload['title']);
        $this->assertSame(today()->addWeeks(3)->toDateString(), $third->fresh()->start_date->toDateString());
    }

    public function test_panel_edited_content_replaces_the_default_page_text(): void
    {
        $this->get(route('privacy'))->assertOk()->assertSee('تصفّح بلا أي حساب');
        $this->get(route('photo-policy'))->assertOk()->assertSee('موافقة وليّ الأمر شرط لا استثناء له');

        Settings::set('guide_content', "فقرة الدليل المخصّصة من اللوحة.\n\nوفقرة ثانية.");

        $this->get(route('guide'))
            ->assertSee('فقرة الدليل المخصّصة من اللوحة.')
            ->assertDontSee('افتحوا الروزنامة واختاروا محافظتكم');
    }
}
