<?php

namespace App\Filament;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * صورة رمزية من أوّل حرفَين في الاسم، تُرسم محلياً كـ SVG.
 * البديل الافتراضي في Filament يطلبها من ui-avatars.com —
 * ولا نريد للوحة أن تعتمد على خدمة خارجية ولا أن تسرّب أسماء المستخدمين.
 */
class InitialsAvatarProvider implements AvatarProvider
{
    public function get(Model|Authenticatable $record): string
    {
        $initials = str(Filament::getNameForDefaultAvatar($record))
            ->trim()
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $segment): string => mb_substr($segment, 0, 1))
            ->join('');

        $svg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">
                <rect width="64" height="64" rx="32" fill="#3b93e4"/>
                <text x="32" y="41" text-anchor="middle" fill="#fff"
                      font-family="Tajawal, system-ui, sans-serif" font-size="26" font-weight="700">{$initials}</text>
            </svg>
            SVG;

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
