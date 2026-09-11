@extends('layouts.organizer')

@section('title', 'تسجيلات — '.$event->title)

@section('organizer')

@php
    $pending = $registrations->where('status', \App\Enums\RegistrationStatus::Pending);
@endphp

<nav class="flex items-center gap-2 text-sm text-ink-soft" aria-label="مسار التصفح">
    <a href="{{ route('organizer.events') }}" class="no-underline hover:text-brand-700">فعالياتي</a>
    <x-ui.icon name="chevron-start" class="size-4"/>
    <span class="text-ink">التسجيلات</span>
</nav>

<div class="mt-4 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="flex items-center gap-3 text-2xl font-bold sm:text-3xl">
            <span class="icon-tile tone tone-sky size-12"><x-ui.icon name="users"/></span>
            تسجيلات «{{ $event->title }}»
        </h1>
        <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-ink-soft">
            <span class="flex items-center gap-1.5">
                <x-ui.icon name="calendar" class="size-4 text-brand-400"/>
                {{ $event->start_date->translatedFormat('l j F Y') }}
            </span>
            <span class="flex items-center gap-1.5">
                <x-ui.icon name="clock" class="size-4 text-brand-400"/>
                {{ substr($event->start_time, 0, 5) }}
            </span>
        </p>
    </div>

    <a href="{{ route('organizer.events.edit', $event) }}" class="btn btn-outline btn-sm">
        <x-ui.icon name="cog" class="size-4"/> تعديل الفعالية
    </a>
</div>

@if(session('status'))
    <p class="mt-5 flex items-center gap-2 rounded-2xl bg-emerald-50 px-4 py-3 font-bold text-emerald-700">
        <x-ui.icon name="check" class="size-5 shrink-0"/> {{ session('status') }}
    </p>
@endif

{{-- مؤشرات سريعة --}}
<dl class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
    @foreach([
        ['tone' => 'tone-amber', 'icon' => 'clock', 'value' => $pending->count(), 'label' => 'بانتظار ردّكم'],
        ['tone' => 'tone-emerald', 'icon' => 'check', 'value' => $registrations->where('status', \App\Enums\RegistrationStatus::Accepted)->count(), 'label' => 'حجوزات مقبولة'],
        ['tone' => 'tone-sky', 'icon' => 'users', 'value' => $acceptedChildren, 'label' => 'أطفال مقبولون'],
        ['tone' => 'tone-rose', 'icon' => 'x', 'value' => $registrations->where('status', \App\Enums\RegistrationStatus::Rejected)->count(), 'label' => 'اعتذارات'],
    ] as $stat)
        <div class="card tone {{ $stat['tone'] }} items-center gap-1 p-4 text-center">
            <span class="icon-tile size-10"><x-ui.icon :name="$stat['icon']"/></span>
            <span class="text-2xl font-extrabold text-ink">{{ number_format($stat['value']) }}</span>
            <span class="text-sm font-semibold text-ink-soft">{{ $stat['label'] }}</span>
        </div>
    @endforeach
</dl>

@if($registrations->isEmpty())
    <div class="card mt-6 items-center gap-3 p-10 text-center">
        <span class="icon-tile tone tone-sky size-14"><x-ui.icon name="users"/></span>
        <p class="text-lg font-bold">لا تسجيلات بعد</p>
        <p class="text-ink-soft">حين تسجّل العائلات أطفالها في هذه الفعالية ستظهر هنا لتقبلوها.</p>
    </div>
@else
    <div class="mt-6 flex flex-col gap-3">
        @foreach($registrations as $registration)
            @php
                $status = $registration->status;
                $tone = match($status) {
                    \App\Enums\RegistrationStatus::Accepted => 'tone tone-emerald',
                    \App\Enums\RegistrationStatus::Rejected => 'tone tone-rose',
                    \App\Enums\RegistrationStatus::Cancelled => 'tone tone-violet',
                    default => 'tone tone-amber',
                };
            @endphp

            <article class="card {{ $tone }} gap-4 p-5" x-data="{ rejecting: false }">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="flex items-start gap-3">
                        <span class="icon-tile size-11 shrink-0">
                            <x-ui.icon name="cake"/>
                        </span>
                        <div>
                            <p class="text-lg font-bold">
                                {{ $registration->child?->name ?? 'طفل غير مسمّى' }}
                                @if($registration->children_count > 1)
                                    <span class="text-sm font-semibold text-ink-soft">(و{{ $registration->children_count - 1 }} إخوة)</span>
                                @endif
                            </p>
                            <p class="mt-0.5 text-sm text-ink-soft">
                                وليّ الأمر: {{ $registration->user?->name }}
                                @if($registration->user?->phone)
                                    <span dir="ltr" class="font-semibold">— {{ $registration->user->phone }}</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    <span class="badge badge-tone">{{ $status->label() }}</span>
                </div>

                @if($registration->note)
                    <p class="rounded-xl bg-white/70 px-3 py-2 text-sm text-ink-soft">
                        <span class="font-bold text-ink">ملاحظة العائلة:</span> {{ $registration->note }}
                    </p>
                @endif

                @if($registration->review_note)
                    <p class="rounded-xl bg-white/70 px-3 py-2 text-sm text-ink-soft">
                        <span class="font-bold text-ink">سبب الاعتذار:</span> {{ $registration->review_note }}
                    </p>
                @endif

                @if($status === \App\Enums\RegistrationStatus::Pending)
                    <div class="flex flex-wrap gap-2 border-t border-white/70 pt-4" x-show="! rejecting">
                        <form method="POST" action="{{ route('organizer.registrations.accept', [$event, $registration]) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-sm">
                                <x-ui.icon name="check" class="size-4"/> قبول الحجز
                            </button>
                        </form>
                        <button type="button" @click="rejecting = true" class="btn btn-outline btn-sm text-rose-600">
                            <x-ui.icon name="x" class="size-4"/> اعتذار مع سبب
                        </button>
                    </div>

                    <form x-show="rejecting" x-cloak method="POST"
                          action="{{ route('organizer.registrations.reject', [$event, $registration]) }}"
                          class="flex flex-col gap-3 border-t border-white/70 pt-4">
                        @csrf
                        <label class="field">
                            <span>السبب — يصل وليّ الأمر كما هو</span>
                            <textarea name="review_note" rows="2" required maxlength="300" class="input"
                                      placeholder="مثال: اكتمل العدد لهذه الفعالية، ونرحّب بكم في القادمة."></textarea>
                        </label>
                        <div class="flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-sm bg-rose-600 text-white hover:bg-rose-700">
                                إرسال الاعتذار
                            </button>
                            <button type="button" @click="rejecting = false" class="btn btn-ghost btn-sm">تراجع</button>
                        </div>
                    </form>
                @endif
            </article>
        @endforeach
    </div>
@endif

@endsection
