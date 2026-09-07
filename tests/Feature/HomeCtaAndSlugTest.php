<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Support\Settings;
use Database\Seeders\AreaSeeder;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * زر تسجيل الفرق البارز في الرئيسية، المعرّف العربي التلقائي
 * من اسم الفريق، وزر واتساب الإدارة العائم.
 */
class HomeCtaAndSlugTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AreaSeeder::class);
        $this->seed(CategorySeeder::class);
    }

    public function test_the_homepage_shows_a_clear_team_registration_button(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('سجّلوا فريقكم أو مؤسستكم')
            ->assertSee(route('teams.join'));
    }

    public function test_a_team_slug_is_generated_from_its_arabic_name(): void
    {
        $team = Team::create(['name' => 'فريق بسمة أمل', 'is_active' => true]);

        $this->assertSame('فريق-بسمة-أمل', $team->slug);

        // نفس الاسم مرة ثانية → لاحقة رقمية بلا تصادم
        $second = Team::create(['name' => 'فريق بسمة أمل', 'is_active' => true]);
        $this->assertSame('فريق-بسمة-أمل-2', $second->slug);

        // والرابط العربي يفتح صفحة الفريق فعلاً
        $this->get(route('teams.show', $team))->assertOk()->assertSee('فريق بسمة أمل');
    }

    public function test_a_manually_chosen_slug_is_respected(): void
    {
        $team = Team::create(['name' => 'فريق النور', 'slug' => 'alnoor-team', 'is_active' => true]);

        $this->assertSame('alnoor-team', $team->slug);
    }

    public function test_the_floating_whatsapp_button_follows_the_settings_number(): void
    {
        Settings::set('site_whatsapp', '970599123456');

        $this->get(route('home'))
            ->assertSee('تواصلوا مع إدارة المنصّة عبر واتساب')
            ->assertSee('wa.me/970599123456', escape: false);

        // بلا إعداد يبقى الزر ظاهراً دائماً برقم الإدارة الافتراضي
        Settings::set('site_whatsapp', '');

        $this->get(route('home'))
            ->assertSee('تواصلوا مع إدارة المنصّة عبر واتساب')
            ->assertSee('wa.me/970593674330', escape: false);
    }
}
