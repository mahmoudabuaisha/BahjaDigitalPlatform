<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * الاستضافة المشتركة تعطّل proc_open: أي مهمة مجدولة تعتمد على عملية منفصلة
 * (Schedule::command) تفشل بصمت كل دقيقة، ومعها طابور البريد كله.
 */
class ScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_scheduled_task_runs_in_process(): void
    {
        $events = collect(app(Schedule::class)->events());

        $this->assertNotEmpty($events);

        $events->each(fn ($event) => $this->assertInstanceOf(CallbackEvent::class, $event, $event->getSummaryForDisplay()));

        $this->assertEqualsCanonicalizing(
            ['heartbeat', 'events:archive', 'events:release-scheduled', 'registrations:remind', 'push:tomorrow', 'newsletter:send', 'queue:work'],
            $events->map(fn (CallbackEvent $event): string => $event->getSummaryForDisplay())->all(),
        );
    }

    public function test_the_heartbeat_and_the_queue_worker_run_from_the_scheduler(): void
    {
        $this->assertNull(cache('schedule:heartbeat'));

        $this->artisan('schedule:test', ['--name' => 'heartbeat'])->assertSuccessful();
        $this->assertNotNull(cache('schedule:heartbeat'));

        $this->artisan('schedule:test', ['--name' => 'queue:work'])->assertSuccessful();
        $this->artisan('schedule:test', ['--name' => 'events:release-scheduled'])->assertSuccessful();
    }
}
