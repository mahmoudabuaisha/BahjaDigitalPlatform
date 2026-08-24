@extends('layouts.organizer')

@section('title', 'تسجيل الحضور — '.\App\Support\Settings::get('site_name'))

@section('organizer')

<nav aria-label="مسار التنقّل" class="flex flex-wrap items-center gap-2 text-sm text-ink-soft">
    <a href="{{ route('organizer.dashboard') }}" class="no-underline hover:text-brand-600">لوحة التحكّم</a>
    <x-ui.icon name="chevron-start" class="size-4"/>
    <a href="{{ route('organizer.events') }}" class="no-underline hover:text-brand-600">فعالياتي</a>
    <x-ui.icon name="chevron-start" class="size-4"/>
    <span class="font-medium text-ink">تسجيل الحضور</span>
</nav>

<h1 class="mt-4 flex items-center gap-3 text-3xl font-bold">
    <span class="icon-tile tone tone-emerald"><x-ui.icon name="users"/></span>
    تسجيل الحضور الفعلي
</h1>
<p class="mt-1 text-ink-soft">{{ $event->title }} — {{ $event->start_date->translatedFormat('l j F Y') }}</p>

<p class="mt-4 flex items-start gap-2 rounded-2xl bg-brand-50 px-4 py-3 text-brand-800">
    <x-ui.icon name="shield-check" class="mt-0.5 size-5 shrink-0"/>
    الأرقام إجمالية فقط — لا تُكتب أسماء أطفال ولا بيانات شخصية.
    تقارير الأثر الرسمية تُبنى على هذه الأرقام بعد تحقق الإدارة.
</p>

@if($report?->verified_at)
    <p class="mt-3 rounded-2xl bg-emerald-50 px-4 py-3 text-emerald-700">
        تحقّقت الإدارة من هذا التقرير — تعديله يعيده إلى طابور التحقق.
    </p>
@endif

<form method="POST" action="{{ route('organizer.events.attendance.store', $event) }}" class="card mt-5 max-w-xl gap-4 p-6">
    @csrf

    <div class="grid gap-4 sm:grid-cols-2">
        <label class="field block">
            <span>عدد الأطفال الحاضرين <b class="text-rose-500">*</b></span>
            <input type="number" name="children_actual" required min="0" max="5000"
                   value="{{ old('children_actual', $report->children_actual ?? '') }}"
                   class="input @error('children_actual') border-rose-300 @enderror">
            @error('children_actual') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </label>

        <label class="field block">
            <span>عدد المرافقين <b class="text-rose-500">*</b></span>
            <input type="number" name="guardians_actual" required min="0" max="5000"
                   value="{{ old('guardians_actual', $report->guardians_actual ?? 0) }}"
                   class="input @error('guardians_actual') border-rose-300 @enderror">
            @error('guardians_actual') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </label>
    </div>

    @if($event->expected_children)
        <p class="text-sm text-ink-soft">كان العدد المتوقّع: {{ $event->expected_children }} طفلاً.</p>
    @endif

    <label class="field block">
        <span>ملاحظة داخلية (لا تُنشر)</span>
        <textarea name="notes_private" maxlength="500" rows="3" class="input"
                  placeholder="ما الذي سار جيداً؟ ما الذي يحتاج تحسيناً؟">{{ old('notes_private', $report->notes_private ?? '') }}</textarea>
    </label>

    <div class="flex flex-wrap gap-3">
        <button type="submit" class="btn btn-primary">
            <x-ui.icon name="check" class="size-5"/> {{ $report ? 'تحديث الحضور' : 'حفظ الحضور' }}
        </button>
        <a href="{{ route('organizer.events') }}" class="btn btn-outline">إلغاء</a>
    </div>
</form>

@endsection
