<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** دورة حياة طلب انضمام فريق تطوعي (القسم 6.1 من الخطة) */
enum TeamApplicationStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Suspended = 'suspended';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => __('قيد المراجعة'),
            self::Approved => __('معتمد'),
            self::Rejected => __('مرفوض'),
            self::Suspended => __('معلّق'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
            self::Suspended => 'gray',
        };
    }
}
