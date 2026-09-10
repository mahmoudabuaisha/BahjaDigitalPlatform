<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ['name', 'slug', 'icon', 'scene', 'color', 'sort_order'];

    /** مفاتيح الرسمات المتوفرة في مكوّن <x-ui.scene> بأسمائها العربية */
    public const SCENES = [
        'games' => 'ألعاب ومكعبات',
        'psychosocial' => 'دعم نفسي',
        'arts-crafts' => 'رسم وأشغال',
        'theatre' => 'مسرح ودمى',
        'music' => 'أناشيد وموسيقى',
        'sports' => 'رياضة وحركة',
        'stories' => 'حكايات وقصص',
        'special' => 'مناسبات خاصة',
        'default' => 'المشهد العام',
    ];

    /**
     * مفتاح رسمة الفئة: الحقل المستقل أولاً، ثم المعرّف إن كان مفتاحاً
     * معروفاً (توافقاً مع البيانات القديمة)، وإلا المشهد العام.
     */
    public function sceneName(): string
    {
        if ($this->scene && array_key_exists($this->scene, self::SCENES)) {
            return $this->scene;
        }

        return array_key_exists((string) $this->slug, self::SCENES) ? $this->slug : 'default';
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * صنف اللون في الواجهة العامة — يُشتق من لون Filament المخزَّن،
     * فيبقى مصدر اللون واحداً بين اللوحة والموقع.
     */
    public function toneClass(): string
    {
        return match ($this->color) {
            'warning' => 'tone tone-amber',
            'danger' => 'tone tone-rose',
            'info' => 'tone tone-sky',
            'success' => 'tone tone-emerald',
            default => 'tone tone-violet',
        };
    }

    /** اسم الأيقونة في مكوّن <x-icon> — مشتق من اسم أيقونة Filament */
    public function iconKey(): string
    {
        $key = str_replace(['heroicon-o-', 'heroicon-s-'], '', (string) $this->icon);

        return $key !== '' ? $key : 'sparkles';
    }

    /** لون الفئة كقيمة سداسية — يُمرَّر إلى الرسمات المرسومة بـ SVG */
    public function toneHex(): string
    {
        return match ($this->color) {
            'warning' => '#f59e0b',
            'danger' => '#f43f5e',
            'info' => '#0ea5e9',
            'success' => '#10b981',
            default => '#3b93e4',
        };
    }
}
