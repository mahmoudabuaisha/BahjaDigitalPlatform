@extends('layouts.app')

@section('title', $event->title.' — '.\App\Support\Settings::get('site_name'))
@section('meta_description', \Illuminate\Support\Str::limit($event->description ?? 'فعالية لأطفال غزة — '.$event->title, 150))

@section('og')
    @include('partials.og', [
        'ogType' => 'article',
        'ogTitle' => $event->title.' — '.$event->start_date->translatedFormat('l j F'),
        'ogDescription' => ($event->shelterCenter?->name ?? $event->area->name).' · الساعة '.substr($event->start_time, 0, 5).($event->description ? ' — '.\Illuminate\Support\Str::limit($event->description, 100) : ''),
        'ogImage' => $event->image_path,
    ])
@endsection

@section('content')
<article data-track-event="{{ $event->id }}" class="max-w-2xl">

    <a href="{{ route('home') }}" class="inline-flex items-center gap-2 font-display text-[20px] font-bold text-ink no-underline hover:text-cyan-700">
        {{-- سهم مرسوم لا محرفاً: المحارف السهمية تنقلب مع اتجاه النص --}}
        <svg class="size-5 shrink-0 text-cyan-700 rtl:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19 3 12l7-7M3 12h18"/>
        </svg>
        عودة إلى الروزنامة
    </a>

    @if(session('feedback_sent'))
        <p class="mt-3 bg-cyan-100 px-3 py-2 text-sm text-cyan-800">
            شكراً لكم — تقييمكم يساعدنا على تحسين الفعاليات القادمة.
        </p>
    @endif

    @if($event->image_path)
        <figure class="halftone mt-4 rounded-[2px]">
            <img src="{{ $event->imageUrl() }}" alt="{{ $event->title }}"
                 class="h-52 w-full object-cover sm:h-64" width="1280" height="720" loading="eager">
        </figure>
    @endif

    <div class="mt-4 flex flex-wrap items-center gap-2">
        @if($event->start_date->isToday())
            <span class="tag tag-magenta">اليوم</span>
        @endif

        @if($event->category)
            <span class="tag tag-cyan">{{ $event->category->name }}</span>
        @endif

        @if($event->status === \App\Enums\EventStatus::Completed)
            <span class="tag tag-neutral">فعالية منتهية</span>
        @else
            <span class="tag tag-outline">معتمدة من الإدارة</span>
        @endif
    </div>

    <h1 class="mt-2 text-[32px] leading-tight sm:text-[38px]">{{ $event->title }}</h1>

    @if($event->description)
        <p class="mt-2 text-[16px] leading-relaxed text-ash-800">{{ $event->description }}</p>
    @endif

    {{-- الزمان والمكان — بلا صندوق: الفراغ وحده يفصل --}}
    <dl class="mt-6 flex flex-col gap-3">
        <div>
            <dt class="text-sm text-ash-700">الوقت</dt>
            <dd class="font-figure text-[19px]">
                {{ $event->start_date->translatedFormat('l j F') }} ·
                {{ substr($event->start_time, 0, 5) }}@if($event->end_time) — {{ substr($event->end_time, 0, 5) }}@endif
            </dd>
        </div>

        <div>
            <dt class="text-sm text-ash-700">المكان بدقة</dt>
            <dd class="text-[19px] leading-normal">
                {{ $event->shelterCenter?->name ?? $event->area->name }}@if($event->location_details) — {{ $event->location_details }}@endif
            </dd>
        </div>

        <div>
            <dt class="text-sm text-ash-700">المحافظة</dt>
            <dd class="text-[19px]">{{ $event->area->name }}</dd>
        </div>

        <div>
            <dt class="text-sm text-ash-700">الفريق المنظّم</dt>
            <dd class="text-[19px]">
                <a href="{{ route('teams.show', $event->team) }}" class="text-cyan-700 hover:text-cyan-600 hover:underline">
                    {{ $event->team->name }}
                </a>
            </dd>
        </div>
    </dl>

    {{-- المشاركة — الطريق الأول لوصول الخبر: مجموعات واتساب العائلة والحي --}}
    <div class="mt-6">
        <x-share-buttons :title="$event->title.' — '.$event->start_date->translatedFormat('l j F').' الساعة '.substr($event->start_time, 0, 5).' في '.($event->shelterCenter?->name ?? $event->area->name)"
                         :url="route('events.show', $event)"
                         :qr-url="route('events.qr', $event)"
                         :short-url="$event->shortUrl()"/>
    </div>

    {{-- تقييم الفعاليات المنتهية --}}
    @if($event->hasEnded() || $event->status === \App\Enums\EventStatus::Completed)
        <section class="mt-8">
            <h2 class="text-[20px]">كيف كانت الفعالية؟</h2>
            <p class="mt-1 text-sm leading-relaxed text-ash-800">
                بلا اسم وبلا أي بيانات شخصية — تقييمكم يصل الفريق والإدارة فقط.
            </p>

            <form method="POST" action="{{ route('events.feedback', $event) }}" class="mt-2 flex flex-col items-start gap-3">
                @csrf
                <input type="text" name="website" value="" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">

                <x-star-rating/>

                @error('rating')
                    <p class="text-sm text-magenta-700">اختاروا عدد النجوم أولاً</p>
                @enderror

                <textarea name="message" rows="2" maxlength="1000"
                          placeholder="ملاحظات إضافية (اختياري)"
                          class="input max-w-md"></textarea>

                <button type="submit" class="btn btn-primary">إرسال التقييم</button>
            </form>
        </section>
    @endif

    {{-- فعاليات قريبة --}}
    @if($related->isNotEmpty())
        <section class="mt-8">
            <h2 class="text-[20px]">فعاليات أخرى في {{ $event->area->name }}</h2>
            <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                @foreach($related as $relatedEvent)
                    <x-event-card :event="$relatedEvent"/>
                @endforeach
            </div>
        </section>
    @endif

</article>
@endsection
