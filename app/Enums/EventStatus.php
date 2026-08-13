<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum EventStatus: string implements HasColor, HasIcon, HasLabel
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Completed = 'completed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => __('مسودة'),
            self::Pending => __('بانتظار الاعتماد'),
            self::Approved => __('معتمدة'),
            self::Rejected => __('مرفوضة'),
            self::Cancelled => __('ملغاة'),
            self::Completed => __('منفَّذة'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Pending => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
            self::Cancelled => 'gray',
            self::Completed => 'info',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Draft => 'heroicon-o-pencil',
            self::Pending => 'heroicon-o-clock',
            self::Approved => 'heroicon-o-check-circle',
            self::Rejected => 'heroicon-o-x-circle',
            self::Cancelled => 'heroicon-o-no-symbol',
            self::Completed => 'heroicon-o-flag',
        };
    }

    /** الحالات الظاهرة للجمهور في الموقع العام */
    public static function publiclyVisible(): array
    {
        return [self::Approved, self::Completed];
    }

    public function isPubliclyVisible(): bool
    {
        return in_array($this, self::publiclyVisible(), true);
    }
}
