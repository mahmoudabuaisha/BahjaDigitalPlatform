@extends('layouts.app')

@section('title', 'الفعاليات — '.\App\Support\Settings::get('site_name'))
@section('meta_description', 'كل فعاليات الترفيه والدعم النفسي لأطفال غزة — ابحثوا حسب الفئة والمحافظة والعمر.')

@section('content')

<section class="surface-tint">
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6">
        <nav class="flex items-center gap-2 text-sm text-ink-soft" aria-label="مسار التصفح">
            <a href="{{ route('home') }}" class="no-underline hover:text-brand-700">الرئيسية</a>
            <x-ui.icon name="chevron-start" class="size-4"/>
            <span class="text-ink">الفعاليات</span>
        </nav>

        <h1 class="mt-3 text-4xl font-bold">الفعاليات</h1>
        <p class="mt-2 max-w-2xl text-lg text-ink-soft">
            اختاروا الفئة والمحافظة والعمر المناسب لأطفالكم — والنتائج تتحدّث فوراً.
        </p>
    </div>
</section>

<div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 lg:grid-cols-[300px_1fr]">

    {{-- ═══ التصفية ═══ --}}
    <aside x-data="{ open: false, desktop: false, init() { const check = () => this.desktop = window.innerWidth >= 1024; check(); window.addEventListener('resize', check); } }">
        <button type="button" @click="open = ! open" class="btn btn-outline btn-block lg:hidden">
            <x-ui.icon name="funnel" class="size-5"/> التصفية
        </button>

        <form method="GET" action="{{ route('events.index') }}" x-show="open || desktop" x-cloak
              class="mt-3 flex flex-col gap-5 lg:mt-0">

            <div class="card gap-4 p-5">
                <label class="field">
                    <span>البحث</span>
                    <span class="relative block">
                        <x-ui.icon name="search" class="absolute top-1/2 start-4 size-5 -translate-y-1/2 text-brand-400"/>
                        <input type="search" name="q" value="{{ $filters['q'] }}" class="input ps-12" placeholder="اسم الفعالية أو المكان">
                    </span>
                </label>

                <label class="field">
                    <span>المحافظة</span>
                    <select name="area" class="input">
                        <option value="">كل المحافظات</option>
                        @foreach($areas as $area)
                            <option value="{{ $area->slug }}" @selected($filters['area'] === $area->slug)>{{ $area->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="field">
                    <span>الفئة العمرية</span>
                    <select name="age" class="input">
                        <option value="">كل الأعمار</option>
                        @foreach($ageBuckets as $value => $label)
                            <option value="{{ $value }}" @selected($filters['age'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <div class="field">
                    <span>الموعد</span>
                    <div class="flex flex-wrap gap-2">
                        @foreach(['' => 'كل المواعيد', 'today' => 'اليوم', 'week' => 'هذا الأسبوع'] as $value => $label)
                            <label class="chip {{ $filters['when'] === $value ? 'is-active' : '' }}">
                                <input type="radio" name="when" value="{{ $value }}" class="sr-only" @checked($filters['when'] === $value)>
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- الفئة تُمرَّر مع النموذج كي لا تضيع عند البحث --}}
                <input type="hidden" name="cat" value="{{ $filters['cat'] }}">

                <div class="flex gap-2">
                    <button type="submit" class="btn btn-primary flex-1">تطبيق</button>
                    <a href="{{ route('events.index') }}" class="btn btn-outline">مسح</a>
                </div>
            </div>

            <div class="card gap-2 p-5">
                <h2 class="text-base font-bold">الفئات</h2>

                <a href="{{ route('events.index', array_filter(['q' => $filters['q'], 'area' => $filters['area'], 'age' => $filters['age'], 'when' => $filters['when']])) }}"
                   class="flex items-center gap-3 rounded-xl p-2 no-underline transition hover:bg-brand-50 {{ $filters['cat'] === '' ? 'bg-brand-50' : '' }}">
                    <span class="icon-tile tone tone-violet size-10"><x-ui.icon name="grid"/></span>
                    <span class="font-medium">جميع الفئات</span>
                </a>

                @foreach($categories as $category)
                    <a href="{{ route('events.index', array_filter(['cat' => $category->slug, 'q' => $filters['q'], 'area' => $filters['area'], 'age' => $filters['age'], 'when' => $filters['when']])) }}"
                       class="{{ $category->toneClass() }} flex items-center gap-3 rounded-xl p-2 no-underline transition hover:bg-brand-50 {{ $filters['cat'] === $category->slug ? 'bg-brand-50' : '' }}">
                        <span class="icon-tile size-10"><x-ui.icon :name="$category->iconKey()"/></span>
                        <span class="flex-1 font-medium">{{ $category->name }}</span>
                        <span class="text-sm text-ink-soft">{{ $category->events_count }}</span>
                    </a>
                @endforeach
            </div>
        </form>
    </aside>

    {{-- ═══ النتائج ═══ --}}
    <section>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-ink-soft">
                <span class="font-bold text-ink">{{ $events->total() }}</span>
                {{ $events->total() === 1 ? 'فعالية قادمة' : 'فعالية قادمة' }}
                @if($filters['q'] !== '') — نتائج البحث عن «{{ $filters['q'] }}» @endif
            </p>

            @if(array_filter($filters))
                <a href="{{ route('events.index') }}" class="btn btn-ghost btn-sm">مسح التصفية</a>
            @endif
        </div>

        @if($events->isEmpty())
            <div class="mt-6 rounded-3xl border border-dashed border-brand-200 bg-brand-50/50 p-12 text-center">
                <span class="icon-tile tone tone-violet icon-tile-lg mx-auto"><x-ui.icon name="search"/></span>
                <p class="mt-3 text-lg font-bold">لا فعاليات تطابق هذه التصفية</p>
                <p class="mt-1 text-ink-soft">جرّبوا محافظة أخرى أو وسّعوا الفئة العمرية.</p>
                <a href="{{ route('events.index') }}" class="btn btn-primary mt-4">اعرضوا كل الفعاليات</a>
            </div>
        @else
            <div class="mt-5 grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                @foreach($events as $event)
                    <x-event-card :event="$event"/>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $events->links('partials.pagination') }}
            </div>
        @endif
    </section>
</div>

@endsection
