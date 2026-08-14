@extends('layouts.app')

@section('title', 'دليل الاستخدام — '.\App\Support\Settings::get('site_name'))
@section('meta_description', 'كيف تستخدمون منصة بهجة للوصول لفعاليات أطفالكم — دليل مبسط بالصور')

@section('content')
<div class="max-w-2xl">

    <h1 class="text-[32px] leading-tight sm:text-[38px]">دليل استخدام المنصة</h1>
    <p class="mt-2 text-[16px] leading-relaxed text-ash-800">
        ثلاث خطوات بسيطة توصلكم لأقرب فعالية لأطفالكم.
    </p>

    <ol class="mt-6 flex flex-col gap-6">
        <li class="flex items-start gap-3">
            <span class="min-w-[30px] font-figure text-[30px] leading-none text-magenta-600">1</span>
            <div>
                <h2 class="text-[20px]">افتحوا الروزنامة واختاروا محافظتكم</h2>
                <p class="mt-1 leading-relaxed text-ash-800">
                    من الصفحة الرئيسية اختاروا محافظتكم (شمال غزة، غزة، الوسطى، خان يونس، رفح)
                    ومركز الإيواء أو المخيم القريب منكم — ستظهر فعاليات منطقتكم فقط.
                </p>
            </div>
        </li>

        <li class="flex items-start gap-3">
            <span class="min-w-[30px] font-figure text-[30px] leading-none text-magenta-600">2</span>
            <div>
                <h2 class="text-[20px]">اضغطوا على الفعالية لمعرفة التفاصيل</h2>
                <p class="mt-1 leading-relaxed text-ash-800">
                    كل فعالية فيها: اليوم والساعة، المكان بالتفصيل، نوع النشاط، والفريق المنفّذ
                    مع زر تواصل واتساب مباشر.
                </p>
            </div>
        </li>

        <li class="flex items-start gap-3">
            <span class="min-w-[30px] font-figure text-[30px] leading-none text-magenta-600">3</span>
            <div>
                <h2 class="text-[20px]">شاركوا الفعالية مع الجيران والأهل</h2>
                <p class="mt-1 leading-relaxed text-ash-800">
                    زر «شاركوا على واتساب» يرسل الفعالية لمجموعات العائلة والحي —
                    هكذا يصل الفرح لأكبر عدد من الأطفال.
                </p>
            </div>
        </li>
    </ol>

    <section class="mt-8">
        <h2 class="text-[22px]">ثبّتوا المنصة كتطبيق — تعمل دون إنترنت</h2>

        <div class="mt-2 flex flex-col gap-3 leading-relaxed text-ash-800">
            <p>
                <strong class="text-ink">على أندرويد (كروم):</strong>
                افتحوا القائمة (⋮) بأعلى المتصفح ثم اختاروا
                <strong class="text-ink">«إضافة إلى الشاشة الرئيسية»</strong> — ستجدون أيقونة بهجة مع تطبيقاتكم.
            </p>
            <p>
                <strong class="text-ink">على آيفون (سفاري):</strong>
                اضغطوا زر المشاركة ثم <strong class="text-ink">«إضافة إلى الصفحة الرئيسية»</strong>.
            </p>
            <p class="bg-cyan-100 px-3 py-2 text-cyan-800">
                بعد أول زيارة تُحفظ الفعاليات في أجهزتكم — حتى لو انقطع الإنترنت
                تستطيعون رؤية مواعيد وأماكن الفعاليات المحفوظة.
            </p>
        </div>
    </section>

    <section class="mt-8">
        <h2 class="text-[22px]">عندكم فريق ترفيهي؟</h2>
        <p class="mt-1 max-w-xl leading-relaxed text-ash-800">
            سجّلوا فريقكم في المنصة وارفعوا جدول فعالياتكم بأنفسكم —
            بعد اعتماد الإدارة ستصل فعالياتكم لآلاف العائلات.
        </p>
        <a href="{{ url('/team/register') }}" class="btn btn-primary mt-2">تسجيل فريق جديد</a>
    </section>

</div>
@endsection
