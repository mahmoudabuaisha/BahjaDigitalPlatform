<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Event;
use App\Models\Feedback;
use App\Models\ShelterCenter;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * بيانات تجريبية للتطوير المحلي فقط — لا تُشغَّل في الإنتاج.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // مراكز إيواء تجريبية موزعة على المحافظات
        $centers = [
            ['slug' => 'gaza', 'name' => 'مدرسة الشاطئ الإعدادية', 'type' => 'school'],
            ['slug' => 'gaza', 'name' => 'مركز إيواء الرمال', 'type' => 'shelter'],
            ['slug' => 'khan-younis', 'name' => 'مخيم المواصي', 'type' => 'camp'],
            ['slug' => 'khan-younis', 'name' => 'مدرسة بني سهيلا', 'type' => 'school'],
            ['slug' => 'deir-al-balah', 'name' => 'مركز إيواء النصيرات', 'type' => 'shelter'],
            ['slug' => 'rafah', 'name' => 'مخيم تل السلطان', 'type' => 'camp'],
        ];

        foreach ($centers as $center) {
            $area = Area::where('slug', $center['slug'])->first();
            ShelterCenter::updateOrCreate(
                ['area_id' => $area->id, 'name' => $center['name']],
                ['type' => $center['type'], 'is_active' => true],
            );
        }

        // 3 فرق معتمدة بمدرائها + فريق بانتظار الموافقة
        $teams = [
            ['name' => 'فريق بسمة أمل', 'slug' => 'basmat-amal'],
            ['name' => 'فريق صناع الفرح', 'slug' => 'sunna-alfarah'],
            ['name' => 'فريق قوس قزح', 'slug' => 'qaws-quzah'],
        ];

        foreach ($teams as $index => $data) {
            $team = Team::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'name' => $data['name'],
                    'description' => 'فريق تطوعي يقدم أنشطة ترفيهية ودعماً نفسياً لأطفال غزة.',
                    'contact_name' => 'منسق '.$data['name'],
                    'whatsapp_phone' => '9705900000'.($index + 10),
                    'is_active' => true,
                ],
            );

            User::updateOrCreate(
                ['email' => "manager{$index}@demo.test"],
                [
                    'name' => 'مسؤول '.$data['name'],
                    'password' => 'password',
                    'role' => \App\Enums\UserRole::TeamManager,
                    'team_id' => $team->id,
                    'is_active' => true,
                ],
            );
        }

        Team::factory()->pending()->create([
            'name' => 'فريق الأمل الجديد (طلب معلق)',
            'slug' => 'new-hope-pending',
        ]);

        // فعاليات: قادمة معتمدة + معلقة + مسودات + منفذة بحضور مسجل
        $activeTeams = Team::active()->get();
        $centersAll = ShelterCenter::all();

        foreach ($activeTeams as $team) {
            Event::factory()
                ->count(5)
                ->approved()
                ->inCenter($centersAll->random())
                ->create(['team_id' => $team->id]);

            Event::factory()
                ->count(2)
                ->pending()
                ->create(['team_id' => $team->id, 'area_id' => Area::inRandomOrder()->value('id')]);

            Event::factory()
                ->count(1)
                ->draft()
                ->create(['team_id' => $team->id, 'area_id' => Area::inRandomOrder()->value('id')]);

            Event::factory()
                ->count(3)
                ->completed()
                ->inCenter($centersAll->random())
                ->create(['team_id' => $team->id]);
        }

        // تقييمات تجريبية: عامة وعلى الفعاليات المنفذة
        Feedback::factory()->count(8)->create();

        Event::where('status', \App\Enums\EventStatus::Completed)
            ->inRandomOrder()
            ->take(5)
            ->get()
            ->each(function (Event $event) {
                Feedback::factory()->count(2)->create(['event_id' => $event->id]);
            });
    }
}
