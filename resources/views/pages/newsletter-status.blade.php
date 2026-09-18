@extends('layouts.app')

@section('title', $title.' — '.\App\Support\Settings::get('site_name'))

@section('content')
<div class="mx-auto max-w-2xl px-4 py-16 sm:px-6">
    <div class="card items-center gap-4 p-8 text-center sm:p-10">
        <span class="icon-tile icon-tile-lg tone {{ $ok ? 'tone-emerald' : 'tone-amber' }}">
            <x-ui.icon :name="$ok ? 'check' : 'envelope'"/>
        </span>
        <h1 class="text-3xl font-bold">{{ $title }}</h1>
        <p class="max-w-md text-lg leading-relaxed text-ink-soft">{{ $message }}</p>
        <div class="mt-2 flex flex-wrap justify-center gap-3">
            <a href="{{ route('events.index') }}" class="btn btn-primary">
                <x-ui.icon name="calendar" class="size-5"/> تصفّحوا الفعاليات
            </a>
            <a href="{{ route('newsletter') }}" class="btn btn-outline">{{ $ok ? 'نشرة هذا الأسبوع' : 'الاشتراك من جديد' }}</a>
        </div>
    </div>
</div>
@endsection
