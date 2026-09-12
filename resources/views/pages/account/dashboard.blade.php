@extends('layouts.account')

@section('title', 'لوحة التحكّم — '.\App\Support\Settings::get('site_name'))

@section('account')

<div class="flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-3xl font-bold">مرحباً، {{ auth()->user()->name }}</h1>
        <p class="mt-1 text-ink-soft">هذه نظرة سريعة على حجوزاتكم وأطفالكم.</p>
    </div>
    <a href="{{ route('events.index') }}" class="btn btn-primary">
        <x-ui.icon name="search" class="size-5"/> ابحثوا عن فعالية
    </a>
</div>

@if(session('welcome'))
    <p class="mt-4 flex items-center gap-2 rounded-2xl bg-emerald-50 px-4 py-3 font-medium text-emerald-700">
        <x-ui.icon name="check" class="size-5"/> أهلاً بكم — حسابكم جاهز. أضيفوا أطفالكم من «ملفي الشخصي» لتحجزوا لهم.
    </p>
@endif

<div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    @foreach([
        ['tone' => 'tone-violet', 'icon' => 'calendar', 'value' => $stats['upcoming'], 'label' => 'حجز قادم'],
        ['tone' => 'tone-amber', 'icon' => 'clock', 'value' => $stats['pending'], 'label' => 'قيد المراجعة'],
        ['tone' => 'tone-sky', 'icon' => 'users', 'value' => $stats['children'], 'label' => 'طفل مسجَّل'],
        ['tone' => 'tone-emerald', 'icon' => 'check', 'value' => $stats['attended'], 'label' => 'فعالية حضروها'],
    ] as $stat)
        <div class="card tone {{ $stat['tone'] }} flex-row items-center gap-3 p-5">
            <span class="icon-tile"><x-ui.icon :name="$stat['icon']"/></span>
            <div>
                <p class="text-2xl leading-tight font-bold">{{ $stat['value'] }}</p>
                <p class="text-sm text-ink-soft">{{ $stat['label'] }}</p>
            </div>
        </div>
    @endforeach
</div>

<x-push-settings :user="auth()->user()" class="mt-8"/>

<section class="mt-8">
    <div class="flex items-center justify-between gap-3">
        <h2 class="text-xl font-bold">حجوزاتكم القادمة</h2>
        <a href="{{ route('my-events') }}" class="btn btn-ghost btn-sm">عرض الكل</a>
    </div>

    @if($upcoming->isEmpty())
        <div class="mt-3 rounded-3xl border border-dashed border-brand-200 bg-brand-50/50 p-10 text-center">
            <p class="font-bold">لا حجوزات قادمة</p>
            <p class="mt-1 text-ink-soft">اختاروا فعالية قريبة من منطقتكم واحجزوا مقعداً لأطفالكم.</p>
            <a href="{{ route('events.index') }}" class="btn btn-primary mt-4">تصفّحوا الفعاليات</a>
        </div>
    @else
        <div class="mt-3 flex flex-col gap-3">
            @foreach($upcoming->take(4) as $registration)
                <x-registration-row :registration="$registration"/>
            @endforeach
        </div>
    @endif
</section>

<section class="mt-8">
    <div class="flex items-center justify-between gap-3">
        <h2 class="text-xl font-bold">آخر الإشعارات</h2>
        <a href="{{ route('notifications') }}" class="btn btn-ghost btn-sm">كل الإشعارات</a>
    </div>

    @if($notifications->isEmpty())
        <p class="mt-3 text-ink-soft">لا إشعارات بعد — سنُعلمكم فور ردّ الفريق على حجوزاتكم.</p>
    @else
        <div class="mt-3 flex flex-col gap-2">
            @foreach($notifications as $notification)
                <x-notification-row :notification="$notification"/>
            @endforeach
        </div>
    @endif
</section>

@endsection
