<?php

namespace App\Console\Commands;

use App\Enums\RevisionStatus;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\EventRevision;
use App\Models\Team;
use App\Services\ImageService;
use App\Support\Settings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * تنظيف رجعي للصور المرفوعة قبل تفعيل المعالجة: يعيد ترميزها فيمسح
 * EXIF/GPS منها، يولّد نسخ البطاقات، ويحدّث كل ما يشير إليها —
 * الفعاليات وشعارات الفرق وصورة المشاركة والنسخ المعلّقة.
 *
 * يُشغَّل مرة واحدة بعد النشر: php artisan images:reprocess
 */
class ReprocessImages extends Command
{
    protected $signature = 'images:reprocess {--dry-run : عرض ما سيُعالَج دون تنفيذ}';

    protected $description = 'إعادة ترميز الصور القديمة ومسح بيانات EXIF/GPS منها';

    /** @var array<string, string> خريطة المسار القديم → الجديد */
    private array $moved = [];

    private int $processed = 0;

    private int $skipped = 0;

    private int $failed = 0;

    public function handle(ImageService $images): int
    {
        // كائن الأمر يُعاد استخدامه بين الاستدعاءات في العملية الواحدة
        $this->moved = [];
        $this->processed = $this->skipped = $this->failed = 0;

        $dry = (bool) $this->option('dry-run');

        // صور الفعاليات — السلسلة الأسبوعية تتشارك الملف فتُجمع المسارات أولاً
        foreach (Event::whereNotNull('image_path')->distinct()->pluck('image_path') as $path) {
            $this->handlePath($images, $path, 'فعالية', $dry);
        }

        foreach (Team::whereNotNull('logo_path')->distinct()->pluck('logo_path') as $path) {
            $this->handlePath($images, $path, 'شعار فريق', $dry);
        }

        if ($og = (string) Settings::get('og_default_image')) {
            $this->handlePath($images, $og, 'صورة المشاركة', $dry);
        }

        // النسخ المعلّقة قد تقترح صورة جديدة لم تصل بعد إلى الفعالية نفسها
        foreach (EventRevision::where('status', RevisionStatus::Pending)->get() as $revision) {
            $proposed = $revision->payload['image_path'] ?? null;

            if ($proposed && ! isset($this->moved[$proposed])) {
                $this->handlePath($images, $proposed, 'نسخة معلّقة', $dry);
            }
        }

        if (! $dry && $this->moved !== []) {
            $this->applyMoves();
        }

        if (! $dry && ($this->processed || $this->failed)) {
            AuditLog::record('images.reprocessed', null, null, [
                'processed' => $this->processed,
                'skipped' => $this->skipped,
                'failed' => $this->failed,
            ]);
        }

        $this->info(sprintf(
            '%sعُولجت %d — سليمة أصلاً %d — تعذّرت %d',
            $dry ? '(تجربة) ' : '',
            $this->processed,
            $this->skipped,
            $this->failed,
        ));

        return self::SUCCESS;
    }

    private function handlePath(ImageService $images, string $path, string $label, bool $dry): void
    {
        if (isset($this->moved[$path])) {
            return;
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            $this->warn("مفقودة ({$label}): {$path}");
            $this->failed++;

            return;
        }

        if (! $this->needsProcessing((string) $disk->get($path), $path)) {
            $this->skipped++;

            return;
        }

        if ($dry) {
            $this->line("ستُعالَج ({$label}): {$path}");
            $this->processed++;

            return;
        }

        $new = $images->reprocess($path);

        if ($new === null) {
            $this->warn("تعذّرت ({$label}): {$path}");
            $this->failed++;

            return;
        }

        $this->moved[$path] = $new;
        $this->processed++;
        $this->line("عُولجت ({$label}): {$path} ← {$new}");
    }

    /** صورة نظيفة سلفاً (معالَجة من الخدمة) لا تحتاج جولة ثانية */
    private function needsProcessing(string $bytes, string $path): bool
    {
        if (str_contains($bytes, "Exif\x00\x00")) {
            return true;
        }

        if (! str_ends_with(strtolower($path), '.jpg')) {
            return true;
        }

        $info = @getimagesizefromstring($bytes);

        if ($info === false) {
            return true;
        }

        if (max($info[0], $info[1]) > ImageService::MAX_EDGE) {
            return true;
        }

        return $info[0] > ImageService::CARD_WIDTH
            && ! Storage::disk('public')->exists(ImageService::cardPath($path));
    }

    /** تحديث كل الجداول التي تشير إلى المسارات المنقولة */
    private function applyMoves(): void
    {
        foreach ($this->moved as $old => $new) {
            Event::where('image_path', $old)->update(['image_path' => $new]);
            Team::where('logo_path', $old)->update(['logo_path' => $new]);
        }

        if ($og = (string) Settings::get('og_default_image')) {
            if (isset($this->moved[$og])) {
                Settings::set('og_default_image', $this->moved[$og]);
            }
        }

        foreach (EventRevision::where('status', RevisionStatus::Pending)->get() as $revision) {
            $proposed = $revision->payload['image_path'] ?? null;

            if ($proposed && isset($this->moved[$proposed])) {
                $payload = $revision->payload;
                $payload['image_path'] = $this->moved[$proposed];
                $revision->update(['payload' => $payload]);
            }
        }
    }
}
