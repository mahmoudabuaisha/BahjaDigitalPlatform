<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/** مستوى ظهور المكان للجمهور — حماية ميدانية (القسم 15.6) */
enum LocationVisibility: string implements HasLabel
{
    /** الاسم والعنوان التفصيلي */
    case PublicExact = 'public_exact';

    /** الاسم دون العنوان التفصيلي */
    case PublicGeneral = 'public_general';

    /** تُعرض المحافظة فقط */
    case Hidden = 'hidden';

    public function getLabel(): string
    {
        return match ($this) {
            self::PublicExact => __('ظاهر بالتفصيل'),
            self::PublicGeneral => __('الاسم فقط دون العنوان'),
            self::Hidden => __('مخفي — تظهر المحافظة فقط'),
        };
    }

    public function showsName(): bool
    {
        return $this !== self::Hidden;
    }

    public function showsAddress(): bool
    {
        return $this === self::PublicExact;
    }
}
