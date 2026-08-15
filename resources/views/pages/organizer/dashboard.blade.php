@extends('layouts.organizer')

@section('title', 'لوحة المنظِّم — '.\App\Support\Settings::get('site_name'))

@section('organizer')

<div class="flex flex-wrap items-start justify-between gap-3">
    <div>
        <h1 class="flex items-center gap-3 text-3xl font-bold">
            <span class="icon-tile tone tone-violet"><x-ui.icon name="grid"/></span>
            لوحة تحكّم المنظِّم
        </h1>
        <p class="mt-1 text-ink-soft">من هنا تديرون فعالياتكم وتتابعون تسجيلات الأطفال.</p>
    </div>

    <a href="{{ route('organizer.events.create') }}" class="btn btn-primary">
        <x-ui.icon name="plus" class="size-5"/> إضافة فعالية جديدة
    </a>
</div>

@if($pendingRegistrations)
    <a href="{{ url('/team/events') }}" class="card card-hover tone tone-amber mt-5 flex-row items-center gap-3 p-5 no-underline">
        <span class="icon-tile"><x-ui.icon name="clock"/></span>
        <div class="flex-1">
            <p class="font-bold">{{ $pendingRegistrations }} طلب حجز بانتظار ردّكم</p>
            <p class="text-sm text-ink-soft">افتحوا الفعالية ثم تبويب «حجوزات العائلات» للقبول أو الاعتذار.</p>
        </div>
        <x-ui.icon name="chevron-start" class="size-5 text-brand-400"/>
    </a>
@endif

<div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    @foreach([
        ['tone' => 'tone-violet', 'icon' => 'calendar', 'value' => $stats['total'], 'label' => 'إجمالي فعالياتي'],
        ['tone' => 'tone-emerald', 'icon' => 'check', 'value' => $stats['upcoming'], 'label' => 'الفعاليات القادمة'],
        ['tone' => 'tone-sky', 'icon' => 'users', 'value' => $stats['registrations'], 'label' => 'التسجيلات'],
        ['tone' => 'tone-rose', 'icon' => 'bolt', 'value' => $stats['completed'], 'label' => 'الفعاليات المنتهية'],
    ] as $stat)
        <div class="card tone {{ $stat['tone'] }} gap-2 p-5">
            <div class="flex items-center justify-between">
                <span class="text-3xl font-bold">{{ number_format($stat['value']) }}</span>
                <span class="icon-tile"><x-ui.icon :name="$stat['icon']"/></span>
            </div>
            <p class="text-sm text-ink-soft">{{ $stat['label'] }}</p>
        </div>
    @endforeach
</div>

<section class="card mt-6 p-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-xl font-bold">فعالياتي الأخيرة</h2>
        <a href="{{ route('organizer.events') }}" class="btn btn-ghost btn-sm">عرض جميع الفعاليات</a>
    </div>

    @if($recent->isEmpty())
        <div class="mt-4 rounded-2xl border border-dashed border-brand-200 p-10 text-center">
            <p class="font-bold">لم ترفعوا فعالية بعد</p>
            <p class="mt-1 text-ink-soft">أضيفوا أول فعالية، وبعد اعتماد الإدارة تظهر للعائلات.</p>
            <a href="{{ route('organizer.events.create') }}" class="btn btn-primary mt-4">إضافة فعالية</a>
        </div>
    @else
        <div class="mt-4 flex flex-col gap-3">
            @foreach($recent as $event)
                <x-organizer-event-row :event="$event"/>
            @endforeach
        </div>
    @endif
</section>

@endsection
