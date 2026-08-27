@extends('layouts.app')

@section('title', 'سياسة صور الأطفال — '.\App\Support\Settings::get('site_name'))
@section('meta_description', 'قواعد نشر صور الأطفال في فعاليات منصة بهجة — حماية الطفل أولاً')

@section('content')
<div class="mx-auto max-w-3xl px-4 py-10 sm:px-6">

    <h1 class="text-[32px] leading-tight sm:text-[38px]">سياسة صور الأطفال</h1>
    <p class="mt-2 text-[16px] leading-relaxed text-ink-soft">
        فرحة الطفل تُوثَّق دون أن تمسّ سلامته — هذه القواعد ملزمة لكل فريق منظِّم على المنصّة.
    </p>

    @php $custom = trim((string) \App\Support\Settings::get('photo_policy_content')); @endphp
    @if($custom !== '')
        {{-- النص المُحرَّر من لوحة الإدارة يحل محل النص الافتراضي --}}
        <x-settings-content :content="$custom"/>
    @else
    <ol class="mt-6 flex flex-col gap-5 leading-relaxed text-ink-soft">
        <li class="flex items-start gap-3">
            <span class="min-w-[30px] text-[26px] leading-none font-bold text-brand-600">1</span>
            <p><strong class="text-ink">موافقة وليّ الأمر شرط لا استثناء له.</strong>
            لا يُلتقط لطفل ظاهر الوجه أي صورة تُنشر إلا بموافقة واضحة من وليّ أمره —
            ومن حق أي وليّ أمر الرفض دون إبداء سبب، ودون أن يؤثر ذلك على مشاركة طفله.</p>
        </li>
        <li class="flex items-start gap-3">
            <span class="min-w-[30px] text-[26px] leading-none font-bold text-brand-600">2</span>
            <p><strong class="text-ink">بلا أسماء وبلا تفاصيل مُعرِّفة.</strong>
            لا يُنشر مع الصورة اسم الطفل أو عمره أو مكان إقامته أو أي تفصيل يدل عليه.
            الأفضل دائماً: لقطات جماعية عامة، أو من الخلف، أو للأنشطة نفسها.</p>
        </li>
        <li class="flex items-start gap-3">
            <span class="min-w-[30px] text-[26px] leading-none font-bold text-brand-600">3</span>
            <p><strong class="text-ink">المنصّة تمسح بيانات الموقع آلياً.</strong>
            كل صورة تُرفع يُعاد ترميزها فتُحذف منها بيانات EXIF كاملة — بما فيها إحداثيات GPS —
            حمايةً لأماكن تجمّع الأطفال من أي تتبّع.</p>
        </li>
        <li class="flex items-start gap-3">
            <span class="min-w-[30px] text-[26px] leading-none font-bold text-brand-600">4</span>
            <p><strong class="text-ink">الحذف حق فوري.</strong>
            أي وليّ أمر طلب إزالة صورة لطفله تُحذف من المنصّة فوراً ودون نقاش —
            راسلونا من صفحة <a href="{{ route('contact') }}" class="font-bold text-brand-700">تواصلوا معنا</a>.</p>
        </li>
        <li class="flex items-start gap-3">
            <span class="min-w-[30px] text-[26px] leading-none font-bold text-brand-600">5</span>
            <p><strong class="text-ink">الالتزام شرط بقاء الفريق.</strong>
            كل فريق يوقّع على هذه السياسة عند انضمامه، والإخلال بها يعرّض الفريق
            لتعليق حسابه وإيقاف فعالياته.</p>
        </li>
    </ol>
    @endif

</div>
@endsection
