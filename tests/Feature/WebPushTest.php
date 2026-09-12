<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Jobs\SendPushNotification;
use App\Models\Area;
use App\Models\Event;
use App\Models\PlaceLink;
use App\Models\PushSubscription;
use App\Models\ShelterCenter;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\Push\WebPushCrypto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * إشعارات الدفع: التعمية مكتوبة عندنا، فتُقاس بمتّجهات المواصفة نفسها
 * لا بثقتنا في الشيفرة.
 */
class WebPushTest extends TestCase
{
    use RefreshDatabase;

    private function keys(): array
    {
        return WebPushCrypto::generateVapidKeys();
    }

    private function subscribe(User $user): PushSubscription
    {
        // مفتاح متصفّح حقيقي الشكل: نقطة P-256 غير مضغوطة
        $browser = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        $ec = openssl_pkey_get_details($browser)['ec'];
        $point = "\x04".str_pad($ec['x'], 32, "\0", STR_PAD_LEFT).str_pad($ec['y'], 32, "\0", STR_PAD_LEFT);

        return PushSubscription::create([
            'user_id' => $user->id,
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/'.bin2hex(random_bytes(8)),
            'endpoint_hash' => PushSubscription::hashFor('e'.$user->id),
            'p256dh' => WebPushCrypto::base64UrlEncode($point),
            'auth' => WebPushCrypto::base64UrlEncode(random_bytes(16)),
        ]);
    }

    public function test_the_encryption_matches_the_rfc_8291_vector(): void
    {
        // لو انحرفت التعمية بايتاً واحداً لرفضتها كل خدمات الدفع بصمت
        $decode = fn (string $value): string => WebPushCrypto::base64UrlDecode($value);

        $body = WebPushCrypto::encrypt(
            'When I grow up, I want to be a watermelon',
            $decode('BCVxsr7N_eNgVRqvHtD0zTZsEc6-VV-JvLexhqUzORcxaOzi6-AYWXvTBHm4bjyPjs7Vd8pZGH6SRpkNtoIAiw4'),
            $decode('BTBZMqHH6r4Tts7J_aSIgg'),
            $decode('DGv6ra1nlYgDCS1FRnbzlw'),
            $decode('yfWPiYE-n46HLnH0KqZOF1fJJU3MYrct3AELtAQ-oRw'),
        );

        $this->assertSame(
            'DGv6ra1nlYgDCS1FRnbzlwAAEABBBP4z9KsN6nGRTbVYI_c7VJSPQTBtkgcy27mlmlMoZIIgDll6e3vCYLocInmYWAmS6Tlz'
            .'AC8wEqKK6PBru3jl7A_yl95bQpu6cVPTpK4Mqgkf1CXztLVBSt2Ks3oZwbuwXPXLWyouBWLVWGNWQexSgSxsj_Qulcy4a-fN',
            WebPushCrypto::base64UrlEncode($body),
        );
    }

    public function test_vapid_claims_are_signed_so_the_push_service_accepts_them(): void
    {
        $keys = $this->keys();

        $headers = WebPushCrypto::vapidHeaders(
            'https://fcm.googleapis.com',
            'mailto:info@bahjagaza.com',
            $keys['public'],
            $keys['private'],
        );

        $this->assertStringStartsWith('vapid t=', $headers['Authorization']);
        $this->assertStringContainsString(', k='.$keys['public'], $headers['Authorization']);

        [$header, $claims, $signature] = explode('.', str_replace(
            ['vapid t=', ', k='.$keys['public']], '', $headers['Authorization'],
        ));

        $payload = json_decode(WebPushCrypto::base64UrlDecode($claims), true);

        $this->assertSame('https://fcm.googleapis.com', $payload['aud']);
        $this->assertSame('mailto:info@bahjagaza.com', $payload['sub']);
        $this->assertGreaterThan(time(), $payload['exp']);
        $this->assertSame(64, strlen(WebPushCrypto::base64UrlDecode($signature)));
    }

    public function test_a_family_registers_its_device_once_per_endpoint(): void
    {
        config(['push.public_key' => $this->keys()['public'], 'push.private_key' => $this->keys()['private']]);

        $family = User::factory()->create(['role' => UserRole::Family, 'team_id' => null]);

        $payload = [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
            'keys' => ['p256dh' => str_repeat('A', 87), 'auth' => str_repeat('B', 22)],
        ];

        $this->actingAs($family)->postJson(route('push.store'), $payload)->assertOk();
        $this->actingAs($family)->postJson(route('push.store'), $payload)->assertOk();

        $this->assertSame(1, PushSubscription::count());
        $this->assertSame($family->id, PushSubscription::sole()->user_id);
    }

    public function test_a_dead_endpoint_is_dropped_rather_than_retried_forever(): void
    {
        $keys = $this->keys();
        config(['push.public_key' => $keys['public'], 'push.private_key' => $keys['private']]);

        $family = User::factory()->create(['role' => UserRole::Family, 'team_id' => null]);
        $subscription = $this->subscribe($family);

        Http::fake([$subscription->endpoint => Http::response('', 410)]);

        app(\App\Services\Push\WebPushSender::class)->sendToUser($family, ['title' => 'اختبار']);

        $this->assertSame(0, PushSubscription::count());
    }

    public function test_a_successful_push_carries_the_vapid_header_and_the_right_encoding(): void
    {
        $keys = $this->keys();
        config(['push.public_key' => $keys['public'], 'push.private_key' => $keys['private']]);

        $family = User::factory()->create(['role' => UserRole::Family, 'team_id' => null]);
        $subscription = $this->subscribe($family);

        Http::fake([$subscription->endpoint => Http::response('', 201)]);

        $sent = app(\App\Services\Push\WebPushSender::class)
            ->sendToUser($family, ['title' => 'غداً في حيّكم', 'body' => 'يوم ألعاب']);

        $this->assertSame(1, $sent);
        $this->assertNotNull($subscription->fresh()->last_sent_at);

        Http::assertSent(function ($request) {
            return str_starts_with($request->header('Authorization')[0], 'vapid t=')
                && $request->header('Content-Encoding')[0] === 'aes128gcm'
                && strlen($request->body()) > 100;
        });
    }

    public function test_only_meaningful_notifications_reach_the_phone(): void
    {
        Queue::fake();

        $family = User::factory()->create(['role' => UserRole::Family, 'team_id' => null]);

        $this->subscribe($family);

        UserNotification::send($family->id, 'event_new', 'فعالية جديدة');
        UserNotification::send($family->id, 'admin_message', 'رسالة من الإدارة');

        // إعلان فعالية يوقظ الهاتف، ورسالة إدارية تبقى داخل الموقع
        Queue::assertPushed(SendPushNotification::class, 1);
    }

    public function test_the_house_is_not_woken_at_night(): void
    {
        Queue::fake();

        $this->travelTo(today()->setHour(23));

        $family = User::factory()->create(['role' => UserRole::Family, 'team_id' => null]);
        $this->subscribe($family);

        UserNotification::send($family->id, 'event_new', 'فعالية جديدة');
        Queue::assertNotPushed(SendPushNotification::class);

        // إلغاء فعالية خبر عاجل: يصل ولو كان البيت نائماً
        UserNotification::send($family->id, 'event_cancelled', 'أُلغيت الفعالية');
        Queue::assertPushed(SendPushNotification::class, 1);
    }

    public function test_the_evening_digest_names_the_nearest_event_tomorrow(): void
    {
        Queue::fake();

        $keys = $this->keys();
        config(['push.public_key' => $keys['public'], 'push.private_key' => $keys['private']]);

        $this->seed(\Database\Seeders\AreaSeeder::class);
        $this->seed(\Database\Seeders\CategorySeeder::class);

        $home = Area::first();
        $far = Area::skip(1)->first();

        $homeCenter = new ShelterCenter;
        $homeCenter->forceFill(['area_id' => $home->id, 'name' => 'مركز الشاطئ', 'is_active' => true])->save();

        $nextDoor = new ShelterCenter;
        $nextDoor->forceFill(['area_id' => $home->id, 'name' => 'مركز النصر', 'is_active' => true])->save();

        PlaceLink::create([
            'from_center_id' => $homeCenter->id,
            'to_center_id' => $nextDoor->id,
            'walk_minutes' => 9,
        ]);

        $family = User::factory()->create([
            'role' => UserRole::Family,
            'team_id' => null,
            'area_id' => $home->id,
            'shelter_center_id' => $homeCenter->id,
        ]);
        $this->subscribe($family);

        Event::factory()->approved()->create([
            'area_id' => $home->id,
            'shelter_center_id' => $nextDoor->id,
            'title' => 'يوم ألعاب في مدرسة النصر',
            'start_date' => today()->addDay(),
            'start_time' => '10:00',
        ]);
        Event::factory()->approved()->create([
            'area_id' => $far->id,
            'shelter_center_id' => null,
            'title' => 'فعالية بعيدة',
            'start_date' => today()->addDay(),
        ]);

        $this->artisan('push:tomorrow')->assertSuccessful();

        Queue::assertPushed(SendPushNotification::class, function (SendPushNotification $job) use ($family): bool {
            return $job->userId === $family->id
                && $job->payload['title'] === 'غداً في حيّكم'
                && str_contains($job->payload['body'], 'يوم ألعاب في مدرسة النصر')
                && str_contains($job->payload['body'], 'على بُعد 9 دقائق مشياً')
                && ! str_contains($job->payload['body'], 'فعالية بعيدة');
        });
    }

    public function test_the_evening_digest_never_wakes_the_same_family_twice(): void
    {
        Queue::fake();

        $keys = $this->keys();
        config(['push.public_key' => $keys['public'], 'push.private_key' => $keys['private']]);

        $this->seed(\Database\Seeders\AreaSeeder::class);
        $this->seed(\Database\Seeders\CategorySeeder::class);

        $home = Area::first();
        $family = User::factory()->create([
            'role' => UserRole::Family,
            'team_id' => null,
            'area_id' => $home->id,
        ]);
        $this->subscribe($family);

        Event::factory()->approved()->create([
            'area_id' => $home->id,
            'start_date' => today()->addDay(),
        ]);

        $this->artisan('push:tomorrow')->assertSuccessful();
        $this->artisan('push:tomorrow')->assertSuccessful();

        // إعلان نشر الفعالية له مهمّته الخاصة — نعدّ ملخّص المساء وحده
        Queue::assertPushed(
            SendPushNotification::class,
            fn (SendPushNotification $job): bool => $job->payload['title'] === 'غداً في حيّكم',
            1,
        );
    }

    public function test_a_family_without_a_device_is_never_queued(): void
    {
        Queue::fake();

        $keys = $this->keys();
        config(['push.public_key' => $keys['public'], 'push.private_key' => $keys['private']]);

        $this->seed(\Database\Seeders\AreaSeeder::class);
        $this->seed(\Database\Seeders\CategorySeeder::class);

        $home = Area::first();
        User::factory()->create(['role' => UserRole::Family, 'team_id' => null, 'area_id' => $home->id]);

        Event::factory()->approved()->create(['area_id' => $home->id, 'start_date' => today()->addDay()]);

        $this->artisan('push:tomorrow')->assertSuccessful();

        Queue::assertNotPushed(SendPushNotification::class);
    }

    public function test_the_account_page_offers_the_switch(): void
    {
        config(['push.public_key' => $this->keys()['public']]);

        $family = User::factory()->create(['role' => UserRole::Family, 'team_id' => null]);

        $this->actingAs($family)->get(route('account'))
            ->assertOk()
            ->assertSee('إشعار حين تصل فعالية إلى حيّكم')
            ->assertSee('فعّلوا الإشعارات');
    }
}
