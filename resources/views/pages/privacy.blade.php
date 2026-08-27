@extends('layouts.app')

@section('title', 'سياسة الخصوصية — '.\App\Support\Settings::get('site_name'))
@section('meta_description', 'ما البيانات التي تجمعها منصة بهجة، ولماذا، وكيف نحميها — بلغة واضحة للأهل')

@section('content')
<div class="mx-auto max-w-3xl px-4 py-10 sm:px-6">

    <h1 class="text-[32px] leading-tight sm:text-[38px]">سياسة الخصوصية</h1>
    <p class="mt-2 text-[16px] leading-relaxed text-ink-soft">
        بلغة واضحة: ما الذي نجمعه، ولماذا، وكيف نحميه.
    </p>

    @php $custom = trim((string) \App\Support\Settings::get('privacy_content')); @endphp
    @if($custom !== '')
        {{-- النص المُحرَّر من لوحة الإدارة يحل محل النص الافتراضي --}}
        <x-settings-content :content="$custom"/>
    @else
    <div class="mt-6 flex flex-col gap-6 leading-relaxed text-ink-soft">
        <section>
            <h2 class="text-[20px] text-ink">تصفّح بلا أي حساب</h2>
            <p class="mt-1">
                تصفّح الفعاليات وتقييمها بعد انتهائها لا يحتاجان أي تسجيل — لا اسم ولا بريد ولا رقم هاتف.
                التقييم مجهول تماماً، ولا نربطه بهوية أحد.
            </p>
        </section>

        <section>
            <h2 class="text-[20px] text-ink">ما نجمعه عند إنشاء حساب حجز (اختياري)</h2>
            <p class="mt-1">
                إن اخترتم حجز مقاعد لأطفالكم نطلب: الاسم، والبريد الإلكتروني، ورقم هاتف اختياري،
                وأسماء الأطفال وأعمارهم. نستخدمها فقط لإدارة الحجوزات وإشعاركم بأي تغيير أو إلغاء —
                ولا نبيعها ولا نشاركها مع أي جهة خارج المنصّة.
            </p>
        </section>

        <section>
            <h2 class="text-[20px] text-ink">حماية الموقع الجغرافي</h2>
            <p class="mt-1">
                كل صورة تُرفع للمنصّة يُعاد ترميزها آلياً فتُمسح منها بياناتها الوصفية كاملة —
                وأهمها إحداثيات GPS التي قد تكشف مكان التصوير. ومراكز الإيواء التي تطلب إدارتها
                إخفاء موقعها تُعرض محافظتها فقط دون اسم أو عنوان.
            </p>
        </section>

        <section>
            <h2 class="text-[20px] text-ink">حقوقكم</h2>
            <p class="mt-1">
                يمكنكم تعديل بياناتكم أو حذف أطفالكم من حسابكم في أي وقت، وإلغاء أي حجز قبل موعده.
                ولطلب حذف الحساب كاملاً راسلونا من صفحة
                <a href="{{ route('contact') }}" class="font-bold text-brand-700">تواصلوا معنا</a>
                وسنحذفه خلال أيام.
            </p>
        </section>

        <section>
            <h2 class="text-[20px] text-ink">صور الأطفال</h2>
            <p class="mt-1">
                لصور الأطفال سياسة مستقلة تلتزم بها كل الفرق المنظِّمة —
                اقرأوها في صفحة <a href="{{ route('photo-policy') }}" class="font-bold text-brand-700">سياسة صور الأطفال</a>.
            </p>
        </section>
    </div>
    @endif

</div>
@endsection
