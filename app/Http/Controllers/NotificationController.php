<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(): View
    {
        $notifications = Auth::user()->notifications()->paginate(20);

        return view('pages.account.notifications', ['notifications' => $notifications]);
    }

    public function markAllRead(): RedirectResponse
    {
        Auth::user()->notifications()->whereNull('read_at')->update(['read_at' => now()]);

        return back()->with('notifications_read', true);
    }
}
