<?php

namespace App\Enums;

/** نوع الجهة المنظِّمة — من نموذج تسجيل الفرق */
enum OrgType: string
{
    case Association = 'association';
    case VolunteerTeam = 'volunteer_team';
    case Initiative = 'initiative';

    public function label(): string
    {
        return match ($this) {
            self::Association => 'جمعية رسمية',
            self::VolunteerTeam => 'فريق تطوعي',
            self::Initiative => 'مبادرة',
        };
    }

    /** @return array<string, string> للقوائم المنسدلة */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type) => [$type->value => $type->label()])
            ->all();
    }
}
