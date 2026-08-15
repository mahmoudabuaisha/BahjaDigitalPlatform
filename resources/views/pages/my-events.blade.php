@extends('layouts.account')

@section('title', 'فعالياتي — '.\App\Support\Settings::get('site_name'))

@section('account')

<h1 class="text-3xl font-bold">الفعاليات المسجَّل بها</h1>
<p class="mt-1 text-ink-soft">كل الفعاليات التي حجزتم فيها مقاعد لأطفالكم.</p>

<p class="mt-4 flex items-start gap-2 rounded-2xl bg-brand-50 px-4 py-3 text-brand-800">
    <x-ui.icon name="clock" class="mt-0.5 size-5 shrink-0"/>
    يمكنكم إلغاء الحجز حتى 24 ساعة قبل موعد الفعالية.
</p>

@if(session('registration_cancelled'))
    <p class="mt-3 rounded-2xl bg-brand-50 px-4 py-3 text-brand-700">أُلغي الحجز.</p>
@endif

@if(session('registration_error'))
    <p class="mt-3 rounded-2xl bg-rose-50 px-4 py-3 text-rose-700">{{ session('registration_error') }}</p>
@endif

@if($upcoming->isEmpty() && $past->isEmpty())
    <div class="mt-6 rounded-3xl border border-dashed border-brand-200 bg-brand-50/50 p-12 text-center">
        <span class="icon-tile tone tone-violet icon-tile-lg mx-auto"><x-ui.icon name="calendar"/></span>
        <p class="mt-3 text-lg font-bold">لا تجدون فعالية حجزتم فيها؟</p>
        <p class="mt-1 text-ink-soft">قد لا يكون الحجز اكتمل، أو تم إلغاؤه.</p>
        <a href="{{ route('events.index') }}" class="btn btn-primary mt-4">
            <x-ui.icon name="calendar" class="size-5"/> استعراض الفعاليات
        </a>
    </div>
@endif

@if($upcoming->isNotEmpty())
    <section class="mt-6">
        <h2 class="text-xl font-bold">الحجوزات القادمة</h2>
        <div class="mt-3 flex flex-col gap-3">
            @foreach($upcoming as $registration)
                <x-registration-row :registration="$registration"/>
            @endforeach
        </div>
    </section>
@endif

@if($past->isNotEmpty())
    <section class="mt-8">
        <h2 class="text-xl font-bold">فعاليات سابقة</h2>
        <div class="mt-3 flex flex-col gap-3">
            @foreach($past as $registration)
                <x-registration-row :registration="$registration" class="opacity-90"/>
            @endforeach
        </div>
    </section>
@endif

@if($upcoming->isNotEmpty() || $past->isNotEmpty())
    <div class="mt-8 flex flex-wrap items-center justify-between gap-3 rounded-3xl bg-brand-50/60 p-6">
        <div>
            <p class="font-bold">تريدون فعالية أخرى لأطفالكم؟</p>
            <p class="text-sm text-ink-soft">الجدول يتحدّث باستمرار في المحافظات الخمس.</p>
        </div>
        <a href="{{ route('events.index') }}" class="btn btn-primary">
            <x-ui.icon name="calendar" class="size-5"/> استعراض الفعاليات
        </a>
    </div>
@endif

@endsection
