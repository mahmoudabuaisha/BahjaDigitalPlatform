<?php

namespace App\Observers;

use App\Enums\EventStatus;
use App\Enums\UserRole;
use App\Models\Event;
use App\Models\User;
use App\Models\UserNotification;
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

    /** إشعارات ما بعد التعديل — تُقرأ من الحالة القديمة قبل أن تُحفظ الجديدة */
    public function updated(Event $event): void
    {
        $this->notifyOnScheduleChange($event);
        $this->notifyAreaOnPublish($event);
    }

    public function created(Event $event): void
    {
        $this->notifyAreaOnPublish($event);
    }

    public function saved(Event $event): void
    {
        self::bustFeedCache();
    }

    /** تغيّر الموعد أو الإلغاء: من حجز مقعداً يجب أن يعرف اليوم لا في الموعد */
    private function notifyOnScheduleChange(Event $event): void
    {
        $cancelled = $event->wasChanged('status') && $event->status === EventStatus::Cancelled;
        $rescheduled = $event->wasChanged(['start_date', 'start_time']);

        if (! $cancelled && ! $rescheduled) {
            return;
        }

        $recipients = $event->registrations()->holdingSeat()->pluck('user_id')->unique();

        foreach ($recipients as $userId) {
            UserNotification::send(
                $userId,
                $cancelled ? 'event_cancelled' : 'event_changed',
                $cancelled
                    ? 'أُلغيت فعالية «'.$event->title.'»'
                    : 'تغيّر موعد فعالية «'.$event->title.'»',
                $cancelled
                    ? 'نعتذر — ألغى الفريق هذه الفعالية.'
                    : 'الموعد الجديد: '.$event->start_date->translatedFormat('l j F').' الساعة '.substr($event->start_time, 0, 5),
                route('events.show', $event),
            );
        }
    }

    /** فعالية جديدة اعتُمدت: تُعلَم عائلات المحافظة نفسها */
    private function notifyAreaOnPublish(Event $event): void
    {
        if ($event->status !== EventStatus::Approved) {
            return;
        }

        // عند التعديل: نُشعر مرة واحدة فقط، لحظة انتقال الحالة إلى معتمدة
        if ($event->exists && $event->wasRecentlyCreated === false && ! $event->wasChanged('status')) {
            return;
        }

        User::query()
            ->where('role', UserRole::Family)
            ->where('is_active', true)
            ->where('area_id', $event->area_id)
            ->pluck('id')
            ->each(fn (int $userId) => UserNotification::send(
                $userId,
                'event_new',
                'فعالية جديدة في محافظتكم',
                $event->title.' — '.$event->start_date->translatedFormat('l j F'),
                route('events.show', $event),
            ));
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
