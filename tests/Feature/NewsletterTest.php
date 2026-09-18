<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Admin\Resources\NewsletterSubscribers\Pages\ListNewsletterSubscribers;
use App\Mail\NewsletterConfirmMail;
use App\Mail\NewsletterDigestMail;
use App\Models\Area;
use App\Models\Event;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use App\Services\NewsletterDigest;
use Database\Seeders\AreaSeeder;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class NewsletterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AreaSeeder::class);
        $this->seed(CategorySeeder::class);
    }

    private function area(string $slug): Area
    {
        return Area::where('slug', $slug)->firstOrFail();
    }

    // ── الاشتراك من الموقع ──

    public function test_a_visitor_subscribes_and_gets_a_confirmation_email(): void
    {
        Mail::fake();
        $rafah = $this->area('rafah');

        $this->from('/events')
            ->post(route('newsletter.subscribe'), ['newsletter_email' => 'Um.Sami@Example.com', 'newsletter_area' => $rafah->id])
            ->assertRedirect('/events#newsletter')
            ->assertSessionHas('newsletter_status');

        $subscriber = NewsletterSubscriber::sole();
        $this->assertSame('um.sami@example.com', $subscriber->email);
        $this->assertSame($rafah->id, $subscriber->area_id);
        $this->assertFalse($subscriber->isConfirmed());

        Mail::assertQueued(NewsletterConfirmMail::class, fn (NewsletterConfirmMail $mail): bool => $mail->hasTo('um.sami@example.com'));
    }

    public function test_the_honeypot_and_validation_guard_the_form(): void
    {
        Mail::fake();

        $this->post(route('newsletter.subscribe'), ['newsletter_email' => 'bot@example.com', 'website' => 'http://spam.example'])
            ->assertRedirect();
        $this->assertSame(0, NewsletterSubscriber::count());

        $this->from('/')
            ->post(route('newsletter.subscribe'), ['newsletter_email' => 'ليس بريداً'])
            ->assertRedirect('/#newsletter')
            ->assertSessionHasErrors('newsletter_email');

        Mail::assertNothingQueued();
    }

    public function test_the_confirmation_link_activates_and_a_wrong_token_is_rejected(): void
    {
        $subscriber = NewsletterSubscriber::factory()->pending()->create();

        $this->get(route('newsletter.confirm', ['subscriber' => $subscriber, 'token' => 'wrong-token']))->assertNotFound();
        $this->assertFalse($subscriber->fresh()->isConfirmed());

        $this->get($subscriber->confirmUrl())->assertOk()->assertSee('تمّ تأكيد اشتراككم');
        $this->assertTrue($subscriber->fresh()->isActive());
    }

    public function test_unsubscribe_works_from_the_link_and_from_a_one_click_post(): void
    {
        $subscriber = NewsletterSubscriber::factory()->create();
        $this->get($subscriber->unsubscribeUrl())->assertOk()->assertSee('أُلغي اشتراككم');
        $this->assertTrue($subscriber->fresh()->isUnsubscribed());

        $oneClick = NewsletterSubscriber::factory()->create();
        $this->post($oneClick->unsubscribeUrl(), ['List-Unsubscribe' => 'One-Click'])->assertOk();
        $this->assertTrue($oneClick->fresh()->isUnsubscribed());
    }

    public function test_a_returning_reader_is_reactivated_without_a_new_confirmation(): void
    {
        Mail::fake();
        $subscriber = NewsletterSubscriber::factory()->unsubscribed()->create(['email' => 'back@example.com']);

        $this->post(route('newsletter.subscribe'), ['newsletter_email' => 'back@example.com'])
            ->assertSessionHas('newsletter_status');

        $this->assertTrue($subscriber->fresh()->isActive());
        Mail::assertNothingQueued();
    }

    public function test_an_active_subscriber_is_not_duplicated_but_may_change_area(): void
    {
        Mail::fake();
        $gaza = $this->area('gaza');
        NewsletterSubscriber::factory()->create(['email' => 'same@example.com']);

        $this->post(route('newsletter.subscribe'), ['newsletter_email' => 'same@example.com', 'newsletter_area' => $gaza->id])
            ->assertSessionHas('newsletter_status');

        $this->assertSame(1, NewsletterSubscriber::count());
        $this->assertSame($gaza->id, NewsletterSubscriber::sole()->area_id);
        Mail::assertNothingQueued();
    }

    // ── نشرة الأسبوع ──

    public function test_the_weekly_digest_reaches_active_subscribers_with_their_area_events_only(): void
    {
        Mail::fake();
        $rafah = $this->area('rafah');
        $gaza = $this->area('gaza');

        Event::factory()->approved()->create(['title' => 'حكايات رفح', 'area_id' => $rafah->id, 'start_date' => today()->addDays(2)]);
        Event::factory()->approved()->create(['title' => 'ألعاب غزة', 'area_id' => $gaza->id, 'start_date' => today()->addDays(3)]);
        Event::factory()->approved()->create(['title' => 'بعد الأسبوع', 'area_id' => $gaza->id, 'start_date' => today()->addDays(10)]);

        $all = NewsletterSubscriber::factory()->create(['email' => 'all@example.com']);
        NewsletterSubscriber::factory()->create(['email' => 'rafah@example.com', 'area_id' => $rafah->id]);
        $quietArea = NewsletterSubscriber::factory()->create(['email' => 'north@example.com', 'area_id' => $this->area('north-gaza')->id]);
        NewsletterSubscriber::factory()->pending()->create(['email' => 'pending@example.com']);
        NewsletterSubscriber::factory()->unsubscribed()->create(['email' => 'gone@example.com']);

        $this->artisan('newsletter:send')->assertSuccessful();

        Mail::assertQueued(NewsletterDigestMail::class, fn (NewsletterDigestMail $mail): bool => $mail->hasTo('all@example.com')
            && $mail->events->pluck('title')->all() === ['حكايات رفح', 'ألعاب غزة']);
        Mail::assertQueued(NewsletterDigestMail::class, fn (NewsletterDigestMail $mail): bool => $mail->hasTo('rafah@example.com')
            && $mail->events->pluck('title')->all() === ['حكايات رفح']);
        Mail::assertQueuedCount(2);

        $this->assertNotNull($all->fresh()->last_sent_at);
        $this->assertNull($quietArea->fresh()->last_sent_at);

        // التشغيل مرتين في اليوم نفسه لا يكرّر الرسالة — و--force يتجاوز الحماية
        $this->artisan('newsletter:send')->assertSuccessful();
        Mail::assertQueuedCount(2);

        $this->artisan('newsletter:send', ['--force' => true])->assertSuccessful();
        Mail::assertQueuedCount(4);
    }

    public function test_the_digest_email_lists_events_by_day_with_an_unsubscribe_link(): void
    {
        $rafah = $this->area('rafah');
        Event::factory()->approved()->create([
            'title' => 'مسرح الدمى',
            'area_id' => $rafah->id,
            'start_date' => today()->addDay(),
            'start_time' => '16:00',
        ]);
        $subscriber = NewsletterSubscriber::factory()->create(['area_id' => $rafah->id]);

        $mail = new NewsletterDigestMail($subscriber, app(NewsletterDigest::class)->events($rafah->id));
        $html = $mail->render();

        $this->assertStringContainsString('مسرح الدمى', $html);
        $this->assertStringContainsString('16:00', $html);
        $this->assertStringContainsString($subscriber->unsubscribeUrl(), $html);
        $this->assertStringContainsString('رفح', $mail->envelope()->subject);
        $this->assertSame('<'.$subscriber->unsubscribeUrl().'>', $mail->headers()->text['List-Unsubscribe']);
    }

    // ── الموقع ──

    public function test_the_site_shows_the_platform_email_and_the_newsletter_form(): void
    {
        Event::factory()->approved()->create(['title' => 'فعالية الأسبوع', 'start_date' => today()->addDays(2)]);

        $this->get('/')
            ->assertOk()
            ->assertSee('info@bahjagaza.com')
            ->assertSee('نشرة بَهْجَة البريدية')
            ->assertSee('name="newsletter_email"', false);

        $this->get(route('contact'))->assertOk()->assertSee('mailto:info@bahjagaza.com', false);

        $this->get(route('newsletter'))->assertOk()->assertSee('فعالية الأسبوع')->assertSee('id="newsletter"', false);
    }

    // ── اللوحة ──

    public function test_the_subscriber_list_is_for_admins_only_and_sends_the_digest_on_demand(): void
    {
        Mail::fake();

        $family = User::factory()->create(['role' => UserRole::Family, 'team_id' => null]);
        $this->actingAs($family)->get('/admin/newsletter-subscribers')->assertForbidden();

        Event::factory()->approved()->create(['start_date' => today()->addDay()]);
        NewsletterSubscriber::factory()->create(['email' => 'reader@example.com']);

        $this->actingAs(User::factory()->create(['role' => UserRole::SuperAdmin, 'team_id' => null]));
        $this->get('/admin/newsletter-subscribers')->assertOk()->assertSee('reader@example.com');

        Livewire::test(ListNewsletterSubscribers::class)
            ->callAction('sendDigest')
            ->assertHasNoActionErrors();

        Mail::assertQueued(NewsletterDigestMail::class, fn (NewsletterDigestMail $mail): bool => $mail->hasTo('reader@example.com'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'newsletter.sent']);
    }
}
