@extends('layouts.app')

{{-- قشرة حساب وليّ الأمر: قائمة جانبية ثابتة + محتوى الصفحة --}}
@section('content')

@php
    $accountLinks = [
        ['route' => 'account', 'icon' => 'grid', 'label' => 'لوحة التحكّم'],
        ['route' => 'events.index', 'icon' => 'calendar', 'label' => 'الفعاليات'],
        ['route' => 'my-events', 'icon' => 'check', 'label' => 'فعالياتي'],
        ['route' => 'account.profile', 'icon' => 'users', 'label' => 'ملفي الشخصي'],
        ['route' => 'notifications', 'icon' => 'megaphone', 'label' => 'الإشعارات'],
        ['route' => 'contact', 'icon' => 'envelope', 'label' => 'تواصلوا معنا'],
        ['route' => 'faq', 'icon' => 'book-open', 'label' => 'الأسئلة الشائعة'],
    ];
    $unread = auth()->user()->unreadNotificationsCount();
@endphp

<div class="mx-auto grid max-w-7xl gap-6 px-4 py-8 sm:px-6 lg:grid-cols-[264px_1fr]">

    <aside class="lg:sticky lg:top-24 lg:self-start">
        <div class="card gap-1 p-3">
            @foreach($accountLinks as $link)
                @php $active = request()->routeIs($link['route']); @endphp
                <a href="{{ route($link['route']) }}"
                   @if($active) aria-current="page" @endif
                   class="flex min-h-[46px] items-center gap-3 rounded-xl px-3 no-underline transition
                          {{ $active ? 'bg-brand-50 font-bold text-brand-700' : 'text-ink-soft hover:bg-brand-50/60' }}">
                    <x-ui.icon :name="$link['icon']" class="size-5 {{ $active ? 'text-brand-600' : 'text-brand-400' }}"/>
                    <span class="flex-1">{{ $link['label'] }}</span>

                    @if($link['route'] === 'notifications' && $unread)
                        <span class="grid size-6 place-items-center rounded-full bg-rose-500 text-xs font-bold text-white">{{ $unread }}</span>
                    @endif
                </a>
            @endforeach
        </div>

        <form method="POST" action="{{ route('logout') }}" class="mt-3">
            @csrf
            <button type="submit" class="btn btn-outline btn-block text-rose-600">
                <x-ui.icon name="arrow-back" class="size-5"/> تسجيل الخروج
            </button>
        </form>
    </aside>

    <div>
        @yield('account')
    </div>
</div>

@endsection
