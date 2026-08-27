<?php

namespace Tests\Feature;

use App\Enums\TeamApplicationStatus;
use App\Enums\UserRole;
use App\Mail\UserNotificationMail;
use App\Models\TeamApplication;
use App\Models\User;
use App\Models\UserNotification;
use App\Notifications\ResetPasswordArabic;
use App\Notifications\VerifyEmailArabic;
use App\Services\TeamApplicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * منظومة البريد: تأكيد الحساب، استعادة كلمة المرور،
 * والصورة البريدية لإشعارات الموقع.
 */
class EmailFlowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_registering_a_family_sends_the_arabic_verification_email(): void
    {
        Notification::fake();

        $this->post(route('register'), [
            'name' => 'أم محمد',
            'email' => 'um.mohammed@example.com',
            'password' => 'kalimat-sirr',
            'password_confirmation' => 'kalimat-sirr',
        ])->assertRedirect(route('account'));

        Notification::assertSentTo(
            User::firstWhere('email', 'um.mohammed@example.com'),
            VerifyEmailArabic::class,
        );
    }

    public function test_the_signed_link_marks_the_email_verified(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Family,
            'team_id' => null,
            'email_verified_at' => null,
        ]);

        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->actingAs($user)->get($url)->assertRedirect(route('account'));

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_the_resend_button_sends_again_and_the_banner_shows_until_verified(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'role' => UserRole::Family,
            'team_id' => null,
            'email_verified_at' => null,
        ]);

        $this->actingAs($user)->get(route('account'))->assertSee('بريدكم غير مؤكَّد بعد');

        $this->actingAs($user)->post(route('verification.send'))->assertRedirect();

        Notification::assertSentTo($user, VerifyEmailArabic::class);
    }

    public function test_forgot_password_sends_the_arabic_reset_email_without_leaking_accounts(): void
    {
        Notification::fake();

        $user = User::factory()->create(['role' => UserRole::Family, 'team_id' => null]);

        // بريد مسجَّل: تصل الرسالة، ويظهر نفس الرد العام
        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPasswordArabic::class);

        // بريد غير مسجَّل: نفس الرد العام بلا أي خطأ يكشف الحسابات
        $this->post(route('password.email'), ['email' => 'ghost@example.com'])
            ->assertSessionHas('status')
            ->assertSessionHasNoErrors();
    }

    public function test_the_reset_link_changes_the_password_and_the_user_can_login_with_it(): void
    {
        $user = User::factory()->create(['role' => UserRole::Family, 'team_id' => null]);
        $token = Password::broker()->createToken($user);

        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))->assertOk();

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'sirr-jadid-123',
            'password_confirmation' => 'sirr-jadid-123',
        ])->assertRedirect(route('login'));

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'sirr-jadid-123',
        ])->assertRedirect(route('account'));
    }

    public function test_every_site_notification_is_also_queued_as_an_email(): void
    {
        Mail::fake();

        $user = User::factory()->create(['role' => UserRole::Family, 'team_id' => null]);

        UserNotification::send($user, 'event_cancelled', 'أُلغيت فعالية «يوم المرح»', 'نعتذر منكم — أُلغي الموعد بسبب الأحوال.', '/my-events');

        Mail::assertQueued(UserNotificationMail::class, fn (UserNotificationMail $mail) => $mail->hasTo($user->email)
            && $mail->notification->title === 'أُلغيت فعالية «يوم المرح»');
    }

    public function test_an_admin_approved_team_manager_starts_with_a_verified_email(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin, 'team_id' => null]));

        $application = TeamApplication::create([
            'team_name' => 'فريق الفرح',
            'contact_name' => 'أبو سالم',
            'contact_email' => 'salem@example.com',
            'contact_phone' => '0599000111',
            'description' => 'أنشطة ترفيهية للأطفال.',
            'status' => TeamApplicationStatus::Pending,
        ]);

        $manager = app(TeamApplicationService::class)->approve($application)['manager'];

        $this->assertTrue($manager->fresh()->hasVerifiedEmail());
    }
}
