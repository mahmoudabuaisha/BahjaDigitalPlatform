<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * يولّد أيقونة التبويب وأيقونات التطبيق (PWA) من ملف الشعار نفسه —
 * فتصبح أيقونة المتصفح هي الشعار حرفياً بألوانه الحالية.
 * التشغيل: php artisan brand:icons
 */
class GenerateBrandIcons extends Command
{
    protected $signature = 'brand:icons';

    protected $description = 'توليد أيقونات التبويب والتطبيق من ملف الشعار';

    public function handle(): int
    {
        $path = public_path('brand/logo.png');

        if (! is_file($path)) {
            $this->warn('لا يوجد ملف شعار في public/brand/logo.png.');

            return self::FAILURE;
        }

        $logo = @imagecreatefromstring((string) file_get_contents($path));

        if ($logo === false) {
            $this->error('تعذّرت قراءة الملف.');

            return self::FAILURE;
        }

        imagepalettetotruecolor($logo);
        imagesavealpha($logo, true);

        // أيقونتا التبويب والتطبيق: الشعار كاملاً داخل مربع شفاف
        imagepng($this->squareFit($logo, 512, null, 0.96), public_path('icons/icon-512.png'), 9);
        imagepng($this->squareFit($logo, 192, null, 0.96), public_path('icons/icon-192.png'), 9);

        // النسخة القابلة للقص (أندرويد): خلفية زرقاء وهامش أمان حول الشعار
        imagepng($this->squareFit($logo, 512, [59, 147, 228], 0.66), public_path('icons/icon-512-maskable.png'), 9);

        $this->info('وُلِّدت الأيقونات الثلاث من الشعار: icon-192 وicon-512 وicon-512-maskable.');

        return self::SUCCESS;
    }

    /** يضع الشعار داخل مربع بمقاس محدد — شفاف أو بخلفية ملوّنة */
    private function squareFit(\GdImage $logo, int $size, ?array $background, float $scale): \GdImage
    {
        $canvas = imagecreatetruecolor($size, $size);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);

        imagefill($canvas, 0, 0, $background === null
            ? imagecolorallocatealpha($canvas, 0, 0, 0, 127)
            : imagecolorallocate($canvas, $background[0], $background[1], $background[2]));

        imagealphablending($canvas, true);

        $w = imagesx($logo);
        $h = imagesy($logo);
        $ratio = min($size * $scale / $w, $size * $scale / $h);
        $newW = (int) round($w * $ratio);
        $newH = (int) round($h * $ratio);

        imagecopyresampled(
            $canvas, $logo,
            (int) (($size - $newW) / 2), (int) (($size - $newH) / 2), 0, 0,
            $newW, $newH, $w, $h,
        );

        imagealphablending($canvas, false);

        return $canvas;
    }
}
