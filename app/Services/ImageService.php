<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * معالجة الصور المرفوعة (القسم 15.3): كل صورة يُعاد ترميزها من الصفر عبر GD
 * قبل التخزين، فتُمسح بيانات EXIF كاملةً — وأخطرها إحداثيات GPS التي تكشف
 * مواقع التصوير — ويُصحَّح اتجاه اللقطة وتُحدّ الأبعاد.
 *
 * الأصل يُحفظ JPEG لأن معاينات واتساب لا تعرض og:image بصيغة WebP بثبات،
 * ومعه نسخة WebP أخف بعرض 640 لبطاقات القوائم.
 */
class ImageService
{
    /** أقصى ضلع للنسخة الأصل بعد المعالجة */
    public const MAX_EDGE = 1600;

    /** عرض نسخة البطاقات في القوائم */
    public const CARD_WIDTH = 640;

    /** حارس قنابل فك الضغط: أي ضلع فوق هذا الحد يُرفض قبل فك الترميز */
    private const MAX_SOURCE_EDGE = 12000;

    /** وحارس المساحة الكلية (~40 ميغابكسل) */
    private const MAX_SOURCE_PIXELS = 40_000_000;

    private const ALLOWED_TYPES = [
        IMAGETYPE_JPEG,
        IMAGETYPE_PNG,
        IMAGETYPE_WEBP,
        IMAGETYPE_GIF,
        IMAGETYPE_BMP,
    ];

    /**
     * يعالج ملفاً مرفوعاً ويخزّن الأصل ونسخة البطاقة على قرص public.
     *
     * @return string مسار الأصل المخزَّن (لعمود image_path/logo_path)
     */
    public function store(UploadedFile $file, string $directory, string $attribute = 'image'): string
    {
        $encoded = $this->encode((string) file_get_contents($file->getRealPath()), $attribute);

        $path = $directory.'/'.Str::random(40).'.jpg';

        Storage::disk('public')->put($path, $encoded['master']);

        if ($encoded['card'] !== null) {
            Storage::disk('public')->put(self::cardPath($path), $encoded['card']);
        }

        return $path;
    }

    /**
     * يعيد معالجة صورة قديمة موجودة على القرص (رُفعت قبل تفعيل المعالجة)
     * ويحذف الملف القديم. يعيد المسار الجديد، أو null إن تعذّرت المعالجة.
     */
    public function reprocess(string $path): ?string
    {
        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            return null;
        }

        try {
            $encoded = $this->encode((string) $disk->get($path));
        } catch (ValidationException) {
            return null;
        }

        $new = (str_contains($path, '/') ? Str::beforeLast($path, '/').'/' : '').Str::random(40).'.jpg';

        $disk->put($new, $encoded['master']);

        if ($encoded['card'] !== null) {
            $disk->put(self::cardPath($new), $encoded['card']);
        }

        $this->delete($path);

        return $new;
    }

    /** يحذف الأصل ونسخ البطاقة المشتقة منه */
    public function delete(?string $path): void
    {
        if (! $path) {
            return;
        }

        Storage::disk('public')->delete([
            $path,
            self::cardPath($path),
            self::cardPath($path, 'jpg'),
        ]);
    }

    /** مسار نسخة البطاقة المشتق من مسار الأصل: events/x.jpg → events/x-card.webp */
    public static function cardPath(string $masterPath, string $extension = 'webp'): string
    {
        return (str_contains($masterPath, '.') ? Str::beforeLast($masterPath, '.') : $masterPath)
            .'-card.'.$extension;
    }

    /**
     * قلب المعالجة: يفكّ الترميز، يصحّح الاتجاه من EXIF، يحدّ الأبعاد،
     * ثم يبني JPEG جديداً خالياً من أي بيانات وصفية + نسخة بطاقة WebP.
     *
     * @return array{master: string, card: ?string}
     */
    private function encode(string $bytes, string $attribute = 'image'): array
    {
        $info = @getimagesizefromstring($bytes);

        if ($info === false || ! in_array($info[2], self::ALLOWED_TYPES, true)) {
            $this->reject($attribute, 'الملف ليس صورة صالحة (JPG أو PNG أو WebP).');
        }

        [$width, $height] = $info;

        if (max($width, $height) > self::MAX_SOURCE_EDGE || $width * $height > self::MAX_SOURCE_PIXELS) {
            $this->reject($attribute, 'أبعاد الصورة أكبر من المسموح — الرجاء تصغيرها قبل الرفع.');
        }

        $image = @imagecreatefromstring($bytes);

        if ($image === false) {
            $this->reject($attribute, 'تعذّرت قراءة الصورة — جرّبوا حفظها من جديد ثم رفعها.');
        }

        imagepalettetotruecolor($image);

        if ($info[2] === IMAGETYPE_JPEG) {
            $image = $this->applyExifOrientation($image, $bytes);
        }

        if (max(imagesx($image), imagesy($image)) > self::MAX_EDGE) {
            $image = $this->scaleToEdge($image, self::MAX_EDGE);
        }

        // JPEG لا يحمل شفافية: تُسطَّح صور PNG الشفافة على أبيض
        $master = $this->flattenOnWhite($image);

        ob_start();
        imagejpeg($master, null, 82);
        $masterBytes = (string) ob_get_clean();

        $card = null;

        if (imagesx($master) > self::CARD_WIDTH && function_exists('imagewebp')) {
            $cardImage = $this->scaleToWidth($master, self::CARD_WIDTH);

            ob_start();
            imagewebp($cardImage, null, 78);
            $card = (string) ob_get_clean();
        }

        return ['master' => $masterBytes, 'card' => $card];
    }

    /**
     * تصحيح اتجاه اللقطة قبل مسح EXIF — وإلا ظهرت صور الجوال مقلوبة.
     * القراءة عبر ملف مؤقت لأن exif_read_data لا يقبل النصوص الخام.
     */
    private function applyExifOrientation(\GdImage $image, string $bytes): \GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $temp = tempnam(sys_get_temp_dir(), 'bahja-exif-');

        if ($temp === false) {
            return $image;
        }

        file_put_contents($temp, $bytes);
        $exif = @exif_read_data($temp);
        unlink($temp);

        $orientation = (int) ($exif['Orientation'] ?? 1);

        if ($orientation < 2 || $orientation > 8) {
            return $image;
        }

        if (in_array($orientation, [2, 5, 7], true)) {
            imageflip($image, IMG_FLIP_HORIZONTAL);
        }

        if ($orientation === 4) {
            imageflip($image, IMG_FLIP_VERTICAL);
        }

        $angle = match ($orientation) {
            3 => 180,
            6, 5 => -90,
            8, 7 => 90,
            default => 0,
        };

        if ($angle !== 0) {
            $rotated = imagerotate($image, $angle, 0);

            if ($rotated !== false) {
                $image = $rotated;
            }
        }

        return $image;
    }

    private function scaleToEdge(\GdImage $image, int $edge): \GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);

        $targetWidth = $width >= $height ? $edge : (int) round($width * $edge / $height);

        return $this->scaleToWidth($image, $targetWidth);
    }

    private function scaleToWidth(\GdImage $image, int $targetWidth): \GdImage
    {
        $scaled = imagescale($image, $targetWidth, -1, IMG_BICUBIC);

        return $scaled === false ? $image : $scaled;
    }

    private function flattenOnWhite(\GdImage $image): \GdImage
    {
        $canvas = imagecreatetruecolor(imagesx($image), imagesy($image));
        imagefill($canvas, 0, 0, (int) imagecolorallocate($canvas, 255, 255, 255));
        imagecopy($canvas, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));

        return $canvas;
    }

    private function reject(string $attribute, string $message): never
    {
        throw ValidationException::withMessages([$attribute => $message]);
    }
}
