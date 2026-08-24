@props(['event'])

<article class="{{ $event->category?->toneClass() ?? 'tone tone-violet' }} flex flex-wrap items-center gap-4 rounded-2xl border border-brand-100 p-4">
    @if($event->image_path)
        <img src="{{ $event->imageUrl() }}" alt="" width="56" height="56" class="size-14 shrink-0 rounded-2xl object-cover">
    @else
        <x-ui.scene :name="$event->category?->slug ?? 'default'" :tone="$event->category?->toneHex() ?? '#7c5cff'"
                    class="size-14 shrink-0 rounded-2xl"/>
    @endif

    <div class="min-w-0 flex-1">
        <p class="truncate font-bold">{{ $event->title }}</p>
        <p class="text-sm text-[color:var(--tone-ink)]">{{ $event->category?->name }}</p>
    </div>

    <p class="flex items-center gap-1.5 text-sm text-ink-soft sm:w-48">
        <x-ui.icon name="map-pin" class="size-4 text-brand-400"/>
        <span class="truncate">{{ $event->area?->name }}@if($event->shelterCenter) — {{ $event->shelterCenter->name }}@endif</span>
    </p>

    <p class="flex items-center gap-1.5 text-sm text-ink-soft">
        <x-ui.icon name="calendar" class="size-4 text-brand-400"/>
        {{ $event->start_date->format('Y/m/d') }} · {{ substr($event->start_time, 0, 5) }}
    </p>

    <p class="flex items-center gap-1.5 text-sm">
        <x-ui.icon name="users" class="size-4 text-brand-400"/>
        <span class="font-bold">{{ $event->seats_taken }}@if($event->expected_children) / {{ $event->expected_children }}@endif</span>
        <span class="text-ink-soft">تسجيل</span>
    </p>

    <span class="flex flex-col items-start gap-1">
        <x-event-status-badge :event="$event"/>
        @if($event->relationLoaded('pendingRevision') ? $event->pendingRevision : $event->pendingRevision()->exists())
            <span class="badge bg-amber-100 text-amber-800">تعديل قيد المراجعة</span>
        @endif
    </span>

    <div x-data="{ open: false }" class="relative">
        <button type="button" @click="open = ! open" class="grid size-9 place-items-center rounded-xl text-ink-soft hover:bg-brand-50"
                aria-label="خيارات">
            <x-ui.icon name="chevron-down" class="size-5"/>
        </button>

        <div x-show="open" x-cloak @click.outside="open = false"
             class="absolute end-0 top-full z-30 mt-1 w-48 rounded-2xl border border-brand-100 bg-white p-2 shadow-lg">
            <a href="{{ route('organizer.events.edit', $event) }}" class="flex min-h-[40px] items-center gap-2 rounded-xl px-3 no-underline hover:bg-brand-50">
                <x-ui.icon name="paint-brush" class="size-4 text-brand-500"/> تعديل الفعالية
            </a>
            @if($event->hasEnded() || ($event->start_date->isToday() && $event->startsAt()->isPast()))
                <a href="{{ route('organizer.events.attendance', $event) }}" class="flex min-h-[40px] items-center gap-2 rounded-xl px-3 no-underline hover:bg-brand-50">
                    <x-ui.icon name="check" class="size-4 text-emerald-600"/> تسجيل الحضور
                </a>
            @endif
            <a href="{{ url('/team/events/'.$event->id.'/edit') }}" class="flex min-h-[40px] items-center gap-2 rounded-xl px-3 no-underline hover:bg-brand-50">
                <x-ui.icon name="users" class="size-4 text-brand-500"/> حجوزات العائلات
            </a>
            @if($event->status->isPubliclyVisible())
                <a href="{{ route('events.show', $event) }}" class="flex min-h-[40px] items-center gap-2 rounded-xl px-3 no-underline hover:bg-brand-50">
                    <x-ui.icon name="eye" class="size-4 text-brand-500"/> كما تراها العائلات
                </a>
            @endif
        </div>
    </div>
</article>
