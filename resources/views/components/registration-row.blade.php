@props(['registration'])

@php
    $event = $registration->event;
    $display = $registration->displayStatus();

    $badge = match ($display) {
        'accepted' => ['tone' => 'bg-emerald-50 text-emerald-700', 'icon' => 'check', 'label' => 'مقبول', 'note' => 'تم قبول حجزكم في هذه الفعالية'],
        'pending' => ['tone' => 'bg-amber-50 text-amber-700', 'icon' => 'clock', 'label' => 'قيد المراجعة', 'note' => 'جارٍ مراجعة الطلب من الفريق'],
        'rejected' => ['tone' => 'bg-rose-50 text-rose-700', 'icon' => 'arrow-back', 'label' => 'مرفوض', 'note' => $registration->review_note ?: 'اعتذر الفريق عن قبول الحجز'],
        'completed' => ['tone' => 'bg-brand-50 text-brand-700', 'icon' => 'check', 'label' => 'مكتمل', 'note' => 'تم حضور الفعالية'],
        default => ['tone' => 'bg-ash-50 text-ink-soft', 'icon' => 'arrow-back', 'label' => 'ملغى', 'note' => 'أُلغي الحجز'],
    };
@endphp

<article {{ $attributes->class(['card '.($event->category?->toneClass() ?? 'tone tone-violet').' gap-4 p-5 sm:flex-row sm:items-center']) }}>
    @if($event->image_path)
        <img src="{{ $event->imageUrl() }}" alt="" width="72" height="72" class="size-18 shrink-0 rounded-2xl object-cover">
    @else
        <x-ui.scene :name="$event->category?->slug ?? 'default'" :tone="$event->category?->toneHex() ?? '#7c5cff'"
                    class="size-18 shrink-0 rounded-2xl"/>
    @endif

    <div class="min-w-0 flex-1">
        <h3 class="text-lg font-bold">
            <a href="{{ route('events.show', $event) }}" class="no-underline hover:text-brand-700">{{ $event->title }}</a>
        </h3>

        <ul class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-ink-soft">
            <li class="flex items-center gap-1.5">
                <x-ui.icon name="map-pin" class="size-4 text-brand-400"/>
                {{ $event->shelterCenter?->name ?? $event->area?->name }}
            </li>
            <li class="flex items-center gap-1.5">
                <x-ui.icon name="calendar" class="size-4 text-brand-400"/>
                {{ $event->start_date->translatedFormat('j F Y') }}
            </li>
            <li class="flex items-center gap-1.5">
                <x-ui.icon name="clock" class="size-4 text-brand-400"/>
                {{ substr($event->start_time, 0, 5) }}
            </li>
            @if($registration->child)
                <li class="flex items-center gap-1.5">
                    <x-ui.icon name="users" class="size-4 text-brand-400"/>
                    {{ $registration->child->nameWithAge() }}
                </li>
            @endif
        </ul>
    </div>

    <div class="flex shrink-0 flex-col items-stretch gap-2 sm:w-56">
        <div class="rounded-2xl {{ $badge['tone'] }} px-4 py-3 text-center">
            <p class="flex items-center justify-center gap-1.5 font-bold">
                <x-ui.icon :name="$badge['icon']" class="size-4"/> {{ $badge['label'] }}
            </p>
            <p class="mt-0.5 text-xs">{{ $badge['note'] }}</p>
        </div>

        <a href="{{ route('events.show', $event) }}" class="btn btn-outline btn-sm">عرض التفاصيل</a>

        @if($registration->isCancellable())
            <form method="POST" action="{{ route('registrations.destroy', $registration) }}"
                  onsubmit="return confirm('هل تريدون إلغاء الحجز؟')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-ghost btn-sm btn-block text-rose-600">إلغاء الحجز</button>
            </form>
        @endif
    </div>
</article>
