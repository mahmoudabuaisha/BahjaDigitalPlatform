<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f3f2f2">

    <title>@yield('title', \App\Support\Settings::get('site_name').' — روزنامة فعاليات أطفال غزة')</title>
    <meta name="description" content="@yield('meta_description', 'روزنامة رقمية تجمع فعاليات الترفيه والدعم النفسي لأطفال غزة في مكان واحد — اعرفوا مكان وموعد أقرب فعالية لأطفالكم.')">

    @yield('og', View::make('partials.og'))

    <link rel="manifest" href="{{ route('pwa.manifest') }}">
    <link rel="icon" href="/icons/icon-192.png" type="image/png">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">

    {{-- خطوط المتن أولاً: هي التي تحمل أول سطر يقرأه الأهالي --}}
    <link rel="preload" href="/fonts/noto-naskh-arabic-arabic-400-normal.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/fonts/amiri-arabic-700-normal.woff2" as="font" type="font/woff2" crossorigin>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-paper font-body text-ink antialiased">

    {{-- شريط حالة الشبكة — أرجواني عند الانقطاع، سماوي لحظة العودة --}}
    <div id="offline-banner" class="hidden bg-magenta-100 px-4 py-2 text-sm text-magenta-800">
        أنتم الآن دون اتصال — تُعرض آخر البيانات المحفوظة على أجهزتكم
    </div>
    <div id="online-banner" class="hidden bg-cyan-100 px-4 py-2 text-sm text-cyan-800">
        عاد الاتصال — الروزنامة محدَّثة الآن
    </div>

    <div class="mx-auto w-full max-w-4xl px-4 pt-3 sm:px-6">
        <header>
            <div class="flex items-center justify-between gap-3">
                <a href="{{ route('home') }}" class="flex items-center gap-2 no-underline">
                    <span class="size-[26px] shrink-0 rounded-full bg-magenta-500"></span>
                    <span class="font-display text-[28px] leading-none font-bold text-ink">{{ \App\Support\Settings::get('site_name') }}</span>
                </a>

                <nav class="flex items-center gap-4 text-sm">
                    <a href="{{ route('guide') }}" class="text-cyan-700 hover:text-cyan-600 hover:underline">دليل الاستخدام</a>
                    <a href="{{ route('feedback.create') }}" class="hidden text-cyan-700 hover:text-cyan-600 hover:underline sm:inline">رأيكم يهمنا</a>
                </nav>
            </div>

            {{-- أثاث الصفحة الأولى: خط ثخين يتبعه رفيع --}}
            <div class="mt-3 masthead-rule" aria-hidden="true"></div>
            <div class="masthead-rule" aria-hidden="true"></div>
        </header>

        <main class="pt-6 pb-10">
            @yield('content')
        </main>

        <footer class="border-t border-ink/10 pt-6 pb-24 text-sm text-ash-700 sm:pb-10">
            @if($about = \App\Support\Settings::get('about_text'))
                <p class="max-w-xl leading-relaxed">{{ $about }}</p>
            @endif

            <p class="mt-2 leading-relaxed">
                منصّة <strong class="font-display text-ink">{{ \App\Support\Settings::get('site_name') }}</strong> —
                نجمع فعاليات صنّاع الفرح في غزة ليصل الفرح لكل طفل.
            </p>

            <div class="mt-3 flex flex-wrap items-center gap-x-6 gap-y-2">
                @if($whatsapp = \App\Support\Settings::get('site_whatsapp'))
                    <a href="https://wa.me/{{ preg_replace('/\D/', '', $whatsapp) }}" class="text-cyan-700 hover:text-cyan-600 hover:underline">تواصلوا معنا واتساب</a>
                @endif
                <a href="{{ route('guide') }}" class="text-cyan-700 hover:text-cyan-600 hover:underline">دليل الاستخدام</a>
                <a href="{{ route('feedback.create') }}" class="text-cyan-700 hover:text-cyan-600 hover:underline">رأيكم يهمنا</a>
                <a href="{{ url('/team/register') }}" class="text-cyan-700 hover:text-cyan-600 hover:underline">انضموا كفريق</a>
            </div>
        </footer>
    </div>

    {{-- شريط التنقل السفلي — على الهاتف فقط، حيث يُستعمل الموقع فعلياً --}}
    <nav class="fixed inset-x-0 bottom-0 border-t border-ink/10 bg-paper sm:hidden" aria-label="التنقل السريع">
        <ul class="mx-auto flex max-w-4xl items-stretch justify-between px-4 text-sm">
            @php
                $bottomLinks = [
                    ['route' => 'home', 'label' => 'الروزنامة', 'active' => request()->routeIs('home')],
                    ['route' => 'guide', 'label' => 'الدليل', 'active' => request()->routeIs('guide')],
                    ['route' => 'feedback.create', 'label' => 'رأيكم', 'active' => request()->routeIs('feedback.*')],
                ];
            @endphp
            @foreach($bottomLinks as $link)
                <li class="flex-1">
                    <a href="{{ route($link['route']) }}"
                       @if($link['active']) aria-current="page" @endif
                       class="flex min-h-[48px] items-center justify-center no-underline {{ $link['active'] ? 'font-display font-bold text-ink' : 'text-ash-700' }}">
                        {{ $link['label'] }}
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

</body>
</html>
