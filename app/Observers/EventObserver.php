<?php

namespace App\Observers;

use App\Enums\EventStatus;
use App\Enums\UserRole;
use App\Models\Event;
use Illuminate\Support\Facades\Cache;

class EventObserver
{
    /**
     * الحقول الجوهرية التي يُعيد تعديلُها فعاليةً معتمدة إلى "بانتظار الاعتماد"
     * (تسجيل الحضور والمشاهدات لا يمسّان حالة الاعتماد)
     */
    private const MODERATED_FIELDS = [
        'title',
        'description',
        'location_details',
        'start_date',
        'start_time',
        'end_time',
        'category_id',
        'area_id',
        'shelter_center_id',
        'image_path',
    ];

    public function updating(Event $event): void
    {
        $user = auth()->user();

        // تعديل مسؤول الفريق لفعالية معتمدة يعيدها للمراجعة — الأدمن يعدّل بحرية
        if (
            $user?->role === UserRole::TeamManager
            && $event->getOriginal('status') === EventStatus::Approved
            && $event->status === EventStatus::Approved
            && $event->isDirty(self::MODERATED_FIELDS)
        ) {
            $event->status = EventStatus::Pending;
            $event->approved_by = null;
            $event->approved_at = null;
        }
    }

    public function saved(Event $event): void
    {
        self::bustFeedCache();
    }

    public function deleted(Event $event): void
    {
        self::bustFeedCache();
    }

    public function restored(Event $event): void
    {
        self::bustFeedCache();
    }

    public static function bustFeedCache(): void
    {
        Cache::forget('events_feed');
    }
}
