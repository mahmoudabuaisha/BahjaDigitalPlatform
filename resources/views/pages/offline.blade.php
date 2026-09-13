@extends('layouts.app')

@section('title', 'الروزنامة المحفوظة — '.\App\Support\Settings::get('site_name'))

@section('content')
{{-- الروزنامة كاملةً من ذاكرة الجهاز.

     هذه الصفحة تُخزَّن مسبقاً ويقدّمها الـ Service Worker عند انقطاع الشبكة،
     فكل ما فيها يُقرأ من النسخة المحفوظة داخل الجهاز بلا طلب واحد للسيرفر:
     فعاليات الأسبوعين مجمّعة بالأيام، بالبحث والتصفية كما لو كنّا متّصلين. --}}

<div class="mx-auto max-w-3xl px-4 py-8 sm:px-6" x-data="offlineCalendar">

    <div class="flex flex-wrap items-center gap-2">
        <span class="badge bg-brand-50 text-brand-800">
            <x-ui.icon name="download" class="size-4"/> محفوظة على جهازكم
        </span>
        <template x-if="generatedLabel && ! expired">
            <span class="text-sm text-ink-soft">آخر تحديث: <span x-text="generatedLabel"></span></span>
        </template>
    </div>

    <h1 class="mt-3 text-[30px] leading-tight sm:text-[34px]">الروزنامة معكم — حتى دون شبكة</h1>

    <p class="mt-2 max-w-prose text-[16px] leading-relaxed text-ink-soft">
        فعاليات الأسبوعين القادمين محفوظة كاملةً على جهازكم: الموعد والمكان وطريق الوصول.
        اضغطوا أي فعالية لتقرؤوا تفاصيلها دون إنترنت.
    </p>

    {{-- تحذير الصلاحية: نسخة تجاوزت يومها قد تحمل موعداً تغيّر --}}
    <template x-if="expired">
        <p class="mt-4 flex items-start gap-2 rounded-2xl bg-rose-50 px-4 py-3 leading-relaxed text-rose-800">
            <x-ui.icon name="bolt" class="mt-0.5 size-5 shrink-0"/>
            <span>
                هذه النسخة قديمة<span x-show="generatedLabel"> (<span x-text="generatedLabel"></span>)</span>
                — قد تكون بعض المواعيد تغيّرت. تأكّدوا فور عودة الشبكة.
            </span>
        </p>
    </template>

    {{-- ═══ البحث والتصفية — يعملان على البيانات المحفوظة نفسها ═══ --}}
    <div x-show="loaded && events.length > 0" x-cloak class="mt-6 flex flex-wrap gap-2">
        <label class="relative min-w-[12rem] flex-1">
            <span class="sr-only">ابحثوا في الروزنامة المحفوظة</span>
            <x-ui.icon name="search" class="pointer-events-none absolute start-3 top-1/2 size-5 -translate-y-1/2 text-ink-soft"/>
            <input type="search" x-model="query" placeholder="ابحثوا باسم الفعالية أو المكان"
                   class="input w-full ps-11">
        </label>

        <label class="shrink-0">
            <span class="sr-only">المحافظة</span>
            <select x-model="area" class="input">
                <option value="">كل المحافظات</option>
                <template x-for="name in areas" :key="name">
                    <option :value="name" x-text="name"></option>
                </template>
            </select>
        </label>
    </div>

    {{-- ═══ لا شيء محفوظ بعد ═══ --}}
    <template x-if="loaded && events.length === 0">
        <div class="mt-8 flex flex-col items-start gap-3 rounded-2xl bg-brand-50/70 px-5 py-6">
            <span class="icon-tile tone tone-sky size-12"><x-ui.icon name="calendar" class="size-6"/></span>
            <p class="leading-relaxed">
                لا توجد روزنامة محفوظة بعد. افتحوا الموقع مرّة واحدة وأنتم متّصلون،
                وستبقى فعاليات الأسبوعين معكم بعدها حتى لو انقطعت الشبكة.
            </p>
            <a href="{{ route('home') }}" class="btn btn-primary">حاولوا الاتصال الآن</a>
        </div>
    </template>

    {{-- ═══ بحث بلا نتيجة ═══ --}}
    <template x-if="loaded && events.length > 0 && days.length === 0">
        <div class="mt-8 flex flex-col items-start gap-3">
            <p class="text-ink-soft">لا فعالية تطابق بحثكم في النسخة المحفوظة.</p>
            <button type="button" @click="clear()" class="btn btn-outline btn-sm">امسحوا البحث</button>
        </div>
    </template>

    {{-- ═══ الأيام ═══ --}}
    <div class="mt-8 flex flex-col gap-8">
        <template x-for="day in days" :key="day.date">
            <section>
                <h2 class="text-[20px]" x-text="day.label"></h2>

                <div class="mt-3 flex flex-col gap-2">
                    <template x-for="event in day.events" :key="event.id">
                        <a :href="`/events/${event.id}`" class="card card-hover gap-2 p-5">
                            <div class="flex flex-wrap items-center gap-2">
                                <template x-if="event.cancelled">
                                    <span class="badge bg-rose-50 text-rose-700">أُلغيت</span>
                                </template>
                                <template x-if="event.category">
                                    <span class="badge" x-text="event.category"></span>
                                </template>
                                <span class="ms-auto shrink-0 text-sm whitespace-nowrap text-ink-soft" x-text="event.time"></span>
                            </div>

                            <h3 class="text-[20px] leading-snug" x-text="event.title"></h3>

                            <p class="flex items-start gap-1.5 text-sm leading-relaxed text-ink-soft">
                                <x-ui.icon name="map-pin" class="mt-0.5 size-4 shrink-0"/>
                                <span x-text="[event.place, event.area].filter(Boolean).join(' — ')"></span>
                            </p>

                            <template x-if="event.directions">
                                <p class="flex items-center gap-1.5 text-sm text-brand-700">
                                    <x-ui.icon name="route" class="size-4 shrink-0"/> طريق الوصول محفوظ
                                </p>
                            </template>
                        </a>
                    </template>
                </div>
            </section>
        </template>
    </div>

    {{-- ═══ ما يحتاج اتصالاً — صراحةً، بدل أزرار لا تستجيب ═══ --}}
    <section class="mt-10 border-t border-brand-100 pt-6">
        <h2 class="text-[20px]">ما يحتاج اتصالاً</h2>
        <p class="mt-1 leading-relaxed text-ink-soft">
            حجز المقاعد وإرسال التقييم والملاحظات. إن ملأتم حجزاً والشبكة مقطوعة فسنحفظه
            ونرسله تلقائياً فور عودتها.
        </p>
        <a href="{{ route('guide') }}" class="btn btn-ghost mt-2 px-0">دليل الاستخدام — متاح دائماً</a>
    </section>
</div>
@endsection
