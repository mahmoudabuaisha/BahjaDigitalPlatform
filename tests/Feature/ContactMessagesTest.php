<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Admin\Resources\ContactMessages\ContactMessageResource;
use App\Filament\Admin\Resources\ContactMessages\Pages\ListContactMessages;
use App\Filament\Admin\Resources\ContactMessages\Pages\ViewContactMessage;
use App\Mail\ContactMessageReceivedMail;
use App\Models\ContactMessage;
use App\Models\Feedback;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class ContactMessagesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::SuperAdmin, 'team_id' => null]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'contact_name' => 'أم محمد',
            'contact_email' => 'um.mohammed@example.com',
            'contact_phone' => '0599 123 456',
            'subject' => 'استفسار عن ورشة الرسم',
            'message' => 'هل الورشة مناسبة لطفلة في الرابعة؟ وهل يلزم إحضار أدوات؟',
        ], $overrides);
    }

    // ── الاستقبال من الموقع العام ──

    public function test_a_contact_message_lands_in_its_own_inbox_not_among_the_ratings(): void
    {
        $this->post(route('contact.store'), $this->payload())
            ->assertRedirect(route('contact'))
            ->assertSessionHas('contact_sent');

        $this->assertDatabaseHas('contact_messages', [
            'name' => 'أم محمد',
            'email' => 'um.mohammed@example.com',
            'phone' => '0599 123 456',
            'subject' => 'استفسار عن ورشة الرسم',
            'handled_at' => null,
        ]);
        $this->assertSame(0, Feedback::count());
    }

    public function test_the_honeypot_drops_bots_silently(): void
    {
        $this->post(route('contact.store'), $this->payload(['website' => 'http://spam.example']))
            ->assertRedirect(route('contact'));

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_name_and_message_are_required(): void
    {
        $this->post(route('contact.store'), ['contact_email' => 'x@example.com'])
            ->assertSessionHasErrors(['contact_name', 'message']);
    }

    // ── التنبيه بالبريد ──

    public function test_active_super_admins_are_emailed_when_no_address_is_configured(): void
    {
        Mail::fake();

        $owner = $this->admin();
        $inactive = User::factory()->create(['role' => UserRole::SuperAdmin, 'team_id' => null, 'is_active' => false]);
        $moderator = User::factory()->create(['role' => UserRole::Admin, 'team_id' => null]);

        $this->post(route('contact.store'), $this->payload());

        Mail::assertQueued(ContactMessageReceivedMail::class, fn (ContactMessageReceivedMail $mail): bool => $mail->hasTo($owner->email)
            && ! $mail->hasTo($inactive->email)
            && ! $mail->hasTo($moderator->email)
            && $mail->contactMessage->subject === 'استفسار عن ورشة الرسم');
    }

    public function test_a_configured_address_receives_the_alert_instead(): void
    {
        Mail::fake();

        $owner = $this->admin();
        Settings::set('contact_notify_email', 'inbox@bahja.example');

        $this->post(route('contact.store'), $this->payload());

        Mail::assertQueued(ContactMessageReceivedMail::class, fn (ContactMessageReceivedMail $mail): bool => $mail->hasTo('inbox@bahja.example')
            && ! $mail->hasTo($owner->email));
    }

    public function test_the_alert_can_be_switched_off(): void
    {
        Mail::fake();

        $this->admin();
        Settings::set('contact_notify_enabled', false);

        $this->post(route('contact.store'), $this->payload());

        Mail::assertNotQueued(ContactMessageReceivedMail::class);
        $this->assertSame(1, ContactMessage::count());
    }

    public function test_a_mail_failure_never_blocks_the_family(): void
    {
        $this->admin();

        // خادم بريد لا يستجيب — تُحفظ الرسالة وتُشكَر العائلة كأن شيئاً لم يكن
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => '127.0.0.1',
            'mail.mailers.smtp.port' => 1,
            'mail.mailers.smtp.timeout' => 1,
        ]);

        $this->post(route('contact.store'), $this->payload())
            ->assertRedirect(route('contact'))
            ->assertSessionHas('contact_sent');

        $this->assertSame(1, ContactMessage::count());
    }

    public function test_the_alert_email_carries_the_whole_message_and_a_link_to_the_inbox(): void
    {
        $message = ContactMessage::factory()->create([
            'name' => 'أبو خليل',
            'email' => 'khalil@example.com',
            'subject' => 'اقتراح فعالية',
            'message' => str_repeat('نصّ طويل يتجاوز الخمسين حرفاً بكثير. ', 12),
        ]);

        $mail = new ContactMessageReceivedMail($message);
        $html = $mail->render();

        $this->assertStringContainsString('اقتراح فعالية', $mail->envelope()->subject);
        $this->assertSame('khalil@example.com', $mail->envelope()->replyTo[0]->address);
        $this->assertStringContainsString(trim($message->message), $html);
        $this->assertStringContainsString('/admin/contact-messages/'.$message->id, $html);
    }

    // ── صندوق اللوحة ──

    public function test_the_inbox_is_closed_to_families(): void
    {
        $family = User::factory()->create(['role' => UserRole::Family, 'team_id' => null]);

        $this->actingAs($family)->get('/admin/contact-messages')->assertForbidden();
    }

    public function test_the_admin_reads_the_whole_message_with_its_subject_and_email(): void
    {
        $long = str_repeat('نصّ طويل يتجاوز الخمسين حرفاً بكثير. ', 12);
        $message = ContactMessage::factory()->create([
            'name' => 'أبو خليل',
            'email' => 'khalil@example.com',
            'subject' => 'اقتراح فعالية',
            'message' => $long,
        ]);

        $this->actingAs($this->admin());

        $this->get('/admin/contact-messages')
            ->assertOk()
            ->assertSee('أبو خليل')
            ->assertSee('اقتراح فعالية');

        $this->get(ContactMessageResource::getUrl('view', ['record' => $message]))
            ->assertOk()
            ->assertSee('اقتراح فعالية')
            ->assertSee('khalil@example.com')
            ->assertSee(trim($long));
    }

    public function test_the_sidebar_badge_counts_open_messages_only(): void
    {
        ContactMessage::factory()->count(2)->create();
        ContactMessage::factory()->handled()->create();

        $this->assertSame('2', ContactMessageResource::getNavigationBadge());

        ContactMessage::open()->get()->each(fn (ContactMessage $message) => $message->markHandled());

        $this->assertNull(ContactMessageResource::getNavigationBadge());
    }

    public function test_the_inbox_opens_on_the_new_messages_tab(): void
    {
        $open = ContactMessage::factory()->create();
        $handled = ContactMessage::factory()->handled()->create();

        $this->actingAs($this->admin());

        Livewire::test(ListContactMessages::class)
            ->assertCanSeeTableRecords([$open])
            ->assertCanNotSeeTableRecords([$handled]);

        Livewire::test(ListContactMessages::class, ['activeTab' => 'all'])
            ->assertCanSeeTableRecords([$open, $handled]);
    }

    public function test_a_message_is_closed_with_a_note_and_can_be_reopened(): void
    {
        $admin = $this->admin();
        $message = ContactMessage::factory()->create();

        $this->actingAs($admin);

        Livewire::test(ViewContactMessage::class, ['record' => $message->getRouteKey()])
            ->callAction('markHandled', ['note' => 'رُدّ عبر واتساب'])
            ->assertHasNoActionErrors();

        $message->refresh();
        $this->assertTrue($message->isHandled());
        $this->assertSame($admin->id, $message->handled_by);
        $this->assertSame('رُدّ عبر واتساب', $message->note);
        $this->assertDatabaseHas('audit_logs', ['action' => 'contact.handled', 'subject_id' => $message->id]);
        $this->assertNull(ContactMessageResource::getNavigationBadge());

        Livewire::test(ViewContactMessage::class, ['record' => $message->getRouteKey()])
            ->callAction('reopen')
            ->assertHasNoActionErrors();

        $this->assertFalse($message->fresh()->isHandled());
        $this->assertSame('1', ContactMessageResource::getNavigationBadge());
    }

    // ── روابط الردّ ──

    public function test_reply_links_use_international_whatsapp_numbers(): void
    {
        $local = ContactMessage::factory()->make(['phone' => '0599 123 456', 'subject' => null]);
        $this->assertStringStartsWith('https://wa.me/970599123456?text=', $local->whatsappUrl());

        $this->assertSame('970599123456', ContactMessage::factory()->make(['phone' => '+970 59-9123456'])->whatsappDigits());
        $this->assertSame('972599123456', ContactMessage::factory()->make(['phone' => '00972599123456'])->whatsappDigits());
        $this->assertNull(ContactMessage::factory()->make(['phone' => null])->whatsappUrl());
        $this->assertNull(ContactMessage::factory()->make(['phone' => '123'])->whatsappUrl());

        $withEmail = ContactMessage::factory()->make(['email' => 'khalil@example.com', 'subject' => 'اقتراح']);
        $this->assertSame('mailto:khalil@example.com?subject='.rawurlencode('رد: اقتراح'), $withEmail->mailtoUrl());
        $this->assertNull(ContactMessage::factory()->make(['email' => null])->mailtoUrl());
    }

    // ── نقل الرسائل القديمة ──

    public function test_legacy_contact_rows_move_out_of_the_feedback_table(): void
    {
        // نعود خطوة إلى ما قبل هجرة الصندوق، فنضع رسالة قديمة وتقييماً في جدول التقييمات
        Artisan::call('migrate:rollback', ['--step' => 1]);

        DB::table('feedback')->insert([
            [
                'source' => 'family', 'rating' => null, 'event_id' => null,
                'message' => 'أين أجد جدول الفعاليات؟', 'subject' => 'سؤال',
                'contact_name' => 'أم سامي', 'contact_email' => 'sami@example.com', 'contact_phone' => '0598000111',
                'created_at' => '2026-09-01 10:00:00', 'updated_at' => '2026-09-01 10:00:00',
            ],
            [
                'source' => 'family', 'rating' => 5, 'event_id' => null,
                'message' => 'شكراً لكم', 'subject' => null,
                'contact_name' => null, 'contact_email' => null, 'contact_phone' => null,
                'created_at' => '2026-09-02 10:00:00', 'updated_at' => '2026-09-02 10:00:00',
            ],
        ]);

        Artisan::call('migrate');

        $this->assertDatabaseHas('contact_messages', [
            'name' => 'أم سامي',
            'email' => 'sami@example.com',
            'subject' => 'سؤال',
            'message' => 'أين أجد جدول الفعاليات؟',
        ]);
        $this->assertSame('2026-09-01 10:00:00', ContactMessage::first()->created_at->toDateTimeString());
        $this->assertSame(1, Feedback::count());
        $this->assertSame(5, Feedback::first()->rating);
    }
}
