<?php

namespace Tests\Feature;

use App\Enums\RegistrationMode;
use App\Models\Event;
use Database\Seeders\AreaSeeder;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * الروزنامة دون إنترنت.
 *
 * الوعد المُختبَر هنا: عائلة في غزة فتحت الموقع مرّة واحدة وهي متّصلة،
 * ثم انقطعت الشبكة — تظلّ تعرف موعد كل فعالية ومكانها وطريق الوصول
 * إليها، وتفتح صفحة أي فعالية حتى لو لم تفتحها من قبل.
 */
class OfflineCalendarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AreaSeeder::class);
        $this->seed(CategorySeeder::class);
    }

    public function test_the_saved_feed_carries_enough_to_read_a_whole_event(): void
    {
        Event::factory()->approved()->create([
            'title' => 'مسرح تفاعلي',
            'description' => 'عرض مسرحي قصير يشارك فيه الأطفال.',
            'directions' => 'من دوّار الشهداء غرباً، ثم أوّل يمين.',
            'terms' => 'يرجى الحضور قبل الموعد بعشر دقائق.',
            'age_min' => 4,
            'age_max' => 10,
            'fee' => 0,
            'registration_mode' => RegistrationMode::Approval,
            'start_date' => today()->addDays(2),
        ]);

        $event = $this->getJson('/api/v1/events')
            ->assertOk()
            ->assertJsonPath('schema', 3)
            ->json('events.0');

        $this->assertSame('مسرح تفاعلي', $event['t']);
        $this->assertStringContainsString('عرض مسرحي', $event['de']);
        $this->assertStringContainsString('دوّار الشهداء', $event['di']);
        $this->assertStringContainsString('عشر دقائق', $event['tr']);
        $this->assertSame('من 4 إلى 10 سنوات', $event['ag']);
        $this->assertSame('approval', $event['rm']);

        // المجاني لا يحمل حقل رسوم — الصفحة تقول «مجاناً» من عندها
        $this->assertArrayNotHasKey('fe', $event);
    }

    public function test_empty_detail_fields_are_left_out_of_the_payload(): void
    {
        Event::factory()->approved()->create([
            'description' => '',
            'directions' => null,
            'terms' => null,
            'age_min' => null,
            'age_max' => null,
            'start_date' => today()->addDay(),
        ]);

        $event = $this->getJson('/api/v1/events')->json('events.0');

        // الحمولة تُنزَّل على 2G: مفتاح فارغ لكل فعالية ثمنٌ بلا مقابل
        foreach (['de', 'di', 'tr', 'ag'] as $key) {
            $this->assertArrayNotHasKey($key, $event);
        }
    }

    public function test_long_text_is_trimmed_so_the_payload_stays_light(): void
    {
        Event::factory()->approved()->create([
            'description' => str_repeat('كلمة ', 500),
            'start_date' => today()->addDay(),
        ]);

        $description = $this->getJson('/api/v1/events')->json('events.0.de');

        $this->assertLessThanOrEqual(710, mb_strlen($description));
    }

    public function test_the_offline_event_shell_is_served_and_precached(): void
    {
        $this->get(route('offline.event'))
            ->assertOk()
            ->assertSee('offlineEvent', false)
            ->assertSee('كيف تصلون؟');

        $worker = $this->get('/sw.js')->assertOk()->getContent();

        $this->assertStringContainsString("'/offline/event'", $worker);
        $this->assertStringContainsString('EVENT_PATH.test(pathname)', $worker);
    }

    public function test_the_offline_page_is_a_calendar_not_an_apology(): void
    {
        $this->get(route('offline'))
            ->assertOk()
            ->assertSee('offlineCalendar', false)
            // بحث وتصفية على البيانات المحفوظة نفسها
            ->assertSee('ابحثوا باسم الفعالية أو المكان', false)
            ->assertSee('كل المحافظات');
    }

    public function test_the_logo_is_served_from_the_device_when_offline(): void
    {
        // كان شعار الترويسة يسقط دون شبكة فتظهر صورة مكسورة فوق كل صفحة
        $worker = $this->get('/sw.js')->getContent();

        $this->assertStringContainsString("'/brand/'", $worker);
        $this->assertStringContainsString("'/brand/logo.png'", $worker);
    }

    public function test_the_empty_shell_is_kept_out_of_search_results(): void
    {
        $robots = (string) file_get_contents(public_path('robots.txt'));

        $this->assertStringContainsString('Disallow: /offline/event', $robots);
    }
}
