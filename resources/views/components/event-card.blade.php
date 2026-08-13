@props(['event'])

<a href="{{ route('events.show', $event) }}"
   data-event-card
   data-area="{{ $event->area?->slug }}"
   data-center="{{ $event->shelter_center_id }}"
   data-category="{{ $event->category?->slug }}"
   data-date="{{ $event->start_date->toDateString() }}"
   data-today="{{ today()->toDateString() }}"
   data-tomorrow="{{ today()->addDay()->toDateString() }}"
   class="block rounded-2xl border border-joy-100 bg-white p-4 shadow-sm transition hover:border-joy-300 hover:shadow-md">

    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0 flex-1">
            <h3 class="truncate font-bold text-gray-900">{{ $event->title }}</h3>

            <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-gray-500">
                <span class="inline-flex items-center gap-1">
                    <svg class="size-4 shrink-0 text-joy-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    {{ substr($event->start_time, 0, 5) }}
                </span>
                <span class="inline-flex min-w-0 items-center gap-1">
                    <svg class="size-4 shrink-0 text-calm-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                    <span class="truncate">{{ $event->shelterCenter?->name ?? $event->area?->name }}</span>
                </span>
            </p>
        </div>

        @if($event->category)
            <span class="shrink-0 rounded-full bg-joy-100 px-2.5 py-1 text-xs font-semibold text-joy-700">
                {{ $event->category->name }}
            </span>
        @endif
    </div>

    <p class="mt-2 text-xs text-gray-400">
        {{ $event->team?->name }}
        @if($event->location_details)
            · {{ $event->location_details }}
        @endif
    </p>
</a>
