<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\NewsletterSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

/**
 * نشرة الأسبوع: فعاليات الأيام القادمة مرتّبة باليوم، ورابط إلغاء في ذيلها
 * وفي ترويسة الرسالة كي تعرضه تطبيقات البريد زرّاً بضغطة واحدة.
 */
class NewsletterDigestMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /** @param  Collection<int, Event>  $events */
    public function __construct(
        public NewsletterSubscriber $subscriber,
        public Collection $events,
    ) {}

    public function envelope(): Envelope
    {
        $area = $this->subscriber->area;

        return new Envelope(subject: 'فعاليات هذا الأسبوع'.($area ? ' في '.$area->name : '').' — نشرة بَهْجَة');
    }

    public function headers(): Headers
    {
        // إلغاء بضغطة واحدة من زر «إلغاء الاشتراك» في تطبيقات البريد نفسها (RFC 8058)
        return new Headers(text: [
            'List-Unsubscribe' => '<'.$this->subscriber->unsubscribeUrl().'>',
            'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
        ]);
    }

    public function content(): Content
    {
        $area = $this->subscriber->area;

        return new Content(
            markdown: 'emails.newsletter-digest',
            with: [
                'days' => $this->events->groupBy(fn (Event $event): string => $event->start_date->toDateString()),
                'browseUrl' => route('events.index', $area ? ['area' => $area->slug] : []),
                'unsubscribeUrl' => $this->subscriber->unsubscribeUrl(),
            ],
        );
    }
}
