<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Models\AuditLog;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * تسجيل الحضور الفعلي (القسم 6.3): لا يفتح قبل نهاية الفعالية،
 * والأرقام إجمالية فقط — وتقارير الأثر الرسمية تُبنى عليها وحدها.
 */
class OrganizerAttendanceController extends Controller
{
    public function create(Event $event): View
    {
        $this->authorizeAttendance($event);

        return view('pages.organizer.attendance', [
            'event' => $event,
            'report' => $event->attendanceReport,
        ]);
    }

    public function store(Request $request, Event $event): RedirectResponse
    {
        $this->authorizeAttendance($event);

        $data = $request->validate([
            'children_actual' => ['required', 'integer', 'min:0', 'max:5000'],
            'guardians_actual' => ['required', 'integer', 'min:0', 'max:5000'],
            'notes_private' => ['nullable', 'string', 'max:500'],
        ], [], [
            'children_actual' => 'عدد الأطفال',
            'guardians_actual' => 'عدد المرافقين',
            'notes_private' => 'الملاحظة الداخلية',
        ]);

        $existing = $event->attendanceReport;
        $before = $existing?->only(['children_actual', 'guardians_actual']);

        $report = $event->attendanceReport()->updateOrCreate([], $data + [
            'submitted_by' => Auth::id(),
            // التصحيح بعد التحقق يعيد التقرير إلى طابور التحقق
            'verified_by' => null,
            'verified_at' => null,
        ]);

        // الأعمدة القديمة على الفعالية تبقى متزامنة لتقارير اللوحة الحالية
        $event->forceFill([
            'actual_children' => $data['children_actual'],
            'actual_caregivers' => $data['guardians_actual'],
            'status' => EventStatus::Completed,
        ])->save();

        AuditLog::record(
            $existing ? 'attendance.corrected' : 'attendance.submitted',
            $event,
            $before,
            $report->only(['children_actual', 'guardians_actual']),
        );

        return redirect()->route('organizer.events')
            ->with('event_saved', 'سُجّل حضور «'.$event->title.'»: '
                .$data['children_actual'].' طفلاً و'.$data['guardians_actual'].' مرافقاً.');
    }

    private function authorizeAttendance(Event $event): void
    {
        abort_unless($event->team_id === Auth::user()->team_id, 403);

        // لا يفتح تسجيل الحضور قبل نهاية الفعالية
        abort_unless($event->startsAt()->isPast() && ! $event->start_date->isFuture(), 403);
    }
}
