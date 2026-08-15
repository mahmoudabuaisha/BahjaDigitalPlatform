@extends('layouts.app')

@section('title', 'تواصلوا معنا — '.\App\Support\Settings::get('site_name'))
@section('meta_description', 'تواصلوا مع فريق منصّة بَهْجَة — أسئلة شائعة وطرق تواصل مباشرة.')

@section('content')

@php
    $whatsapp = \App\Support\Settings::get('site_whatsapp');
    $whatsappDigits = $whatsapp ? preg_replace('/\D/', '', $whatsapp) : null;
@endphp

<section class="surface-tint">
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6">
        <nav class="flex items-center gap-2 text-sm text-ink-soft" aria-label="مسار التصفح">
            <a href="{{ route('home') }}" class="no-underline hover:text-brand-700">الرئيسية</a>
            <x-ui.icon name="chevron-start" class="size-4"/>
            <span class="text-ink">تواصلوا معنا</span>
        </nav>

        <h1 class="mt-3 text-4xl font-bold">تواصلوا معنا</h1>
        <p class="mt-2 max-w-2xl text-lg text-ink-soft">
            سؤال عن فعالية؟ اقتراح؟ أو فريق يريد الانضمام؟ نحن هنا لمساعدتكم — ونردّ في أقرب وقت.
        </p>
    </div>
</section>

<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6">

    {{-- ═══ طرق التواصل ═══ --}}
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        @if($whatsappDigits)
            <a href="https://wa.me/{{ $whatsappDigits }}" target="_blank" rel="noopener"
               class="card card-hover tone tone-emerald items-start gap-3 p-6 no-underline">
                <span class="icon-tile icon-tile-lg"><x-ui.icon name="whatsapp"/></span>
                <h2 class="text-lg font-bold">واتساب</h2>
                <p class="text-sm text-ink-soft">أسرع طريق للردّ خلال ساعات النهار.</p>
                <span class="font-medium text-brand-700" dir="ltr">{{ $whatsapp }}</span>
            </a>
        @else
            {{-- لم يُضبط رقم تواصل في إعدادات المنصّة بعد — نعرض الطريق البديل بدل بطاقة فارغة --}}
            <a href="{{ route('guide') }}" class="card card-hover tone tone-emerald items-start gap-3 p-6 no-underline">
                <span class="icon-tile icon-tile-lg"><x-ui.icon name="book-open"/></span>
                <h2 class="text-lg font-bold">دليل الاستخدام</h2>
                <p class="text-sm text-ink-soft">أغلب الأسئلة إجابتها في الدليل المصوّر — ثلاث خطوات بسيطة.</p>
                <span class="font-medium text-brand-700">افتحوا الدليل ←</span>
            </a>
        @endif

        <a href="{{ url('/team/register') }}" class="card card-hover tone tone-violet items-start gap-3 p-6 no-underline">
            <span class="icon-tile icon-tile-lg"><x-ui.icon name="users"/></span>
            <h2 class="text-lg font-bold">فريق يريد الانضمام؟</h2>
            <p class="text-sm text-ink-soft">سجّلوا فريقكم التطوعي وابدؤوا برفع فعالياتكم بعد الاعتماد.</p>
            <span class="font-medium text-brand-700">تسجيل فريق ←</span>
        </a>

        <a href="#contact-form" class="card card-hover tone tone-rose items-start gap-3 p-6 no-underline">
            <span class="icon-tile icon-tile-lg"><x-ui.icon name="envelope"/></span>
            <h2 class="text-lg font-bold">أرسلوا رسالة</h2>
            <p class="text-sm text-ink-soft">اكتبوا لنا، وتصل رسالتكم مباشرة إلى فريق الإدارة داخل لوحة المنصّة.</p>
            <span class="font-medium text-brand-700">اكتبوا الآن ←</span>
        </a>
    </div>

    <div class="mt-10 grid gap-8 lg:grid-cols-[1fr_1fr]">

        {{-- ═══ الأسئلة الشائعة ═══ --}}
        <section x-data="{ open: 0 }">
            <h2 class="section-title">
                <span class="icon-tile tone tone-amber size-11"><x-ui.icon name="book-open"/></span>
                أسئلة شائعة
            </h2>

            <div class="mt-5 flex flex-col gap-3">
                @foreach([
                    ['q' => 'كيف أعرف الفعاليات القريبة من مكاني؟', 'a' => 'افتحوا صفحة الفعاليات واختاروا محافظتكم ومركز الإيواء — ستظهر فعاليات منطقتكم وحدها، مرتّبة بحسب الأقرب موعداً.'],
                    ['q' => 'هل يلزم التسجيل لحضور فعالية؟', 'a' => 'لا. الفعاليات مفتوحة ومجانية، وتكفي معرفة الموعد والمكان. لا نطلب منكم أي بيانات شخصية.'],
                    ['q' => 'هل الفعاليات مناسبة لجميع الأعمار؟', 'a' => 'كل فعالية تذكر فئتها العمرية حين يحدّدها الفريق المنظّم، وتجدونها على بطاقة الفعالية وفي صفحتها.'],
                    ['q' => 'هل يعمل الموقع دون إنترنت؟', 'a' => 'نعم. بعد أول زيارة تُحفظ الروزنامة على جهازكم، وتستطيعون مراجعة المواعيد والأماكن حتى لو انقطعت الشبكة.'],
                    ['q' => 'كيف ينضمّ فريقنا التطوعي إلى المنصّة؟', 'a' => 'سجّلوا فريقكم من صفحة تسجيل الفرق، وبعد اعتماد الإدارة تستطيعون رفع جدول فعالياتكم بأنفسكم.'],
                ] as $index => $faq)
                    <div class="card overflow-hidden">
                        <h3>
                            <button type="button" @click="open === {{ $index }} ? open = null : open = {{ $index }}"
                                    :aria-expanded="open === {{ $index }} ? 'true' : 'false'"
                                    class="flex w-full items-center gap-3 p-5 text-start font-bold">
                                <span class="flex-1">{{ $faq['q'] }}</span>
                                <x-ui.icon name="chevron-down" class="size-5 shrink-0 text-brand-500 transition"
                                        x-bind:class="open === {{ $index }} ? 'rotate-180' : ''"/>
                            </button>
                        </h3>
                        <div x-show="open === {{ $index }}" x-cloak x-transition.opacity.duration.200ms>
                            <p class="border-t border-brand-100 px-5 py-4 leading-relaxed text-ink-soft">{{ $faq['a'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- ═══ نموذج الرسالة ═══ --}}
        <section id="contact-form">
            <h2 class="section-title">
                <span class="icon-tile tone tone-violet size-11"><x-ui.icon name="envelope"/></span>
                أرسلوا لنا رسالة
            </h2>

            @if(session('contact_sent'))
                <p class="mt-4 flex items-center gap-2 rounded-2xl bg-emerald-50 px-4 py-3 font-medium text-emerald-700">
                    <x-ui.icon name="check" class="size-5"/> وصلتنا رسالتكم — شكراً لكم، وسنردّ قريباً.
                </p>
            @endif

            <form method="POST" action="{{ route('contact.store') }}" class="card mt-5 gap-4 p-6">
                @csrf

                {{-- فخ البوتات — مخفي عن البشر --}}
                <input type="text" name="website" value="" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="field">
                        <span>الاسم <span class="text-brand-500">*</span></span>
                        <input type="text" name="contact_name" value="{{ old('contact_name') }}" maxlength="100" required class="input">
                        @error('contact_name') <span class="mt-1 block text-sm text-rose-600">{{ $message }}</span> @enderror
                    </label>

                    <label class="field">
                        <span>البريد الإلكتروني</span>
                        <input type="email" name="contact_email" value="{{ old('contact_email') }}" maxlength="120" dir="ltr" class="input">
                        @error('contact_email') <span class="mt-1 block text-sm text-rose-600">{{ $message }}</span> @enderror
                    </label>

                    <label class="field">
                        <span>رقم الجوال</span>
                        <input type="tel" name="contact_phone" value="{{ old('contact_phone') }}" maxlength="20" dir="ltr" class="input">
                    </label>

                    <label class="field">
                        <span>الموضوع</span>
                        <input type="text" name="subject" value="{{ old('subject') }}" maxlength="120" class="input" placeholder="استفسار عن فعالية">
                    </label>
                </div>

                <label class="field">
                    <span>الرسالة <span class="text-brand-500">*</span></span>
                    <textarea name="message" rows="5" maxlength="1000" required class="input"
                              placeholder="اكتبوا رسالتكم هنا…">{{ old('message') }}</textarea>
                    @error('message') <span class="mt-1 block text-sm text-rose-600">{{ $message }}</span> @enderror
                </label>

                <button type="submit" class="btn btn-primary btn-block">
                    <x-ui.icon name="envelope" class="size-5"/> إرسال الرسالة
                </button>

                <p class="text-center text-sm text-ink-soft">لا نشارك بياناتكم مع أي جهة، وتصل الرسالة إلى فريق المنصّة وحده.</p>
            </form>
        </section>
    </div>
</div>

@endsection
