<?php

namespace Tests\Feature;

use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventFeedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\AreaSeeder::class);
        $this->seed(\Database\Seeders\CategorySeeder::class);
    }

    public function test_feed_returns_compact_schema_with_lookups(): void
    {
        Event::factory()->approved()->create([
            'title' => 'فعالية التغذية',
            'start_date' => today()->addDays(3),
        ]);

        Event::factory()->draft()->create(['start_date' => today()->addDays(3)]);

        $response = $this->getJson('/api/v1/events')
            ->assertOk()
            ->assertJsonStructure(['v', 'areas', 'cats', 'centers', 'events'])
            ->assertJsonCount(1, 'events')
            ->assertJsonCount(5, 'areas');

        $event = $response->json('events.0');

        $this->assertSame('فعالية التغذية', $event['t']);
        $this->assertArrayHasKey('d', $event);
        $this->assertArrayHasKey('s', $event);
    }

    public function test_feed_returns_304_with_matching_etag(): void
    {
        Event::factory()->approved()->create(['start_date' => today()->addDay()]);

        $first = $this->getJson('/api/v1/events')->assertOk();
        $etag = $first->headers->get('ETag');

        $this->assertNotNull($etag);

        $this->getJson('/api/v1/events', ['If-None-Match' => $etag])
            ->assertStatus(304);
    }

    public function test_feed_cache_busts_when_event_changes(): void
    {
        $event = Event::factory()->approved()->create([
            'title' => 'قبل التعديل',
            'start_date' => today()->addDay(),
        ]);

        $this->getJson('/api/v1/events')->assertJsonPath('events.0.t', 'قبل التعديل');

        $event->update(['title' => 'بعد التعديل']);

        $this->getJson('/api/v1/events')->assertJsonPath('events.0.t', 'بعد التعديل');
    }

    public function test_view_tracking_increments_once_per_session_window(): void
    {
        $event = Event::factory()->approved()->create();

        $this->post('/t/e/'.$event->id)->assertNoContent();
        $this->post('/t/e/'.$event->id)->assertNoContent();

        $this->assertSame(1, $event->fresh()->views_count);
    }
}
