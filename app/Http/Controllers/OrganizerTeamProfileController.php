<?php

namespace App\Http\Controllers;

use App\Enums\OrgType;
use App\Models\Area;
use App\Models\Team;
use App\Models\TeamApplication;
use App\Services\ImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\View\View;

/**
 * ملف الفريق داخل لوحته: يعدّل الفريق بياناته وشعاره بنفسه بلا وسيط —
 * وهي البيانات التي تراها العائلات في صفحة الفريق بالموقع العام.
 */
class OrganizerTeamProfileController extends Controller
{
    public function __construct(private ImageService $images) {}

    public function edit(Request $request): View
    {
        return view('pages.organizer.profile', [
            'team' => $this->team($request),
            'areas' => Area::orderBy('sort_order')->get(),
            'orgTypes' => OrgType::options(),
            'activityOptions' => TeamApplication::ACTIVITIES,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $team = $this->team($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'org_type' => ['nullable', new Enum(OrgType::class)],
            'area_id' => ['nullable', 'exists:areas,id'],
            'base_location' => ['nullable', 'string', 'max:160'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'whatsapp_phone' => ['nullable', 'string', 'max:30'],
            'emergency_phone' => ['nullable', 'string', 'max:30'],
            'coverage_areas' => ['nullable', 'array'],
            'coverage_areas.*' => ['exists:areas,id'],
            'activities' => ['nullable', 'array'],
            'activities.*' => [Rule::in(array_keys(TeamApplication::ACTIVITIES))],
            'coverage_details' => ['nullable', 'string', 'max:500'],
            'volunteers_count' => ['nullable', 'integer', 'min:1', 'max:5000'],
            'capacity_per_event' => ['nullable', 'integer', 'min:1', 'max:5000'],
        ]);

        if ($request->hasFile('logo')) {
            // الشعار الجديد يمرّ بمعالج الصور (تصغير ومسح EXIF) والقديم يُحذف
            $previous = $team->logo_path;
            $data['logo_path'] = $this->images->store($request->file('logo'), 'teams', 'logo_path');
            $this->images->delete($previous);
        }

        unset($data['logo']);

        $team->update($data);

        return redirect()
            ->route('organizer.profile')
            ->with('status', 'حُفظت بيانات الفريق — وتظهر الآن للعائلات في صفحتكم.');
    }

    private function team(Request $request): Team
    {
        $team = $request->user()->team;

        abort_unless($team !== null, 403, 'لا يوجد فريق مرتبط بحسابكم.');

        return $team;
    }
}
