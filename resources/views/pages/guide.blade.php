@extends('layouts.app')

@section('title', 'دليل الاستخدام — '.\App\Support\Settings::get('site_name'))
@section('meta_description', 'كيف تستخدمون منصة بهجة للوصول لفعاليات أطفالكم — دليل مبسط بالصور')

@section('content')
<div class="mx-auto max-w-2xl">

    <header class="mb-8 text-center">
        <p class="text-4xl">📖</p>
        <h1 class="mt-2 text-2xl font-bold text-gray-900 sm:text-3xl">دليل استخدام المنصة</h1>
        <p class="mt-2 text-sm text-gray-500">ثلاث خطوات بسيطة توصلكم لأقرب فعالية لأطفالكم</p>
    </header>

    <ol class="space-y-4">
        <li class="flex gap-4 rounded-2xl border border-joy-100 bg-white p-5 shadow-sm">
            <span class="grid size-10 shrink-0 place-items-center rounded-full bg-joy-500 text-lg font-bold text-white">١</span>
            <div>
                <h2 class="font-bold text-gray-900">افتحوا الروزنامة واختاروا منطقتكم</h2>
                <p class="mt-1 text-sm leading-relaxed text-gray-500">
                    من الصفحة الرئيسية اختاروا محافظتكم (شمال غزة، غزة، الوسطى، خان يونس، رفح)
                    ومركز الإيواء أو المخيم القريب منكم — ستظهر فعاليات منطقتكم فقط.
                </p>
            </div>
        </li>

        <li class="flex gap-4 rounded-2xl border border-joy-100 bg-white p-5 shadow-sm">
            <span class="grid size-10 shrink-0 place-items-center rounded-full bg-joy-500 text-lg font-bold text-white">٢</span>
            <div>
                <h2 class="font-bold text-gray-900">اضغطوا على الفعالية لمعرفة التفاصيل</h2>
                <p class="mt-1 text-sm leading-relaxed text-gray-500">
                    كل فعالية فيها: اليوم والساعة، المكان بالتفصيل، نوع النشاط، والفريق المنفذ
                    مع زر تواصل واتساب مباشر.
                </p>
            </div>
        </li>

        <li class="flex gap-4 rounded-2xl border border-joy-100 bg-white p-5 shadow-sm">
            <span class="grid size-10 shrink-0 place-items-center rounded-full bg-joy-500 text-lg font-bold text-white">٣</span>
            <div>
                <h2 class="font-bold text-gray-900">شاركوا الفعالية مع الجيران والأهل</h2>
                <p class="mt-1 text-sm leading-relaxed text-gray-500">
                    زر "مشاركة واتساب" يرسل الفعالية لمجموعات العائلة والحي —
                    هكذا يصل الفرح لأكبر عدد من الأطفال.
                </p>
            </div>
        </li>
    </ol>

    <section class="mt-8 rounded-3xl bg-calm-50 p-6">
        <h2 class="flex items-center gap-2 text-lg font-bold text-calm-900">
            <span>📲</span> ثبّتوا المنصة كتطبيق — تعمل دون إنترنت!
        </h2>
        <div class="mt-3 space-y-3 text-sm leading-relaxed text-calm-800">
            <p>
                <strong>على أندرويد (كروم):</strong>
                افتحوا القائمة (⋮) بأعلى المتصفح ثم اختاروا
                <strong>"إضافة إلى الشاشة الرئيسية"</strong> — ستجدون أيقونة بهجة مع تطبيقاتكم.
            </p>
            <p>
                <strong>على آيفون (سفاري):</strong>
                اضغطوا زر المشاركة ثم <strong>"إضافة إلى الصفحة الرئيسية"</strong>.
            </p>
            <p class="rounded-xl bg-white/60 p-3 font-semibold">
                💡 بعد أول زيارة، تُحفظ الفعاليات في جهازكم —
                حتى لو انقطع الإنترنت تقدرون تشوفون مواعيد وأماكن الفعاليات المحفوظة.
            </p>
        </div>
    </section>

    <section class="mt-8 rounded-3xl border border-joy-100 bg-white p-6 text-center">
        <h2 class="text-lg font-bold text-gray-900">عندكم فريق ترفيهي؟ 🎪</h2>
        <p class="mx-auto mt-2 max-w-md text-sm text-gray-500">
            سجلوا فريقكم في المنصة وارفعوا جدول فعالياتكم بأنفسكم —
            بعد اعتماد الإدارة ستصل فعالياتكم لآلاف العائلات.
        </p>
        <a href="{{ url('/team/register') }}"
           class="mt-4 inline-block rounded-xl bg-calm-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-calm-700">
            تسجيل فريق جديد
        </a>
    </section>

</div>
@endsection
