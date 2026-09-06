@props(['event'])

@php
    $tone = $event->category?->toneClass() ?? 'tone tone-violet';
    $isToday = $event->start_date->isToday();
    $place = $event->publicPlaceName();
@endphp

<article {{ $attributes->class(['card card-hover '.$tone]) }}
   data-event-card
   data-area="{{ $event->area?->slug }}"
   data-center="{{ $event->shelter_center_id }}"
   data-category="{{ $event->category?->slug }}"
   data-date="{{ $event->start_date->toDateString() }}"
   data-today="{{ today()->toDateString() }}"
   data-tomorrow="{{ today()->addDay()->toDateString() }}">

    <a href="{{ route('events.show', $event) }}" class="relative block aspect-[16/10] overflow-hidden no-underline">
        @if($event->image_path)
            <img src="{{ $event->imageCardUrl() }}" alt="{{ $event->title }}" loading="lazy"
                 class="size-full object-cover transition duration-300 hover:scale-105" width="640" height="400">
        @else
            {{-- لا صورة مرفوعة: رسمة الفئة بدل مربّع رمادي فارغ --}}
            <x-ui.scene :name="$event->category?->slug ?? 'default'"
                        :tone="$event->category?->toneHex() ?? '#3b93e4'"
                        class="size-full transition duration-300 hover:scale-105"/>
        @endif

        <span class="absolute top-3 start-3 flex flex-wrap gap-2">
            @if($event->category)
                <span class="badge badge-solid">
                    <x-ui.icon :name="$event->category->iconKey()" class="size-4 text-[color:var(--tone)]"/>
                    {{ $event->category->name }}
                </span>
            @endif

            @if($isToday)
                <span class="badge bg-brand-600 text-white">اليوم</span>
            @endif
        </span>
    </a>

    <div class="flex flex-1 flex-col gap-3 p-5">
        <h3 class="text-lg leading-snug font-bold">
            <a href="{{ route('events.show', $event) }}" class="no-underline hover:text-brand-700">{{ $event->title }}</a>
        </h3>

        <ul class="flex flex-col gap-2 text-sm text-ink-soft">
            <li class="flex items-center gap-2">
                <x-ui.icon name="calendar" class="size-[18px] text-brand-400"/>
                {{ $event->start_date->translatedFormat('l j F Y') }}
            </li>
            <li class="flex items-center gap-2">
                <x-ui.icon name="clock" class="size-[18px] text-brand-400"/>
                {{ substr($event->start_time, 0, 5) }}@if($event->end_time) — {{ substr($event->end_time, 0, 5) }}@endif
            </li>
            @if($place)
                <li class="flex items-start gap-2">
                    <x-ui.icon name="map-pin" class="mt-0.5 size-[18px] shrink-0 text-brand-400"/>
                    <span class="line-clamp-2">{{ $place }}@if($event->location_details) — {{ $event->location_details }}@endif</span>
                </li>
            @endif
        </ul>

        <div class="mt-auto flex flex-wrap items-center gap-2 pt-1">
            @if($event->ageLabel())
                <span class="badge badge-tone"><x-ui.icon name="cake" class="size-4"/> {{ $event->ageLabel() }}</span>
            @endif
            @if($event->expected_children)
                <span class="badge"><x-ui.icon name="users" class="size-4"/> يتّسع لـ {{ $event->expected_children }}</span>
            @endif
        </div>

        <div class="flex items-center justify-between gap-3 border-t border-brand-100 pt-4">
            @if($event->team)
                <a href="{{ route('teams.show', $event->team) }}" class="truncate text-sm text-ink-soft no-underline hover:text-brand-700">
                    {{ $event->team->name }}
                </a>
            @else
                <span></span>
            @endif

            <a href="{{ route('events.show', $event) }}" class="btn btn-primary btn-sm shrink-0">عرض التفاصيل</a>
        </div>
    </div>
</article>
