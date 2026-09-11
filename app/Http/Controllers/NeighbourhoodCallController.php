<?php

namespace App\Http\Controllers;

use App\Models\NeighbourhoodCall;
use App\Services\NeighbourhoodDemandService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * نداء الحيّ: عائلة لم تجد فعالية قريبة ترفع يدها، فيعرف الفرق
 * أين ينتظر الأطفال. مرساة المكان تؤخذ من حساب العائلة نفسه — لا
 * يُطلب منها موقع، ولا يُسجَّل شيء أدقّ ممّا سجّلته من قبل.
 */
class NeighbourhoodCallController extends Controller
{
    /** فئات عمرية بلغة الأهل — نفس تقسيم نموذج الفعاليات */
    public const AGE_BANDS = OrganizerEventController::AGE_RANGES;

    public function __construct(private readonly NeighbourhoodDemandService $demand) {}

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user->isFamily(), 403);

        if (! $user->hasLocationAnchor()) {
            return redirect()->route('account.profile')
                ->with('profile_notice', 'حدّدوا مكانكم أولاً كي يصل نداؤكم إلى الفرق القريبة منكم.');
        }

        // نداء واحد قائم يكفي: تكراره لا يزيد الطلب، ويشوّش القراءة
        if ($this->demand->standingCallOf($user) !== null) {
            return back()->with('call_sent', 'نداؤكم قائم بالفعل — سنُعلمكم فور وصول فعالية قريبة منكم.');
        }

        $recent = NeighbourhoodCall::query()
            ->where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(NeighbourhoodCall::COOLDOWN_DAYS))
            ->exists();

        if ($recent) {
            return back()->with('call_sent', 'وصلنا نداؤكم قبل أيام قليلة، وما زال أمام الفرق. جرّبوا بعد فترة إن لم يصلكم جواب.');
        }

        $data = $request->validate([
            'age_band' => ['nullable', Rule::in(array_keys(self::AGE_BANDS))],
            'children_count' => ['nullable', 'integer', 'min:1', 'max:12'],
            'note' => ['nullable', 'string', 'max:200'],
        ], [], [
            'age_band' => 'الفئة العمرية',
            'children_count' => 'عدد الأطفال',
            'note' => 'ملاحظة',
        ]);

        NeighbourhoodCall::create([
            'user_id' => $user->id,
            'area_id' => $user->area_id,
            'shelter_center_id' => $user->shelter_center_id,
            'age_band' => $data['age_band'] ?? null,
            'children_count' => $data['children_count'] ?? 1,
            'note' => $data['note'] ?? null,
        ]);

        return back()->with('call_sent', 'وصل نداؤكم، وسنُعلمكم فور إعلان فعالية قريبة منكم.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $call = $this->demand->standingCallOf($request->user());

        $call?->delete();

        return back()->with('call_sent', 'سحبنا نداءكم.');
    }
}
