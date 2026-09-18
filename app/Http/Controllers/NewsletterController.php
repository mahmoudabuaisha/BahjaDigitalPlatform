<?php

namespace App\Http\Controllers;

use App\Mail\NewsletterConfirmMail;
use App\Models\Area;
use App\Models\NewsletterSubscriber;
use App\Services\NewsletterDigest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * النشرة البريدية: صفحة الأسبوع مع نموذج الاشتراك، وتأكيد البريد وإلغاؤه
 * من روابط الرسائل. لا حساب ولا كلمة مرور — البريد ورمز الرابط فقط.
 */
class NewsletterController extends Controller
{
    public function show(NewsletterDigest $digest): View
    {
        return view('pages.newsletter', [
            'areas' => Area::orderBy('sort_order')->get(['id', 'name']),
            'days' => $digest->eventsByDay(),
        ]);
    }

    public function subscribe(Request $request): RedirectResponse
    {
        // فخ البوتات — البشر لا يرون الحقل؛ نتظاهر بالنجاح ولا نحفظ شيئاً
        if (filled($request->input('website'))) {
            return $this->backToForm()->with('newsletter_status', 'أرسلنا رسالة تأكيد إلى بريدكم — اضغطوا رابطها ليبدأ الاشتراك.');
        }

        $validator = Validator::make($request->all(), [
            'newsletter_email' => ['required', 'email', 'max:120'],
            'newsletter_area' => ['nullable', 'integer', 'exists:areas,id'],
        ], [], ['newsletter_email' => 'البريد الإلكتروني', 'newsletter_area' => 'المحافظة']);

        if ($validator->fails()) {
            return $this->backToForm()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();
        $email = Str::lower(trim($data['newsletter_email']));

        $subscriber = NewsletterSubscriber::firstOrNew(['email' => $email]);
        $subscriber->area_id = $data['newsletter_area'] ?? null;
        $subscriber->token ??= NewsletterSubscriber::newToken();

        if ($subscriber->exists && $subscriber->isActive()) {
            $subscriber->save();

            return $this->backToForm()->with('newsletter_status', 'أنتم مشتركون أصلاً، وحفظنا اختياركم للمحافظة.');
        }

        // عائد بعد إلغاء: بريده مؤكَّد من قبل فلا نطلب تأكيداً ثانياً
        if ($subscriber->exists && $subscriber->isConfirmed()) {
            $subscriber->unsubscribed_at = null;
            $subscriber->save();

            return $this->backToForm()->with('newsletter_status', 'أهلاً بعودتكم — عاد اشتراككم فعّالاً.');
        }

        $subscriber->save();

        rescue(fn () => Mail::to($subscriber->email)->queue(new NewsletterConfirmMail($subscriber)), report: true);

        return $this->backToForm()->with('newsletter_status', 'أرسلنا رسالة تأكيد إلى بريدكم — اضغطوا رابطها ليبدأ الاشتراك.');
    }

    public function confirm(NewsletterSubscriber $subscriber, string $token): View
    {
        abort_unless($subscriber->tokenMatches($token), 404);

        $subscriber->confirm();

        return view('pages.newsletter-status', [
            'ok' => true,
            'title' => 'تمّ تأكيد اشتراككم',
            'message' => 'ستصلكم نشرة بَهْجَة كل أسبوع بفعاليات الأيام القادمة'
                .($subscriber->area ? ' في '.$subscriber->area->name : ' في كل غزة').'.',
        ]);
    }

    public function unsubscribe(NewsletterSubscriber $subscriber, string $token): View
    {
        abort_unless($subscriber->tokenMatches($token), 404);

        $subscriber->unsubscribe();

        return view('pages.newsletter-status', [
            'ok' => false,
            'title' => 'أُلغي اشتراككم',
            'message' => 'لن تصلكم النشرة بعد الآن. وإن اشتقتم إليها فالاشتراك من جديد بضغطة واحدة، بلا تأكيد.',
        ]);
    }

    /** العودة إلى الصفحة نفسها عند نموذج النشرة، لا إلى أعلاها */
    private function backToForm(): RedirectResponse
    {
        $previous = url()->previous();

        // الرئيسية تعود بلا مسار («https://host») فيلزمها الشرطة قبل المقطع
        if (blank(parse_url($previous, PHP_URL_PATH))) {
            $previous .= '/';
        }

        return redirect()->to($previous.'#newsletter');
    }
}
