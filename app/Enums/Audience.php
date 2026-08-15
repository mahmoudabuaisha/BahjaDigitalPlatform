<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/** الفئة المستهدفة من الفعالية */
enum Audience: string implements HasLabel
{
    case All = 'all';
    case Boys = 'boys';
    case Girls = 'girls';

    public function getLabel(): string
    {
        return match ($this) {
            self::All => __('الجميع'),
            self::Boys => __('بنين'),
            self::Girls => __('بنات'),
        };
    }

    public function iconKey(): string
    {
        return $this === self::All ? 'users' : 'user';
    }
}
