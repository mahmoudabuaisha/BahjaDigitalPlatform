@props(['event'])

@php
    $isToday = $event->start_date->isToday();
    $place = collect([
        $event->shelterCenter?->name ?? $event->area?->name,
        $event->location_details,
    ])->filter()->implode(' — ');
@endphp

<a href="{{ route('events.show', $event) }}"
   data-event-card
   data-area="{{ $event->area?->slug }}"
   data-center="{{ $event->shelter_center_id }}"
   data-category="{{ $event->category?->slug }}"
   data-date="{{ $event->start_date->toDateString() }}"
   data-today="{{ today()->toDateString() }}"
   data-tomorrow="{{ today()->addDay()->toDateString() }}"
   class="card">

    <div class="flex items-center gap-2">
        @if($event->category)
            <span class="tag tag-cyan">{{ $event->category->name }}</span>
        @endif

        @if($isToday)
            <span class="tag tag-magenta">اليوم</span>
        @endif

        <span class="ms-auto font-figure text-sm text-ash-700">{{ substr($event->start_time, 0, 5) }}</span>
    </div>

    <h3 class="text-[22px] leading-snug">{{ $event->title }}</h3>

    @if($place)
        <p class="text-sm leading-relaxed text-ash-800">{{ $place }}</p>
    @endif

    <div class="flex items-center justify-between gap-2 text-sm text-ash-700">
        <span class="truncate">{{ $event->team?->name }}</span>
        <span class="shrink-0 font-figure">{{ $event->start_date->translatedFormat('l j F') }}</span>
    </div>
</a>
