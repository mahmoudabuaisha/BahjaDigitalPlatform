<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/** كيف يُعامَل حجز وليّ الأمر لحظة وصوله */
enum RegistrationMode: string implements HasLabel
{
    /** يُقبل فوراً ويُحجز المقعد */
    case Direct = 'direct';

    /** ينتظر ردّ الفريق المنظِّم */
    case Approval = 'approval';

    public function getLabel(): string
    {
        return match ($this) {
            self::Direct => __('تسجيل مباشر'),
            self::Approval => __('بموافقة الفريق'),
        };
    }

    public function hint(): string
    {
        return match ($this) {
            self::Direct => __('يُحجز المقعد فور تسجيل وليّ الأمر.'),
            self::Approval => __('يصلكم الطلب لتقبلوه أو ترفضوه.'),
        };
    }

    public function initialStatus(): RegistrationStatus
    {
        return $this === self::Direct
            ? RegistrationStatus::Accepted
            : RegistrationStatus::Pending;
    }
}
