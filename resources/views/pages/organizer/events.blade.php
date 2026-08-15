@extends('layouts.organizer')

@section('title', 'فعالياتي — '.\App\Support\Settings::get('site_name'))

@section('organizer')

<div class="flex flex-wrap items-start justify-between gap-3">
    <div>
        <h1 class="flex items-center gap-3 text-3xl font-bold">
            <span class="icon-tile tone tone-violet"><x-ui.icon name="calendar"/></span>
            قائمة فعالياتي
        </h1>
        <p class="mt-1 text-ink-soft">عرض وإدارة جميع الفعاليات التي أنشأها فريقكم.</p>
    </div>

    <a href="{{ url('/team/events/create') }}" class="btn btn-primary">
        <x-ui.icon name="plus" class="size-5"/> إضافة فعالية جديدة
    </a>
</div>

{{-- بطاقات الأرقام --}}
<div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    @foreach([
        ['key' => '', 'tone' => 'tone-violet', 'icon' => 'calendar', 'value' => $stats['total'], 'label' => 'إجمالي الفعاليات'],
        ['key' => 'published', 'tone' => 'tone-emerald', 'icon' => 'check', 'value' => $stats['published'], 'label' => 'مقبولة ومنشورة'],
        ['key' => 'pending', 'tone' => 'tone-amber', 'icon' => 'clock', 'value' => $stats['pending'], 'label' => 'قيد المراجعة'],
        ['key' => 'cancelled', 'tone' => 'tone-rose', 'icon' => 'arrow-back', 'value' => $stats['cancelled'], 'label' => 'ملغاة أو مرفوضة'],
    ] as $card)
        <a href="{{ route('organizer.events', array_filter(['status' => $card['key'], 'q' => $search])) }}"
           class="card card-hover tone {{ $card['tone'] }} gap-2 p-5 no-underline {{ $filter === $card['key'] ? 'border-brand-300' : '' }}">
            <div class="flex items-center justify-between">
                <span class="text-3xl font-bold">{{ number_format($card['value']) }}</span>
                <span class="icon-tile"><x-ui.icon :name="$card['icon']"/></span>
            </div>
            <p class="text-sm text-ink-soft">{{ $card['label'] }}</p>
        </a>
    @endforeach
</div>

{{-- البحث والتبويبات --}}
<div class="card mt-6 gap-4 p-5">
    <form method="GET" action="{{ route('organizer.events') }}" class="flex flex-wrap items-center gap-3">
        <label class="field relative min-w-56 flex-1">
            <span class="sr-only">ابحثوا عن فعالية</span>
            <x-ui.icon name="search" class="absolute top-1/2 start-4 size-5 -translate-y-1/2 text-brand-400"/>
            <input type="search" name="q" value="{{ $search }}" class="input ps-12" placeholder="ابحثوا عن فعالية…">
        </label>

        <input type="hidden" name="status" value="{{ $filter }}">
        <button type="submit" class="btn btn-primary">بحث</button>
    </form>

    <div class="flex flex-wrap gap-2">
        @foreach(['' => 'الكل', 'published' => 'منشورة', 'pending' => 'قيد المراجعة', 'completed' => 'منتهية', 'cancelled' => 'ملغاة'] as $key => $label)
            <a href="{{ route('organizer.events', array_filter(['status' => $key, 'q' => $search])) }}"
               class="chip {{ $filter === $key ? 'is-active' : '' }}">{{ $label }}</a>
        @endforeach
    </div>

    @if($events->isEmpty())
        <div class="rounded-2xl border border-dashed border-brand-200 p-12 text-center">
            <span class="icon-tile tone tone-violet icon-tile-lg mx-auto"><x-ui.icon name="calendar"/></span>
            <p class="mt-3 text-lg font-bold">لا فعاليات في هذا التبويب</p>
            <a href="{{ url('/team/events/create') }}" class="btn btn-primary mt-4">إضافة فعالية جديدة</a>
        </div>
    @else
        <div class="flex flex-col gap-3">
            @foreach($events as $event)
                <x-organizer-event-row :event="$event"/>
            @endforeach
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-brand-100 pt-4">
            <p class="text-sm text-ink-soft">
                عرض {{ $events->firstItem() }} – {{ $events->lastItem() }} من {{ $events->total() }} فعالية
            </p>
            {{ $events->links('partials.pagination') }}
        </div>
    @endif
</div>

@endsection
