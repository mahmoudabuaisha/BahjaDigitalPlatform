@extends('layouts.app')

{{-- قشرة لوحة الفريق المنظِّم --}}
@section('content')

@php
    $organizerLinks = [
        ['route' => 'organizer.dashboard', 'icon' => 'grid', 'label' => 'لوحة التحكّم', 'url' => null],
        ['route' => 'organizer.events', 'icon' => 'calendar', 'label' => 'فعالياتي', 'url' => null],
        ['route' => null, 'icon' => 'plus', 'label' => 'إضافة فعالية', 'url' => url('/team/events/create')],
        ['route' => null, 'icon' => 'bolt', 'label' => 'تسجيل الحضور والتقارير', 'url' => url('/team')],
        ['route' => 'notifications', 'icon' => 'megaphone', 'label' => 'الإشعارات', 'url' => null],
        ['route' => 'contact', 'icon' => 'envelope', 'label' => 'تواصلوا معنا', 'url' => null],
    ];
    $unread = auth()->user()->unreadNotificationsCount();
@endphp

<div class="mx-auto grid max-w-7xl gap-6 px-4 py-8 sm:px-6 lg:grid-cols-[264px_1fr]">

    <aside class="lg:sticky lg:top-24 lg:self-start">
        <div class="card items-center gap-2 p-5 text-center">
            <span class="grid size-16 place-items-center rounded-full bg-brand-100 text-2xl font-bold text-brand-700">
                {{ mb_substr(auth()->user()->team?->name ?? auth()->user()->name, 0, 1) }}
            </span>
            <p class="font-bold">{{ auth()->user()->name }}</p>
            <p class="text-sm text-ink-soft">منظِّم فعاليات — {{ auth()->user()->team?->name }}</p>
        </div>

        <div class="card mt-3 gap-1 p-3">
            @foreach($organizerLinks as $link)
                @php
                    $active = $link['route'] && request()->routeIs($link['route']);
                    $href = $link['url'] ?? route($link['route']);
                @endphp
                <a href="{{ $href }}"
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
        @yield('organizer')
    </div>
</div>

@endsection
