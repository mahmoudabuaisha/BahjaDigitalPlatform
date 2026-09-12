<?php

namespace App\Console\Commands;

use GdImage;
use Illuminate\Console\Command;

/**
 * يولّد طقم أيقونات التطبيق من ملف الشعار نفسه، فتبقى الأيقونات
 * مشتقّة من مصدر واحد لا ملفات غامضة لا يُعرف كيف صُنعت.
 *
 * التصميم المعتمد: الشعار كاملاً (الكلمة والوجه) بلا البالونات — حذفها
 * يقرّب الشعار من المربّع فتكبر الكلمة وتبقى مقروءة على الشاشة.
 *
 * التشغيل: php artisan brand:icons
 */
class GenerateBrandIcons extends Command
{
    protected $signature = 'brand:icons';

    protected $description = 'توليد أيقونات التطبيق من ملف الشعار';

    /** تدرّج الهوية: من الأزرق الفاتح أعلى إلى الغامق أسفل */
    private const BRAND_TOP = [59, 147, 228];

    private const BRAND_BOTTOM = [31, 98, 167];

    /** نسبة عرض الرسم داخل المربّع العادي */
    private const FILL = 0.94;

    /**
     * النسخة القابلة للقصّ: أندرويد قد يقصّ الأيقونة دائرةً قطرها 80%
     * من الضلع، فالرسم كلّه يجب أن يقع داخل تلك الدائرة.
     */
    private const MASKABLE_FILL = 0.72;

    public function handle(): int
    {
        $path = public_path('brand/logo.png');

        if (! is_file($path)) {
            $this->warn('لا يوجد ملف شعار في public/brand/logo.png.');

            return self::FAILURE;
        }

        $logo = @imagecreatefromstring((string) file_get_contents($path));

        if ($logo === false) {
            $this->error('تعذّرت قراءة ملف الشعار.');

            return self::FAILURE;
        }

        imagepalettetotruecolor($logo);
        imagesavealpha($logo, true);

        $art = $this->trim($this->withoutBalloons($logo));

        $written = [
            'icons/icon-512.png' => $this->compose($art, 512, self::FILL, true),
            'icons/icon-192.png' => $this->compose($art, 192, self::FILL, true),
            'icons/icon-512-maskable.png' => $this->compose($art, 512, self::MASKABLE_FILL, true),
            'icons/icon-192-maskable.png' => $this->compose($art, 192, self::MASKABLE_FILL, true),
            // سفاري يرسم الشفافية سوداء: أيقونة iOS معتمة إجباراً
            'icons/apple-touch-icon.png' => $this->compose($art, 180, self::FILL, true),
            // شارة شريط الحالة: أندرويد يقرأ قناة الشفافية فقط ويرسمها
            // لوناً واحداً — فالشعار الملوّن يظهر بقعةً، والظلّ الأبيض يظهر شكلاً
            'icons/badge-96.png' => $this->silhouette($art, 96),
        ];

        foreach ($written as $file => $image) {
            imagepng($image, public_path($file), 9);
        }

        $this->info('Generated '.count($written).' app icons from public/brand/logo.png');

        foreach (array_keys($written) as $file) {
            $this->line('  public/'.$file);
        }

        return self::SUCCESS;
    }

    /**
     * البالونات تُمدّ الشعار عرضاً فتصغر الكلمة داخل المربّع.
     * تُقصّ من اليسار بنسبة ثابتة من عرض الشعار.
     */
    private function withoutBalloons(GdImage $logo): GdImage
    {
        $width = (int) round(imagesx($logo) * 0.78);
        $height = imagesy($logo);

        $cropped = imagecreatetruecolor($width, $height);
        imagealphablending($cropped, false);
        imagesavealpha($cropped, true);
        imagefill($cropped, 0, 0, imagecolorallocatealpha($cropped, 0, 0, 0, 127));
        imagealphablending($cropped, true);

        imagecopy($cropped, $logo, 0, 0, 0, 0, $width, $height);
        imagealphablending($cropped, false);

        return $cropped;
    }

    /** يزيل الهوامش الشفافة كي يملأ الرسم المربّع فعلاً */
    private function trim(GdImage $image): GdImage
    {
        $box = imagecropauto($image, IMG_CROP_TRANSPARENT);

        return $box === false ? $image : $box;
    }

    /** ظلّ أبيض على شفاف: كل بكسل غير شفاف يصير أبيض بدرجة شفافيته */
    private function silhouette(GdImage $art, int $size): GdImage
    {
        $canvas = imagecreatetruecolor($size, $size);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));

        $w = imagesx($art);
        $h = imagesy($art);
        $scale = min($size * 0.9 / $w, $size * 0.9 / $h);
        $newW = (int) round($w * $scale);
        $newH = (int) round($h * $scale);

        $scaled = imagecreatetruecolor($newW, $newH);
        imagealphablending($scaled, false);
        imagesavealpha($scaled, true);
        imagefill($scaled, 0, 0, imagecolorallocatealpha($scaled, 0, 0, 0, 127));
        imagealphablending($scaled, true);
        imagecopyresampled($scaled, $art, 0, 0, 0, 0, $newW, $newH, $w, $h);

        $offsetX = (int) round(($size - $newW) / 2);
        $offsetY = (int) round(($size - $newH) / 2);

        for ($y = 0; $y < $newH; $y++) {
            for ($x = 0; $x < $newW; $x++) {
                $alpha = (imagecolorat($scaled, $x, $y) >> 24) & 0x7F;

                if ($alpha >= 127) {
                    continue;
                }

                imagesetpixel(
                    $canvas,
                    $offsetX + $x,
                    $offsetY + $y,
                    imagecolorallocatealpha($canvas, 255, 255, 255, $alpha),
                );
            }
        }

        return $canvas;
    }

    private function compose(GdImage $art, int $size, float $fill, bool $opaque): GdImage
    {
        $canvas = imagecreatetruecolor($size, $size);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, ! $opaque);

        // تدرّج رأسي مرسوم سطراً سطراً
        for ($y = 0; $y < $size; $y++) {
            $t = $size > 1 ? $y / ($size - 1) : 0;

            $color = imagecolorallocate(
                $canvas,
                (int) round(self::BRAND_TOP[0] + (self::BRAND_BOTTOM[0] - self::BRAND_TOP[0]) * $t),
                (int) round(self::BRAND_TOP[1] + (self::BRAND_BOTTOM[1] - self::BRAND_TOP[1]) * $t),
                (int) round(self::BRAND_TOP[2] + (self::BRAND_BOTTOM[2] - self::BRAND_TOP[2]) * $t),
            );

            imageline($canvas, 0, $y, $size - 1, $y, $color);
        }

        imagealphablending($canvas, true);

        $w = imagesx($art);
        $h = imagesy($art);
        $scale = min($size * $fill / $w, $size * $fill / $h);
        $newW = (int) round($w * $scale);
        $newH = (int) round($h * $scale);

        imagecopyresampled(
            $canvas, $art,
            (int) round(($size - $newW) / 2), (int) round(($size - $newH) / 2), 0, 0,
            $newW, $newH, $w, $h,
        );

        imagealphablending($canvas, false);
        imagesavealpha($canvas, ! $opaque);

        return $canvas;
    }
}
