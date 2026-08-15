<?php

namespace App\Support;

use App\Models\Category;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\ComponentAttributeBag;

/**
 * رسمة الفئة كعنوان data: — لتظهر الرسمة نفسها في جداول Filament
 * التي تنتظر رابط صورة لا مكوّن Blade.
 */
class Scene
{
    public static function dataUri(?Category $category): string
    {
        $slug = $category?->slug ?? 'default';
        $tone = $category?->toneHex() ?? '#7c5cff';

        return Cache::rememberForever('scene:'.$slug.':'.$tone, function () use ($slug, $tone): string {
            $svg = view('components.ui.scene', [
                'name' => $slug,
                'tone' => $tone,
                'attributes' => new ComponentAttributeBag,
            ])->render();

            return 'data:image/svg+xml;base64,'.base64_encode(trim($svg));
        });
    }
}
