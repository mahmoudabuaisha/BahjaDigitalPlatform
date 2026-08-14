@extends('layouts.app')

@section('title', 'دون اتصال — '.\App\Support\Settings::get('site_name'))

@section('content')
{{-- هذه الصفحة تُخزَّن مسبقاً وتُقدَّم من الـ Service Worker عند انقطاع الشبكة:
     كل ما فيها يُقرأ من ذاكرة الجهاز، بلا أي طلب للسيرفر. --}}
<div class="max-w-2xl" x-data="savedEvents">

    <h1 class="text-[30px] leading-tight sm:text-[34px]">الروزنامة معكم — حتى دون شبكة</h1>

    <p class="mt-2 text-[16px] leading-relaxed text-ash-800">
        هذه آخر نسخة محفوظة على جهازكم من زيارتكم السابقة. الفعاليات التي فتحتموها من قبل تبقى مقروءة كاملة.
    </p>

    <a href="{{ route('home') }}" class="btn btn-secondary btn-block mt-4 max-w-xs">العودة إلى الروزنامة المحفوظة</a>

    <section class="mt-8">
        <h2 class="text-[20px]">محفوظ على جهازكم</h2>

        <template x-if="loaded && events.length === 0">
            <p class="mt-2 text-ash-800">
                لا توجد فعاليات محفوظة بعد — افتحوا الروزنامة مرة واحدة وأنتم متصلون، وستبقى معكم بعدها.
            </p>
        </template>

        <div class="mt-2 flex flex-col gap-2">
            <template x-for="event in events" :key="event.id">
                <a :href="`/events/${event.id}`" class="card">
                    <div class="flex items-center gap-2">
                        <span class="tag tag-neutral">نسخة محفوظة</span>
                        <span class="ms-auto shrink-0 font-figure text-sm whitespace-nowrap text-ash-700"
                              x-text="`${event.dateLabel} · ${event.time}`"></span>
                    </div>
                    <h3 class="text-[20px] leading-snug" x-text="event.title"></h3>
                    <p class="text-sm leading-relaxed text-ash-800" x-text="event.place"></p>
                </a>
            </template>
        </div>
    </section>

    <section class="mt-8">
        <h2 class="text-[20px]">غير متاح الآن</h2>
        <p class="mt-1 leading-relaxed text-ash-800">
            إرسال التقييم والملاحظات يحتاج اتصالاً — احتفظوا بما تريدون كتابته، وأرسلوه حين تعود الشبكة.
        </p>
    </section>

    <section class="mt-8">
        <h2 class="text-[20px]">دليل الاستخدام</h2>
        <p class="mt-1 leading-relaxed text-ash-800">متاح دائماً — محفوظ مسبقاً مع أول زيارة.</p>
        <a href="{{ route('guide') }}" class="btn btn-ghost mt-1 px-0">افتحوا الدليل</a>
    </section>

</div>
@endsection
