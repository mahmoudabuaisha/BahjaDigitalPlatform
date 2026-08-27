<?php

namespace App\Http\Controllers;

use App\Enums\Audience;
use App\Enums\EventStatus;
use App\Enums\RegistrationMode;
use App\Models\Area;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Event;
use App\Models\EventSeries;
use App\Models\ShelterCenter;
use App\Services\ImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * إضافة الفعاليات وتعديلها من داخل الموقع — بنموذج الفريق المنظِّم.
 * كل فعالية جديدة تمرّ على اعتماد الإدارة قبل أن تراها العائلات.
 */
class OrganizerEventController extends Controller
{
    /** الفئات العمرية الجاهزة في النموذج: المفتاح «من-إلى» */
    public const AGE_RANGES = [
        '3-5' => '3 - 5 سنوات',
        '6-9' => '6 - 9 سنوات',
        '8-12' => '8 - 12 سنة',
        '10-14' => '10 - 14 سنة',
        '4-14' => '4 - 14 سنة (كل الأعمار)',
    ];

    public function __construct(private readonly ImageService $images) {}

    public function create(): View
    {
        return view('pages.organizer.event-form', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $isDraft = $request->input('action') === 'draft';

        // التكرار الأسبوعي (القسم 6.4): سلسلة تولّد مواعيد مستقلة حتى 8 أسابيع
        $occurrences = 1;
        $series = null;

        if ($request->boolean('repeat_weekly')) {
            $occurrences = min(max((int) $request->input('repeat_count', 2), 2), 8);

            $series = EventSeries::create([
                'team_id' => Auth::user()->team_id,
                'title' => $data['title'],
                'recurrence_rule' => 'weekly',
                'starts_on' => $data['start_date'],
                'occurrence_count' => $occurrences,
            ]);
        }

        // إعادة الترميز تمسح EXIF/GPS من الصورة قبل أن تلمس القرص
        $imagePath = $request->hasFile('image')
            ? $this->images->store($request->file('image'), 'events')
            : null;

        foreach (range(0, $occurrences - 1) as $week) {
            $event = new Event($this->attributes($data));
            $event->team_id = Auth::user()->team_id;
            $event->created_by = Auth::id();
            $event->series_id = $series?->id;
            $event->status = $isDraft ? EventStatus::Draft : EventStatus::Pending;
            $event->start_date = Carbon::parse($data['start_date'])->addWeeks($week);
            $event->image_path = $imagePath;
            $event->save();
        }

        $message = match (true) {
            $isDraft => 'حُفظت الفعالية كمسودة — يمكنكم إكمالها وإرسالها لاحقاً.',
            $occurrences > 1 => 'أُرسلت سلسلة من '.$occurrences.' مواعيد أسبوعية للاعتماد — كل موعد يُراجع ويُدار مستقلاً.',
            default => 'أُرسلت الفعالية للاعتماد — تظهر للعائلات فور موافقة الإدارة.',
        };

        return redirect()->route('organizer.events')->with('event_saved', $message);
    }

    public function edit(Event $event): View
    {
        $this->authorizeTeam($event);

        return view('pages.organizer.event-form', $this->formData($event));
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $this->authorizeTeam($event);

        $data = $this->validated($request, $event);

        $event->fill($this->attributes($data));

        if ($request->hasFile('image')) {
            $previous = $event->image_path;
            $event->image_path = $this->images->store($request->file('image'), 'events');

            // صورة الفعالية المنشورة تبقى: النسخة المعروضة للعائلات ما زالت تعرضها،
            // وصورة السلسلة الأسبوعية مشتركة فلا تُحذف ما دامت فعالية أخرى تستعملها
            if ($previous
                && ! $event->status->isPubliclyVisible()
                && ! Event::where('image_path', $previous)->where('id', '!=', $event->id)->exists()
            ) {
                $this->images->delete($previous);
            }
        }

        // المسودة تُرسل للاعتماد عند الحفظ؛ الفعالية المعتمدة تبقى معتمدة
        if ($event->status === EventStatus::Draft && $request->input('action') !== 'draft') {
            $event->status = EventStatus::Pending;
        }

        $event->save();

        // مراقب الفعالية حوّل التعديلات الجوهرية إلى نسخة تنتظر الاعتماد
        $hasPendingRevision = $event->pendingRevision()->exists();

        return redirect()->route('organizer.events')->with('event_saved', $hasPendingRevision
            ? 'حُفظ تعديل «'.$event->title.'» كنسخة بانتظار اعتماد الإدارة — والنسخة المنشورة الحالية تبقى ظاهرة للعائلات حتى الاعتماد.'
            : 'حُفظت تعديلات «'.$event->title.'».');
    }

    public function destroy(Event $event): RedirectResponse
    {
        $this->authorizeTeam($event);

        // الحجوزات القائمة تمنع الحذف الصامت — تُلغى الفعالية ليصل الأهل إشعار
        if ($event->seatsTaken() > 0) {
            $event->update(['status' => EventStatus::Cancelled]);

            AuditLog::record('event.cancelled', $event);

            return redirect()->route('organizer.events')
                ->with('event_saved', 'أُلغيت الفعالية ووصل الإشعار إلى العائلات المسجَّلة.');
        }

        AuditLog::record('event.deleted', $event);
        $event->delete();

        return redirect()->route('organizer.events')->with('event_saved', 'حُذفت الفعالية.');
    }

    private function authorizeTeam(Event $event): void
    {
        abort_unless($event->team_id === Auth::user()->team_id, 403);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Event $event = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'category_id' => ['required', Rule::exists('categories', 'id')],
            'audience' => ['required', Rule::enum(Audience::class)],
            'description' => ['required', 'string', 'max:1000'],
            'start_date' => ['required', 'date', $event ? 'after_or_equal:2000-01-01' : 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'area_id' => ['required', Rule::exists('areas', 'id')],
            'shelter_center_id' => ['nullable', Rule::exists('shelter_centers', 'id')],
            'location_details' => ['nullable', 'string', 'max:180'],
            'age_range' => ['nullable', Rule::in(array_keys(self::AGE_RANGES))],
            'expected_children' => ['required', 'integer', 'min:1', 'max:2000'],
            'fee' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'registration_mode' => ['required', Rule::enum(RegistrationMode::class)],
            'terms' => ['nullable', 'string', 'max:500'],
            'image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
            'repeat_weekly' => ['nullable', 'boolean'],
            'repeat_count' => ['nullable', 'integer', 'min:2', 'max:8'],
        ], [], [
            'title' => 'اسم الفعالية',
            'category_id' => 'الفئة',
            'description' => 'وصف الفعالية',
            'start_date' => 'تاريخ الفعالية',
            'start_time' => 'وقت البداية',
            'end_time' => 'وقت النهاية',
            'area_id' => 'المكان',
            'expected_children' => 'عدد المقاعد المتاحة',
            'fee' => 'رسوم المشاركة',
            'image' => 'صورة الفعالية',
        ]);
    }

    /** @return array<string, mixed> */
    private function attributes(array $data): array
    {
        [$ageMin, $ageMax] = $data['age_range'] ?? null
            ? array_map('intval', explode('-', $data['age_range']))
            : [null, null];

        return [
            'title' => $data['title'],
            'category_id' => $data['category_id'],
            'audience' => $data['audience'],
            'description' => $data['description'],
            'start_date' => $data['start_date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'] ?? null,
            'area_id' => $data['area_id'],
            'shelter_center_id' => $data['shelter_center_id'] ?? null,
            'location_details' => $data['location_details'] ?? null,
            'age_min' => $ageMin,
            'age_max' => $ageMax,
            'expected_children' => $data['expected_children'],
            'fee' => $data['fee'] ?? null,
            'registration_mode' => $data['registration_mode'],
            'terms' => $data['terms'] ?? null,
        ];
    }

    /** @return array<string, mixed> */
    private function formData(?Event $event = null): array
    {
        return [
            'event' => $event,
            'categories' => Category::orderBy('name')->get(),
            'areas' => Area::orderBy('name')->get(),
            'centers' => ShelterCenter::orderBy('name')->get(['id', 'name', 'area_id']),
            'ageRanges' => self::AGE_RANGES,
            'ageRange' => $event && $event->age_min && $event->age_max
                ? $event->age_min.'-'.$event->age_max
                : null,
        ];
    }
}
