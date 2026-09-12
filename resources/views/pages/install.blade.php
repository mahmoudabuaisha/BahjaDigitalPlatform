@extends('layouts.app')

@section('title', 'تطبيق بَهْجَة — '.\App\Support\Settings::get('site_name'))
@section('meta_description', 'ثبّتوا بَهْجَة على هاتفكم بضغطة واحدة: أيقونة على الشاشة، وروزنامة تعمل دون إنترنت، وبلا متجر ولا مساحة تُذكر.')

@section('content')

<section class="surface-tint">
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6">
        <nav class="flex items-center gap-2 text-sm text-ink-soft" aria-label="مسار التصفح">
            <a href="{{ route('home') }}" class="no-underline hover:text-brand-700">الرئيسية</a>
            <x-ui.icon name="chevron-start" class="size-4"/>
            <span class="text-ink">تطبيق بَهْجَة</span>
        </nav>

        <h1 class="mt-3 text-4xl font-bold">بَهْجَة في جيبكم</h1>
        <p class="mt-2 max-w-2xl text-lg text-ink-soft">
            بَهْجَة تعمل في المتصفّح كما هي، لكنها حين تُثبَّت تصير تطبيقاً كاملاً:
            أيقونة على الشاشة، وفتحٌ فوري، وروزنامة محفوظة تُقرأ حين تنقطع الشبكة.
        </p>
    </div>
</section>

<div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] lg:items-start">

    <x-install-app class="reveal"/>

    <div class="flex flex-col gap-5">

        {{-- ما الذي يتغيّر فعلاً --}}
        <section class="card reveal gap-4 p-6" style="--stagger: 1">
            <h2 class="text-xl font-bold">ما الذي يتغيّر بعد التثبيت؟</h2>

            <dl class="m-0 flex flex-col gap-4">
                @foreach([
                    ['q' => 'هل تأخذ مساحة من هاتفي؟', 'a' => 'لا تُذكر — أقل من صورة واحدة. بَهْجَة ليست تطبيقاً يُنزَّل من متجر، بل الموقع نفسه يُحفظ على جهازكم.'],
                    ['q' => 'هل أحتاج إنترنت لفتحها؟', 'a' => 'لا. تُفتح وتعرض آخر روزنامة محفوظة، مع تنبيه واضح بتاريخ آخر تحديث. وحين تعود الشبكة تتحدّث وحدها.'],
                    ['q' => 'هل تطلب صلاحيات؟', 'a' => 'لا كاميرا ولا جهات اتصال ولا موقع جغرافي. مكانكم — إن حدّدتموه — تختارونه بأنفسكم من قائمة، ولا يظهر لأحد.'],
                    ['q' => 'كيف أحذفها؟', 'a' => 'كأي تطبيق: ضغطة مطوّلة على الأيقونة ثم «إزالة». وحسابكم وبياناتكم تبقى كما هي على الموقع.'],
                ] as $item)
                    <div>
                        <dt class="font-bold">{{ $item['q'] }}</dt>
                        <dd class="mt-1 ms-0 leading-relaxed text-ink-soft">{{ $item['a'] }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>

        {{-- الاختصارات: ميزة يعرفها قليلون وتستحق أن تُقال --}}
        <section class="card reveal gap-4 p-6" style="--stagger: 2">
            <h2 class="text-xl font-bold">بعد التثبيت</h2>
            <p class="text-ink-soft">
                الضغط المطوّل على أيقونة بَهْجَة يفتح اختصارات مباشرة، وتصير الإشعارات ممكنة —
                وعلى الآيفون لا تعمل الإشعارات إلا بعد التثبيت:
            </p>

            <div class="flex flex-col gap-2">
                @foreach([
                    ['icon' => 'megaphone', 'tone' => 'tone-amber', 'title' => 'إشعار «غداً في حيّكم»', 'body' => 'يصل الهاتف مساءً وبَهْجَة مغلقة — تُفعَّل من صفحة حسابكم'],
                    ['icon' => 'calendar', 'tone' => 'tone-sky', 'title' => 'الفعاليات القادمة', 'body' => 'كل ما هو معتمد، مرتّباً بالأقرب موعداً'],
                    ['icon' => 'map-pin', 'tone' => 'tone-emerald', 'title' => 'الأقرب إلى مكانكم', 'body' => 'مرتّبة بدقائق المشي من حيّكم'],
                    ['icon' => 'user', 'tone' => 'tone-rose', 'title' => 'حسابي', 'body' => 'أطفالكم وحجوزاتكم وإشعاراتكم'],
                ] as $shortcut)
                    <div class="flex items-center gap-3 rounded-2xl bg-brand-50/50 px-4 py-3">
                        <span class="icon-tile tone {{ $shortcut['tone'] }} size-10 shrink-0">
                            <x-ui.icon :name="$shortcut['icon']" class="size-5"/>
                        </span>
                        <span class="min-w-0">
                            <span class="block font-bold">{{ $shortcut['title'] }}</span>
                            <span class="block text-sm text-ink-soft">{{ $shortcut['body'] }}</span>
                        </span>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- دعوة الفرق لنشرها — التثبيت ينتشر بالكلام لا بالإعلان --}}
        <section class="card tone tone-amber reveal gap-3 p-6" style="--stagger: 3">
            <h2 class="flex items-center gap-2 text-lg font-bold">
                <x-ui.icon name="megaphone" class="size-5"/> للفرق المنظِّمة
            </h2>
            <p class="leading-relaxed">
                أكثر العائلات لا تعرف أن الموقع يُثبَّت. إن كنتم تلتقون الأهالي في فعالياتكم،
                دُلّوهم على هذه الصفحة — عائلة مثبِّتة تفتح الروزنامة مرّتين أكثر، وتصلها
                الفعاليات القريبة منها حتى حين تضعف الشبكة.
            </p>
        </section>
    </div>
</div>

@endsection
