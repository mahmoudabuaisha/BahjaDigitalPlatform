<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f06406">

    <title>@yield('title', \App\Support\Settings::get('site_name').' — روزنامة فعاليات أطفال غزة')</title>
    <meta name="description" content="@yield('meta_description', 'روزنامة رقمية تجمع فعاليات الترفيه والدعم النفسي لأطفال غزة في مكان واحد — اعرفوا مكان وموعد أقرب فعالية لأطفالكم.')">

    @yield('og', View::make('partials.og'))

    <link rel="manifest" href="{{ route('pwa.manifest') }}">
    <link rel="icon" href="/icons/icon-192.png" type="image/png">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">

    <link rel="preload" href="/fonts/tajawal-arabic-400-normal.woff2" as="font" type="font/woff2" crossorigin>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-joy-50 font-sans text-gray-800 antialiased">

    {{-- شريط انقطاع الاتصال --}}
    <div id="offline-banner" class="hidden bg-gray-800 px-4 py-2 text-center text-sm text-white">
        أنتم الآن دون اتصال — تُعرض البيانات المحفوظة مسبقاً
    </div>

    <header class="border-b border-joy-100 bg-white/90 backdrop-blur">
        <div class="mx-auto flex max-w-5xl items-center justify-between gap-3 px-4 py-3">
            <a href="{{ route('home') }}" class="flex items-center gap-2">
                <span class="grid size-10 place-items-center rounded-2xl bg-joy-500 text-xl font-bold text-white shadow-sm">بـ</span>
                <span class="text-xl font-bold text-joy-700">{{ \App\Support\Settings::get('site_name') }}</span>
            </a>

            <nav class="flex items-center gap-1 text-sm font-semibold">
                <a href="{{ route('home') }}" class="rounded-lg px-3 py-2 text-gray-600 hover:bg-joy-100 hover:text-joy-700">الروزنامة</a>
                <a href="{{ route('guide') }}" class="rounded-lg px-3 py-2 text-gray-600 hover:bg-joy-100 hover:text-joy-700">دليل الاستخدام</a>
                <a href="{{ route('feedback.create') }}" class="hidden rounded-lg px-3 py-2 text-gray-600 hover:bg-joy-100 hover:text-joy-700 sm:block">رأيكم يهمنا</a>
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-4 py-6">
        @yield('content')
    </main>

    <footer class="mt-10 border-t border-joy-100 bg-white">
        <div class="mx-auto max-w-5xl space-y-4 px-4 py-8 text-center text-sm text-gray-500">
            @if($about = \App\Support\Settings::get('about_text'))
                <p class="mx-auto max-w-xl leading-relaxed">{{ $about }}</p>
            @endif

            <p>
                منصّة <strong class="text-joy-600">{{ \App\Support\Settings::get('site_name') }}</strong> —
                نجمع فعاليات صنّاع الفرح في غزة ليصل الفرح لكل طفل
            </p>

            <div class="flex flex-wrap items-center justify-center gap-4 text-xs">
                @if($whatsapp = \App\Support\Settings::get('site_whatsapp'))
                    <a href="https://wa.me/{{ preg_replace('/\D/', '', $whatsapp) }}" class="font-semibold text-calm-600 hover:underline">
                        تواصلوا معنا واتساب
                    </a>
                @endif
                <a href="{{ url('/team/register') }}" class="font-semibold text-calm-600 hover:underline">انضمام فريق ترفيهي</a>
                <a href="{{ url('/team') }}" class="text-gray-400 hover:underline">دخول الفرق</a>
            </div>
        </div>
    </footer>

</body>
</html>
