<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** لوحة المنظِّم لمسؤولي الفرق النشطة وحدهم */
class EnsureTeamManager
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless(
            $user?->role === UserRole::TeamManager && $user->team?->is_active,
            403,
            'هذه الصفحة لفرق المنصّة المعتمدة.',
        );

        return $next($request);
    }
}
