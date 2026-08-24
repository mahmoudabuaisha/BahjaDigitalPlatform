<?php

namespace App\Services;

use App\Enums\RevisionStatus;
use App\Models\AuditLog;
use App\Models\EventRevision;
use App\Models\UserNotification;
use Illuminate\Support\Facades\DB;

/**
 * اعتماد أو رفض نسخة تعديل — منطق الأعمال خارج Filament
 * ليكون قابلاً للاختبار والاستدعاء من أي واجهة (القسم 8.4).
 */
class RevisionService
{
    public function approve(EventRevision $revision): void
    {
        DB::transaction(function () use ($revision): void {
            $event = $revision->event;
            $before = collect($event->only(array_keys($revision->payload)))
                ->map(fn ($value) => $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : $value)
                ->all();

            // تُطبَّق النسخة على الفعالية المنشورة — بهوية المشرف فلا يعترضها المراقب
            $event->forceFill($revision->payload)->save();

            $revision->update([
                'status' => RevisionStatus::Approved,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);

            AuditLog::record('revision.approved', $event, $before, $revision->payload);
        });

        foreach ($revision->event->team?->users ?? [] as $manager) {
            UserNotification::send(
                $manager,
                'revision_approved',
                'اعتُمد تعديلكم على «'.$revision->event->title.'»',
                'صارت النسخة الجديدة هي الظاهرة للعائلات.',
            );
        }
    }

    public function reject(EventRevision $revision, string $reason): void
    {
        $revision->update([
            'status' => RevisionStatus::Rejected,
            'decision_reason' => $reason,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        AuditLog::record('revision.rejected', $revision->event, reason: $reason);

        foreach ($revision->event->team?->users ?? [] as $manager) {
            UserNotification::send(
                $manager,
                'revision_rejected',
                'رُفض تعديلكم على «'.$revision->event->title.'»',
                $reason,
            );
        }
    }
}
