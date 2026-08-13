<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum FeedbackSource: string implements HasColor, HasLabel
{
    case Family = 'family';
    case Team = 'team';

    public function getLabel(): string
    {
        return match ($this) {
            self::Family => __('عائلة'),
            self::Team => __('فريق'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Family => 'info',
            self::Team => 'success',
        };
    }
}
