<?php

namespace App\Mail;

use App\Models\NewsletterSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * تأكيد الاشتراك في النشرة: لا تُرسَل نشرة لبريد لم يضغط صاحبه هذا الرابط،
 * فلا يستطيع أحد أن يشترك باسم غيره.
 */
class NewsletterConfirmMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public NewsletterSubscriber $subscriber) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'أكّدوا اشتراككم في نشرة بَهْجَة');
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.newsletter-confirm',
            with: ['url' => $this->subscriber->confirmUrl()],
        );
    }
}
