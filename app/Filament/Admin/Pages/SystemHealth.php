<?php

namespace App\Filament\Admin\Pages;

use App\Enums\EventStatus;
use App\Enums\RegistrationStatus;
use App\Models\AttendanceReport;
use App\Models\Event;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use UnitEnum;

/** شاشة صحة النظام (القسم 5.3): الطوابير والجدولة والتخزين والتقارير المتأخرة */
class SystemHealth extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    protected static ?string $title = 'صحة النظام';

    protected static ?string $navigationLabel = 'صحة النظام';

    protected static string|UnitEnum|null $navigationGroup = 'النظام';

    protected static ?int $navigationSort = 30;

    protected string $view = 'filament.admin.pages.system-health';

    /** @return array<int, array{label: string, value: string, ok: bool, hint: string}> */
    public function checks(): array
    {
        $failedJobs = (int) DB::table('failed_jobs')->count();

        $lastArchiveRun = cache('events_archive_last_run');

        // فعاليات منفَّذة منذ أكثر من 72 ساعة دون تقرير حضور (مؤشر الخطة: 85%+)
        $lateReports = Event::query()
            ->where('status', EventStatus::Completed)
            ->whereDate('start_date', '<', today()->subDays(3))
            ->whereDoesntHave('attendanceReport')
            ->count();

        $pendingRegistrations = DB::table('registrations')
            ->where('status', RegistrationStatus::Pending->value)
            ->where('created_at', '<', now()->subDays(2))
            ->count();

        $diskFree = @disk_free_space(storage_path()) ?: 0;
        $diskFreeGb = round($diskFree / 1024 / 1024 / 1024, 1);

        $unverified = AttendanceReport::whereNull('verified_at')->count();

        return [
            [
                'label' => 'المهام الفاشلة في الطابور',
                'value' => (string) $failedJobs,
                'ok' => $failedJobs === 0,
                'hint' => $failedJobs ? 'راجعوا جدول failed_jobs.' : 'لا مهام فاشلة.',
            ],
            [
                'label' => 'آخر تشغيل للأرشفة المجدولة',
                'value' => $lastArchiveRun ? Carbon::parse($lastArchiveRun)->diffForHumans() : 'لم يُسجَّل بعد',
                'ok' => $lastArchiveRun ? Carbon::parse($lastArchiveRun)->gt(now()->subDays(2)) : false,
                'hint' => 'تتأكد أن الـ cron يعمل: schedule:run كل دقيقة.',
            ],
            [
                'label' => 'تقارير حضور متأخرة (أكثر من 72 ساعة)',
                'value' => (string) $lateReports,
                'ok' => $lateReports === 0,
                'hint' => 'هدف الخطة: 85% من الفعاليات تسجَّل خلال 72 ساعة.',
            ],
            [
                'label' => 'تقارير حضور بانتظار التحقق',
                'value' => (string) $unverified,
                'ok' => $unverified === 0,
                'hint' => 'من قائمة «الحضور» في إدارة المحتوى.',
            ],
            [
                'label' => 'حجوزات معلّقة منذ أكثر من يومين',
                'value' => (string) $pendingRegistrations,
                'ok' => $pendingRegistrations === 0,
                'hint' => 'ذكّروا الفرق بالردّ على طلبات العائلات.',
            ],
            [
                'label' => 'المساحة الحرة على القرص',
                'value' => $diskFreeGb.' GB',
                'ok' => $diskFreeGb > 1,
                'hint' => 'صور الفعاليات تستهلك التخزين تدريجياً.',
            ],
        ];
    }
}
