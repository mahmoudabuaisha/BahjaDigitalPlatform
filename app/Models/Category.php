<?php

namespace App\Models;

use App\Services\ImageService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Category extends Model
{
    protected $fillable = ['name', 'slug', 'icon', 'scene', 'image_path', 'color', 'sort_order'];

    // المعالجة نفسها (تصغير + مسح EXIF + نسخة بطاقة) تجري لحظة الرفع
    // في نموذج اللوحة عبر ImageService::store — هنا تنظيف الملفات فقط
    protected static function booted(): void
    {
        // استبدال الصورة يحذف ملفات القديمة من القرص
        static::updating(function (Category $category): void {
            if ($category->isDirty('image_path') && ($old = $category->getOriginal('image_path'))) {
                app(ImageService::class)->delete($old);
            }
        });

        static::deleted(function (Category $category): void {
            app(ImageService::class)->delete($category->image_path);
        });
    }

    /** رابط صورة البطاقة المخصصة إن رُفعت — النسخة الخفيفة أولاً ثم الأصل */
    public function imageCardUrl(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        foreach (['webp', 'jpg'] as $extension) {
            $card = ImageService::cardPath($this->image_path, $extension);

            if (Storage::disk('public')->exists($card)) {
                return Storage::disk('public')->url($card);
            }
        }

        return Storage::disk('public')->exists($this->image_path)
            ? Storage::disk('public')->url($this->image_path)
            : null;
    }

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
