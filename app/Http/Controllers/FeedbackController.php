<?php

namespace App\Http\Controllers;

use App\Enums\FeedbackSource;
use App\Models\Event;
use App\Models\Feedback;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FeedbackController extends Controller
{
    public function create(): View
    {
        return view('pages.feedback');
    }

    public function store(Request $request): RedirectResponse
    {
        // حقل فخ للبوتات: البشر لا يرونه ولا يملؤونه — نتجاهل الطلب بصمت
        if (filled($request->input('website'))) {
            return redirect()->route('feedback.create')->with('feedback_sent', true);
        }

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'source' => ['nullable', 'in:family,team'],
            'message' => ['nullable', 'string', 'max:1000'],
            'contact_name' => ['nullable', 'string', 'max:100'],
            'contact_phone' => ['nullable', 'string', 'max:20'],
        ]);

        Feedback::create([
            ...$data,
            'source' => $data['source'] ?? FeedbackSource::Family->value,
        ]);

        return redirect()->route('feedback.create')->with('feedback_sent', true);
    }

    /**
     * تقييم فعالية (قواعد الخطة 6.3): بعد النهاية فقط ولمدة 72 ساعة،
     * وبحدّ تقييم واحد لكل جهاز مجهول — دون أي بيانات شخصية.
     */
    public function storeForEvent(Request $request, Event $event): RedirectResponse
    {
        abort_unless($event->status->isPubliclyVisible(), 404);

        if (filled($request->input('website'))) {
            return redirect()->route('events.show', $event)->with('feedback_sent', true);
        }

        $endsAt = $event->startsAt()->copy()->addHours(4);

        if ($endsAt->isFuture()) {
            return redirect()->route('events.show', $event)
                ->with('feedback_error', 'التقييم يفتح بعد انتهاء الفعالية.');
        }

        if ($endsAt->copy()->addHours(72)->isPast()) {
            return redirect()->route('events.show', $event)
                ->with('feedback_error', 'انتهت مهلة تقييم هذه الفعالية.');
        }

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        // بصمة جهاز مجهولة: كعكة عشوائية تُمزج بمفتاح التطبيق — لا IP ولا هوية
        $deviceHash = $this->deviceHash($request);

        if ($event->feedback()->where('device_hash', $deviceHash)->exists()) {
            return redirect()->route('events.show', $event)
                ->with('feedback_error', 'سبق أن قيّمتم هذه الفعالية من هذا الجهاز.');
        }

        $event->feedback()->create([
            ...$data,
            'source' => FeedbackSource::Family,
            'device_hash' => $deviceHash,
        ]);

        return redirect()->route('events.show', $event)->with('feedback_sent', true);
    }

    private function deviceHash(Request $request): string
    {
        $device = $request->cookie('bahja_device');

        if (! $device) {
            $device = Str::uuid()->toString();

            cookie()->queue(cookie('bahja_device', $device, 60 * 24 * 365));
        }

        return hash_hmac('sha256', $device, (string) config('app.key'));
    }
}
