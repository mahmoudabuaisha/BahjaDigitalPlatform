@extends('layouts.app')

@section('title', 'عن '.\App\Support\Settings::get('site_name'))
@section('meta_description', 'منصّة بَهْجَة — رؤيتنا ورسالتنا وقيمنا، وقصة مبادرة تجمع فعاليات الترفيه والدعم النفسي لأطفال غزة.')

@section('content')

@php
    // أرقام حقيقية من قاعدة البيانات — لا أرقام تسويقية
    $stats = [
        ['icon' => 'calendar', 'tone' => 'tone-violet', 'value' => \App\Models\Event::publiclyVisible()->count(), 'label' => 'فعالية منشورة'],
        ['icon' => 'users', 'tone' => 'tone-sky', 'value' => \App\Models\Team::where('is_active', true)->count(), 'label' => 'فريق تطوّعي'],
        ['icon' => 'sparkles', 'tone' => 'tone-amber', 'value' => (int) \App\Models\Event::where('status', \App\Enums\EventStatus::Completed)->sum('actual_children'), 'label' => 'طفل حضر فعلياً'],
        ['icon' => 'map-pin', 'tone' => 'tone-rose', 'value' => \App\Models\Area::count(), 'label' => 'محافظة مغطاة'],
        ['icon' => 'shield-check', 'tone' => 'tone-emerald', 'value' => '100%', 'label' => 'فعاليات معتمَدة'],
    ];
@endphp

{{-- ═══ البطل ═══ --}}
<section class="surface-tint relative overflow-hidden">
    <div class="mx-auto grid max-w-7xl items-center gap-10 px-4 py-14 sm:px-6 lg:grid-cols-[1fr_.85fr] lg:py-20">
        <div>
            <nav class="flex items-center gap-2 text-sm text-ink-soft" aria-label="مسار التصفح">
                <a href="{{ route('home') }}" class="no-underline hover:text-brand-700">الرئيسية</a>
                <x-ui.icon name="chevron-start" class="size-4"/>
                <span class="text-ink">عن {{ \App\Support\Settings::get('site_name') }}</span>
            </nav>

            <h1 class="mt-4 flex items-center gap-3 text-4xl font-bold sm:text-5xl">
                عن {{ \App\Support\Settings::get('site_name') }}
                <x-ui.icon name="heart" class="size-9 text-brand-400"/>
            </h1>

            <p class="mt-4 max-w-2xl text-lg leading-relaxed text-ink-soft">
                منصّة تجمع فعاليات الترفيه والدعم النفسي لأطفال غزة في مكان واحد، وتوصلها إلى العائلات
                في مراكز الإيواء والمخيّمات — لتقدّم لأطفالنا تجارب آمنة تُسهم في نموّهم وتخفّف عنهم
                ثقل ما يعيشونه.
            </p>
        </div>

        <div class="relative hidden lg:block" aria-hidden="true">
            <div class="absolute -top-8 start-10 size-32 rounded-full bg-brand-200/60 blur-2xl"></div>
            <div class="absolute bottom-4 end-6 size-40 rounded-full bg-pink-200/50 blur-2xl"></div>

            <div class="relative grid grid-cols-2 gap-4">
                @foreach([
                    ['icon' => 'sparkles', 'tone' => 'tone-violet', 'label' => 'ترفيه'],
                    ['icon' => 'heart', 'tone' => 'tone-rose', 'label' => 'دعم نفسي'],
                    ['icon' => 'paint-brush', 'tone' => 'tone-sky', 'label' => 'فنون'],
                    ['icon' => 'trophy', 'tone' => 'tone-amber', 'label' => 'رياضة'],
                ] as $index => $item)
                    <div class="card tone {{ $item['tone'] }} items-center gap-2 p-6 text-center {{ $index % 2 ? 'translate-y-6' : '' }}">
                        <span class="icon-tile icon-tile-lg"><x-ui.icon :name="$item['icon']"/></span>
                        <span class="font-bold">{{ $item['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- ═══ الرؤية والرسالة والقيم ═══ --}}
<section class="mx-auto max-w-7xl px-4 py-14 sm:px-6">
    <h2 class="section-title justify-center text-center">رؤيتنا ورسالتنا</h2>

    <div class="mt-8 grid gap-5 lg:grid-cols-3">
        <div class="card tone tone-amber gap-3 p-7">
            <span class="icon-tile icon-tile-lg"><x-ui.icon name="megaphone"/></span>
            <h3 class="text-xl font-bold">رسالتنا</h3>
            <p class="leading-relaxed text-ink-soft">
                أن نكون الجسر بين الأهالي والفرق التطوعية: جدول واحد واضح، يصل حتى مع ضعف الشبكة،
                فلا تفوت عائلةً فعاليةٌ قريبة من مكانها.
            </p>
        </div>

        <div class="card tone tone-violet gap-3 p-7">
            <span class="icon-tile icon-tile-lg"><x-ui.icon name="sparkles"/></span>
            <h3 class="text-xl font-bold">رؤيتنا</h3>
            <p class="leading-relaxed text-ink-soft">
                ألّا يمرّ أسبوع على طفل في غزة بلا لحظة فرح واحدة — وأن يجد أهله موعدها ومكانها
                في ثوانٍ، من هاتف واحد.
            </p>
        </div>

        <div class="card tone tone-emerald gap-3 p-7">
            <span class="icon-tile icon-tile-lg"><x-ui.icon name="shield-check"/></span>
            <h3 class="text-xl font-bold">قيمنا</h3>
            <ul class="flex flex-col gap-2 text-ink-soft">
                @foreach(['الأمان أولاً — كل فعالية معتمَدة قبل نشرها', 'خصوصية العائلات: لا نطلب بيانات لا نحتاجها', 'الوصول للجميع: يعمل على أبسط هاتف ودون إنترنت', 'الشراكة مع الفرق التطوعية لا منافستها'] as $value)
                    <li class="flex items-start gap-2">
                        <x-ui.icon name="check" class="mt-1 size-4 shrink-0 text-emerald-500"/>
                        <span>{{ $value }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</section>

{{-- ═══ الأرقام ═══ --}}
<section class="mx-auto max-w-7xl px-4 pb-6 sm:px-6">
    <div class="card grid gap-6 p-8 sm:grid-cols-3 lg:grid-cols-5">
        @foreach($stats as $stat)
            <div class="tone {{ $stat['tone'] }} flex items-center gap-3">
                <span class="icon-tile"><x-ui.icon :name="$stat['icon']"/></span>
                <div>
                    <p class="text-2xl leading-tight font-bold">{{ is_int($stat['value']) ? number_format($stat['value']) : $stat['value'] }}</p>
                    <p class="text-sm text-ink-soft">{{ $stat['label'] }}</p>
                </div>
            </div>
        @endforeach
    </div>
</section>

{{-- ═══ قصتنا ═══ --}}
<section class="mx-auto max-w-7xl px-4 py-14 sm:px-6">
    <div class="surface-tint grid gap-8 rounded-3xl p-8 sm:p-12 lg:grid-cols-[1.2fr_.8fr]">
        <div>
            <h2 class="flex items-center gap-3 text-3xl font-bold">
                <x-ui.icon name="book-open" class="size-8 text-brand-500"/>
                قصّتنا
            </h2>
            <div class="mt-4 flex flex-col gap-4 text-lg leading-relaxed text-ink-soft">
                <p>
                    بدأت بَهْجَة من ملاحظة بسيطة: فرق تطوعية كثيرة تعمل في المراكز والمخيّمات، وعائلات
                    قريبة منها لا تعلم بمواعيدها. كان الخبر يصل بالصدفة — أو لا يصل.
                </p>
                <p>
                    فجمعنا الجداول في مكان واحد: الفريق يرفع فعاليته، والإدارة تعتمدها، والعائلة تراها
                    مرتّبة بحسب محافظتها ومركزها. وصمّمنا كل شيء ليعمل على شبكة ضعيفة وهاتف قديم —
                    فما يُفتح مرة يبقى محفوظاً على الجهاز حتى لو انقطع الإنترنت.
                </p>
                <p>
                    ما زلنا في البداية، وكل فريق ينضمّ يضاعف ما يصل إلى الأطفال.
                </p>
            </div>

            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('teams.join') }}" class="btn btn-primary">سجّلوا فريقكم</a>
                <a href="{{ route('contact') }}" class="btn btn-outline">تواصلوا معنا</a>
            </div>
        </div>

        <div class="flex flex-col gap-4">
            @foreach([
                ['icon' => 'grid', 'tone' => 'tone-violet', 'title' => 'جدول واحد', 'text' => 'كل الفرق في روزنامة واحدة مرتّبة يوماً بيوم.'],
                ['icon' => 'shield-check', 'tone' => 'tone-emerald', 'title' => 'اعتماد قبل النشر', 'text' => 'لا تظهر فعالية للعائلات قبل مراجعة الإدارة.'],
                ['icon' => 'bolt', 'tone' => 'tone-amber', 'title' => 'يعمل دون اتصال', 'text' => 'الروزنامة تبقى مقروءة حين تنقطع الشبكة.'],
            ] as $point)
                <div class="card tone {{ $point['tone'] }} flex-row items-start gap-3 p-5">
                    <span class="icon-tile"><x-ui.icon :name="$point['icon']"/></span>
                    <div>
                        <h3 class="font-bold">{{ $point['title'] }}</h3>
                        <p class="text-sm leading-relaxed text-ink-soft">{{ $point['text'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

@endsection
