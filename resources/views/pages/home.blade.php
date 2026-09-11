@extends('layouts.app')

@section('content')

{{-- ═══ البطل ═══ --}}
<section class="surface-tint relative overflow-hidden">
    <div class="mx-auto grid max-w-7xl items-center gap-10 px-4 py-14 sm:px-6 lg:grid-cols-[1.05fr_.95fr] lg:py-20">
        <div>
            {{-- افتتاحية البطل: العناصر تدخل تباعاً حسب --stagger --}}
            <span class="badge hero-enter bg-white shadow-sm">
                <x-ui.icon name="sparkles" class="size-4 text-brand-500"/> رسالة بَهْجَة
            </span>

            <h1 class="hero-enter mt-4 text-[1.9rem] leading-[1.3] font-bold sm:text-4xl sm:leading-[1.25] lg:text-5xl" style="--stagger: 110">
                اكتشفوا وشاركوا في
                <span class="text-joy-gradient">فعاليات ممتعة وآمنة</span>
                لأطفالكم
            </h1>

            <p class="hero-enter mt-4 max-w-xl text-lg leading-relaxed text-ink-soft" style="--stagger: 220">
                كل لحظة لعب هي فرصة جديدة للتعلّم والنموّ. نجمع فعاليات الترفيه والدعم النفسي
                في المحافظات الخمس، ونعرضها لكم يوماً بيوم — وتعمل حتى حين تضعف الشبكة.
            </p>

            {{-- هرمية واضحة: فعل العائلات (تصفّح الفعاليات) هو الأهم فيتصدّر ويكبر،
                 وتسجيل الفرق فعل ثانوي بحضور وردي هادئ لا ينافسه --}}
            <div class="hero-enter mt-7 flex flex-col gap-3 sm:flex-row sm:items-center" style="--stagger: 330">
                <a href="{{ route('events.index') }}"
                   class="btn btn-primary btn-lg w-full hover:-translate-y-0.5 sm:w-auto">
                    <x-ui.icon name="calendar" class="size-5"/> تصفّحوا الفعاليات
                </a>
                <a href="{{ route('teams.join') }}" class="btn btn-outline-pink w-full sm:w-auto">
                    <x-ui.icon name="users" class="size-5"/> سجّلوا فريقكم أو مؤسستكم
                </a>
            </div>

            <dl class="hero-enter mt-8 grid max-w-lg grid-cols-2 gap-4 sm:grid-cols-4" style="--stagger: 440">
                @foreach([
                    ['value' => $stats['upcoming'], 'label' => 'فعالية قادمة'],
                    ['value' => $stats['completed'], 'label' => 'فعالية منفَّذة'],
                    ['value' => $stats['children'], 'label' => 'طفل حضر'],
                    ['value' => $stats['teams'], 'label' => 'فريق تطوّعي'],
                ] as $stat)
                    <div class="rounded-2xl border border-white/70 bg-white/90 p-3 text-center shadow-sm">
                        {{-- عدّاد متصاعد حتى الهدف — يبدأ عند الظهور وبتتابع بين البطاقات --}}
                        <dt x-data="countUp({{ (int) $stat['value'] }}, {{ $loop->index * 180 }})"
                            :class="done && 'stat-pop'" x-text="display"
                            class="text-[1.7rem] leading-tight font-extrabold text-brand-800 tabular-nums">{{ number_format($stat['value']) }}</dt>
                        <dd class="mt-0.5 text-[15px] font-semibold text-ink">{{ $stat['label'] }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>

        {{-- لوحة البطل الحية: أقرب فعالية قادمة حقيقية وزرها يفتح صفحتها —
             وإن لم توجد فعاليات قادمة تظهر بطاقة تعريفية تقود لكل الفعاليات --}}
        @php $heroEvent = $upcoming->first(); @endphp
        <div class="hero-enter relative hidden lg:block" style="--stagger: 260">
            <div class="absolute -top-6 start-6 size-28 rounded-3xl bg-brand-200/70 blur-xl" aria-hidden="true"></div>
            <div class="absolute bottom-0 end-10 size-36 rounded-full bg-pink-200/60 blur-2xl" aria-hidden="true"></div>

            <div class="float-slow relative mx-auto mb-14 max-w-sm rotate-2 rounded-3xl bg-white p-5 shadow-[0_20px_60px_rgb(93_60_190_/_18%)]">
                @if($heroEvent)
                    @if($heroImage = $heroEvent->imageCardUrl())
                        <img src="{{ $heroImage }}" alt="" class="h-40 w-full rounded-2xl object-cover">
                    @else
                        <x-ui.scene :name="$heroEvent->category?->sceneName() ?? 'default'"
                                    :tone="$heroEvent->category?->toneHex() ?? '#3b93e4'" class="h-40 w-full rounded-2xl"/>
                    @endif
                    <p class="mt-4 text-lg font-bold">
                        <a href="{{ route('events.show', $heroEvent) }}" class="no-underline hover:text-brand-700">{{ $heroEvent->title }}</a>
                    </p>
                    <p class="mt-1 flex items-center gap-2 text-sm text-ink-soft">
                        <x-ui.icon name="calendar" class="size-4 shrink-0 text-brand-400"/>
                        {{ $heroEvent->start_date->translatedFormat('l j F') }} — {{ substr($heroEvent->start_time, 0, 5) }}
                    </p>
                    @if($heroPlace = $heroEvent->publicPlaceName())
                        <p class="mt-1 flex items-center gap-2 text-sm text-ink-soft">
                            <x-ui.icon name="map-pin" class="size-4 shrink-0 text-brand-400"/>
                            <span class="line-clamp-1">{{ $heroPlace }}</span>
                        </p>
                    @endif
                    <div class="mt-4 flex items-center justify-between gap-2">
                        @if($heroEvent->ageLabel())
                            <span class="badge badge-tone {{ $heroEvent->category?->toneClass() ?? 'tone tone-amber' }}">
                                <x-ui.icon name="cake" class="size-4"/> {{ $heroEvent->ageLabel() }}
                            </span>
                        @else
                            <span class="badge tone tone-amber badge-tone"><x-ui.icon name="sparkles" class="size-4"/> لكل الأعمار</span>
                        @endif
                        <a href="{{ route('events.show', $heroEvent) }}" class="btn btn-primary btn-sm">عرض التفاصيل</a>
                    </div>
                @else
                    <x-ui.scene name="games" tone="#f59e0b" class="h-40 w-full rounded-2xl"/>
                    <p class="mt-4 text-lg font-bold">فعاليات جديدة تُنشر تباعاً</p>
                    <p class="mt-1 flex items-center gap-2 text-sm text-ink-soft">
                        <x-ui.icon name="map-pin" class="size-4 text-brand-400"/> في المحافظات الخمس ومراكز الإيواء
                    </p>
                    <div class="mt-4 flex items-center justify-between">
                        <span class="badge tone tone-amber badge-tone"><x-ui.icon name="cake" class="size-4"/> لكل الأعمار</span>
                        <a href="{{ route('events.index') }}" class="btn btn-primary btn-sm">عرض التفاصيل</a>
                    </div>
                @endif
            </div>

            <a href="{{ route('about') }}"
               class="float-slow-2 absolute bottom-0 start-0 flex -rotate-3 items-center gap-2 rounded-2xl bg-white px-4 py-3 shadow-lg no-underline transition-shadow hover:shadow-xl">
                <span class="icon-tile tone tone-emerald size-10"><x-ui.icon name="shield-check"/></span>
                <span class="text-sm font-bold text-ink">كل فعالية معتمَدة من الإدارة</span>
            </a>
        </div>
    </div>
</section>

{{-- ═══ شريط البحث: لوحة بارزة بعناوين ظاهرة وحقول متساوية وزر بمحاذاتها ═══ --}}
<section class="relative z-10 mx-auto mt-8 max-w-7xl px-4 sm:px-6">
    <form method="GET" action="{{ route('events.index') }}"
          class="grid items-end gap-4 rounded-[1.75rem] border border-brand-100 bg-white p-5 shadow-[0_18px_50px_rgb(31_37_57_/_10%)] sm:grid-cols-2 md:grid-cols-[1.4fr_1fr_1fr_auto] sm:p-6">
        <label class="field">
            <span class="!font-bold !text-ink">ابحثوا عن فعالية</span>
            <span class="relative !mb-0 block">
                <x-ui.icon name="search" class="absolute top-1/2 start-4 size-5 -translate-y-1/2 text-brand-400"/>
                <input type="search" name="q" class="input ps-12" placeholder="اسم الفعالية أو المكان">
            </span>
        </label>

        <label class="field">
            <span class="!font-bold !text-ink">الفئة</span>
            <select name="cat" class="input">
                <option value="">جميع الفئات</option>
                @foreach($categories as $category)
                    <option value="{{ $category->slug }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </label>

        <label class="field">
            <span class="!font-bold !text-ink">المحافظة</span>
            <select name="area" class="input">
                <option value="">كل المحافظات</option>
                @foreach($areas as $area)
                    <option value="{{ $area->slug }}">{{ $area->name }}</option>
                @endforeach
            </select>
        </label>

        <button type="submit" class="btn btn-primary min-h-[50px] w-full sm:col-span-2 md:col-span-1 md:w-auto md:min-w-32">
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

    <div class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 lg:gap-5 xl:grid-cols-5">
        <a href="{{ route('events.index') }}" class="cat-card cat-card-all group tone">
            <span class="cat-card-media">
                <x-ui.scene name="default" tone="#3b93e4" class="aspect-[8/5] h-auto w-full transition-transform duration-500 group-hover:scale-110"/>
            </span>
            <span class="cat-card-chip"><x-ui.icon name="grid"/></span>
            <span class="px-3 pt-2.5 pb-5 text-center">
                <span class="block text-lg font-extrabold text-white">جميع الفئات</span>
                <span class="cat-card-count mt-2.5">{{ $stats['upcoming'] }} فعالية قادمة</span>
            </span>
        </a>

        @foreach($categories as $category)
            <a href="{{ route('events.index', ['cat' => $category->slug]) }}"
               class="cat-card group {{ $category->toneClass() }}">
                <span class="cat-card-media">
                    @if($categoryImage = $category->imageCardUrl())
                        {{-- صورة مخصصة رُفعت من اللوحة — تحل محل الرسمة --}}
                        <img src="{{ $categoryImage }}" alt="" loading="lazy"
                             class="aspect-[8/5] h-auto w-full object-cover transition-transform duration-500 group-hover:scale-110">
                    @else
                        {{-- نسبة 8/5 تطابق أبعاد المشهد المرسوم فيظهر كاملاً بلا قص --}}
                        <x-ui.scene :name="$category->sceneName()" :tone="$category->toneHex()" class="aspect-[8/5] h-auto w-full transition-transform duration-500 group-hover:scale-110"/>
                    @endif
                </span>
                <span class="cat-card-chip"><x-ui.icon :name="$category->iconKey()"/></span>
                <span class="px-3 pt-2.5 pb-5 text-center">
                    <span class="block text-lg font-extrabold text-ink">{{ $category->name }}</span>
                    <span class="cat-card-count mt-2.5">
                        {{ $category->events_count }} {{ $category->events_count === 1 ? 'فعالية قادمة' : 'فعاليات قادمة' }}
                    </span>
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

{{-- ═══ دعوة الفرق: لوحة كاملة برحلة انضمام واضحة ═══ --}}
<section class="mx-auto max-w-7xl px-4 pb-16 sm:px-6">
    <div class="relative overflow-hidden rounded-[2rem] bg-gradient-to-bl from-brand-600 via-brand-700 to-[#17335a] px-6 py-12 text-white sm:rounded-[2.5rem] sm:px-10 lg:px-14 lg:py-16">

        {{-- زخارف احتفالية خلف المحتوى --}}
        <div class="pointer-events-none absolute inset-0" aria-hidden="true">
            <div class="absolute -top-16 -start-16 size-56 rounded-full bg-white/10 blur-2xl"></div>
            <div class="absolute -bottom-20 end-0 size-72 rounded-full bg-pink-400/20 blur-3xl"></div>
            <svg class="absolute -end-10 top-10 hidden h-16 w-56 -rotate-6 opacity-30 lg:block" viewBox="0 0 200 40" fill="none">
                <path d="M4 24 Q30 8 60 18 T120 16 T196 20 Q160 34 100 30 T4 24 Z" fill="#f9a8d4"/>
            </svg>
            <span class="drift-1 absolute left-[12%] top-8 size-2.5 rounded-full bg-amber-300/70"></span>
            <span class="drift-2 absolute left-[26%] bottom-10 size-2 rounded-full bg-pink-300/70"></span>
            <span class="drift-3 absolute right-[18%] bottom-16 size-2 rotate-45 bg-sky-200/60"></span>
        </div>

        <div class="relative grid items-center gap-10 lg:grid-cols-[1.05fr_.95fr] lg:gap-14">

            {{-- الرسالة والدعوة --}}
            <div>
                <span class="badge bg-white/15 text-white ring-1 ring-white/25">
                    <x-ui.icon name="users" class="size-4"/> للفرق التطوّعية والمؤسسات
                </span>

                <h2 class="mt-4 text-[1.9rem] leading-[1.25] font-extrabold text-white sm:text-4xl lg:text-[2.6rem]">
                    فريقكم يستحقّ أن يُرى
                </h2>

                <p class="mt-4 max-w-xl text-lg leading-relaxed text-brand-50">
                    أنتم من يصنع الفرح في المراكز والمخيّمات — ونحن نوصله للعائلات.
                    سجّلوا فريقكم مرة واحدة، ثم ارفعوا فعالياتكم بأنفسكم متى شئتم.
                </p>

                {{-- ما يكسبه الفريق --}}
                <ul class="mt-6 flex flex-col gap-2.5">
                    @foreach([
                        ['icon' => 'eye', 'text' => 'صفحة خاصة بفريقكم يراها الأهالي'],
                        ['icon' => 'grid', 'text' => 'لوحة تحكّم ترفعون منها فعالياتكم وصورها'],
                        ['icon' => 'megaphone', 'text' => 'فعالياتكم تصل عائلات المحافظات الخمس'],
                    ] as $perk)
                        <li class="flex items-center gap-3">
                            <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-white/15 text-white ring-1 ring-white/20">
                                <x-ui.icon :name="$perk['icon']" class="size-[18px]"/>
                            </span>
                            <span class="font-semibold text-white/95">{{ $perk['text'] }}</span>
                        </li>
                    @endforeach
                </ul>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center">
                    <a href="{{ route('teams.join') }}"
                       class="btn btn-lg w-full bg-white font-extrabold text-brand-700 shadow-xl shadow-black/20 transition hover:-translate-y-0.5 hover:bg-brand-50 sm:w-auto">
                        <x-ui.icon name="plus" class="size-5"/> سجّلوا فريقكم الآن
                    </a>
                    <a href="{{ route('organizers') }}"
                       class="btn w-full border-white/45 text-white hover:bg-white/10 sm:w-auto">
                        تعرّفوا على الفرق الشريكة
                    </a>
                </div>

                @if($stats['teams'])
                    {{-- الجملة في span واحد كي لا تتقطّع كلماتها على الجوال --}}
                    <p class="mt-5 flex items-start gap-2 text-sm leading-relaxed text-brand-100">
                        <x-ui.icon name="shield-check" class="mt-0.5 size-4 shrink-0"/>
                        <span>انضم إلينا <span class="font-extrabold text-white">{{ number_format($stats['teams']) }}</span>
                            {{ $stats['teams'] === 1 ? 'فريق تطوّعي' : 'فريقاً تطوّعياً' }} — والتسجيل مجاني بالكامل.</span>
                    </p>
                @endif
            </div>

            {{-- رحلة الانضمام: ثلاث خطوات حقيقية كما تجري فعلاً --}}
            <ol class="relative flex flex-col gap-3">
                @foreach([
                    ['n' => '١', 'icon' => 'users', 'title' => 'قدّموا طلب الانضمام', 'text' => 'نموذج واحد: بيانات الفريق ومسؤول الميدان وأنشطتكم ومناطق وصولكم.'],
                    ['n' => '٢', 'icon' => 'shield-check', 'title' => 'تراجعه الإدارة', 'text' => 'مراجعة سريعة لسلامة الأطفال، ثم يصلكم بريد فيه بيانات دخول لوحتكم.'],
                    ['n' => '٣', 'icon' => 'calendar', 'title' => 'ارفعوا فعالياتكم', 'text' => 'من لوحتكم: الموعد والمكان والصور وحجوزات العائلات — كلها بيدكم.'],
                ] as $step)
                    <li class="flex items-start gap-4 rounded-2xl bg-white/10 p-5 ring-1 ring-white/15 backdrop-blur-sm transition hover:bg-white/15">
                        <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-white text-lg font-extrabold text-brand-700 shadow-lg">
                            {{ $step['n'] }}
                        </span>
                        <span class="block">
                            <span class="flex items-center gap-2 text-[17px] font-bold text-white">
                                <x-ui.icon :name="$step['icon']" class="size-[18px] text-brand-100"/>
                                {{ $step['title'] }}
                            </span>
                            <span class="mt-1 block text-sm leading-relaxed text-brand-50/90">{{ $step['text'] }}</span>
                        </span>
                    </li>
                @endforeach
            </ol>
        </div>
    </div>
</section>

@endsection
