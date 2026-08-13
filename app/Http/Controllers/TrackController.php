<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class TrackController extends Controller
{
    /** عدّاد مشاهدات عبر sendBeacon — بدون تكرار لنفس الجلسة خلال 6 ساعات */
    public function event(Request $request, Event $event): Response
    {
        $key = 'viewed:'.$event->id.':'.sha1($request->ip().$request->userAgent());

        if (Cache::add($key, true, now()->addHours(6))) {
            $event->incrementQuietly('views_count');
        }

        return response()->noContent();
    }
}
