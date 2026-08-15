@extends('layouts.app')

@section('title', 'الأسئلة الشائعة — '.\App\Support\Settings::get('site_name'))
@section('meta_description', 'إجابات عن أكثر ما تسأل عنه العائلات: الحجز، الأعمار، الإلغاء، والعمل دون إنترنت.')

@section('content')

<section class="surface-tint">
    <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
        <nav class="flex items-center gap-2 text-sm text-ink-soft" aria-label="مسار التصفح">
            <a href="{{ route('home') }}" class="no-underline hover:text-brand-700">الرئيسية</a>
            <x-ui.icon name="chevron-start" class="size-4"/>
            <span class="text-ink">الأسئلة الشائعة</span>
        </nav>

        <h1 class="mt-3 flex items-center gap-3 text-4xl font-bold">
            <span class="icon-tile tone tone-amber icon-tile-lg"><x-ui.icon name="book-open"/></span>
            الأسئلة الشائعة
        </h1>
        <p class="mt-2 text-lg text-ink-soft">أكثر ما تسأل عنه العائلات — وإن لم تجدوا إجابتكم، راسلونا.</p>
    </div>
</section>

<div class="mx-auto max-w-3xl px-4 py-10 sm:px-6" x-data="{ open: 0 }">
    <div class="flex flex-col gap-3">
        @foreach([
            ['q' => 'كيف أحجز مقعداً لطفلي؟', 'a' => 'أنشئوا حساباً، أضيفوا أطفالكم من «ملفي الشخصي»، ثم افتحوا الفعالية واضغطوا «احجزوا مقعداً» واختاروا الطفل. يصل الطلب إلى الفريق المنظّم للموافقة.'],
            ['q' => 'لماذا يظهر حجزي «قيد المراجعة»؟', 'a' => 'لأن الفريق المنظّم يراجع الطلبات ليضمن أن العدد يناسب المكان والفئة العمرية. سيصلكم إشعار فور القبول أو الاعتذار.'],
            ['q' => 'هل يمكنني إلغاء الحجز؟', 'a' => 'نعم، حتى 24 ساعة قبل موعد الفعالية. بعدها يكون الفريق قد جهّز المكان والمواد على عددكم — تواصلوا معه مباشرة إن اضطررتم.'],
            ['q' => 'كيف أعرف الفعاليات القريبة من مكاني؟', 'a' => 'من صفحة الفعاليات اختاروا محافظتكم ومركز الإيواء — ستظهر فعاليات منطقتكم وحدها مرتّبة بحسب الأقرب موعداً.'],
            ['q' => 'هل الفعاليات مناسبة لعمر طفلي؟', 'a' => 'كل فعالية تذكر فئتها العمرية حين يحدّدها الفريق، وتجدونها على بطاقة الفعالية وفي صفحتها، ويمكنكم التصفية بالعمر.'],
            ['q' => 'هل الفعاليات مجانية؟', 'a' => 'نعم، كل ما يُنشر على المنصّة مجاني تماماً — مبادرة تطوعية لأطفال غزة.'],
            ['q' => 'هل يعمل الموقع دون إنترنت؟', 'a' => 'بعد أول زيارة تُحفظ الروزنامة على جهازكم، فتستطيعون مراجعة المواعيد والأماكن حتى لو انقطعت الشبكة. الحجز وحده يحتاج اتصالاً.'],
            ['q' => 'كيف ينضمّ فريقنا التطوعي؟', 'a' => 'سجّلوا فريقكم من صفحة تسجيل الفرق، وبعد اعتماد الإدارة تستطيعون رفع جدول فعالياتكم ومتابعة حجوزات العائلات.'],
        ] as $index => $faq)
            <div class="card overflow-hidden">
                <h2>
                    <button type="button" @click="open === {{ $index }} ? open = null : open = {{ $index }}"
                            :aria-expanded="open === {{ $index }} ? 'true' : 'false'"
                            class="flex w-full items-center gap-3 p-5 text-start font-bold">
                        <span class="flex-1">{{ $faq['q'] }}</span>
                        <x-ui.icon name="chevron-down" class="size-5 shrink-0 text-brand-500 transition"
                                   x-bind:class="open === {{ $index }} ? 'rotate-180' : ''"/>
                    </button>
                </h2>
                <div x-show="open === {{ $index }}" x-cloak x-transition.opacity.duration.200ms>
                    <p class="border-t border-brand-100 px-5 py-4 leading-relaxed text-ink-soft">{{ $faq['a'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card mt-8 items-center gap-3 p-8 text-center">
        <span class="icon-tile tone tone-violet icon-tile-lg"><x-ui.icon name="envelope"/></span>
        <h2 class="text-xl font-bold">لم تجدوا إجابتكم؟</h2>
        <p class="text-ink-soft">راسلونا وسنردّ في أقرب وقت.</p>
        <a href="{{ route('contact') }}" class="btn btn-primary">تواصلوا معنا</a>
    </div>
</div>

@endsection
