@extends('layouts.app')

@section('title', $event->title.' — '.\App\Support\Settings::get('site_name'))
@section('meta_description', \Illuminate\Support\Str::limit($event->description ?? 'فعالية لأطفال غزة — '.$event->title, 150))

@section('og')
    @include('partials.og', [
        'ogType' => 'article',
        'ogTitle' => $event->title.' — '.$event->start_date->translatedFormat('l j F'),
        'ogDescription' => $event->publicPlaceName().' · الساعة '.substr($event->start_time, 0, 5).($event->description ? ' — '.\Illuminate\Support\Str::limit($event->description, 100) : ''),
        'ogImage' => $event->image_path,
    ])
@endsection

@section('content')

@php $tone = $event->category?->toneClass() ?? 'tone tone-violet'; @endphp

<article data-track-event="{{ $event->id }}" class="{{ $tone }}">

    <section class="surface-tint">
        <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6">
            <nav class="flex items-center gap-2 text-sm text-ink-soft" aria-label="مسار التصفح">
                <a href="{{ route('home') }}" class="no-underline hover:text-brand-700">الرئيسية</a>
                <x-ui.icon name="chevron-start" class="size-4"/>
                <a href="{{ route('events.index') }}" class="no-underline hover:text-brand-700">الفعاليات</a>
                <x-ui.icon name="chevron-start" class="size-4"/>
                <span class="line-clamp-1 text-ink">{{ $event->title }}</span>
            </nav>

            @if(session('feedback_sent'))
                <p class="mt-4 flex items-center gap-2 rounded-2xl bg-emerald-50 px-4 py-3 font-medium text-emerald-700">
                    <x-ui.icon name="check" class="size-5"/> شكراً لكم — تقييمكم يساعدنا على تحسين الفعاليات القادمة.
                </p>
            @endif

            @if(session('feedback_error'))
                <p class="mt-4 rounded-2xl bg-amber-50 px-4 py-3 font-medium text-amber-800">
                    {{ session('feedback_error') }}
                </p>
            @endif

            <div class="mt-4 flex flex-wrap items-center gap-2">
                @if($event->start_date->isToday())
                    <span class="badge bg-brand-600 text-white">اليوم</span>
                @endif
                @if($event->category)
                    <span class="badge badge-tone">
                        <x-ui.icon :name="$event->category->iconKey()" class="size-4"/> {{ $event->category->name }}
                    </span>
                @endif
                @if($event->status === \App\Enums\EventStatus::Completed)
                    <span class="badge">فعالية منتهية</span>
                @else
                    <span class="badge"><x-ui.icon name="shield-check" class="size-4 text-emerald-500"/> معتمَدة من الإدارة</span>
                @endif
            </div>

            <h1 class="mt-3 text-3xl leading-tight font-bold sm:text-4xl">{{ $event->title }}</h1>
        </div>
    </section>

    <div class="mx-auto grid max-w-5xl gap-8 px-4 py-8 sm:px-6 lg:grid-cols-[1.5fr_1fr]">

        <div>
            @if($event->image_path)
                <img src="{{ $event->imageUrl() }}" alt="{{ $event->title }}" width="1280" height="720" loading="eager"
                     class="aspect-[16/9] w-full rounded-3xl object-cover shadow-[0_10px_40px_rgb(31_25_55_/_10%)]">
            @else
                <x-ui.scene :name="$event->category?->sceneName() ?? 'default'"
                            :tone="$event->category?->toneHex() ?? '#3b93e4'" fit="meet"
                            class="aspect-[16/7] w-full rounded-3xl shadow-[0_10px_40px_rgb(31_25_55_/_10%)]"/>
            @endif

            @if($event->description)
                <h2 class="mt-8 text-xl font-bold">عن الفعالية</h2>
                <p class="mt-2 text-lg leading-relaxed text-ink-soft">{{ $event->description }}</p>
            @endif

            {{-- التقييم بعد انتهاء الفعالية --}}
            @if($event->hasEnded() || $event->status === \App\Enums\EventStatus::Completed)
                <section class="card mt-8 gap-3 p-6">
                    <h2 class="text-xl font-bold">كيف كانت الفعالية؟</h2>
                    <p class="text-ink-soft">بلا اسم وبلا أي بيانات شخصية — تقييمكم يصل الفريق والإدارة فقط.</p>

                    <form method="POST" action="{{ route('events.feedback', $event) }}" class="mt-2 flex flex-col items-start gap-4">
                        @csrf
                        <input type="text" name="website" value="" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">

                        <x-star-rating/>

                        @error('rating')
                            <p class="text-sm text-rose-600">اختاروا عدد النجوم أولاً</p>
                        @enderror

                        <textarea name="message" rows="3" maxlength="1000" class="input"
                                  placeholder="ملاحظات إضافية (اختياري)"></textarea>

                        <button type="submit" class="btn btn-primary">إرسال التقييم</button>
                    </form>
                </section>
            @endif
        </div>

        {{-- ═══ بطاقة التفاصيل ═══ --}}
        <aside class="flex flex-col gap-5">
            <div class="card gap-4 p-6">
                <h2 class="text-lg font-bold">التفاصيل</h2>

                <dl class="flex flex-col gap-4">
                    <div>
                        <dt class="flex items-center gap-3">
                            <span class="icon-tile"><x-ui.icon name="calendar"/></span>
                            <span class="text-sm text-ink-soft">اليوم والتاريخ</span>
                        </dt>
                        <dd class="-mt-4 ps-[60px] font-bold">{{ $event->start_date->translatedFormat('l j F Y') }}</dd>
                    </div>

                    <div>
                        <dt class="flex items-center gap-3">
                            <span class="icon-tile"><x-ui.icon name="clock"/></span>
                            <span class="text-sm text-ink-soft">الوقت</span>
                        </dt>
                        <dd class="-mt-4 ps-[60px] font-bold">{{ substr($event->start_time, 0, 5) }}@if($event->end_time) — {{ substr($event->end_time, 0, 5) }}@endif</dd>
                    </div>

                    <div>
                        <dt class="flex items-center gap-3">
                            <span class="icon-tile"><x-ui.icon name="map-pin"/></span>
                            <span class="text-sm text-ink-soft">المكان</span>
                        </dt>
                        <dd class="-mt-4 ps-[60px] font-bold">{{ $event->publicPlaceName() }}
                                @if($event->publicLocationDetails())
                                    <span class="block font-normal text-ink-soft">{{ $event->publicLocationDetails() }}</span>
                                @endif
                                <span class="block font-normal text-ink-soft">{{ $event->area->name }}</span></dd>
                    </div>

                    @if($event->ageLabel())
                        <div>
                        <dt class="flex items-center gap-3">
                            <span class="icon-tile"><x-ui.icon name="cake"/></span>
                            <span class="text-sm text-ink-soft">الفئة العمرية</span>
                        </dt>
                        <dd class="-mt-4 ps-[60px] font-bold">{{ $event->ageLabel() }}</dd>
                    </div>
                    @endif

                    @if($event->expected_children)
                        <div>
                        <dt class="flex items-center gap-3">
                            <span class="icon-tile"><x-ui.icon name="users"/></span>
                            <span class="text-sm text-ink-soft">العدد المتوقّع</span>
                        </dt>
                        <dd class="-mt-4 ps-[60px] font-bold">{{ $event->expected_children }} طفلاً</dd>
                    </div>
                    @endif

                    @if($event->fee > 0)
                        <div>
                        <dt class="flex items-center gap-3">
                            <span class="icon-tile"><x-ui.icon name="money"/></span>
                            <span class="text-sm text-ink-soft">رسوم المشاركة</span>
                        </dt>
                        <dd class="-mt-4 ps-[60px] font-bold">{{ $event->feeLabel() }}</dd>
                    </div>
                    @endif
                </dl>

                {{-- طمأنة عن حداثة المعلومات — مهمة لمن يتصفح نسخة الأوفلاين (القسم 10) --}}
                <p class="mt-2 border-t border-brand-100 pt-3 text-xs text-ink-soft" title="{{ $event->updated_at->translatedFormat('l j F Y — H:i') }}">
                    آخر تحديث لمعلومات الفعالية: {{ $event->updated_at->diffForHumans() }}
                </p>

                @if($event->terms)
                    <div class="mt-5 rounded-2xl bg-brand-50/70 p-4">
                        <p class="flex items-center gap-2 font-bold text-brand-700">
                            <x-ui.icon name="shield-check" class="size-5"/> شروط وملاحظات
                        </p>
                        <p class="mt-1 whitespace-pre-line text-ink-soft">{{ $event->terms }}</p>
                    </div>
                @endif
            </div>

            {{-- ═══ حجز مقعد ═══ --}}
            @php
                $remaining = $event->seatsRemaining();
                $myChildren = auth()->check() ? auth()->user()->children()->orderBy('birth_date')->get() : collect();
                $myRegistrations = auth()->check()
                    ? $event->registrations()->where('user_id', auth()->id())->with('child')->get()
                    : collect();
                $bookedChildIds = $myRegistrations->whereIn('status', \App\Enums\RegistrationStatus::holdingSeat())->pluck('child_id');
                $bookableChildren = $myChildren->reject(fn ($child) => $bookedChildIds->contains($child->id));
            @endphp

            <div class="card gap-3 p-6">
                <div class="flex items-center justify-between gap-2">
                    <h2 class="text-lg font-bold">حجز مقعد</h2>
                    @if($remaining !== null)
                        <span class="badge {{ $remaining > 0 ? '' : 'bg-rose-50 text-rose-700' }}">
                            {{ $remaining > 0 ? 'بقي '.$remaining.' مقعد من '.$event->expected_children : 'اكتمل العدد' }}
                        </span>
                    @endif
                </div>

                @if(session('registration_done'))
                    <p class="flex items-start gap-2 rounded-2xl bg-emerald-50 px-4 py-3 font-medium text-emerald-700">
                        <x-ui.icon name="check" class="mt-0.5 size-5 shrink-0"/>
                        وصل طلبكم — يراجعه الفريق ويصلكم إشعار بالردّ.
                    </p>
                @endif

                @if(session('registration_cancelled'))
                    <p class="rounded-2xl bg-brand-50 px-4 py-3 text-brand-700">أُلغي حجزكم.</p>
                @endif

                @if(session('registration_error'))
                    <p class="rounded-2xl bg-rose-50 px-4 py-3 text-rose-700">{{ session('registration_error') }}</p>
                @endif

                @guest
                    <p class="text-ink-soft">سجّلوا الدخول لحجز مقاعد لأطفالكم — الحجز مجاني ولا يستغرق دقيقة.</p>
                    <a href="{{ route('login') }}" class="btn btn-primary btn-block">تسجيل الدخول للحجز</a>
                    <a href="{{ route('register') }}" class="btn btn-ghost btn-block">ليس لديكم حساب؟ أنشئوا واحداً</a>
                @endguest

                @auth
                    {{-- حجوزات هذه العائلة في هذه الفعالية --}}
                    @foreach($myRegistrations as $registration)
                        @php
                            $tone = match ($registration->displayStatus()) {
                                'accepted', 'completed' => 'bg-emerald-50 text-emerald-700',
                                'pending' => 'bg-amber-50 text-amber-700',
                                'rejected' => 'bg-rose-50 text-rose-700',
                                default => 'bg-brand-50 text-ink-soft',
                            };
                        @endphp
                        <div class="rounded-2xl {{ $tone }} px-4 py-3">
                            <p class="flex flex-wrap items-center gap-2 font-bold">
                                {{ $registration->child?->name ?? 'حجزكم' }}
                                <span class="text-sm font-normal">— {{ $registration->status->getLabel() }}</span>
                            </p>
                            @if($registration->review_note)
                                <p class="mt-1 text-sm">{{ $registration->review_note }}</p>
                            @endif

                            @if($registration->isCancellable())
                                <form method="POST" action="{{ route('registrations.destroy', $registration) }}"
                                      onsubmit="return confirm('هل تريدون إلغاء الحجز؟')" class="mt-2">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm font-bold text-rose-600">إلغاء الحجز</button>
                                </form>
                            @endif
                        </div>
                    @endforeach

                    @if($myChildren->isEmpty())
                        <p class="text-ink-soft">أضيفوا أطفالكم أولاً كي نحجز باسم كل طفل.</p>
                        <a href="{{ route('account.profile') }}" class="btn btn-primary btn-block">
                            <x-ui.icon name="plus" class="size-5"/> أضيفوا طفلاً
                        </a>
                    @elseif($event->acceptsRegistrations() && $bookableChildren->isNotEmpty())
                        {{-- حجوزات محفوظة على الجهاز بانتظار عودة الشبكة --}}
                        <p data-queued-bookings class="hidden mb-3 flex items-start gap-2 rounded-2xl bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            <x-ui.icon name="clock" class="mt-0.5 size-4 shrink-0"/>
                            <span>
                                لديكم <b data-queued-count>0</b> طلب حجز محفوظ على هذا الجهاز — يُرسل تلقائياً فور عودة الإنترنت.
                            </span>
                        </p>

                        <form method="POST" action="{{ route('registrations.store', $event) }}" data-booking-form class="flex flex-col gap-3">
                            @csrf

                            <div class="field">
                                <span>لمن تحجزون؟</span>
                                <div class="flex flex-col gap-2">
                                    @foreach($bookableChildren as $child)
                                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-brand-100 p-3 has-[:checked]:border-brand-400 has-[:checked]:bg-brand-50">
                                            <input type="checkbox" name="children[]" value="{{ $child->id }}" class="size-5 accent-brand-600">
                                            <span class="flex-1 font-medium">{{ $child->nameWithAge() }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <label class="field">
                                <span>ملاحظة للفريق (اختياري)</span>
                                <input type="text" name="note" maxlength="300" class="input" placeholder="مثال: طفل يحتاج مرافقاً">
                            </label>

                            <button type="submit" class="btn btn-primary btn-block">أرسلوا طلب الحجز</button>
                            <p class="text-center text-sm text-ink-soft">
                                يراجع الفريق الطلب ويصلكم إشعار بالردّ.
                                وإن انقطع الإنترنت يُحفظ الطلب على جهازكم ويُرسل تلقائياً عند عودته.
                            </p>
                        </form>
                    @elseif($bookableChildren->isEmpty() && $myRegistrations->isNotEmpty())
                        <a href="{{ route('my-events') }}" class="btn btn-outline btn-block">كل حجوزاتي</a>
                    @else
                        <p class="text-ink-soft">
                            {{ $event->hasEnded() ? 'انتهى موعد هذه الفعالية.' : 'اكتمل العدد في هذه الفعالية — تابعوا الروزنامة لفعاليات أخرى.' }}
                        </p>
                        <a href="{{ route('events.index') }}" class="btn btn-outline btn-block">فعاليات أخرى</a>
                    @endif
                @endauth
            </div>

            {{-- الفريق المنظّم --}}
            <a href="{{ route('teams.show', $event->team) }}" class="card card-hover flex-row items-center gap-4 p-5 no-underline">
                @if($event->team->logo_path)
                    <img src="{{ $event->team->logoUrl() }}" alt="" width="56" height="56" class="size-14 rounded-2xl object-cover">
                @else
                    <span class="icon-tile icon-tile-lg"><x-ui.icon name="users"/></span>
                @endif
                <div class="min-w-0 flex-1">
                    <p class="text-sm text-ink-soft">الفريق المنظِّم</p>
                    <p class="truncate font-bold">{{ $event->team->name }}</p>
                </div>
                <x-ui.icon name="chevron-start" class="size-5 text-brand-400"/>
            </a>

            {{-- المشاركة --}}
            <div class="card gap-3 p-6">
                <h2 class="text-lg font-bold">شاركوا الفعالية</h2>
                <p class="text-sm text-ink-soft">أوصلوها لأهالي منطقتكم — مجموعة العائلة أو الحيّ.</p>
                <x-share-buttons :title="$event->title.' — '.$event->start_date->translatedFormat('l j F').' الساعة '.substr($event->start_time, 0, 5).' في '.($event->shelterCenter?->name ?? $event->area->name)"
                                 :url="route('events.show', $event)"
                                 :qr-url="route('events.qr', $event)"
                                 :short-url="$event->shortUrl()"/>
            </div>
        </aside>
    </div>

    {{-- فعاليات قريبة --}}
    @if($related->isNotEmpty())
        <section class="surface-tint py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6">
                <h2 class="section-title">فعاليات أخرى في {{ $event->area->name }}</h2>
                <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($related as $relatedEvent)
                        <x-event-card :event="$relatedEvent"/>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

</article>
@endsection
