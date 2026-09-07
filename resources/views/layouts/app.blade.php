<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#3b93e4">

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
                @if(file_exists(public_path('brand/logo.png')))
                    {{-- شعار المنصّة الأصلي — يُفعَّل تلقائياً فور وجود الملف --}}
                    <img src="{{ asset('brand/logo.png') }}" alt="{{ \App\Support\Settings::get('site_name') }}" class="h-14 w-auto sm:h-16 lg:h-20">
                @else
                    <x-brand-mark class="size-11"/>
                    <span class="text-2xl font-bold text-brand-700">{{ \App\Support\Settings::get('site_name') }}</span>
                @endif
            </a>

            <nav class="mx-auto hidden items-center gap-1 lg:flex" aria-label="التنقل الرئيسي">
                <a href="{{ route('home') }}" class="nav-link" @if(request()->routeIs('home')) aria-current="page" @endif>الرئيسية</a>
                <a href="{{ route('events.index') }}" class="nav-link" @if(request()->routeIs('events.index')) aria-current="page" @endif>الفعاليات</a>
                <a href="{{ route('organizers') }}" class="nav-link" @if(request()->routeIs('organizers')) aria-current="page" @endif>المنظِّمون</a>
                <a href="{{ route('about') }}" class="nav-link" @if(request()->routeIs('about')) aria-current="page" @endif>عن بَهْجَة</a>
                <a href="{{ route('contact') }}" class="nav-link" @if(request()->routeIs('contact')) aria-current="page" @endif>تواصلوا معنا</a>
            </nav>

            <div class="ms-auto hidden items-center gap-2.5 lg:flex">
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
                        {{-- زر الحساب: مساحة مريحة وحدود وظل ليقرأ كزر واضح لا كنص --}}
                        <button type="button" @click="menu = ! menu" :aria-expanded="menu ? 'true' : 'false'"
                                class="flex min-h-[46px] items-center gap-2.5 rounded-full border-[1.5px] border-brand-200 bg-white py-1.5 pe-4 ps-2 text-[15px] font-bold text-ink shadow-sm transition hover:border-brand-400 hover:bg-brand-50">
                            <span class="grid size-8 shrink-0 place-items-center rounded-full bg-gradient-to-br from-brand-500 to-brand-700 text-sm font-bold text-white">
                                {{ mb_substr(auth()->user()->name, 0, 1) }}
                            </span>
                            {{ \Illuminate\Support\Str::limit(auth()->user()->name, 14) }}
                            <x-ui.icon name="chevron-down" class="size-4 text-brand-500"/>
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
                           class="flex min-h-[48px] items-center rounded-xl px-3 text-base no-underline {{ request()->routeIs($item['route']) ? 'bg-brand-50 font-bold text-brand-700' : 'font-semibold text-ink' }}">
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

    {{-- فوتر احتفالي: موجة تُدخل إلى جسم كحلي مزخرف، فموجة وردية، فشريط الحقوق --}}
    <footer class="mt-16">
        <svg class="-mb-px block h-10 w-full text-brand-800 sm:h-16" viewBox="0 0 1440 64" preserveAspectRatio="none" fill="currentColor" aria-hidden="true">
            <path d="M0 44 C240 8 480 64 720 40 C960 16 1200 56 1440 28 L1440 64 L0 64 Z"/>
        </svg>

        <div class="relative bg-gradient-to-b from-brand-800 to-[#17335a] text-white">
            <x-footer-decor/>

            <div class="relative mx-auto grid max-w-7xl gap-9 px-4 pt-10 pb-14 sm:px-6 md:grid-cols-2 lg:grid-cols-4">
                <div>
                    <a href="{{ route('home') }}" class="inline-flex rounded-2xl bg-white px-4 py-2.5 shadow-lg shadow-black/20 no-underline">
                        @if(file_exists(public_path('brand/logo.png')))
                            <img src="{{ asset('brand/logo.png') }}" alt="{{ \App\Support\Settings::get('site_name') }}" class="h-12 w-auto sm:h-14">
                        @else
                            <span class="flex items-center gap-2.5">
                                <x-brand-mark class="size-10"/>
                                <span class="text-xl font-bold text-brand-700">{{ \App\Support\Settings::get('site_name') }}</span>
                            </span>
                        @endif
                    </a>
                    <p class="mt-4 max-w-xs leading-relaxed text-white/85">
                        {{ \App\Support\Settings::get('about_text') ?: 'منصّة واحدة تجمع فعاليات الترفيه والدعم النفسي لأطفال غزة، وتصل إلى العائلات حتى حين تضعف الشبكة.' }}
                    </p>
                    @if($whatsapp = \App\Support\Settings::get('site_whatsapp'))
                        <a href="https://wa.me/{{ preg_replace('/\D/', '', $whatsapp) }}" target="_blank" rel="noopener"
                           class="mt-4 inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-bold text-emerald-600 no-underline shadow-md transition hover:bg-emerald-50">
                            <x-ui.icon name="whatsapp" class="size-5"/> واتساب
                        </a>
                    @endif
                </div>

                <div>
                    <h2 class="text-base font-bold text-amber-300">روابط سريعة</h2>
                    <ul class="mt-3.5 flex flex-col gap-2.5 text-[15px] text-white/80">
                        <li><a href="{{ route('home') }}" class="no-underline transition hover:text-amber-200">الرئيسية</a></li>
                        <li><a href="{{ route('events.index') }}" class="no-underline transition hover:text-amber-200">الفعاليات</a></li>
                        <li><a href="{{ route('organizers') }}" class="no-underline transition hover:text-amber-200">المنظِّمون</a></li>
                        <li><a href="{{ route('guide') }}" class="no-underline transition hover:text-amber-200">دليل الاستخدام</a></li>
                        <li><a href="{{ route('faq') }}" class="no-underline transition hover:text-amber-200">الأسئلة الشائعة</a></li>
                        <li><a href="{{ route('privacy') }}" class="no-underline transition hover:text-amber-200">سياسة الخصوصية</a></li>
                        <li><a href="{{ route('photo-policy') }}" class="no-underline transition hover:text-amber-200">سياسة صور الأطفال</a></li>
                        <li><a href="{{ route('contact') }}" class="no-underline transition hover:text-amber-200">تواصلوا معنا</a></li>
                    </ul>
                </div>

                <div>
                    <h2 class="text-base font-bold text-amber-300">الفئات</h2>
                    <ul class="mt-3.5 flex flex-col gap-2.5 text-[15px] text-white/80">
                        @foreach(\App\Models\Category::orderBy('sort_order')->limit(6)->get() as $footerCategory)
                            <li>
                                <a href="{{ route('events.index', ['cat' => $footerCategory->slug]) }}" class="no-underline transition hover:text-amber-200">
                                    {{ $footerCategory->name }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div>
                    <h2 class="text-base font-bold text-amber-300">للفرق التطوعية</h2>
                    <ul class="mt-3.5 flex flex-col gap-2.5 text-[15px] text-white/80">
                        <li><a href="{{ route('teams.join') }}" class="no-underline transition hover:text-amber-200">سجّلوا فريقكم</a></li>
                        <li><a href="{{ url('/team') }}" class="no-underline transition hover:text-amber-200">دخول الفرق</a></li>
                        <li><a href="{{ route('feedback.create') }}" class="no-underline transition hover:text-amber-200">رأيكم يهمنا</a></li>
                    </ul>

                    @if($whatsapp = \App\Support\Settings::get('site_whatsapp'))
                        <p class="mt-4 text-sm text-white/70" dir="ltr">{{ $whatsapp }}</p>
                    @endif
                </div>
            </div>

            <svg class="relative -mb-px block h-10 w-full sm:h-14" viewBox="0 0 1440 64" preserveAspectRatio="none" aria-hidden="true">
                <path d="M0 40 C260 0 520 64 780 36 C1040 8 1240 52 1440 24 L1440 64 L0 64 Z" fill="#ec4899" opacity=".45"/>
                <path d="M0 52 C280 16 560 60 840 32 C1080 10 1260 48 1440 36 L1440 64 L0 64 Z" fill="#db2777"/>
            </svg>
        </div>

        <div class="bg-pink-600 px-4 py-4 text-center text-sm font-semibold text-white">
            جميع الحقوق محفوظة © {{ \App\Support\Settings::get('site_name') }} {{ now()->year }}
            — صُنعت بحب لأطفال غزة 💙
        </div>
    </footer>

    {{-- واتساب الإدارة العائم: ثابت مع التمرير، بنبضة موجية وطفو وتلويحة خفيفة،
         وتسمية تنبسط عند التمرير — رقم الإعدادات وإلا فرقم الإدارة الافتراضي --}}
    @php $adminWhatsapp = \App\Support\Settings::get('site_whatsapp') ?: '+970 593 674 330'; @endphp
    <a href="https://wa.me/{{ preg_replace('/\D/', '', $adminWhatsapp) }}?text={{ rawurlencode('مرحباً، أحتاج مساعدة في منصة بهجة 🙏') }}"
       target="_blank" rel="noopener"
       class="wa-float group fixed bottom-5 end-5 z-40 flex items-center no-underline print:hidden"
       aria-label="تواصلوا مع إدارة المنصّة عبر واتساب">
        <span class="pointer-events-none me-3 hidden translate-x-2 whitespace-nowrap rounded-full bg-white px-4 py-2 text-sm font-bold text-emerald-700 opacity-0 shadow-lg ring-1 ring-emerald-100 transition-all duration-300 group-hover:translate-x-0 group-hover:opacity-100 group-focus-visible:translate-x-0 group-focus-visible:opacity-100 sm:block">
            راسلونا على واتساب
        </span>
        <span class="relative block">
            <span class="wa-ripple absolute inset-0 rounded-full bg-emerald-400" aria-hidden="true"></span>
            <span class="wa-ripple wa-ripple-2 absolute inset-0 rounded-full bg-emerald-400" aria-hidden="true"></span>
            <span class="relative grid size-14 place-items-center rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 text-white shadow-xl shadow-emerald-600/40 ring-4 ring-white/80 transition-transform duration-300 group-hover:scale-110">
                <x-ui.icon name="whatsapp" class="wa-icon size-7"/>
            </span>
        </span>
    </a>

</body>
</html>
