<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // خلف Cloudflare (أو أي بروكسي): بدون هذا يرى لارافل عنوان البروكسي
        // وحده — فيصبح حدّ التقييم «5 بالساعة لكل IP» حدّاً واحداً لكل الزوار
        // مجتمعين، وتخرج روابط route() بـ http بدل https في وسوم OG وكروت QR
        $middleware->trustProxies(at: '*');

        // عدّاد المشاهدات يصل عبر sendBeacon بلا رمز CSRF
        $middleware->validateCsrfTokens(except: [
            't/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
