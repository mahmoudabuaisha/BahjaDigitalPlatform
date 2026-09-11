@extends('layouts.organizer')

@section('title', 'ملف الفريق')

@section('organizer')

<div class="flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="flex items-center gap-3 text-3xl font-bold">
            <span class="icon-tile tone tone-sky size-12"><x-ui.icon name="users"/></span>
            ملف الفريق
        </h1>
        <p class="mt-1 text-ink-soft">هذه البيانات تظهر للعائلات في صفحة فريقكم بالموقع العام.</p>
    </div>

    <a href="{{ route('teams.show', $team) }}" target="_blank" rel="noopener" class="btn btn-outline btn-sm">
        <x-ui.icon name="eye" class="size-4"/> معاينة صفحتنا العامة
    </a>
</div>

@if(session('status'))
    <p class="mt-5 flex items-center gap-2 rounded-2xl bg-emerald-50 px-4 py-3 font-bold text-emerald-700">
        <x-ui.icon name="check" class="size-5 shrink-0"/> {{ session('status') }}
    </p>
@endif

@if($errors->any())
    <div class="mt-5 rounded-2xl bg-rose-50 px-4 py-3 text-rose-700">
        <p class="flex items-center gap-2 font-bold"><x-ui.icon name="x" class="size-5"/> راجعوا الحقول التالية:</p>
        <ul class="mt-2 list-disc ps-6 text-sm">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('organizer.profile.update') }}" enctype="multipart/form-data" class="mt-6 flex flex-col gap-5">
    @csrf
    @method('PUT')

    {{-- ── هوية الفريق ── --}}
    <section class="card gap-5 p-6">
        <h2 class="section-title text-xl">
            <span class="icon-tile tone tone-violet size-10"><x-ui.icon name="sparkles"/></span>
            هوية الفريق
        </h2>

        <label class="field">
            <span>اسم الفريق <span class="text-rose-500">*</span></span>
            <input type="text" name="name" required maxlength="120" class="input"
                   value="{{ old('name', $team->name) }}">
        </label>

        <label class="field">
            <span>نبذة عن الفريق وأنشطته</span>
            <textarea name="description" rows="4" maxlength="2000" class="input"
                      placeholder="من أنتم، وما الذي تقدّمونه للأطفال؟">{{ old('description', $team->description) }}</textarea>
        </label>

        <div class="field">
            <span>شعار الفريق</span>
            <div class="flex flex-wrap items-center gap-4">
                <span class="grid size-20 shrink-0 place-items-center overflow-hidden rounded-2xl border border-brand-100 bg-brand-50">
                    @if($team->logo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($team->logo_path))
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($team->logo_path) }}"
                             alt="شعار {{ $team->name }}" class="size-full object-cover">
                    @else
                        <span class="text-2xl font-bold text-brand-700">{{ mb_substr($team->name, 0, 1) }}</span>
                    @endif
                </span>
                <label class="flex-1">
                    <input type="file" name="logo" accept="image/*"
                           class="input file:me-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:font-bold file:text-brand-700">
                    <span class="mt-1.5 block text-sm text-ink-soft">
                        صورة مربّعة أوضح. تُعالَج تلقائياً: تصغير ومسح بيانات الكاميرا والموقع.
                    </span>
                </label>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <label class="field">
                <span>نوع الجهة</span>
                <select name="org_type" class="input">
                    <option value="">غير محدَّد</option>
                    @foreach($orgTypes as $value => $label)
                        <option value="{{ $value }}" @selected(old('org_type', $team->org_type?->value) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            <label class="field">
                <span>المحافظة</span>
                <select name="area_id" class="input">
                    <option value="">غير محدَّدة</option>
                    @foreach($areas as $area)
                        <option value="{{ $area->id }}" @selected((int) old('area_id', $team->area_id) === $area->id)>{{ $area->name }}</option>
                    @endforeach
                </select>
            </label>

            <label class="field">
                <span>المقر / منطقة التواجد</span>
                <input type="text" name="base_location" maxlength="160" class="input"
                       value="{{ old('base_location', $team->base_location) }}">
            </label>
        </div>
    </section>

    {{-- ── التواصل ── --}}
    <section class="card gap-5 p-6">
        <h2 class="section-title text-xl">
            <span class="icon-tile tone tone-emerald size-10"><x-ui.icon name="phone"/></span>
            التواصل
        </h2>

        <div class="grid gap-4 md:grid-cols-3">
            <label class="field">
                <span>اسم مسؤول الميدان</span>
                <input type="text" name="contact_name" maxlength="255" class="input"
                       value="{{ old('contact_name', $team->contact_name) }}">
            </label>

            <label class="field">
                <span>واتساب التواصل</span>
                <input type="text" name="whatsapp_phone" maxlength="30" dir="ltr" class="input" placeholder="970599999999"
                       value="{{ old('whatsapp_phone', $team->whatsapp_phone) }}">
            </label>

            <label class="field">
                <span>رقم الطوارئ</span>
                <input type="text" name="emergency_phone" maxlength="30" dir="ltr" class="input"
                       value="{{ old('emergency_phone', $team->emergency_phone) }}">
            </label>
        </div>
    </section>

    {{-- ── الأنشطة والتغطية ── --}}
    <section class="card gap-5 p-6">
        <h2 class="section-title text-xl">
            <span class="icon-tile tone tone-amber size-10"><x-ui.icon name="puzzle-piece"/></span>
            الأنشطة والتغطية
        </h2>

        <fieldset class="field">
            <legend class="mb-2 block font-medium text-ink-soft">الأنشطة التي يقدّمها الفريق</legend>
            @php $selectedActivities = old('activities', $team->activities ?? []); @endphp
            <div class="flex flex-wrap gap-2">
                @foreach($activityOptions as $value => $label)
                    <label class="has-checked:border-brand-400 has-checked:bg-brand-50 has-checked:text-brand-700 flex cursor-pointer items-center gap-2 rounded-full border border-brand-100 px-4 py-2 font-medium transition">
                        <input type="checkbox" name="activities[]" value="{{ $value }}" class="size-4 accent-brand-600"
                               @checked(in_array($value, (array) $selectedActivities, true))>
                        {{ $label }}
                    </label>
                @endforeach
            </div>
        </fieldset>

        <fieldset class="field">
            <legend class="mb-2 block font-medium text-ink-soft">المحافظات التي يصلها الفريق</legend>
            @php $selectedAreas = array_map('intval', (array) old('coverage_areas', $team->coverage_areas ?? [])); @endphp
            <div class="flex flex-wrap gap-2">
                @foreach($areas as $area)
                    <label class="has-checked:border-brand-400 has-checked:bg-brand-50 has-checked:text-brand-700 flex cursor-pointer items-center gap-2 rounded-full border border-brand-100 px-4 py-2 font-medium transition">
                        <input type="checkbox" name="coverage_areas[]" value="{{ $area->id }}" class="size-4 accent-brand-600"
                               @checked(in_array($area->id, $selectedAreas, true))>
                        {{ $area->name }}
                    </label>
                @endforeach
            </div>
        </fieldset>

        <label class="field">
            <span>تفاصيل التغطية</span>
            <input type="text" name="coverage_details" maxlength="500" class="input"
                   placeholder="مثال: مراكز الإيواء في شمال المحافظة والمخيّمات القريبة"
                   value="{{ old('coverage_details', $team->coverage_details) }}">
        </label>

        <div class="grid gap-4 sm:grid-cols-2">
            <label class="field">
                <span>عدد المتطوعين</span>
                <input type="number" name="volunteers_count" min="1" max="5000" class="input"
                       value="{{ old('volunteers_count', $team->volunteers_count) }}">
            </label>

            <label class="field">
                <span>الاستيعاب في الفعالية الواحدة (طفل)</span>
                <input type="number" name="capacity_per_event" min="1" max="5000" class="input"
                       value="{{ old('capacity_per_event', $team->capacity_per_event) }}">
            </label>
        </div>
    </section>

    <div class="flex flex-wrap gap-3">
        <button type="submit" class="btn btn-primary btn-lg w-full sm:w-auto">
            <x-ui.icon name="save" class="size-5"/> حفظ بيانات الفريق
        </button>
        <a href="{{ route('organizer.dashboard') }}" class="btn btn-ghost w-full sm:w-auto">إلغاء</a>
    </div>
</form>

@endsection
