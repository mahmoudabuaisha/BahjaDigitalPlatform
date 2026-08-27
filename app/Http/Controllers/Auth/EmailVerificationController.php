<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * تأكيد البريد الإلكتروني — لافتة ودّية في صفحات الحساب بدل بوابة إجبارية:
 * وصول البريد في غزة غير مضمون فلا نقفل الحجز على التحقق.
 */
class EmailVerificationController extends Controller
{
    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        $request->fulfill();

        return redirect()->route('account')->with('status', 'تأكّد بريدكم الإلكتروني — شكراً لكم!');
    }

    public function resend(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return back();
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'أُعيد إرسال رسالة التأكيد — تفقّدوا بريدكم (والبريد غير المرغوب).');
    }
}
