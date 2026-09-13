{{-- لوح التثبيت الملتصق بأسفل الشاشة.

     أسفلُ الشاشة حيث الإبهام، لا أعلاها حيث يمرّ النظر: الرسالة لا تُفيد
     إن لم تُرَ. ولكل منصّة طريقها إلى التثبيت، فيقود اللوحُ كلَّ مستخدم
     إلى طريقه هو بدل أن يَعِد الجميع بزرّ لا يملكه إلا بعضهم. --}}

<div x-data="installSheet" x-show="visible" x-cloak
     {{-- ارتفاع اللوح يُكتب في متغيّر CSS: به تُفسح الصفحةُ أسفلها مكاناً
          ويرتفع زرّ واتساب فوقه، فلا يُحجب شيء تحته. وحين تُفتح الخطوات
          يطول اللوح حتى يصير رفعُ الزرّ دفعاً به إلى وسط الشاشة — فيُخفى
          ريثما تُقرأ الخطوات، وهي لحظة لا يُراسَل فيها أحد. --}}
     x-effect="[visible, expanded, platform], $nextTick(() => {
         document.documentElement.style.setProperty('--install-sheet-h', (visible
             ? $refs.panel.offsetHeight + parseFloat(getComputedStyle($el).paddingBottom) + 12
             : 0) + 'px');
         document.documentElement.classList.toggle('install-sheet-open', visible && expanded);
     })"
     x-transition:enter="transition duration-300 ease-out"
     x-transition:enter-start="translate-y-full opacity-0"
     x-transition:enter-end="translate-y-0 opacity-100"
     x-transition:leave="transition duration-200 ease-in"
     x-transition:leave-end="translate-y-full opacity-0"
     class="install-only fixed inset-x-0 bottom-0 z-50 px-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] print:hidden"
     role="complementary" aria-label="تثبيت التطبيق">

    <div x-ref="panel"
         class="mx-auto flex max-w-xl flex-col gap-3 rounded-3xl border border-brand-100 bg-white p-4 shadow-2xl shadow-brand-900/15">

        {{-- ═══ السطر الأول: من نحن وماذا نعرض ═══ --}}
        <div class="flex items-center gap-3">
            <img src="{{ \App\Support\AssetVersion::url('icons/icon-192.png') }}" alt=""
                 width="48" height="48" class="size-12 shrink-0 rounded-2xl shadow-sm">

            <p class="min-w-0 flex-1 leading-snug">
                <span class="block font-bold">ثبّتوا بَهْجَة على شاشتكم</span>
                <span class="block text-sm text-ink-soft">تُفتح بضغطة وتعمل دون إنترنت</span>
            </p>

            <button type="button" @click="dismiss()" aria-label="إخفاء دعوة التثبيت"
                    class="grid size-9 shrink-0 place-items-center rounded-xl text-ink-soft hover:bg-brand-50 hover:text-ink">
                <x-ui.icon name="x" class="size-5"/>
            </button>
        </div>

        {{-- ═══ الزرّ: يفعل ما يقوله على كل منصّة ═══ --}}
        <div class="flex items-center gap-2">
            <button type="button" @click="act()" :disabled="busy"
                    class="btn btn-primary btn-block">
                <x-ui.icon name="plus-square" class="size-5"/>
                <span x-text="action">ثبّتوا التطبيق</span>
            </button>

            <a href="{{ route('install') }}" class="btn btn-ghost shrink-0 px-3 text-sm">التفاصيل</a>
        </div>

        {{-- ═══ خطوات سفاري — داخل اللوح، فلا يغادر أحد الصفحة ليقرأها ═══ --}}
        <div x-show="expanded && platform === 'ios'" x-cloak class="flex flex-col gap-2">
            <ol class="m-0 flex list-none flex-col gap-2 p-0 text-sm">
                <li class="flex items-center gap-2.5 rounded-2xl bg-brand-50/70 px-3 py-2.5">
                    <span class="grid size-7 shrink-0 place-items-center rounded-full bg-brand-600 font-bold text-white">١</span>
                    <span class="flex flex-wrap items-center gap-1">
                        اضغطوا زرّ المشاركة <x-ui.icon name="share-ios" class="size-5 text-brand-700"/> أسفل سفاري
                    </span>
                </li>
                <li class="flex items-center gap-2.5 rounded-2xl bg-brand-50/70 px-3 py-2.5">
                    <span class="grid size-7 shrink-0 place-items-center rounded-full bg-brand-600 font-bold text-white">٢</span>
                    <span class="flex flex-wrap items-center gap-1">
                        اختاروا «إضافة إلى الشاشة الرئيسية» <x-ui.icon name="plus-square" class="size-5 text-brand-700"/>
                    </span>
                </li>
                <li class="flex items-center gap-2.5 rounded-2xl bg-brand-50/70 px-3 py-2.5">
                    <span class="grid size-7 shrink-0 place-items-center rounded-full bg-brand-600 font-bold text-white">٣</span>
                    <span>اضغطوا «إضافة» — وتظهر أيقونة بَهْجَة بين تطبيقاتكم</span>
                </li>
            </ol>
        </div>

        {{-- ═══ أندرويد وسطح المكتب حين لا يمنحنا المتصفّح نافذة ═══ --}}
        <div x-show="expanded && (platform === 'android' || platform === 'desktop')" x-cloak>
            <p class="flex items-start gap-2 rounded-2xl bg-brand-50/70 px-3 py-2.5 text-sm leading-relaxed">
                <x-ui.icon name="light-bulb" class="mt-0.5 size-5 shrink-0 text-brand-700"/>
                <span x-show="platform === 'android'">
                    افتحوا قائمة المتصفّح (⋮) ثم اختاروا «تثبيت التطبيق» أو «إضافة إلى الشاشة الرئيسية».
                </span>
                <span x-show="platform === 'desktop'" x-cloak>
                    ابحثوا عن أيقونة التثبيت في شريط العنوان، أو من قائمة المتصفّح اختاروا «تثبيت بَهْجَة».
                </span>
            </p>
        </div>

        {{-- ═══ متصفّحات iOS غير سفاري: لا تُثبِّت أصلاً — نقولها ونعطي الحل ═══ --}}
        <p x-show="platform === 'ios-other-browser'" x-cloak
           class="flex items-start gap-2 rounded-2xl bg-amber-50 px-3 py-2.5 text-sm leading-relaxed text-amber-900">
            <x-ui.icon name="light-bulb" class="mt-0.5 size-5 shrink-0"/>
            <span>
                التثبيت على الآيفون لا يتم إلا من <b>Safari</b>.
                انسخوا الرابط وافتحوه هناك:
                <b class="select-all" dir="ltr">{{ parse_url(config('app.url'), PHP_URL_HOST) ?: 'bahjagaza.com' }}</b>
            </span>
        </p>
    </div>
</div>
