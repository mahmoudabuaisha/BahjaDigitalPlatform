@extends('layouts.app')

@section('title', $team->name.' — '.\App\Support\Settings::get('site_name'))
@section('meta_description', \Illuminate\Support\Str::limit($team->description ?? 'فريق ترفيهي تطوعي لأطفال غزة', 150))

@section('og')
    @include('partials.og', [
        'ogTitle' => $team->name.' — صنّاع فرح',
        'ogDescription' => \Illuminate\Support\Str::limit($team->description ?? 'فريق ترفيهي تطوعي يقدم فعاليات لأطفال غزة', 120),
        'ogImage' => $team->logo_path,
    ])
@endsection

@section('content')
<div class="mx-auto max-w-2xl">

    <header class="rounded-3xl border border-calm-100 bg-white p-6 text-center shadow-sm">
        @if($team->logo_path)
            <img src="{{ $team->logoUrl() }}" alt="{{ $team->name }}"
                 class="mx-auto size-24 rounded-full object-cover ring-4 ring-calm-100" width="96" height="96">
        @else
            <span class="mx-auto grid size-24 place-items-center rounded-full bg-calm-100 text-4xl">🎭</span>
        @endif

        <h1 class="mt-4 text-2xl font-bold text-gray-900">{{ $team->name }}</h1>

        @if($team->description)
            <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-gray-500">{{ $team->description }}</p>
        @endif

        <div class="mt-5 flex items-center justify-center gap-6 text-center">
            <div>
                <p class="text-2xl font-bold text-joy-600">{{ number_format($completedCount) }}</p>
                <p class="text-xs font-semibold text-gray-400">فعالية منفَّذة</p>
            </div>
            <div class="h-8 w-px bg-gray-100"></div>
            <div>
                <p class="text-2xl font-bold text-calm-600">{{ number_format($childrenReached) }}</p>
                <p class="text-xs font-semibold text-gray-400">طفل مستفيد</p>
            </div>
        </div>

        @if($team->whatsappUrl())
            <a href="{{ $team->whatsappUrl() }}" target="_blank" rel="noopener"
               class="mt-5 inline-flex items-center gap-2 rounded-xl bg-[#25D366] px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:brightness-95">
                تواصلوا مع الفريق واتساب
            </a>
        @endif
    </header>

    <section class="mt-8">
        <h2 class="mb-3 text-lg font-bold text-gray-900">الفعاليات القادمة للفريق</h2>

        @if($upcoming->isEmpty())
            <div class="rounded-2xl border border-dashed border-gray-200 bg-white px-6 py-10 text-center text-sm text-gray-400">
                لا فعاليات قادمة معلنة حالياً
            </div>
        @else
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                @foreach($upcoming as $event)
                    <x-event-card :event="$event"/>
                @endforeach
            </div>
        @endif
    </section>

</div>
@endsection
