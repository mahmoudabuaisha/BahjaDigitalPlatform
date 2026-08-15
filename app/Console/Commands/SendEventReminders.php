<?php

namespace App\Console\Commands;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\UserNotification;
use Illuminate\Console\Command;

class SendEventReminders extends Command
{
    protected $signature = 'registrations:remind';

    protected $description = 'تذكير العائلات بفعالياتهم المحجوزة غداً';

    public function handle(): int
    {
        $events = Event::query()
            ->where('status', EventStatus::Approved)
            ->whereDate('start_date', today()->addDay())
            ->with(['registrations' => fn ($query) => $query->holdingSeat()->with('child')])
            ->get();

        $sent = 0;

        foreach ($events as $event) {
            foreach ($event->registrations as $registration) {
                UserNotification::send(
                    $registration->user_id,
                    'event_reminder',
                    'تذكير: فعالية غداً',
                    $event->title.' — '.substr($event->start_time, 0, 5)
                        .($registration->child ? ' · '.$registration->child->name : ''),
                    route('events.show', $event),
                );

                $sent++;
            }
        }

        $this->info("أُرسل {$sent} تذكيراً.");

        return self::SUCCESS;
    }
}
