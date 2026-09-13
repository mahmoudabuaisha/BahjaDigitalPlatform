@extends('layouts.app')

@section('title', 'فعالية محفوظة — '.\App\Support\Settings::get('site_name'))

@section('content')
{{-- هيكل صفحة الفعالية دون إنترنت.

     الـ Service Worker يقدّم هذه الصفحة لأي ‎/events/{id} تعذّر جلبها، فيبقى
     العنوان في شريط المتصفّح كما هو ويعمل زرّ الرجوع والمشاركة كالمعتاد.
     كل ما يظهر هنا يُقرأ من نسخة الروزنامة المحفوظة داخل الجهاز — لا طلب
     واحد للسيرفر — فتعرف العائلةُ الموعدَ والمكانَ وطريقَ الوصول والشبكة
     مقطوعة، بدل أن تُقابَل باعتذار. --}}

<div class="mx-auto max-w-3xl px-4 py-8 sm:px-6" x-data="offlineEvent">

    <a href="{{ route('offline') }}" class="inline-flex items-center gap-1 text-sm text-ink-soft no-underline hover:text-ink">
        <x-ui.icon name="chevron-start" class="size-4"/>
        الروزنامة المحفوظة
    </a>

    {{-- ═══ جارٍ القراءة من الجهاز ═══ --}}
    <template x-if="! loaded">
        <div class="mt-6 flex flex-col gap-3">
            <div class="h-7 w-2/3 animate-pulse rounded-lg bg-brand-50"></div>
            <div class="h-4 w-1/3 animate-pulse rounded-lg bg-brand-50"></div>
            <div class="mt-4 h-40 animate-pulse rounded-2xl bg-brand-50"></div>
        </div>
    </template>

    {{-- ═══ الفعالية ليست في النسخة المحفوظة ═══ --}}
    <template x-if="loaded && ! event">
        <div class="mt-6 flex flex-col items-start gap-4">
            <span class="icon-tile tone tone-amber size-12">
                <x-ui.icon name="bolt" class="size-6"/>
            </span>

            <h1 class="text-[26px] leading-tight sm:text-[30px]">هذه الفعالية ليست ضمن النسخة المحفوظة</h1>

            <p class="max-w-prose leading-relaxed text-ink-soft">
                نحفظ على أجهزتكم فعاليات الأسبوعين القادمين. هذه الفعالية خارج تلك المدّة
                أو أُضيفت بعد آخر مرّة كنتم فيها متّصلين — افتحوها حين تعود الشبكة.
            </p>

            <div class="flex flex-wrap gap-2">
                <button type="button" @click="retry()" class="btn btn-primary">جرّبوا مرّة أخرى</button>
                <a href="{{ route('offline') }}" class="btn btn-outline">الروزنامة المحفوظة</a>
            </div>
        </div>
    </template>

    {{-- ═══ الفعالية كاملةً من ذاكرة الجهاز ═══ --}}
    <template x-if="loaded && event">
        <article class="mt-4 flex flex-col gap-6">

            <div class="flex flex-wrap items-center gap-2">
                <span class="badge bg-brand-50 text-brand-800">
                    <x-ui.icon name="download" class="size-4"/> نسخة محفوظة على جهازكم
                </span>
                <template x-if="event.cancelled">
                    <span class="badge bg-rose-50 text-rose-700">أُلغيت هذه الفعالية</span>
                </template>
                <template x-if="event.category">
                    <span class="badge" x-text="event.category"></span>
                </template>
            </div>

            <div>
                <h1 class="text-[28px] leading-tight sm:text-[34px]" x-text="event.title"></h1>
                <p class="mt-1 text-ink-soft">
                    <span x-text="event.fullDateLabel"></span> · <span x-text="event.time"></span>
                </p>
            </div>

            {{-- الصورة تظهر فقط إن كانت محفوظة أصلاً؛ وإلّا تُخفى بلا مربّع مكسور --}}
            <template x-if="event.image">
                {{-- x-on:error لا @error: الثانية توجيه Blade لا مستمع Alpine --}}
                <img :src="event.image" :alt="event.title" loading="lazy"
                     x-on:error="$el.remove()"
                     class="aspect-[16/9] w-full rounded-2xl object-cover">
            </template>

            <template x-if="event.description">
                <section>
                    <h2 class="text-[20px]">عن الفعالية</h2>
                    <p class="mt-2 leading-relaxed whitespace-pre-line" x-text="event.description"></p>
                </section>
            </template>

            {{-- ═══ التفاصيل ═══ --}}
            <div class="card gap-4 p-6">
                <h2 class="text-lg font-bold">التفاصيل</h2>

                <dl class="flex flex-col gap-4">
                    <div>
                        <dt class="flex items-center gap-3">
                            <span class="icon-tile"><x-ui.icon name="calendar"/></span>
                            <span class="text-sm text-ink-soft">اليوم والتاريخ</span>
                        </dt>
                        <dd class="-mt-4 ps-[60px] font-bold" x-text="event.fullDateLabel"></dd>
                    </div>

                    <div>
                        <dt class="flex items-center gap-3">
                            <span class="icon-tile"><x-ui.icon name="clock"/></span>
                            <span class="text-sm text-ink-soft">الوقت</span>
                        </dt>
                        <dd class="-mt-4 ps-[60px] font-bold" x-text="event.time"></dd>
                    </div>

                    <div>
                        <dt class="flex items-center gap-3">
                            <span class="icon-tile"><x-ui.icon name="map-pin"/></span>
                            <span class="text-sm text-ink-soft">المكان</span>
                        </dt>
                        <dd class="-mt-4 ps-[60px] font-bold">
                            <span x-text="event.place"></span>
                            <template x-if="event.address">
                                <span class="block font-normal text-ink-soft" x-text="event.address"></span>
                            </template>
                            <span class="block font-normal text-ink-soft" x-text="event.area"></span>
                        </dd>
                    </div>

                    <template x-if="event.ages">
                        <div>
                            <dt class="flex items-center gap-3">
                                <span class="icon-tile"><x-ui.icon name="cake"/></span>
                                <span class="text-sm text-ink-soft">الفئة العمرية</span>
                            </dt>
                            <dd class="-mt-4 ps-[60px] font-bold" x-text="event.ages"></dd>
                        </div>
                    </template>

                    <template x-if="event.audience">
                        <div>
                            <dt class="flex items-center gap-3">
                                <span class="icon-tile"><x-ui.icon name="users"/></span>
                                <span class="text-sm text-ink-soft">الفئة المستهدفة</span>
                            </dt>
                            <dd class="-mt-4 ps-[60px] font-bold" x-text="event.audience"></dd>
                        </div>
                    </template>

                    <template x-if="event.fee !== 'مجاناً'">
                        <div>
                            <dt class="flex items-center gap-3">
                                <span class="icon-tile"><x-ui.icon name="money"/></span>
                                <span class="text-sm text-ink-soft">رسوم المشاركة</span>
                            </dt>
                            <dd class="-mt-4 ps-[60px] font-bold" x-text="event.fee"></dd>
                        </div>
                    </template>

                    <template x-if="event.team">
                        <div>
                            <dt class="flex items-center gap-3">
                                <span class="icon-tile"><x-ui.icon name="hand-raised"/></span>
                                <span class="text-sm text-ink-soft">الفريق المنظِّم</span>
                            </dt>
                            <dd class="-mt-4 ps-[60px] font-bold" x-text="event.team"></dd>
                        </div>
                    </template>
                </dl>

                <p class="mt-2 border-t border-brand-100 pt-3 text-xs text-ink-soft">
                    <template x-if="generatedLabel">
                        <span>هذه النسخة محفوظة منذ <span x-text="generatedLabel"></span>.</span>
                    </template>
                    قد تكون المواعيد تغيّرت — تأكّدوا حين تعود الشبكة.
                </p>
            </div>

            {{-- ═══ كيف تصلون؟ أثمن ما في النسخة المحفوظة ═══ --}}
            <template x-if="event.directions">
                <section class="card gap-3 p-6">
                    <h2 class="flex items-center gap-2 text-lg font-bold">
                        <x-ui.icon name="route" class="size-5 text-brand-700"/> كيف تصلون؟
                    </h2>
                    <p class="leading-relaxed whitespace-pre-line" x-text="event.directions"></p>
                </section>
            </template>

            <template x-if="event.terms">
                <section class="card gap-3 p-6">
                    <h2 class="flex items-center gap-2 text-lg font-bold">
                        <x-ui.icon name="shield-check" class="size-5 text-emerald-600"/> شروط وملاحظات
                    </h2>
                    <p class="leading-relaxed whitespace-pre-line" x-text="event.terms"></p>
                </section>
            </template>

            {{-- ═══ ما لا يعمل دون شبكة — نقوله صراحةً بدل زرّ لا يستجيب ═══ --}}
            <section class="rounded-2xl bg-brand-50/70 px-5 py-4">
                <p class="flex items-start gap-2 leading-relaxed">
                    <x-ui.icon name="light-bulb" class="mt-0.5 size-5 shrink-0 text-brand-700"/>
                    <span>
                        <b>حجز المقعد يحتاج اتصالاً.</b>
                        <span x-show="event.needsApproval">هذه الفعالية بموافقة الفريق المنظِّم، </span>
                        احفظوا الموعد الآن، واحجزوا فور عودة الشبكة — سنفتح لكم الصفحة كاملة عندها.
                    </span>
                </p>
                <button type="button" @click="retry()" class="btn btn-outline btn-sm mt-3">جرّبوا الاتصال الآن</button>
            </section>
        </article>
    </template>
</div>
@endsection
