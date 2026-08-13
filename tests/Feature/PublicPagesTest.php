<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\AreaSeeder::class);
        $this->seed(\Database\Seeders\CategorySeeder::class);
    }

    public function test_home_renders_upcoming_approved_events_grouped_by_day(): void
    {
        $visible = Event::factory()->approved()->create([
            'title' => 'فعالية ظاهرة للعموم',
            'start_date' => today()->addDay(),
        ]);

        Event::factory()->draft()->create(['title' => 'مسودة مخفية', 'start_date' => today()->addDay()]);
        Event::factory()->pending()->create(['title' => 'معلقة مخفية', 'start_date' => today()->addDay()]);

        $this->get('/')
            ->assertOk()
            ->assertSee('فعالية ظاهرة للعموم')
            ->assertDontSee('مسودة مخفية')
            ->assertDontSee('معلقة مخفية')
            ->assertSee('dir="rtl"', false);
    }

    public function test_event_page_shows_details_with_og_tags(): void
    {
        $event = Event::factory()->approved()->create(['title' => 'مسرح الدمى الكبير']);

        $this->get(route('events.show', $event))
            ->assertOk()
            ->assertSee('مسرح الدمى الكبير')
            ->assertSee('og:title', false)
            ->assertSee('og:image', false)
            ->assertSee('property="og:locale" content="ar_AR"', false);
    }

    public function test_hidden_event_returns_404_publicly(): void
    {
        $draft = Event::factory()->draft()->create();

        $this->get(route('events.show', $draft))->assertNotFound();
    }

    public function test_short_url_redirects_to_event_page(): void
    {
        $event = Event::factory()->approved()->create();

        $this->get('/e/'.$event->id)
            ->assertRedirect(route('events.show', $event));
    }

    public function test_team_page_shows_active_team_and_404s_pending(): void
    {
        $active = Team::factory()->create(['slug' => 'active-team']);
        $pending = Team::factory()->pending()->create(['slug' => 'pending-team']);

        $this->get('/teams/active-team')->assertOk()->assertSee($active->name);
        $this->get('/teams/pending-team')->assertNotFound();
    }

    public function test_guide_offline_and_pwa_endpoints_work(): void
    {
        $this->get('/guide')->assertOk()->assertSee('دليل استخدام المنصة');
        $this->get('/offline')->assertOk();
        $this->get('/manifest.webmanifest')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json');

        $sw = $this->get('/sw.js');
        $sw->assertOk();
        $this->assertStringStartsWith('application/javascript', $sw->headers->get('Content-Type'));
        $this->assertStringContainsString('VERSION', $sw->getContent());
    }

    public function test_sitemap_lists_public_events_only(): void
    {
        $visible = Event::factory()->approved()->create(['start_date' => today()->addDay()]);
        $draft = Event::factory()->draft()->create(['start_date' => today()->addDay()]);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee(route('events.show', $visible), false)
            ->assertDontSee(route('events.show', $draft), false);
    }
}
