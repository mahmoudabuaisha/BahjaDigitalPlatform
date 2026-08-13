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
<article data-track-event="{{ $event->id }}" class="mx-auto max-w-2xl">

    <nav class="mb-4 text-sm text-gray-400">
        <a href="{{ route('home') }}" class="hover:text-joy-600">الروزنامة</a>
        <span class="mx-1">←</span>
        <span class="text-gray-600">{{ \Illuminate\Support\Str::limit($event->title, 30) }}</span>
    </nav>

    @if(session('feedback_sent'))
        <div class="mb-4 rounded-2xl bg-calm-100 px-4 py-3 text-sm font-semibold text-calm-800">
            شكراً لكم — تقييمكم يساعدنا على تحسين الفعاليات القادمة 💚
        </div>
    @endif

    <div class="overflow-hidden rounded-3xl border border-joy-100 bg-white shadow-sm">
        @if($event->image_path)
            <img src="{{ $event->imageUrl() }}" alt="{{ $event->title }}"
                 class="h-52 w-full object-cover sm:h-64" width="1280" height="720" loading="eager">
        @endif

        <div class="space-y-5 p-5 sm:p-7">
            <header>
                <div class="flex flex-wrap items-center gap-2">
                    @if($event->category)
                        <span class="rounded-full bg-joy-100 px-3 py-1 text-xs font-bold text-joy-700">{{ $event->category->name }}</span>
                    @endif
                    @if($event->status === \App\Enums\EventStatus::Completed)
                        <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-bold text-gray-500">فعالية منتهية</span>
                    @endif
                </div>
                <h1 class="mt-2 text-2xl font-bold leading-snug text-gray-900 sm:text-3xl">{{ $event->title }}</h1>
            </header>

            @if($event->description)
                <p class="leading-relaxed text-gray-600">{{ $event->description }}</p>
            @endif

            {{-- تفاصيل الزمان والمكان --}}
            <dl class="grid grid-cols-1 gap-3 rounded-2xl bg-joy-50 p-4 sm:grid-cols-2">
                <div class="flex items-center gap-3">
                    <svg class="size-6 shrink-0 text-joy-500" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/></svg>
                    <div>
                        <dt class="text-xs font-bold text-gray-400">اليوم والتاريخ</dt>
                        <dd class="font-bold text-gray-800">{{ $event->start_date->translatedFormat('l j F Y') }}</dd>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <svg class="size-6 shrink-0 text-joy-500" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    <div>
                        <dt class="text-xs font-bold text-gray-400">الوقت</dt>
                        <dd class="font-bold text-gray-800">
                            {{ substr($event->start_time, 0, 5) }}
                            @if($event->end_time) — {{ substr($event->end_time, 0, 5) }} @endif
                        </dd>
                    </div>
                </div>
                <div class="flex items-center gap-3 sm:col-span-2">
                    <svg class="size-6 shrink-0 text-calm-500" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                    <div>
                        <dt class="text-xs font-bold text-gray-400">المكان</dt>
                        <dd class="font-bold text-gray-800">
                            {{ $event->area->name }}
                            @if($event->shelterCenter) — {{ $event->shelterCenter->name }} @endif
                            @if($event->location_details)
                                <span class="block text-sm font-normal text-gray-500">{{ $event->location_details }}</span>
                            @endif
                        </dd>
                    </div>
                </div>
            </dl>

            {{-- الفريق المنفذ --}}
            <a href="{{ route('teams.show', $event->team) }}"
               class="flex items-center gap-3 rounded-2xl border border-gray-100 p-3 transition hover:border-calm-300 hover:bg-calm-50">
                @if($event->team->logo_path)
                    <img src="{{ $event->team->logoUrl() }}" alt="" class="size-12 rounded-full object-cover" width="48" height="48">
                @else
                    <span class="grid size-12 place-items-center rounded-full bg-calm-100 text-lg font-bold text-calm-700">
                        {{ mb_substr($event->team->name, 5, 1) ?: 'ف' }}
                    </span>
                @endif
                <div class="min-w-0">
                    <p class="text-xs font-bold text-gray-400">الفريق المنفذ</p>
                    <p class="truncate font-bold text-gray-800">{{ $event->team->name }}</p>
                </div>
                <span class="ms-auto text-sm font-bold text-calm-600">صفحة الفريق ←</span>
            </a>

            {{-- المشاركة --}}
            <div class="border-t border-gray-100 pt-5">
                <p class="mb-3 text-sm font-bold text-gray-500">شاركوا الفعالية مع أهالي منطقتكم:</p>
                <x-share-buttons :title="$event->title.' — '.$event->start_date->translatedFormat('l j F').' الساعة '.substr($event->start_time, 0, 5).' في '.($event->shelterCenter?->name ?? $event->area->name)"
                                 :url="route('events.show', $event)"/>
            </div>

            {{-- تقييم الفعاليات المنتهية --}}
            @if($event->hasEnded() || $event->status === \App\Enums\EventStatus::Completed)
                <div class="border-t border-gray-100 pt-5">
                    <p class="mb-3 text-sm font-bold text-gray-500">حضرتم هذه الفعالية؟ قيّموها:</p>
                    <form method="POST" action="{{ route('events.feedback', $event) }}" class="space-y-3 text-center">
                        @csrf
                        <input type="text" name="website" value="" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">
                        <x-star-rating/>
                        <textarea name="message" rows="2" maxlength="1000"
                                  placeholder="ملاحظات إضافية (اختياري)"
                                  class="w-full rounded-xl border-gray-200 text-sm focus:border-joy-400 focus:ring-joy-300"></textarea>
                        @error('rating')
                            <p class="text-sm font-semibold text-red-600">اختاروا عدد النجوم أولاً</p>
                        @enderror
                        <button type="submit" class="rounded-xl bg-joy-500 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-joy-600">
                            إرسال التقييم
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>

    {{-- فعاليات قريبة --}}
    @if($related->isNotEmpty())
        <section class="mt-8">
            <h2 class="mb-3 text-lg font-bold text-gray-900">فعاليات أخرى في {{ $event->area->name }}</h2>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                @foreach($related as $relatedEvent)
                    <x-event-card :event="$relatedEvent"/>
                @endforeach
            </div>
        </section>
    @endif

</article>
@endsection
