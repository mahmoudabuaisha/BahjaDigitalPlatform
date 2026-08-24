@extends('layouts.app')

@section('title', 'المنظِّمون — '.\App\Support\Settings::get('site_name'))
@section('meta_description', 'الفرق التطوعية التي تنظّم فعاليات الترفيه والدعم النفسي لأطفال غزة على منصّة بَهْجَة.')

@section('content')

<section class="surface-tint">
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6">
        <nav class="flex items-center gap-2 text-sm text-ink-soft" aria-label="مسار التصفح">
            <a href="{{ route('home') }}" class="no-underline hover:text-brand-700">الرئيسية</a>
            <x-ui.icon name="chevron-start" class="size-4"/>
            <span class="text-ink">المنظِّمون</span>
        </nav>

        <h1 class="mt-3 flex items-center gap-3 text-4xl font-bold">
            <span class="icon-tile tone tone-violet icon-tile-lg"><x-ui.icon name="users"/></span>
            المنظِّمون
        </h1>
        <p class="mt-2 max-w-2xl text-lg text-ink-soft">
            تعرّفوا على الفرق التطوعية التي تنظّم الفعاليات الترفيهية والتعليمية لأطفال غزة —
            كلّها معتمَدة من إدارة المنصّة.
        </p>
    </div>
</section>

<div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 lg:grid-cols-[1fr_320px]">

    <section>
        {{-- البحث والتصفية --}}
        <form method="GET" action="{{ route('organizers') }}" class="card gap-3 p-4 sm:flex-row sm:items-center">
            <label class="field relative flex-1">
                <span class="sr-only">ابحثوا عن فريق</span>
                <x-ui.icon name="search" class="absolute top-1/2 start-4 size-5 -translate-y-1/2 text-brand-400"/>
                <input type="search" name="q" value="{{ $filters['q'] }}" class="input ps-12" placeholder="ابحثوا باسم الفريق">
            </label>

            <label class="field sm:w-56">
                <span class="sr-only">الفئة</span>
                <select name="cat" class="input">
                    <option value="">جميع الفئات</option>
                    @foreach($categoryList as $category)
                        <option value="{{ $category->slug }}" @selected($filters['cat'] === $category->slug)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </label>

            <button type="submit" class="btn btn-primary">بحث</button>
        </form>

        <p class="mt-5 text-ink-soft">
            <span class="font-bold text-ink">{{ $teams->total() }}</span> فريق تطوّعي
        </p>

        @if($teams->isEmpty())
            <div class="mt-4 rounded-3xl border border-dashed border-brand-200 bg-brand-50/50 p-12 text-center">
                <span class="icon-tile tone tone-violet icon-tile-lg mx-auto"><x-ui.icon name="users"/></span>
                <p class="mt-3 text-lg font-bold">لا فرق مطابقة</p>
                <a href="{{ route('organizers') }}" class="btn btn-primary mt-4">اعرضوا كل الفرق</a>
            </div>
        @else
            <div class="mt-4 grid gap-5 sm:grid-cols-2">
                @foreach($teams as $team)
                    @php
                        $category = $categories->get($mainCategories[$team->id] ?? null);
                        $rating = $ratings[$team->id] ?? null;
                    @endphp

                    <article class="card card-hover {{ $category?->toneClass() ?? 'tone tone-violet' }} gap-4 p-6">
                        <div class="flex items-start gap-4">
                            @if($team->logo_path)
                                <img src="{{ $team->logoUrl() }}" alt="{{ $team->name }}" width="64" height="64"
                                     class="size-16 shrink-0 rounded-2xl object-cover">
                            @else
                                <span class="icon-tile icon-tile-lg">
                                    <x-ui.icon :name="$category?->iconKey() ?? 'sparkles'"/>
                                </span>
                            @endif

                            <div class="min-w-0 flex-1">
                                <h2 class="truncate text-lg font-bold">
                                    <a href="{{ route('teams.show', $team) }}" class="no-underline hover:text-brand-700">{{ $team->name }}</a>
                                </h2>

                                <div class="mt-1 flex flex-wrap items-center gap-2">
                                    @if($category)
                                        <span class="badge badge-tone">{{ $category->name }}</span>
                                    @endif

                                    @if($rating)
                                        <span class="badge">
                                            <x-ui.icon name="star" class="size-4 text-amber-400"/>
                                            {{ number_format($rating, 1) }}
                                        </span>
                                    @endif

                                    <span class="badge">{{ $team->completed_count }} فعالية منفَّذة</span>
                                </div>
                            </div>
                        </div>

                        @if($team->description)
                            <p class="line-clamp-3 leading-relaxed text-ink-soft">{{ $team->description }}</p>
                        @endif

                        <div class="mt-auto flex items-center justify-between gap-3 border-t border-brand-100 pt-4">
                            <span class="text-sm text-ink-soft">
                                @if($team->upcoming_count)
                                    {{ $team->upcoming_count }} فعالية قادمة
                                @else
                                    لا فعاليات قادمة حالياً
                                @endif
                            </span>
                            <a href="{{ route('teams.show', $team) }}" class="btn btn-outline btn-sm">عرض الفعاليات</a>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $teams->links('partials.pagination') }}
            </div>
        @endif
    </section>

    {{-- ═══ لماذا تنضمّون ═══ --}}
    <aside class="flex flex-col gap-5">
        <div class="card gap-4 p-6">
            <h2 class="text-lg font-bold">لماذا تنضمّون إلى بَهْجَة؟</h2>

            @foreach([
                ['icon' => 'megaphone', 'tone' => 'tone-rose', 'title' => 'وصول أوسع', 'text' => 'فعالياتكم تصل لآلاف العائلات عبر واتساب وكروت QR في المراكز.'],
                ['icon' => 'grid', 'tone' => 'tone-sky', 'title' => 'إدارة سهلة', 'text' => 'لوحة بسيطة ترفعون منها الجدول وتسجّلون الحضور الفعلي.'],
                ['icon' => 'shield-check', 'tone' => 'tone-emerald', 'title' => 'ثقة الأهالي', 'text' => 'كل فعالية معتمَدة من الإدارة قبل نشرها، والرفض يصلكم بسببه مكتوباً.'],
                ['icon' => 'bolt', 'tone' => 'tone-amber', 'title' => 'تقارير أثر', 'text' => 'أرقام حضوركم تُجمع تلقائياً في تقارير تصلح للجهات الداعمة.'],
            ] as $reason)
                <div class="tone {{ $reason['tone'] }} flex items-start gap-3">
                    <span class="icon-tile"><x-ui.icon :name="$reason['icon']"/></span>
                    <div>
                        <h3 class="font-bold">{{ $reason['title'] }}</h3>
                        <p class="text-sm leading-relaxed text-ink-soft">{{ $reason['text'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="rounded-3xl bg-gradient-to-l from-brand-700 to-brand-500 p-6 text-center text-white">
            <h2 class="text-xl font-bold text-white">فريقكم يستحقّ أن يُرى</h2>
            <p class="mt-2 text-sm leading-relaxed text-brand-50">
                سجّلوا فريقكم اليوم، وابدؤوا برفع فعالياتكم بعد اعتماد الإدارة.
            </p>
            <a href="{{ route('teams.join') }}" class="btn mt-4 bg-white text-brand-700 hover:bg-brand-50">سجّلوا الآن كمنظِّم</a>
        </div>
    </aside>
</div>

@endsection
