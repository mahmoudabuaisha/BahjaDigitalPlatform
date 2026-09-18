@extends('layouts.app')

@section('title', 'نشرة بَهْجَة البريدية — '.\App\Support\Settings::get('site_name'))
@section('meta_description', 'اشتركوا في نشرة بَهْجَة لتصلكم كل أسبوع فعاليات الأطفال القادمة في محافظتكم أو في كل غزة.')

@section('content')
<section class="surface-tint">
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6">
        <nav class="flex items-center gap-2 text-sm text-ink-soft" aria-label="مسار التصفح">
            <a href="{{ route('home') }}" class="no-underline hover:text-brand-700">الرئيسية</a>
            <x-ui.icon name="chevron-start" class="size-4"/>
            <span class="text-ink">النشرة البريدية</span>
        </nav>
        <h1 class="mt-3 text-4xl font-bold">نشرة بَهْجَة البريدية</h1>
        <p class="mt-2 max-w-2xl text-lg text-ink-soft">
            رسالة واحدة كل أسبوع فيها فعاليات الأيام السبعة القادمة، في محافظتكم أو في كل غزة.
            تصلكم وأنتم على الشبكة، وتبقى في بريدكم حين تنقطع.
        </p>
    </div>
</section>

<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6">
    {{-- ═══ الاشتراك ═══ --}}
    <section id="newsletter" class="card scroll-mt-28 gap-6 p-6 sm:p-8 lg:grid lg:grid-cols-[1fr_1.3fr] lg:items-center lg:gap-10">
        <div>
            <h2 class="section-title">
                <span class="icon-tile tone tone-violet size-11"><x-ui.icon name="envelope"/></span>
                اشتركوا الآن
            </h2>
            <ul class="mt-4 flex flex-col gap-2.5 text-ink-soft">
                <li class="flex items-start gap-2"><x-ui.icon name="check" class="mt-1 size-5 shrink-0 text-emerald-600"/> فعاليات الأسبوع مرتّبة باليوم: الموعد والمكان والفئة العمرية.</li>
                <li class="flex items-start gap-2"><x-ui.icon name="check" class="mt-1 size-5 shrink-0 text-emerald-600"/> اختاروا محافظتكم لتصلكم فعالياتها وحدها، أو اتركوها لكل غزة.</li>
                <li class="flex items-start gap-2"><x-ui.icon name="check" class="mt-1 size-5 shrink-0 text-emerald-600"/> بلا حساب ولا كلمة مرور: بريدكم فقط، وتأكيد بضغطة واحدة.</li>
            </ul>
        </div>
        @include('partials.newsletter-form', ['formId' => 'page', 'dark' => false, 'areas' => $areas])
    </section>

    {{-- ═══ نشرة هذا الأسبوع كما ستصل المشتركين ═══ --}}
    <section class="mt-12">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="section-title">
                    <span class="icon-tile tone tone-amber size-11"><x-ui.icon name="calendar"/></span>
                    نشرة هذا الأسبوع
                </h2>
                <p class="mt-1 text-ink-soft">هذا ما يصل المشتركين: فعاليات الأيام السبعة القادمة في كل المحافظات.</p>
            </div>
            <a href="{{ route('events.index') }}" class="btn btn-outline btn-sm">كل الفعاليات</a>
        </div>

        @forelse($days as $date => $dayEvents)
            <h3 class="mt-8 flex items-center gap-2 text-xl font-bold">
                <x-ui.icon name="calendar" class="size-5 text-brand-500"/>
                {{ \Illuminate\Support\Carbon::parse($date)->translatedFormat('l j F') }}
            </h3>
            <div class="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($dayEvents as $event)
                    <x-event-card :event="$event"/>
                @endforeach
            </div>
        @empty
            <div class="mt-6 rounded-3xl border border-dashed border-brand-200 bg-white/70 p-10 text-center">
                <span class="icon-tile tone tone-violet icon-tile-lg mx-auto"><x-ui.icon name="calendar"/></span>
                <p class="mt-3 text-lg font-bold">لا فعاليات منشورة للأسبوع القادم بعد</p>
                <p class="mt-1 text-ink-soft">اشتركوا الآن، وحين تنشر الفرق جداولها تصلكم في النشرة.</p>
            </div>
        @endforelse
    </section>
</div>
@endsection
