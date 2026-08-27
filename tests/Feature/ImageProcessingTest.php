<?php

namespace Tests\Feature;

use App\Enums\RevisionStatus;
use App\Enums\UserRole;
use App\Models\Area;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Event;
use App\Models\Team;
use App\Models\User;
use App\Services\ImageService;
use Database\Seeders\AreaSeeder;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * معالجة الصور (القسم 15.3): إعادة الترميز تمسح EXIF/GPS، تصحّح الاتجاه،
 * تحدّ الأبعاد، وتولّد نسخة بطاقة — والأمر الرجعي ينظّف الصور القديمة.
 */
class ImageProcessingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AreaSeeder::class);
        $this->seed(CategorySeeder::class);
        Storage::fake('public');
    }

    private function manager(): User
    {
        return User::factory()->create([
            'role' => UserRole::TeamManager,
            'team_id' => Team::factory()->create()->id,
        ]);
    }

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'مهرجان الرسم الكبير',
            'category_id' => Category::first()->id,
            'audience' => 'all',
            'description' => 'رسم حر وألوان وموسيقى لأطفال الحي.',
            'start_date' => today()->addWeek()->toDateString(),
            'start_time' => '10:00',
            'area_id' => Area::first()->id,
            'expected_children' => 30,
            'registration_mode' => 'direct',
            'action' => 'publish',
        ], $overrides);
    }

    /**
     * JPEG حقيقي يحمل مقطع EXIF فيه علم اتجاه ومؤشر GPS —
     * مثل أي لقطة جوال لم تُنظَّف.
     */
    private function jpegWithExif(int $width, int $height, int $orientation = 1): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, (int) imagecolorallocate($image, 210, 120, 40));

        ob_start();
        imagejpeg($image, null, 88);
        $jpeg = (string) ob_get_clean();

        // TIFF مصغّر: IFD0 يحمل Orientation ومؤشر GPS IFD يحمل GPSLatitudeRef
        $tiff = "II\x2A\x00\x08\x00\x00\x00"
            ."\x02\x00"
            ."\x12\x01\x03\x00\x01\x00\x00\x00".chr($orientation)."\x00\x00\x00"
            ."\x25\x88\x04\x00\x01\x00\x00\x00\x26\x00\x00\x00"
            ."\x00\x00\x00\x00"
            ."\x01\x00"
            ."\x01\x00\x02\x00\x02\x00\x00\x00N\x00\x00\x00"
            ."\x00\x00\x00\x00";

        $app1 = "\xFF\xE1".pack('n', strlen($tiff) + 8)."Exif\x00\x00".$tiff;

        return substr($jpeg, 0, 2).$app1.substr($jpeg, 2);
    }

    public function test_exif_and_gps_are_stripped_and_size_capped_on_upload(): void
    {
        $original = $this->jpegWithExif(2400, 1200);
        $this->assertTrue(str_contains($original, "Exif\x00\x00"));

        $this->actingAs($this->manager())->post(route('organizer.events.store'), $this->payload([
            'image' => UploadedFile::fake()->createWithContent('photo.jpg', $original),
        ]))->assertRedirect(route('organizer.events'));

        $event = Event::first();
        $stored = Storage::disk('public')->get($event->image_path);

        $this->assertStringEndsWith('.jpg', $event->image_path);
        $this->assertFalse(str_contains($stored, "Exif\x00\x00"));

        [$width, $height] = getimagesizefromstring($stored);
        $this->assertSame(ImageService::MAX_EDGE, $width);
        $this->assertSame(ImageService::MAX_EDGE / 2, $height);
    }

    public function test_a_sideways_phone_photo_is_straightened_before_stripping(): void
    {
        // العلم 6 يعني «أدر اللقطة ربع دورة» — بعد المعالجة تنعكس الأبعاد
        $this->actingAs($this->manager())->post(route('organizer.events.store'), $this->payload([
            'image' => UploadedFile::fake()->createWithContent('sideways.jpg', $this->jpegWithExif(400, 200, orientation: 6)),
        ]));

        [$width, $height] = getimagesizefromstring(
            Storage::disk('public')->get(Event::first()->image_path),
        );

        $this->assertSame(200, $width);
        $this->assertSame(400, $height);
    }

    public function test_a_card_variant_is_generated_and_served_to_lists(): void
    {
        $this->actingAs($this->manager())->post(route('organizer.events.store'), $this->payload([
            'image' => UploadedFile::fake()->createWithContent('big.jpg', $this->jpegWithExif(1800, 900)),
        ]));

        $event = Event::first();
        $card = ImageService::cardPath($event->image_path);

        Storage::disk('public')->assertExists($card);
        $this->assertStringEndsWith('-card.webp', (string) $event->imageCardUrl());
        $this->assertNotSame($event->imageUrl(), $event->imageCardUrl());

        [$cardWidth] = getimagesizefromstring(Storage::disk('public')->get($card));
        $this->assertSame(ImageService::CARD_WIDTH, $cardWidth);
    }

    public function test_a_small_image_skips_the_card_but_is_still_reencoded(): void
    {
        $this->actingAs($this->manager())->post(route('organizer.events.store'), $this->payload([
            'image' => UploadedFile::fake()->createWithContent('small.jpg', $this->jpegWithExif(320, 200)),
        ]));

        $event = Event::first();

        Storage::disk('public')->assertMissing(ImageService::cardPath($event->image_path));
        $this->assertSame($event->imageUrl(), $event->imageCardUrl());
        $this->assertFalse(str_contains(Storage::disk('public')->get($event->image_path), "Exif\x00\x00"));
    }

    public function test_absurd_dimensions_are_rejected_as_a_decompression_guard(): void
    {
        $wide = imagecreatetruecolor(12500, 2);
        ob_start();
        imagejpeg($wide, null, 60);
        $bytes = (string) ob_get_clean();

        $this->actingAs($this->manager())->post(route('organizer.events.store'), $this->payload([
            'image' => UploadedFile::fake()->createWithContent('bomb.jpg', $bytes),
        ]))->assertSessionHasErrors('image');

        $this->assertSame(0, Event::count());
    }

    public function test_the_reprocess_command_cleans_legacy_images_and_updates_references(): void
    {
        $disk = Storage::disk('public');

        // صورة قديمة رُفعت قبل تفعيل المعالجة وتتشاركها سلسلة من موعدين
        $disk->put('events/legacy-one.jpg', $this->jpegWithExif(2000, 1000));
        $first = Event::factory()->approved()->create(['image_path' => 'events/legacy-one.jpg']);
        $second = Event::factory()->approved()->create([
            'team_id' => $first->team_id,
            'image_path' => 'events/legacy-one.jpg',
        ]);

        // ونسخة معلّقة تقترح صورة أخرى لم تصل إلى الفعالية بعد
        $disk->put('events/legacy-two.jpg', $this->jpegWithExif(900, 600));
        $revision = $first->revisions()->create([
            'version' => 1,
            'payload' => ['title' => 'عنوان مقترح', 'image_path' => 'events/legacy-two.jpg'],
            'status' => RevisionStatus::Pending,
            'submitted_by' => User::factory()->create(['role' => UserRole::TeamManager, 'team_id' => $first->team_id])->id,
        ]);

        $this->artisan('images:reprocess')->assertSuccessful();

        $first->refresh();
        $second->refresh();

        $this->assertNotSame('events/legacy-one.jpg', $first->image_path);
        $this->assertSame($first->image_path, $second->image_path);
        $disk->assertMissing('events/legacy-one.jpg');
        $this->assertFalse(str_contains($disk->get($first->image_path), "Exif\x00\x00"));

        $this->assertNotSame('events/legacy-two.jpg', $revision->fresh()->payload['image_path']);
        $disk->assertExists($revision->fresh()->payload['image_path']);

        $this->assertDatabaseHas('audit_logs', ['action' => 'images.reprocessed']);

        // تشغيل ثانٍ لا يعيد معالجة ما نُظِّف
        $path = $first->image_path;
        $this->artisan('images:reprocess')->assertSuccessful();
        $this->assertSame($path, $first->fresh()->image_path);
        $this->assertSame(0, AuditLog::where('action', 'images.reprocessed')->count() - 1);
    }
}
