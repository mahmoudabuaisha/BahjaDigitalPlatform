<?php

namespace App\Console\Commands;

use GdImage;
use Illuminate\Console\Command;

/**
 * يولّد طقم أيقونات التطبيق من ملف الشعار نفسه، فتبقى الأيقونات
 * مشتقّة من مصدر واحد لا ملفات غامضة لا يُعرف كيف صُنعت.
 *
 * التصميم المعتمد: الشعار كاملاً كما هو، لا يُقصّ منه شيء. حرف الباء هو
 * نفسه اليد التي تمسك البالونات، فأي قصٍّ لإبعادها يقطع الحرف معها.
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

    /**
     * نسبة عرض الرسم داخل المربّع العادي. تُترك هوامش معتبرة لأن الأنظمة
     * ترسم الأيقونة داخل شكل مستدير الأطراف، فالرسم الملاصق للحافة يبدو
     * مقصوصاً حتى لو لم يُقصّ فعلاً.
     */
    private const FILL = 0.86;

    /** iOS يقصّ بشكل أقرب إلى الدائرة من أندرويد، فالهامش عنده أوسع */
    private const APPLE_FILL = 0.80;

    /**
     * أيقونة نتائج البحث تُعرض داخل دائرة صغيرة جداً، فتُملأ أكثر من
     * أيقونة التطبيق كي يبقى للشعار حجمٌ يُرى.
     */
    private const FAVICON_FILL = 0.92;

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

        $art = $this->trim($logo);

        $written = [
            'icons/icon-512.png' => $this->compose($art, 512, self::FILL, true),
            'icons/icon-192.png' => $this->compose($art, 192, self::FILL, true),
            'icons/icon-512-maskable.png' => $this->compose($art, 512, self::MASKABLE_FILL, true),
            'icons/icon-192-maskable.png' => $this->compose($art, 192, self::MASKABLE_FILL, true),
            // سفاري يرسم الشفافية سوداء: أيقونة iOS معتمة إجباراً
            'icons/apple-touch-icon.png' => $this->compose($art, 180, self::APPLE_FILL, true),
            // شارة شريط الحالة: أندرويد يقرأ قناة الشفافية فقط ويرسمها
            // لوناً واحداً — فالشعار الملوّن يظهر بقعةً، والظلّ الأبيض يظهر شكلاً
            'icons/badge-96.png' => $this->silhouette($art, 96),
            // أيقونة نتائج البحث: جوجل يفضّل مربّعاً من مضاعفات 48
            'icons/favicon-48.png' => $this->compose($art, 48, self::FAVICON_FILL, true),
            'icons/favicon-96.png' => $this->compose($art, 96, self::FAVICON_FILL, true),
            'icons/favicon-192.png' => $this->compose($art, 192, self::FAVICON_FILL, true),
        ];

        foreach ($written as $file => $image) {
            imagepng($image, public_path($file), 9);
        }

        // favicon.ico: المتصفّحات وزاحف جوجل يطلبونه من جذر الموقع بحكم
        // العُرف حتى مع وجود وسم <link>. ملفٌ فارغ هناك يعني أيقونة عامّة.
        $this->writeIco(public_path('favicon.ico'), $art, [16, 32, 48]);

        $this->info('Generated '.(count($written) + 1).' icons from public/brand/logo.png');

        foreach (array_keys($written) as $file) {
            $this->line('  public/'.$file);
        }

        $this->line('  public/favicon.ico  (16 + 32 + 48)');

        return self::SUCCESS;
    }

    /**
     * يكتب ملف ICO يحوي عدّة مقاسات مرمَّزة PNG.
     *
     * GD لا تكتب ICO، والصيغة بسيطة: ترويسة ثم فهرس بمدخل لكل مقاس ثم
     * الصور. المتصفّحات الحديثة وزاحف جوجل تقبل PNG داخل ICO.
     *
     * @param  array<int, int>  $sizes
     */
    private function writeIco(string $path, GdImage $art, array $sizes): void
    {
        $images = [];

        foreach ($sizes as $size) {
            ob_start();
            imagepng($this->compose($art, $size, self::FAVICON_FILL, true), null, 9);
            $images[$size] = (string) ob_get_clean();
        }

        // ICONDIR: محجوز(2) + النوع(2)=1 للأيقونة + العدد(2)
        $ico = pack('vvv', 0, 1, count($images));

        // الصور تبدأ بعد الترويسة والفهرس
        $offset = 6 + 16 * count($images);

        foreach ($images as $size => $data) {
            // العرض والارتفاع بايت واحد لكلٍّ (0 تعني 256)
            $ico .= pack('CCCCvvVV', $size, $size, 0, 0, 1, 32, strlen($data), $offset);
            $offset += strlen($data);
        }

        file_put_contents($path, $ico.implode('', $images));
    }

    /**
     * يقصّ الصورة على حدود الرسم المرئي فعلاً.
     *
     * imagecropauto لا تُعوَّل عليها هنا: أي بكسل شبه شفاف يبقيه فتبقى
     * هوامش غير متساوية، فيتمركز الملفُّ لا الرسمُ وتنزاح الكلمة جانباً.
     */
    private function trim(GdImage $image): GdImage
    {
        $w = imagesx($image);
        $h = imagesy($image);
        $left = $w;
        $right = -1;
        $top = $h;
        $bottom = -1;

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                // 127 = شفاف تماماً؛ نتجاهل ما يكاد يكون شفافاً أيضاً
                if (((imagecolorat($image, $x, $y) >> 24) & 0x7F) > 120) {
                    continue;
                }

                $left = min($left, $x);
                $right = max($right, $x);
                $top = min($top, $y);
                $bottom = max($bottom, $y);
            }
        }

        if ($right < 0) {
            return $image;
        }

        $cropped = imagecreatetruecolor($right - $left + 1, $bottom - $top + 1);
        imagealphablending($cropped, false);
        imagesavealpha($cropped, true);
        imagefill($cropped, 0, 0, imagecolorallocatealpha($cropped, 0, 0, 0, 127));
        imagealphablending($cropped, true);

        imagecopy($cropped, $image, 0, 0, $left, $top, $right - $left + 1, $bottom - $top + 1);
        imagealphablending($cropped, false);

        return $cropped;
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
