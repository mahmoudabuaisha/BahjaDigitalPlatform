@extends('layouts.account')

@section('title', 'الإشعارات — '.\App\Support\Settings::get('site_name'))

@section('account')

<div class="flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-3xl font-bold">الإشعارات</h1>
        <p class="mt-1 text-ink-soft">ردود الفرق على حجوزاتكم وتحديثات الفعاليات.</p>
    </div>

    @if(auth()->user()->unreadNotificationsCount())
        <form method="POST" action="{{ route('notifications.read') }}">
            @csrf
            <button type="submit" class="btn btn-outline btn-sm">تعليم الكل كمقروء</button>
        </form>
    @endif
</div>

@if($notifications->isEmpty())
    <div class="mt-6 rounded-3xl border border-dashed border-brand-200 bg-brand-50/50 p-12 text-center">
        <span class="icon-tile tone tone-violet icon-tile-lg mx-auto"><x-ui.icon name="megaphone"/></span>
        <p class="mt-3 text-lg font-bold">لا إشعارات بعد</p>
        <p class="mt-1 text-ink-soft">سنُعلمكم فور ردّ الفريق على حجوزاتكم.</p>
    </div>
@else
    <div class="mt-6 flex flex-col gap-3">
        @foreach($notifications as $notification)
            <x-notification-row :notification="$notification"/>
        @endforeach
    </div>

    <div class="mt-6">{{ $notifications->links('partials.pagination') }}</div>
@endif

@endsection
