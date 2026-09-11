<?php

namespace App\Observers;

use App\Enums\EventStatus;
use App\Enums\RevisionStatus;
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
        'directions',
        'start_date',
        'start_time',
        'end_time',
        'category_id',
        'area_id',
        'shelter_center_id',
        'image_path',
    ];

    /**
     * قاعدة الخطة (6.3): تعديل مسؤول الفريق لفعالية منشورة لا يمسّها —
     * يُحفظ نسخةَ تعديل تنتظر الاعتماد، وتبقى النسخة المنشورة ظاهرة.
     * الأدمن يعدّل بحرية.
     */
    public function updating(Event $event): void
    {
        $user = auth()->user();

        if (
            $user?->role !== UserRole::TeamManager
            || $event->getOriginal('status') !== EventStatus::Approved
            || $event->status !== EventStatus::Approved
            || ! $event->isDirty(self::MODERATED_FIELDS)
        ) {
            return;
        }

        $proposed = collect($event->getDirty())
            ->only(self::MODERATED_FIELDS)
            ->map(fn ($value) => $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : $value)
            ->all();

        // نسخة معلّقة واحدة لكل فعالية: الأحدث يحلّ محل الأقدم
        $event->pendingRevision()->delete();

        $event->revisions()->create([
            'version' => ($event->revisions()->max('version') ?? 0) + 1,
            'payload' => $proposed,
            'status' => RevisionStatus::Pending,
            'submitted_by' => $user->id,
        ]);

        // يصل المراجعين تنبيه كي لا يتأخّر القرار
        User::query()
            ->whereIn('role', [UserRole::SuperAdmin, UserRole::Admin])
            ->pluck('id')
            ->each(fn (int $adminId) => UserNotification::send(
                $adminId,
                'revision_submitted',
                'تعديل بانتظار المراجعة على «'.$event->title.'»',
                ($user->team?->name ?? $user->name).' — تبقى النسخة المنشورة ظاهرة حتى الاعتماد.',
            ));

        // تُعاد الحقول الخاضعة للمراجعة إلى قيمها المنشورة
        foreach (self::MODERATED_FIELDS as $field) {
            if ($event->isDirty($field)) {
                $event->setAttribute($field, $event->getOriginal($field));
            }
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

        // نشر مؤجَّل: الإعلان يصدر وقت الظهور الفعلي (أمر events:release-scheduled)
        if ($event->publish_at?->isFuture()) {
            return;
        }

        $this->announceToArea($event);
    }

    /** بث «فعالية جديدة» لعائلات المحافظة — يُستدعى أيضاً لحظة النشر المجدول */
    public function announceToArea(Event $event): void
    {
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
