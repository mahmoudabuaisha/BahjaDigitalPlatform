@extends('layouts.app')

@section('title', $team->name.' — '.\App\Support\Settings::get('site_name'))
@section('meta_description', \Illuminate\Support\Str::limit($team->description ?? 'فريق ترفيهي تطوعي لأطفال غزة', 150))

@section('og')
    @include('partials.og', [
        'ogTitle' => $team->name.' — صنّاع فرح',
        'ogDescription' => \Illuminate\Support\Str::limit($team->description ?? 'فريق ترفيهي تطوعي يقدم فعاليات لأطفال غزة', 120),
        'ogImage' => $team->logo_path,
    ])
@endsection

@section('content')

<section class="surface-tint">
    <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6">
        <nav class="flex items-center gap-2 text-sm text-ink-soft" aria-label="مسار التصفح">
            <a href="{{ route('home') }}" class="no-underline hover:text-brand-700">الرئيسية</a>
            <x-ui.icon name="chevron-start" class="size-4"/>
            <a href="{{ route('organizers') }}" class="no-underline hover:text-brand-700">المنظِّمون</a>
            <x-ui.icon name="chevron-start" class="size-4"/>
            <span class="text-ink">{{ $team->name }}</span>
        </nav>

        <div class="mt-5 flex flex-wrap items-center gap-5 tone tone-violet">
            @if($team->logo_path)
                <img src="{{ $team->logoUrl() }}" alt="{{ $team->name }}" width="96" height="96"
                     class="size-24 rounded-3xl object-cover shadow-lg">
            @else
                <span class="icon-tile size-24 rounded-3xl"><x-ui.icon name="users" class="size-12"/></span>
            @endif

            <div class="min-w-0 flex-1">
                <span class="badge"><x-ui.icon name="shield-check" class="size-4 text-emerald-500"/> فريق معتمَد من الإدارة</span>
                <h1 class="mt-2 text-3xl font-bold sm:text-4xl">{{ $team->name }}</h1>
                @if($team->description)
                    <p class="mt-2 max-w-2xl leading-relaxed text-ink-soft">{{ $team->description }}</p>
                @endif
            </div>
        </div>

        <div class="mt-6 grid max-w-2xl grid-cols-2 gap-3 sm:grid-cols-3">
            <div class="rounded-2xl bg-white p-4 text-center shadow-sm">
                <p class="text-2xl font-bold text-brand-700">{{ number_format($completedCount) }}</p>
                <p class="text-sm text-ink-soft">فعالية منفَّذة</p>
            </div>
            <div class="rounded-2xl bg-white p-4 text-center shadow-sm">
                <p class="text-2xl font-bold text-brand-700">{{ number_format($childrenReached) }}</p>
                <p class="text-sm text-ink-soft">طفل حضر فعلياً</p>
            </div>
            @if($averageRating)
                <div class="rounded-2xl bg-white p-4 text-center shadow-sm">
                    <p class="flex items-center justify-center gap-1 text-2xl font-bold text-brand-700">
                        <x-ui.icon name="star" class="size-5 text-amber-400"/> {{ number_format($averageRating, 1) }}
                    </p>
                    <p class="text-sm text-ink-soft">تقييم العائلات</p>
                </div>
            @endif
        </div>

        @if($team->whatsappUrl())
            <a href="{{ $team->whatsappUrl() }}" target="_blank" rel="noopener" class="btn btn-outline mt-5">
                <x-ui.icon name="whatsapp" class="size-5 text-emerald-600"/> تواصلوا مع الفريق
            </a>
        @endif
    </div>
</section>

<section class="mx-auto max-w-7xl px-4 py-12 sm:px-6">
    <h2 class="section-title">
        <span class="icon-tile tone tone-amber size-11"><x-ui.icon name="calendar"/></span>
        الفعاليات القادمة للفريق
    </h2>

    @if($upcoming->isEmpty())
        <div class="mt-5 rounded-3xl border border-dashed border-brand-200 bg-brand-50/50 p-12 text-center">
            <p class="text-lg font-bold">لا فعاليات قادمة معلنة حالياً</p>
            <p class="mt-1 text-ink-soft">تابعوا الروزنامة — الجدول يتحدّث باستمرار.</p>
            <a href="{{ route('events.index') }}" class="btn btn-primary mt-4">تصفّحوا كل الفعاليات</a>
        </div>
    @else
        <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($upcoming as $event)
                <x-event-card :event="$event"/>
            @endforeach
        </div>
    @endif
</section>

@endsection
