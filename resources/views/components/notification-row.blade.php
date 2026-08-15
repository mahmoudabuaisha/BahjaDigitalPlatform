@props(['notification'])

@php
    $icon = match ($notification->type) {
        'registration_accepted' => ['name' => 'check', 'tone' => 'tone-emerald'],
        'registration_rejected' => ['name' => 'arrow-back', 'tone' => 'tone-rose'],
        'registration_received' => ['name' => 'users', 'tone' => 'tone-sky'],
        'event_changed' => ['name' => 'clock', 'tone' => 'tone-amber'],
        default => ['name' => 'megaphone', 'tone' => 'tone-violet'],
    };
@endphp

<article {{ $attributes->class(['card tone '.$icon['tone'].' flex-row items-start gap-3 p-4 '.($notification->read_at ? '' : 'border-brand-300 bg-brand-50/40')]) }}>
    <span class="icon-tile"><x-ui.icon :name="$icon['name']"/></span>

    <div class="min-w-0 flex-1">
        <p class="font-bold">
            @if($notification->url)
                <a href="{{ $notification->url }}" class="no-underline hover:text-brand-700">{{ $notification->title }}</a>
            @else
                {{ $notification->title }}
            @endif
        </p>
        @if($notification->body)
            <p class="text-sm text-ink-soft">{{ $notification->body }}</p>
        @endif
        <p class="mt-1 text-xs text-ink-soft">{{ $notification->created_at->diffForHumans() }}</p>
    </div>

    @unless($notification->read_at)
        <span class="mt-2 size-2.5 shrink-0 rounded-full bg-brand-500" title="غير مقروء"></span>
    @endunless
</article>
