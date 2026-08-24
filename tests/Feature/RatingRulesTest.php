<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Feedback;
use Database\Seeders\AreaSeeder;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** قواعد التقييم (6.3): بعد النهاية، ضمن 72 ساعة، وواحد لكل جهاز */
class RatingRulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AreaSeeder::class);
        $this->seed(CategorySeeder::class);
    }

    public function test_rating_is_refused_before_the_event_ends(): void
    {
        $event = Event::factory()->approved()->create(['start_date' => today()->addDays(2)]);

        $this->post(route('events.feedback', $event), ['rating' => 5])
            ->assertSessionHas('feedback_error');

        $this->assertSame(0, Feedback::count());
    }

    public function test_rating_is_refused_after_the_72_hour_window(): void
    {
        $event = Event::factory()->approved()->create([
            'start_date' => today()->subDays(5),
            'start_time' => '10:00',
        ]);

        $this->post(route('events.feedback', $event), ['rating' => 5])
            ->assertSessionHas('feedback_error');

        $this->assertSame(0, Feedback::count());
    }

    public function test_the_same_device_cannot_rate_twice(): void
    {
        $event = Event::factory()->approved()->create([
            'start_date' => today()->subDay(),
            'start_time' => '10:00',
        ]);

        $this->post(route('events.feedback', $event), ['rating' => 5])
            ->assertSessionHas('feedback_sent');

        $device = Feedback::first()->device_hash;
        $this->assertNotNull($device);

        // نفس الجهاز (نفس الكعكة) يحاول مرة ثانية
        $cookie = collect(app('cookie')->getQueuedCookies())
            ->first(fn ($c) => $c->getName() === 'bahja_device');

        $this->withUnencryptedCookie('bahja_device', $cookie?->getValue() ?? 'x')
            ->post(route('events.feedback', $event), ['rating' => 1]);

        // جهاز واحد = تقييم واحد
        $this->assertSame(1, Feedback::where('device_hash', $device)->count());
    }
}
