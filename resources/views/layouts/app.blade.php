<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#7c3aed">

    <title>@yield('title', \App\Support\Settings::get('site_name').' — روزنامة فعاليات أطفال غزة')</title>
    <meta name="description" content="@yield('meta_description', 'روزنامة رقمية تجمع فعاليات الترفيه والدعم النفسي لأطفال غزة في مكان واحد — اعرفوا مكان وموعد أقرب فعالية لأطفالكم.')">

    @yield('og', View::make('partials.og'))

    <link rel="manifest" href="{{ route('pwa.manifest') }}">
    <link rel="icon" href="/icons/icon-192.png" type="image/png">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">

    <link rel="preload" href="/fonts/tajawal-arabic-400-normal.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/fonts/tajawal-arabic-700-normal.woff2" as="font" type="font/woff2" crossorigin>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <noscript>
        <style>[x-cloak]{display:revert !important}</style>
    </noscript>
</head>
<body class="min-h-screen bg-white font-sans text-ink antialiased">

    {{-- شريط حالة الشبكة --}}
    <div id="offline-banner" class="hidden bg-brand-900 px-4 py-2 text-center text-sm text-white">
        أنتم الآن دون اتصال — تُعرض آخر البيانات المحفوظة على أجهزتكم
    </div>
    <div id="online-banner" class="hidden bg-brand-100 px-4 py-2 text-center text-sm text-brand-800">
        عاد الاتصال — الروزنامة محدَّثة الآن
    </div>

    <header x-data="{ open: false }" class="sticky top-0 z-40 border-b border-brand-100 bg-white/90 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center gap-4 px-4 py-3 sm:px-6">
            <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2.5 no-underline">
                <x-brand-mark class="size-11"/>
                <span class="text-2xl font-bold text-brand-700">{{ \App\Support\Settings::get('site_name') }}</span>
            </a>

            <nav class="mx-auto hidden items-center gap-7 lg:flex" aria-label="التنقل الرئيسي">
                <a href="{{ route('home') }}" class="nav-link" @if(request()->routeIs('home')) aria-current="page" @endif>الرئيسية</a>
                <a href="{{ route('events.index') }}" class="nav-link" @if(request()->routeIs('events.index')) aria-current="page" @endif>الفعاليات</a>
                <a href="{{ route('organizers') }}" class="nav-link" @if(request()->routeIs('organizers')) aria-current="page" @endif>المنظِّمون</a>
                <a href="{{ route('about') }}" class="nav-link" @if(request()->routeIs('about')) aria-current="page" @endif>عن بَهْجَة</a>
                <a href="{{ route('contact') }}" class="nav-link" @if(request()->routeIs('contact')) aria-current="page" @endif>تواصلوا معنا</a>
            </nav>

            <div class="ms-auto hidden items-center gap-2 lg:flex">
                @guest
                    <a href="{{ route('register') }}" class="btn btn-outline btn-sm">إنشاء حساب</a>
                    <a href="{{ route('login') }}" class="btn btn-primary btn-sm">تسجيل دخول</a>
                @endguest

                @auth
                    <a href="{{ route('notifications') }}" class="relative grid size-11 place-items-center rounded-xl border border-brand-100 text-brand-700 no-underline hover:bg-brand-50"
                       aria-label="الإشعارات">
                        <x-ui.icon name="megaphone" class="size-5"/>
                        @php $unreadCount = auth()->user()->unreadNotificationsCount(); @endphp
                        @if($unreadCount)
                            <span class="absolute -top-1 -end-1 grid size-5 place-items-center rounded-full bg-rose-500 text-[11px] font-bold text-white">{{ $unreadCount }}</span>
                        @endif
                    </a>

                    <div x-data="{ menu: false }" class="relative">
                        <button type="button" @click="menu = ! menu" :aria-expanded="menu ? 'true' : 'false'"
                                class="btn btn-outline btn-sm">
                            <span class="grid size-6 place-items-center rounded-full bg-brand-100 text-xs font-bold text-brand-700">
                                {{ mb_substr(auth()->user()->name, 0, 1) }}
                            </span>
                            {{ \Illuminate\Support\Str::limit(auth()->user()->name, 14) }}
                            <x-ui.icon name="chevron-down" class="size-4"/>
                        </button>

                        <div x-show="menu" x-cloak @click.outside="menu = false" x-transition.opacity
                             class="absolute end-0 top-full z-50 mt-2 w-52 rounded-2xl border border-brand-100 bg-white p-2 shadow-lg">
                            @if(auth()->user()->isFamily())
                                <a href="{{ route('account') }}" class="flex min-h-[42px] items-center gap-2 rounded-xl px-3 no-underline hover:bg-brand-50">
                                    <x-ui.icon name="grid" class="size-4 text-brand-500"/> لوحة التحكّم
                                </a>
                                <a href="{{ route('my-events') }}" class="flex min-h-[42px] items-center gap-2 rounded-xl px-3 no-underline hover:bg-brand-50">
                                    <x-ui.icon name="calendar" class="size-4 text-brand-500"/> فعالياتي
                                </a>
                                <a href="{{ route('account.profile') }}" class="flex min-h-[42px] items-center gap-2 rounded-xl px-3 no-underline hover:bg-brand-50">
                                    <x-ui.icon name="users" class="size-4 text-brand-500"/> ملفي الشخصي
                                </a>
                            @else
                                <a href="{{ auth()->user()->role->isAdministrative() ? url('/admin') : route('organizer.dashboard') }}"
                                   class="flex min-h-[42px] items-center gap-2 rounded-xl px-3 no-underline hover:bg-brand-50">
                                    <x-ui.icon name="grid" class="size-4 text-brand-500"/> لوحة التحكّم
                                </a>
                            @endif

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="flex min-h-[42px] w-full items-center gap-2 rounded-xl px-3 text-start text-rose-600 hover:bg-rose-50">
                                    <x-ui.icon name="arrow-back" class="size-4"/> تسجيل الخروج
                                </button>
                            </form>
                        </div>
                    </div>
                @endauth
            </div>

            <button type="button" @click="open = ! open" :aria-expanded="open ? 'true' : 'false'"
                    class="ms-auto grid size-11 place-items-center rounded-xl border border-brand-100 text-brand-700 lg:hidden"
                    aria-label="القائمة">
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                    <path x-show="! open" d="M4 7h16M4 12h16M4 17h16"/>
                    <path x-show="open" x-cloak d="M6 6l12 12M18 6 6 18"/>
                </svg>
            </button>
        </div>

        {{-- قائمة الهاتف --}}
        <nav x-show="open" x-cloak x-transition.opacity class="border-t border-brand-100 bg-white px-4 py-3 lg:hidden" aria-label="التنقل">
            <ul class="flex flex-col">
                @foreach([
                    ['route' => 'home', 'label' => 'الرئيسية'],
                    ['route' => 'events.index', 'label' => 'الفعاليات'],
                    ['route' => 'organizers', 'label' => 'المنظِّمون'],
                    ['route' => 'about', 'label' => 'عن بَهْجَة'],
                    ['route' => 'guide', 'label' => 'دليل الاستخدام'],
                    ['route' => 'contact', 'label' => 'تواصلوا معنا'],
                ] as $item)
                    <li>
                        <a href="{{ route($item['route']) }}"
                           class="flex min-h-[48px] items-center rounded-xl px-3 no-underline {{ request()->routeIs($item['route']) ? 'bg-brand-50 font-bold text-brand-700' : 'text-ink-soft' }}">
                            {{ $item['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="mt-3 flex flex-col gap-2 border-t border-brand-100 pt-3">
                @guest
                    <a href="{{ route('login') }}" class="btn btn-primary btn-block">تسجيل دخول</a>
                    <a href="{{ route('register') }}" class="btn btn-outline btn-block">إنشاء حساب</a>
                @endguest

                @auth
                    @if(auth()->user()->isFamily())
                        <a href="{{ route('account') }}" class="btn btn-outline btn-block">لوحة التحكّم</a>
                        <a href="{{ route('my-events') }}" class="btn btn-outline btn-block">فعالياتي</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-ghost btn-block text-rose-600">تسجيل الخروج</button>
                    </form>
                @endauth
            </div>
        </nav>
    </header>

    <main>
        @yield('content')
    </main>

    <footer class="mt-16 border-t border-brand-100 bg-brand-50/60">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 py-12 sm:px-6 md:grid-cols-2 lg:grid-cols-4">
            <div>
                <div class="flex items-center gap-2.5">
                    <x-brand-mark class="size-11"/>
                    <span class="text-2xl font-bold text-brand-700">{{ \App\Support\Settings::get('site_name') }}</span>
                </div>
                <p class="mt-3 max-w-xs leading-relaxed text-ink-soft">
                    {{ \App\Support\Settings::get('about_text') ?: 'منصّة واحدة تجمع فعاليات الترفيه والدعم النفسي لأطفال غزة، وتصل إلى العائلات حتى حين تضعف الشبكة.' }}
                </p>
                @if($whatsapp = \App\Support\Settings::get('site_whatsapp'))
                    <a href="https://wa.me/{{ preg_replace('/\D/', '', $whatsapp) }}" target="_blank" rel="noopener"
                       class="mt-4 inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-bold text-emerald-600 no-underline shadow-sm">
                        <x-ui.icon name="whatsapp" class="size-5"/> واتساب
                    </a>
                @endif
            </div>

            <div>
                <h2 class="text-base font-bold">روابط سريعة</h2>
                <ul class="mt-3 flex flex-col gap-2 text-ink-soft">
                    <li><a href="{{ route('home') }}" class="no-underline hover:text-brand-700">الرئيسية</a></li>
                    <li><a href="{{ route('events.index') }}" class="no-underline hover:text-brand-700">الفعاليات</a></li>
                    <li><a href="{{ route('organizers') }}" class="no-underline hover:text-brand-700">المنظِّمون</a></li>
                    <li><a href="{{ route('guide') }}" class="no-underline hover:text-brand-700">دليل الاستخدام</a></li>
                    <li><a href="{{ route('faq') }}" class="no-underline hover:text-brand-700">الأسئلة الشائعة</a></li>
                    <li><a href="{{ route('contact') }}" class="no-underline hover:text-brand-700">تواصلوا معنا</a></li>
                </ul>
            </div>

            <div>
                <h2 class="text-base font-bold">الفئات</h2>
                <ul class="mt-3 flex flex-col gap-2 text-ink-soft">
                    @foreach(\App\Models\Category::orderBy('sort_order')->limit(6)->get() as $footerCategory)
                        <li>
                            <a href="{{ route('events.index', ['cat' => $footerCategory->slug]) }}" class="no-underline hover:text-brand-700">
                                {{ $footerCategory->name }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div>
                <h2 class="text-base font-bold">للفرق التطوعية</h2>
                <ul class="mt-3 flex flex-col gap-2 text-ink-soft">
                    <li><a href="{{ url('/team/register') }}" class="no-underline hover:text-brand-700">سجّلوا فريقكم</a></li>
                    <li><a href="{{ url('/team') }}" class="no-underline hover:text-brand-700">دخول الفرق</a></li>
                    <li><a href="{{ route('feedback.create') }}" class="no-underline hover:text-brand-700">رأيكم يهمنا</a></li>
                </ul>

                @if($whatsapp = \App\Support\Settings::get('site_whatsapp'))
                    <p class="mt-4 text-sm text-ink-soft" dir="ltr">{{ $whatsapp }}</p>
                @endif
            </div>
        </div>

        <div class="border-t border-brand-100 px-4 py-5 text-center text-sm text-ink-soft">
            جميع الحقوق محفوظة © {{ \App\Support\Settings::get('site_name') }} {{ now()->year }}
        </div>
    </footer>

</body>
</html>
