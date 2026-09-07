<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * يغمّق الدرجات الوردية في شعار المنصّة (public/brand/logo.png) دون
 * المساس بالأزرق أو بقية الألوان — درجة التغميق قابلة للضبط.
 * التشغيل: php artisan brand:darken-pink 25
 * تنبيه: التكرار يراكم التغميق — شغّلوه مرة واحدة بعد كل استبدال للشعار.
 */
class DarkenLogoPink extends Command
{
    protected $signature = 'brand:darken-pink {amount=25 : نسبة التغميق المئوية (10-60)}';

    protected $description = 'تغميق اللون الوردي في ملف الشعار';

    public function handle(): int
    {
        $amount = min(60, max(10, (int) $this->argument('amount'))) / 100;
        $path = public_path('brand/logo.png');

        if (! is_file($path)) {
            $this->warn('لا يوجد ملف شعار في public/brand/logo.png.');

            return self::FAILURE;
        }

        $image = @imagecreatefromstring((string) file_get_contents($path));

        if ($image === false) {
            $this->error('تعذّرت قراءة الملف.');

            return self::FAILURE;
        }

        imagepalettetotruecolor($image);
        imagealphablending($image, false);
        imagesavealpha($image, true);

        $width = imagesx($image);
        $height = imagesy($image);
        $changed = 0;

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $c = imagecolorat($image, $x, $y);
                $alpha = ($c >> 24) & 0x7F;

                if ($alpha >= 127) {
                    continue;
                }

                [$h, $s, $l] = $this->rgbToHsl(($c >> 16) & 255, ($c >> 8) & 255, $c & 255);

                // النطاق الوردي/الفوشيا فقط: صبغة 295°-355° بإشباع وإضاءة كافيين
                if ($h < 295 || $h > 355 || $s < 0.15 || $l < 0.3) {
                    continue;
                }

                $l = max(0.28, $l * (1 - $amount));
                $s = min(1.0, $s * 1.12);

                [$r, $g, $b] = $this->hslToRgb($h, $s, $l);
                imagesetpixel($image, $x, $y, imagecolorallocatealpha($image, $r, $g, $b, $alpha));
                $changed++;
            }
        }

        imagepng($image, $path, 9);

        $this->info(sprintf('غُمِّق الوردي بنسبة %d%% — عُدِّل %s بكسل (%.1f%% من الصورة).',
            (int) ($amount * 100), number_format($changed), $changed * 100 / ($width * $height)));
        $this->line('شغّلوا بعده: php artisan brand:icons لتحديث أيقونة التبويب بنفس الألوان.');

        return self::SUCCESS;
    }

    /** @return array{0: float, 1: float, 2: float} الصبغة بالدرجات، الإشباع والإضاءة 0-1 */
    private function rgbToHsl(int $r, int $g, int $b): array
    {
        $r /= 255;
        $g /= 255;
        $b /= 255;
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;

        if ($max === $min) {
            return [0.0, 0.0, $l];
        }

        $d = $max - $min;
        $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);

        $h = match ($max) {
            $r => fmod(($g - $b) / $d + 6, 6),
            $g => ($b - $r) / $d + 2,
            default => ($r - $g) / $d + 4,
        } * 60;

        return [$h, $s, $l];
    }

    /** @return array{0: int, 1: int, 2: int} */
    private function hslToRgb(float $h, float $s, float $l): array
    {
        $c = (1 - abs(2 * $l - 1)) * $s;
        $x = $c * (1 - abs(fmod($h / 60, 2) - 1));
        $m = $l - $c / 2;

        [$r, $g, $b] = match (true) {
            $h < 60 => [$c, $x, 0],
            $h < 120 => [$x, $c, 0],
            $h < 180 => [0, $c, $x],
            $h < 240 => [0, $x, $c],
            $h < 300 => [$x, 0, $c],
            default => [$c, 0, $x],
        };

        return [(int) round(($r + $m) * 255), (int) round(($g + $m) * 255), (int) round(($b + $m) * 255)];
    }
}
