<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ['name', 'slug', 'icon', 'color', 'sort_order'];

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
}
