<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * وصول مبسّط للإعدادات المخزنة في قاعدة البيانات مع كاش دائم
 * يُبطَل عند كل حفظ — بديل خفيف عن حزم الإعدادات.
 */
class Settings
{
    private const CACHE_KEY = 'app_settings';

    /** القيم الافتراضية عند غياب المفتاح من قاعدة البيانات */
    private const DEFAULTS = [
        'site_name' => 'بَهْجَة',
        'site_whatsapp' => '',
        'about_text' => '',
        'og_default_image' => '',
        'target_children' => 1000,
        'target_indirect' => 3000,
        // محتوى الصفحات يُدار من اللوحة (القسم 12) — فارغ = النص الافتراضي في القالب
        'guide_content' => '',
        'privacy_content' => '',
        'photo_policy_content' => '',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = self::all();

        return $all[$key] ?? $default ?? self::DEFAULTS[$key] ?? null;
    }

    /** @return array<string, mixed> */
    public static function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            $stored = Setting::query()
                ->pluck('value', 'key')
                ->map(fn (?string $value) => $value === null ? null : json_decode($value, true))
                ->all();

            return array_merge(self::DEFAULTS, $stored);
        });
    }

    public static function set(string $key, mixed $value): void
    {
        Setting::updateOrCreate(
            ['key' => $key],
            ['value' => json_encode($value, JSON_UNESCAPED_UNICODE)],
        );

        Cache::forget(self::CACHE_KEY);
    }

    /** @param array<string, mixed> $values */
    public static function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => json_encode($value, JSON_UNESCAPED_UNICODE)],
            );
        }

        Cache::forget(self::CACHE_KEY);
    }
}
