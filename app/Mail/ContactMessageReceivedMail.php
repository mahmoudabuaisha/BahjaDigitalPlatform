<?php

namespace App\Mail;

use App\Filament\Admin\Resources\ContactMessages\ContactMessageResource;
use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * تنبيه الإدارة برسالة جديدة من «تواصلوا معنا»: نصّها كاملاً ورابط فتحها
 * في اللوحة، والردّ على البريد يذهب إلى المرسِل مباشرة إن ترك بريده.
 */
class ContactMessageReceivedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public ContactMessage $contactMessage) {}

    public function envelope(): Envelope
    {
        $message = $this->contactMessage;

        return new Envelope(
            subject: 'رسالة جديدة من '.$message->name.($message->subject ? ': '.$message->subject : ''),
            replyTo: filled($message->email) ? [new Address($message->email, $message->name)] : [],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.contact-message',
            with: [
                'url' => ContactMessageResource::getUrl('view', ['record' => $this->contactMessage], panel: 'admin'),
            ],
        );
    }
}
