<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /** تبويبات الصفحة — المفتاح يصل في الرابط ?tab= */
    private const TABS = [
        'all' => 'الكل',
        'unread' => 'غير مقروءة',
        'events' => 'الفعاليات',
        'registrations' => 'التسجيلات',
        'messages' => 'الرسائل',
        'system' => 'النظام',
    ];

    public function index(Request $request): View
    {
        $user = Auth::user();
        $tab = array_key_exists((string) $request->query('tab'), self::TABS)
            ? (string) $request->query('tab')
            : 'all';

        $notifications = $user->notifications()
            ->when($tab === 'unread', fn ($query) => $query->unread())
            ->when(in_array($tab, ['events', 'registrations', 'messages', 'system'], true),
                fn ($query) => $query->ofGroup($tab))
            ->paginate(15)
            ->withQueryString();

        // عدّادات التبويبات في استعلام واحد بدل ستة
        $counts = $user->notifications()
            ->selectRaw('type, COUNT(*) as total, SUM(read_at IS NULL) as unread')
            ->groupBy('type')
            ->get();

        $byGroup = ['all' => (int) $counts->sum('total'), 'unread' => (int) $counts->sum('unread')];

        foreach (['events', 'registrations', 'messages', 'system'] as $group) {
            $byGroup[$group] = (int) $counts
                ->filter(fn ($row) => (new UserNotification(['type' => $row->type]))->group() === $group)
                ->sum('total');
        }

        return view('pages.account.notifications', [
            'notifications' => $notifications,
            'tabs' => self::TABS,
            'activeTab' => $tab,
            'counts' => $byGroup,
        ]);
    }

    public function destroyAll(): RedirectResponse
    {
        Auth::user()->notifications()->delete();

        return back()->with('notifications_cleared', true);
    }

    public function markAllRead(): RedirectResponse
    {
        Auth::user()->notifications()->whereNull('read_at')->update(['read_at' => now()]);

        return back()->with('notifications_read', true);
    }
}
