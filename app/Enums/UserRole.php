<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasColor, HasLabel
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case TeamManager = 'team_manager';
    case Family = 'family';

    public function getLabel(): string
    {
        return match ($this) {
            self::SuperAdmin => __('مدير عام'),
            self::Admin => __('مشرف'),
            self::TeamManager => __('مسؤول فريق'),
            self::Family => __('وليّ أمر'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::SuperAdmin => 'danger',
            self::Admin => 'warning',
            self::TeamManager => 'info',
            self::Family => 'gray',
        };
    }

    public function isAdministrative(): bool
    {
        // تعداد صريح: أي دور جديد (مثل وليّ الأمر) يجب ألا يرث صلاحية اللوحة
        return in_array($this, [self::SuperAdmin, self::Admin], true);
    }
}
