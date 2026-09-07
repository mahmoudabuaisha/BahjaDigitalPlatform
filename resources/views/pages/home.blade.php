@extends('layouts.app')

@section('content')

{{-- ═══ البطل ═══ --}}
<section class="surface-tint relative overflow-hidden">
    <div class="mx-auto grid max-w-7xl items-center gap-10 px-4 py-14 sm:px-6 lg:grid-cols-[1.05fr_.95fr] lg:py-20">
        <div>
            <span class="badge bg-white shadow-sm">
                <x-ui.icon name="sparkles" class="size-4 text-brand-500"/> رسالة بَهْجَة
            </span>

            <h1 class="mt-4 text-[1.9rem] leading-[1.3] font-bold sm:text-4xl sm:leading-[1.25] lg:text-5xl">
                اكتشفوا وشاركوا في
                <span class="text-joy-gradient">فعاليات ممتعة وآمنة</span>
                لأطفالكم
            </h1>

            <p class="mt-4 max-w-xl text-lg leading-relaxed text-ink-soft">
                كل لحظة لعب هي فرصة جديدة للتعلّم والنموّ. نجمع فعاليات الترفيه والدعم النفسي
                في المحافظات الخمس، ونعرضها لكم يوماً بيوم — وتعمل حتى حين تضعف الشبكة.
            </p>

            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('events.index') }}" class="btn btn-primary w-full sm:w-auto">
                    <x-ui.icon name="calendar" class="size-5"/> تصفّحوا الفعاليات
                </a>
                <a href="{{ route('teams.join') }}"
                   class="btn w-full bg-pink-600 text-white shadow-lg shadow-pink-600/25 hover:bg-pink-700 sm:w-auto">
                    <x-ui.icon name="users" class="size-5"/> سجّلوا فريقكم أو مؤسستكم
                </a>
            </div>

            <dl class="mt-8 grid max-w-lg grid-cols-2 gap-4 sm:grid-cols-4">
                @foreach([
                    ['value' => $stats['upcoming'], 'label' => 'فعالية قادمة'],
                    ['value' => $stats['completed'], 'label' => 'فعالية منفَّذة'],
                    ['value' => $stats['children'], 'label' => 'طفل حضر'],
                    ['value' => $stats['teams'], 'label' => 'فريق تطوّعي'],
                ] as $stat)
                    <div class="rounded-2xl bg-white/70 p-3 text-center shadow-sm">
                        <dt class="text-2xl font-bold text-brand-700">{{ number_format($stat['value']) }}</dt>
                        <dd class="text-sm text-ink-soft">{{ $stat['label'] }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>

        {{-- لوحة زخرفية: بطاقة فعالية مصغّرة فوق أشكال ملوّنة --}}
        <div class="relative hidden lg:block" aria-hidden="true">
            <div class="absolute -top-6 start-6 size-28 rounded-3xl bg-brand-200/70 blur-xl"></div>
            <div class="absolute bottom-0 end-10 size-36 rounded-full bg-pink-200/60 blur-2xl"></div>

            <div class="relative mx-auto mb-14 max-w-sm rotate-2 rounded-3xl bg-white p-5 shadow-[0_20px_60px_rgb(93_60_190_/_18%)]">
                <x-ui.scene name="games" tone="#f59e0b" class="h-40 w-full rounded-2xl"/>
                <p class="mt-4 text-lg font-bold">يوم ألعاب في ساحة المركز</p>
                <p class="mt-1 flex items-center gap-2 text-sm text-ink-soft">
                    <x-ui.icon name="map-pin" class="size-4 text-brand-400"/> مركز الإيواء — الساحة الشمالية
                </p>
                <div class="mt-4 flex items-center justify-between">
                    <span class="badge tone tone-amber badge-tone"><x-ui.icon name="cake" class="size-4"/> من 4 إلى 10 سنوات</span>
                    <span class="btn btn-primary btn-sm">عرض التفاصيل</span>
                </div>
            </div>

            <div class="absolute bottom-0 start-0 flex -rotate-3 items-center gap-2 rounded-2xl bg-white px-4 py-3 shadow-lg">
                <span class="icon-tile tone tone-emerald size-10"><x-ui.icon name="shield-check"/></span>
                <span class="text-sm font-bold">كل فعالية معتمَدة من الإدارة</span>
            </div>
        </div>
    </div>
</section>

{{-- ═══ شريط البحث ═══ --}}
<section class="mx-auto -mt-8 max-w-7xl px-4 sm:px-6">
    <form method="GET" action="{{ route('events.index') }}"
          class="grid gap-3 rounded-3xl border border-brand-100 bg-white p-4 shadow-[0_10px_40px_rgb(31_25_55_/_8%)] md:grid-cols-[1.4fr_1fr_1fr_auto]">
        <label class="field">
            <span class="sr-only">ابحثوا عن فعالية</span>
            <span class="relative block">
                <x-ui.icon name="search" class="absolute top-1/2 start-4 size-5 -translate-y-1/2 text-brand-400"/>
                <input type="search" name="q" class="input ps-12" placeholder="ابحثوا باسم الفعالية أو المكان">
            </span>
        </label>

        <label class="field">
            <span class="sr-only">الفئة</span>
            <select name="cat" class="input">
                <option value="">جميع الفئات</option>
                @foreach($categories as $category)
                    <option value="{{ $category->slug }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </label>

        <label class="field">
            <span class="sr-only">المحافظة</span>
            <select name="area" class="input">
                <option value="">كل المحافظات</option>
                @foreach($areas as $area)
                    <option value="{{ $area->slug }}">{{ $area->name }}</option>
                @endforeach
            </select>
        </label>

        <button type="submit" class="btn btn-primary md:min-w-32">
            <x-ui.icon name="search" class="size-5"/> بحث
        </button>
    </form>
</section>

{{-- ═══ الفئات ═══ --}}
<section class="mx-auto max-w-7xl px-4 py-14 sm:px-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="section-title">
                <span class="icon-tile tone tone-violet size-11"><x-ui.icon name="grid"/></span>
                الفئات
            </h2>
            <p class="mt-1 text-ink-soft">اختاروا نوع النشاط الذي يحبّه أطفالكم.</p>
        </div>
        <a href="{{ route('events.index') }}" class="btn btn-ghost btn-sm">كل الفعاليات ←</a>
    </div>

    <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
        <a href="{{ route('events.index') }}" class="card card-hover tone tone-violet items-center gap-3 overflow-hidden p-5 text-center no-underline">
            <x-ui.scene name="default" tone="#3b93e4" class="-mx-5 -mt-5 h-24 w-[calc(100%+2.5rem)]"/>
            <span class="font-bold">جميع الفئات</span>
            <span class="text-sm text-ink-soft">{{ $stats['upcoming'] }} فعالية قادمة</span>
        </a>

        @foreach($categories as $category)
            <a href="{{ route('events.index', ['cat' => $category->slug]) }}"
               class="card card-hover {{ $category->toneClass() }} items-center gap-3 overflow-hidden p-5 text-center no-underline">
                <x-ui.scene :name="$category->slug" :tone="$category->toneHex()" class="-mx-5 -mt-5 h-24 w-[calc(100%+2.5rem)]"/>
                <span class="font-bold">{{ $category->name }}</span>
                <span class="text-sm text-ink-soft">
                    {{ $category->events_count }} {{ $category->events_count === 1 ? 'فعالية قادمة' : 'فعاليات قادمة' }}
                </span>
            </a>
        @endforeach
    </div>
</section>

{{-- ═══ الفعاليات القادمة ═══ --}}
<section class="surface-tint py-14">
    <div class="mx-auto max-w-7xl px-4 sm:px-6">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="section-title">
                    <span class="icon-tile tone tone-amber size-11"><x-ui.icon name="calendar"/></span>
                    الفعاليات القادمة
                </h2>
                <p class="mt-1 text-ink-soft">أقرب المواعيد في المحافظات الخمس.</p>
            </div>
            <a href="{{ route('events.index') }}" class="btn btn-outline btn-sm">عرض الكل</a>
        </div>

        @if($upcoming->isEmpty())
            <div class="mt-6 rounded-3xl border border-dashed border-brand-200 bg-white/70 p-10 text-center">
                <span class="icon-tile tone tone-violet icon-tile-lg mx-auto"><x-ui.icon name="calendar"/></span>
                <p class="mt-3 text-lg font-bold">لا فعاليات منشورة حالياً</p>
                <p class="mt-1 text-ink-soft">الفرق التطوعية ترفع جداولها باستمرار — عودوا قريباً.</p>
            </div>
        @else
            <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($upcoming as $event)
                    <x-event-card :event="$event"/>
                @endforeach
            </div>
        @endif
    </div>
</section>

{{-- ═══ لماذا بَهْجَة ═══ --}}
<section class="mx-auto max-w-7xl px-4 py-14 sm:px-6">
    <h2 class="section-title justify-center text-center">لماذا بَهْجَة؟</h2>
    <p class="mx-auto mt-1 max-w-xl text-center text-ink-soft">
        منصّة واحدة تجمع الفرق والعائلات، بأبسط طريق ممكن.
    </p>

    <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
        @foreach([
            ['icon' => 'sparkles', 'tone' => 'tone-violet', 'title' => 'فعاليات للأطفال', 'text' => 'ترفيه ودعم نفسي واحتفالات، مصمّمة لأعمار الأطفال في مراكز الإيواء.'],
            ['icon' => 'grid', 'tone' => 'tone-amber', 'title' => 'فعاليات متنوّعة', 'text' => 'ألعاب ورسم ومسرح ورياضة وحكايات — لكل طفل ما يحبّه.'],
            ['icon' => 'megaphone', 'tone' => 'tone-rose', 'title' => 'إعلانات فورية', 'text' => 'الجدول يصل عبر واتساب وكروت QR في المراكز، ويُحفظ على الهاتف.'],
            ['icon' => 'shield-check', 'tone' => 'tone-emerald', 'title' => 'آمن وموثوق', 'text' => 'كل فعالية تمرّ على اعتماد الإدارة قبل أن تظهر للعائلات.'],
        ] as $feature)
            <div class="card tone {{ $feature['tone'] }} gap-3 p-6">
                <span class="icon-tile icon-tile-lg"><x-ui.icon :name="$feature['icon']"/></span>
                <h3 class="text-lg font-bold">{{ $feature['title'] }}</h3>
                <p class="leading-relaxed text-ink-soft">{{ $feature['text'] }}</p>
            </div>
        @endforeach
    </div>
</section>

{{-- ═══ دعوة الفرق ═══ --}}
<section class="mx-auto max-w-7xl px-4 pb-16 sm:px-6">
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-l from-brand-600 to-pink-500 px-6 py-12 text-center text-white sm:px-12">
        <div class="absolute -top-10 -start-10 size-40 rounded-full bg-white/10"></div>
        <div class="absolute -bottom-12 end-0 size-52 rounded-full bg-white/10"></div>

        <h2 class="relative text-3xl font-bold text-white">عندكم فريق ترفيهي؟</h2>
        <p class="relative mx-auto mt-3 max-w-xl leading-relaxed text-brand-50">
            سجّلوا فريقكم وارفعوا جدول فعالياتكم بأنفسكم — بعد اعتماد الإدارة تصل فعالياتكم لآلاف العائلات.
        </p>
        <div class="relative mt-6 flex flex-wrap justify-center gap-3">
            <a href="{{ route('teams.join') }}" class="btn w-full bg-white text-brand-700 hover:bg-brand-50 sm:w-auto">سجّلوا فريقكم الآن</a>
            <a href="{{ route('organizers') }}" class="btn w-full border-white/50 text-white hover:bg-white/10 sm:w-auto">تعرّفوا على المنظِّمين</a>
        </div>
    </div>
</section>

@endsection
