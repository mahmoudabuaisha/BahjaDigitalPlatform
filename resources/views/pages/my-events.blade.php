@extends('layouts.app')

@section('title', 'فعالياتي — '.\App\Support\Settings::get('site_name'))

@section('content')

<section class="surface-tint">
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6">
        @if(session('welcome'))
            <p class="mb-4 flex items-center gap-2 rounded-2xl bg-emerald-50 px-4 py-3 font-medium text-emerald-700">
                <x-ui.icon name="check" class="size-5"/> أهلاً بكم في {{ \App\Support\Settings::get('site_name') }} — حسابكم جاهز.
            </p>
        @endif

        <h1 class="flex items-center gap-3 text-4xl font-bold">
            <span class="icon-tile tone tone-violet icon-tile-lg"><x-ui.icon name="calendar"/></span>
            فعالياتي
        </h1>
        <p class="mt-2 text-lg text-ink-soft">
            المقاعد التي حجزتموها لأطفالكم — يمكنكم تعديل العدد أو الإلغاء في أي وقت.
        </p>
    </div>
</section>

<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6">

    <h2 class="section-title">الحجوزات القادمة</h2>

    @if($upcoming->isEmpty())
        <div class="mt-4 rounded-3xl border border-dashed border-brand-200 bg-brand-50/50 p-12 text-center">
            <span class="icon-tile tone tone-violet icon-tile-lg mx-auto"><x-ui.icon name="calendar"/></span>
            <p class="mt-3 text-lg font-bold">لا حجوزات قادمة</p>
            <p class="mt-1 text-ink-soft">تصفّحوا الفعاليات واحجزوا مقعداً لأطفالكم.</p>
            <a href="{{ route('events.index') }}" class="btn btn-primary mt-4">تصفّحوا الفعاليات</a>
        </div>
    @else
        <div class="mt-4 flex flex-col gap-4">
            @foreach($upcoming as $registration)
                @php $event = $registration->event; @endphp
                <article class="card {{ $event->category?->toneClass() ?? 'tone tone-violet' }} gap-4 p-6 sm:flex-row sm:items-center">
                    <span class="icon-tile icon-tile-lg"><x-ui.icon :name="$event->category?->iconKey() ?? 'sparkles'"/></span>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="badge badge-tone">{{ $registration->status->getLabel() }}</span>
                            <span class="badge"><x-ui.icon name="users" class="size-4"/> {{ $registration->children_count }} أطفال</span>
                        </div>

                        <h3 class="mt-2 text-lg font-bold">
                            <a href="{{ route('events.show', $event) }}" class="no-underline hover:text-brand-700">{{ $event->title }}</a>
                        </h3>

                        <p class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-ink-soft">
                            <span class="flex items-center gap-1.5">
                                <x-ui.icon name="calendar" class="size-4 text-brand-400"/>
                                {{ $event->start_date->translatedFormat('l j F') }}
                            </span>
                            <span class="flex items-center gap-1.5">
                                <x-ui.icon name="clock" class="size-4 text-brand-400"/>
                                {{ substr($event->start_time, 0, 5) }}
                            </span>
                            <span class="flex items-center gap-1.5">
                                <x-ui.icon name="map-pin" class="size-4 text-brand-400"/>
                                {{ $event->shelterCenter?->name ?? $event->area?->name }}
                            </span>
                        </p>
                    </div>

                    <div class="flex shrink-0 gap-2">
                        <a href="{{ route('events.show', $event) }}" class="btn btn-outline btn-sm">التفاصيل</a>

                        @if($registration->status === \App\Enums\RegistrationStatus::Confirmed)
                            <form method="POST" action="{{ route('registrations.destroy', $event) }}"
                                  onsubmit="return confirm('هل تريدون إلغاء الحجز؟')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-ghost btn-sm text-rose-600">إلغاء الحجز</button>
                            </form>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    @endif

    @if($past->isNotEmpty())
        <h2 class="section-title mt-12">حجوزات سابقة</h2>
        <div class="mt-4 flex flex-col gap-3">
            @foreach($past as $registration)
                <article class="card flex-row items-center gap-4 p-5 opacity-80">
                    <div class="min-w-0 flex-1">
                        <h3 class="truncate font-bold">{{ $registration->event->title }}</h3>
                        <p class="text-sm text-ink-soft">
                            {{ $registration->event->start_date->translatedFormat('j F Y') }} ·
                            {{ $registration->children_count }} أطفال
                        </p>
                    </div>
                    <a href="{{ route('events.show', $registration->event) }}" class="btn btn-ghost btn-sm shrink-0">قيّموها</a>
                </article>
            @endforeach
        </div>
    @endif
</div>

@endsection
