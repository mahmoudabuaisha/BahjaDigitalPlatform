<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * السجل الرقابي (القسم 15.7): كل قرار حساس يسجَّل بمن قام به
 * ووقته وما تغيّر — ولا يُعدَّل من اللوحة.
 */
class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['actor_id', 'action', 'subject_type', 'subject_id', 'before', 'after', 'reason', 'created_at'];

    protected function casts(): array
    {
        return [
            'before' => 'array',
            'after' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /** التسجيل من أي مكان: AuditLog::record('event.approved', $event) */
    public static function record(
        string $action,
        ?Model $subject = null,
        ?array $before = null,
        ?array $after = null,
        ?string $reason = null,
    ): void {
        static::create([
            'actor_id' => Auth::id(),
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'before' => $before,
            'after' => $after,
            'reason' => $reason,
            'created_at' => now(),
        ]);
    }

    /** تسميات عربية للقارئ في اللوحة */
    public static function actionLabel(string $action): string
    {
        return match ($action) {
            'event.approved' => 'اعتماد فعالية',
            'event.rejected' => 'رفض فعالية',
            'event.cancelled' => 'إلغاء فعالية',
            'event.deleted' => 'حذف فعالية',
            'revision.approved' => 'اعتماد تعديل',
            'revision.rejected' => 'رفض تعديل',
            'application.approved' => 'اعتماد طلب فريق',
            'application.rejected' => 'رفض طلب فريق',
            'application.suspended' => 'تعليق فريق',
            'attendance.submitted' => 'تسجيل حضور',
            'attendance.verified' => 'تحقق من حضور',
            'attendance.corrected' => 'تصحيح حضور',
            'user.role_changed' => 'تغيير دور مستخدم',
            'user.toggled' => 'تفعيل/إيقاف حساب',
            'export.csv' => 'تصدير بيانات',
            'broadcast.sent' => 'رسالة عامة',
            default => $action,
        };
    }
}
