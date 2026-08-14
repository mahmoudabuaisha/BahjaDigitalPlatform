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
<div class="max-w-2xl">

    <header class="flex items-center gap-3">
        @if($team->logo_path)
            <img src="{{ $team->logoUrl() }}" alt="{{ $team->name }}"
                 class="halftone size-16 shrink-0 rounded-full object-cover" width="64" height="64">
        @else
            <span class="grid size-16 shrink-0 place-items-center rounded-full bg-cyan-200 font-display text-[26px] font-bold text-cyan-800">
                {{ mb_substr(preg_replace('/^فريق\s+/u', '', $team->name), 0, 1) }}
            </span>
        @endif

        <div>
            <span class="tag tag-outline">فريق موثوق · معتمد من الإدارة</span>
            <h1 class="mt-1 text-[30px] leading-tight">{{ $team->name }}</h1>
        </div>
    </header>

    @if($team->description)
        <p class="mt-3 text-[16px] leading-relaxed text-ash-800">{{ $team->description }}</p>
    @endif

    <div class="mt-4 flex flex-wrap gap-x-8 gap-y-3">
        <div>
            <p class="font-figure text-[30px] leading-none">{{ number_format($completedCount) }}</p>
            <p class="mt-1 text-sm text-ash-700">فعالية منفّذة</p>
        </div>
        <div>
            <p class="font-figure text-[30px] leading-none text-cyan-700">{{ number_format($childrenReached) }}</p>
            <p class="mt-1 text-sm text-ash-700">طفل حاضر فعلياً</p>
        </div>
        @if($averageRating)
            <div>
                <p class="font-figure text-[30px] leading-none">{{ number_format($averageRating, 1) }}</p>
                <p class="mt-1 text-sm text-ash-700">تقييم العائلات</p>
            </div>
        @endif
    </div>

    @if($team->whatsappUrl())
        <a href="{{ $team->whatsappUrl() }}" target="_blank" rel="noopener" class="btn btn-secondary mt-4">
            تواصلوا مع الفريق واتساب
        </a>
    @endif

    <section class="mt-8">
        <h2 class="text-[22px]">فعالياته القادمة</h2>

        @if($upcoming->isEmpty())
            <p class="mt-2 text-ash-800">لا فعاليات قادمة معلنة حالياً — تابعوا الروزنامة، الجدول يتحدّث باستمرار.</p>
        @else
            <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                @foreach($upcoming as $event)
                    <x-event-card :event="$event"/>
                @endforeach
            </div>
        @endif
    </section>

    {{-- دليل مختصر — الأهالي يصلون صفحة الفريق من واتساب قبل أن يعرفوا المنصة --}}
    <section class="mt-8">
        <h2 class="text-[22px]">كيف تصلون إلى الفعالية؟</h2>
        <p class="mt-1 text-ash-800">ثلاث خطوات بلغة بسيطة — محفوظة على هاتفكم، تُقرأ حتى دون اتصال.</p>

        <ol class="mt-3 flex flex-col gap-2">
            <li class="flex items-start gap-3">
                <span class="min-w-[22px] font-figure text-[22px] leading-tight text-magenta-600">1</span>
                <span class="leading-relaxed">افتحوا الرابط من واتساب، أو امسحوا كرت QR المعلّق في المركز.</span>
            </li>
            <li class="flex items-start gap-3">
                <span class="min-w-[22px] font-figure text-[22px] leading-tight text-magenta-600">2</span>
                <span class="leading-relaxed">اختاروا محافظتكم أو مركز الإيواء الذي تسكنونه.</span>
            </li>
            <li class="flex items-start gap-3">
                <span class="min-w-[22px] font-figure text-[22px] leading-tight text-magenta-600">3</span>
                <span class="leading-relaxed">احضروا في الموعد، وشاركوا الرابط مع الجيران.</span>
            </li>
        </ol>

        <a href="{{ route('guide') }}" class="btn btn-ghost mt-2 px-0">الدليل المصوّر كاملاً</a>
    </section>

</div>
@endsection
