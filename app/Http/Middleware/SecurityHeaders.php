<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** ترويسات الأمان (القسم 15.4) — تُضاف على مستوى التطبيق لتصل مع كل استضافة */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // لوحات Filament تحقن أنماطها وسكربتاتها؛ CSP الصارمة للواجهة العامة فقط
        if (! $request->is('admin*') && ! $request->is('team*') && ! $request->is('livewire*')) {
            $response->headers->set(
                'Content-Security-Policy',
                "default-src 'self'; img-src 'self' data: blob:; style-src 'self' 'unsafe-inline'; "
                ."script-src 'self' 'unsafe-inline' 'unsafe-eval'; connect-src 'self'; font-src 'self'; " // unsafe-eval: تعابير Alpine.js

                ."frame-ancestors 'self'; base-uri 'self'; form-action 'self' https://wa.me https://api.whatsapp.com",
            );
        }

        return $response;
    }
}
