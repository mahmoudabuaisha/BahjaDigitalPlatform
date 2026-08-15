@props(['event'])

@php
    $map = [
        'approved' => ['label' => 'منشورة', 'class' => 'bg-emerald-50 text-emerald-700'],
        'pending' => ['label' => 'قيد المراجعة', 'class' => 'bg-amber-50 text-amber-700'],
        'draft' => ['label' => 'مسودة', 'class' => 'bg-brand-50 text-ink-soft'],
        'completed' => ['label' => 'منتهية', 'class' => 'bg-brand-50 text-brand-700'],
        'rejected' => ['label' => 'مرفوضة', 'class' => 'bg-rose-50 text-rose-700'],
        'cancelled' => ['label' => 'ملغاة', 'class' => 'bg-rose-50 text-rose-700'],
    ];
    $state = $map[$event->status->value] ?? ['label' => $event->status->getLabel(), 'class' => 'bg-brand-50 text-ink-soft'];
@endphp

<span {{ $attributes->class(['badge '.$state['class']]) }}>
    <span class="size-1.5 rounded-full bg-current opacity-70"></span>
    {{ $state['label'] }}
</span>
