<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasColor, HasLabel
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case TeamManager = 'team_manager';

    public function getLabel(): string
    {
        return match ($this) {
            self::SuperAdmin => __('مدير عام'),
            self::Admin => __('مشرف'),
            self::TeamManager => __('مسؤول فريق'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::SuperAdmin => 'danger',
            self::Admin => 'warning',
            self::TeamManager => 'info',
        };
    }

    public function isAdministrative(): bool
    {
        return $this !== self::TeamManager;
    }
}
