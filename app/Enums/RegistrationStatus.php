<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum RegistrationStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => __('قيد المراجعة'),
            self::Accepted => __('مقبول'),
            self::Rejected => __('مرفوض'),
            self::Cancelled => __('ملغى'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Accepted => 'success',
            self::Rejected => 'danger',
            self::Cancelled => 'gray',
        };
    }

    /**
     * الحالات التي تشغل مقعداً فعلياً — الحجز قيد المراجعة يحجز مكانه
     * كي لا يَعِد الفريق بمقاعد أكثر مما يتّسع له المكان.
     *
     * @return array<int, self>
     */
    public static function holdingSeat(): array
    {
        return [self::Pending, self::Accepted];
    }
}
