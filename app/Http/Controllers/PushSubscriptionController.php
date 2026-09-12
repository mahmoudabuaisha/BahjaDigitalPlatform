<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use App\Services\Push\WebPushSender;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * تسجيل جهاز العائلة في الإشعارات وإلغاؤه.
 * الاشتراك يُحفظ بعنوانه الفريد، فإعادة الاشتراك من الجهاز نفسه تحدّثه
 * ولا تضاعفه.
 */
class PushSubscriptionController extends Controller
{
    public function __construct(private readonly WebPushSender $sender) {}

    public function store(Request $request): JsonResponse
    {
        if (! $this->sender->isConfigured()) {
            return response()->json(['message' => 'الإشعارات غير مفعَّلة على الخادم بعد.'], 503);
        }

        $data = $request->validate([
            'endpoint' => ['required', 'url', 'max:1000'],
            'keys.p256dh' => ['required', 'string', 'max:120'],
            'keys.auth' => ['required', 'string', 'max:60'],
        ]);

        PushSubscription::updateOrCreate(
            ['endpoint_hash' => PushSubscription::hashFor($data['endpoint'])],
            [
                'user_id' => $request->user()->id,
                'endpoint' => $data['endpoint'],
                'p256dh' => $data['keys']['p256dh'],
                'auth' => $data['keys']['auth'],
                'device' => substr((string) $request->userAgent(), 0, 160),
                'failures' => 0,
            ],
        );

        return response()->json(['message' => 'سيصلكم إشعار حين تصل فعالية إلى حيّكم.']);
    }

    public function destroy(Request $request): JsonResponse
    {
        $endpoint = (string) $request->input('endpoint');

        PushSubscription::query()
            ->where('user_id', $request->user()->id)
            ->when(
                $endpoint !== '',
                fn ($query) => $query->where('endpoint_hash', PushSubscription::hashFor($endpoint)),
            )
            ->delete();

        return response()->json(['message' => 'أوقفنا الإشعارات على هذا الجهاز.']);
    }

    /** إشعار تجريبي كي يطمئنّ وليّ الأمر أن الأمر يعمل فعلاً */
    public function test(Request $request): JsonResponse
    {
        $sent = $this->sender->sendToUser($request->user(), [
            'title' => 'بَهْجَة تصلكم',
            'body' => 'هكذا سيصلكم الإشعار حين تُعلَن فعالية قرب مكانكم.',
            'url' => route('account'),
            'tag' => 'bahja-test',
        ]);

        return response()->json([
            'message' => $sent > 0
                ? 'أرسلنا إشعاراً تجريبياً إلى جهازكم.'
                : 'لم نتمكّن من الإرسال — جرّبوا تفعيل الإشعارات من جديد.',
        ], $sent > 0 ? 200 : 422);
    }
}
