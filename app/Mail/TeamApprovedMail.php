<?php

namespace App\Mail;

use App\Models\Team;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * بريد ترحيب الفريق بعد اعتماد طلبه: يحمل بيانات الدخول ورابط اللوحة،
 * فلا تبقى كلمة المرور معلّقة على تسليم يدوي عبر واتساب.
 */
class TeamApprovedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Team $team,
        public User $manager,
        public string $password,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'تم اعتماد فريق '.$this->team->name.' في منصّة بَهْجَة 🎉');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.team-approved');
    }
}
