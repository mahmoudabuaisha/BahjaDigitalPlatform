<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** حالة نسخة التعديل على فعالية منشورة */
enum RevisionStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => __('بانتظار الاعتماد'),
            self::Approved => __('اعتُمدت'),
            self::Rejected => __('رُفضت'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
        };
    }
}
