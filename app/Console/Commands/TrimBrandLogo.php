<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * يشذّب هوامش شعار المنصّة (public/brand/logo.png): يقص الأبيض/الشفاف
 * الزائد حول الرسمة ويحدّ الارتفاع، فيظهر الشعار كبيراً مريحاً في الترويسة.
 * يُشغَّل مرة بعد كل استبدال للشعار: php artisan brand:trim-logo
 */
class TrimBrandLogo extends Command
{
    protected $signature = 'brand:trim-logo {--force : إعادة القص حتى لو بدا مشذَّباً}';

    protected $description = 'قص الهوامش البيضاء من ملف الشعار وضبط أبعاده';

    /** أقصى ارتفاع للملف — ثلاثة أضعاف عرض العرض الفعلي لشاشات الريتنا */
    private const MAX_HEIGHT = 280;

    public function handle(): int
    {
        $path = public_path('brand/logo.png');

        if (! is_file($path)) {
            $this->warn('لا يوجد ملف شعار في public/brand/logo.png — ارفعوه أولاً.');

            return self::FAILURE;
        }

        $image = @imagecreatefrompng($path);

        if ($image === false) {
            $this->error('تعذّرت قراءة الملف — تأكدوا أنه PNG سليم.');

            return self::FAILURE;
        }

        imagepalettetotruecolor($image);
        imagesavealpha($image, true);

        $width = imagesx($image);
        $height = imagesy($image);

        // حدود الرسمة: أول بكسل غير أبيض وغير شفاف من كل جهة
        [$minX, $maxX, $minY, $maxY] = [$width, 0, $height, 0];

        for ($y = 0; $y < $height; $y += 2) {
            for ($x = 0; $x < $width; $x += 2) {
                $c = imagecolorat($image, $x, $y);
                $alpha = ($c >> 24) & 0x7F;

                if ($alpha > 100) {
                    continue;
                }

                $r = ($c >> 16) & 255;
                $g = ($c >> 8) & 255;
                $b = $c & 255;

                if ($r > 246 && $g > 246 && $b > 246) {
                    continue;
                }

                $minX = min($minX, $x);
                $maxX = max($maxX, $x);
                $minY = min($minY, $y);
                $maxY = max($maxY, $y);
            }
        }

        if ($maxX <= $minX || $maxY <= $minY) {
            $this->error('لم أجد رسمة داخل الملف — هل هو أبيض بالكامل؟');

            return self::FAILURE;
        }

        $marginRatio = min($minX, $minY, $width - 1 - $maxX, $height - 1 - $maxY) / max($width, $height);

        if ($marginRatio < 0.03 && $height <= self::MAX_HEIGHT && ! $this->option('force')) {
            $this->info('الشعار مشذَّب أصلاً — لا حاجة لشيء.');

            return self::SUCCESS;
        }

        // قص مع تنفّس بسيط حول الرسمة
        $pad = (int) round(max($maxX - $minX, $maxY - $minY) * 0.05);
        $cropX = max(0, $minX - $pad);
        $cropY = max(0, $minY - $pad);
        $cropW = min($width, $maxX + $pad) - $cropX;
        $cropH = min($height, $maxY + $pad) - $cropY;

        $cropped = imagecrop($image, ['x' => $cropX, 'y' => $cropY, 'width' => $cropW, 'height' => $cropH]);

        if ($cropped === false) {
            $this->error('فشل القص.');

            return self::FAILURE;
        }

        imagesavealpha($cropped, true);

        if (imagesy($cropped) > self::MAX_HEIGHT) {
            $scaled = imagescale($cropped, (int) round(imagesx($cropped) * self::MAX_HEIGHT / imagesy($cropped)), self::MAX_HEIGHT, IMG_BICUBIC);

            if ($scaled !== false) {
                imagesavealpha($scaled, true);
                $cropped = $scaled;
            }
        }

        imagepng($cropped, $path, 9);

        $this->info(sprintf('شُذِّب الشعار: %dx%d ← %dx%d (%.0fKB).',
            $width, $height, imagesx($cropped), imagesy($cropped), filesize($path) / 1024));

        return self::SUCCESS;
    }
}
