<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // نماذج التقييم العامة: 5 إرسالات بالساعة لكل عنوان IP
        RateLimiter::for('feedback', function (Request $request) {
            return Limit::perHour(5)->by($request->ip());
        });
    }
}
