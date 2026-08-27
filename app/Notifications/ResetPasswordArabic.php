<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

/** بريد استعادة كلمة المرور — الصياغة العربية بدل قالب الإطار الإنجليزي */
class ResetPasswordArabic extends ResetPassword implements ShouldQueue
{
    use Queueable;

    public function toMail($notifiable): MailMessage
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        $minutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('استعادة كلمة المرور — '.config('app.name'))
            ->greeting('أهلاً '.$notifiable->name.'!')
            ->line('وصلنا طلب لاستعادة كلمة المرور لحسابكم. اضغطوا الزر لاختيار كلمة مرور جديدة.')
            ->action('تعيين كلمة مرور جديدة', $url)
            ->line('صلاحية الرابط '.$minutes.' دقيقة.')
            ->line('إن لم تطلبوا الاستعادة فتجاهلوا هذه الرسالة — كلمة المرور الحالية لم تتغيّر.');
    }
}
