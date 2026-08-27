<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

/** بريد تأكيد الحساب — الصياغة العربية بدل قالب الإطار الإنجليزي */
class VerifyEmailArabic extends VerifyEmail implements ShouldQueue
{
    use Queueable;

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('تأكيد بريدكم في '.config('app.name'))
            ->greeting('أهلاً '.$notifiable->name.'!')
            ->line('سعداء بانضمامكم إلى منصّة بَهْجَة. اضغطوا الزر لتأكيد بريدكم الإلكتروني وتفعيل كل ميزات الحساب.')
            ->action('تأكيد البريد الإلكتروني', $this->verificationUrl($notifiable))
            ->line('إن لم تنشئوا هذا الحساب فتجاهلوا هذه الرسالة ولا داعي لأي إجراء.');
    }
}
