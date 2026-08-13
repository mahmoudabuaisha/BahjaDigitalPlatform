<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Feedback;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\AreaSeeder::class);
        $this->seed(\Database\Seeders\CategorySeeder::class);
    }

    public function test_valid_feedback_is_stored(): void
    {
        $this->post('/feedback', [
            'rating' => 5,
            'source' => 'family',
            'message' => 'شكراً لجهودكم',
        ])->assertRedirect();

        $this->assertDatabaseHas('feedback', [
            'rating' => 5,
            'message' => 'شكراً لجهودكم',
        ]);
    }

    public function test_honeypot_silently_drops_bot_submissions(): void
    {
        $this->post('/feedback', [
            'rating' => 5,
            'website' => 'http://spam.example',
        ])->assertRedirect();

        $this->assertSame(0, Feedback::count());
    }

    public function test_rating_is_required_and_bounded(): void
    {
        $this->post('/feedback', ['rating' => 9])->assertSessionHasErrors('rating');
        $this->post('/feedback', [])->assertSessionHasErrors('rating');
    }

    public function test_event_feedback_attaches_to_event(): void
    {
        $event = Event::factory()->completed()->create();

        $this->post(route('events.feedback', $event), ['rating' => 4])
            ->assertRedirect(route('events.show', $event));

        $this->assertSame(1, $event->feedback()->count());
    }

    public function test_feedback_is_rate_limited_per_ip(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/feedback', ['rating' => 3])->assertRedirect();
        }

        $this->post('/feedback', ['rating' => 3])->assertStatus(429);
    }
}
