{{-- بطاقة تثبيت التطبيق: تعرف منصّتها وتقول لكل مستخدم ما يفعله فعلاً،
     ولا تَعِد بزرّ حيث لا يوجد زر. --}}

<div x-data="installApp" x-cloak {{ $attributes->merge(['class' => 'card overflow-hidden p-0']) }}>

    {{-- الواجهة: الطلسم على تدرّج الهوية، كما ستبدو الأيقونة على الشاشة --}}
    <div class="relative flex items-center gap-5 overflow-hidden bg-gradient-to-l from-brand-700 to-brand-500 px-6 py-6">
        <span class="grid size-20 shrink-0 place-items-center overflow-hidden rounded-[1.4rem] bg-white/15 shadow-lg ring-1 ring-white/25">
            <img src="/icons/icon-192.png" alt="" width="80" height="80" class="size-20 rounded-[1.4rem]">
        </span>

        <div class="min-w-0">
            <h2 class="text-2xl leading-tight font-bold text-white">
                <span x-show="! installed">بَهْجَة على شاشتكم مباشرة</span>
                <span x-show="installed" x-cloak>بَهْجَة مثبَّتة على هذا الجهاز</span>
            </h2>
            <p class="mt-1 text-sm text-brand-50">
                <span x-show="! installed">ثبّتوها مرّة واحدة — بلا متجر ولا مساحة تُذكر</span>
                <span x-show="installed" x-cloak>افتحوها من أيقونتها كأي تطبيق آخر</span>
            </p>
        </div>
    </div>

    <div class="flex flex-col gap-5 p-6">

        {{-- ═══ مثبَّتة بالفعل ═══ --}}
        <template x-if="installed">
            <div class="flex flex-col gap-4">
                <p class="flex items-start gap-2 rounded-2xl bg-emerald-50 px-4 py-3 font-medium text-emerald-800">
                    <x-ui.icon name="check" class="mt-0.5 size-5 shrink-0"/>
                    كل شيء جاهز. الروزنامة تُحفظ على جهازكم وتُفتح حتى حين تنقطع الشبكة.
                </p>
                <p class="text-sm text-ink-soft">
                    جرّبوا الضغط المطوّل على أيقونة بَهْجَة: تظهر اختصارات مباشرة إلى
                    الفعاليات القادمة، والأقرب إلى مكانكم، وحسابكم.
                </p>
            </div>
        </template>

        {{-- ═══ غير مثبَّتة ═══ --}}
        <template x-if="! installed">
            <div class="flex flex-col gap-5">

                <ul class="flex list-none flex-col gap-3 p-0">
                    @foreach([
                        ['icon' => 'phone-app', 'tone' => 'tone-sky', 'title' => 'أيقونة على شاشتكم', 'body' => 'تُفتح بضغطة واحدة، بلا بحث ولا كتابة عنوان.'],
                        ['icon' => 'bolt', 'tone' => 'tone-amber', 'title' => 'تعمل حين تنقطع الشبكة', 'body' => 'الروزنامة محفوظة على الجهاز، فتقرؤون المواعيد والأماكن دون إنترنت.'],
                        ['icon' => 'download', 'tone' => 'tone-emerald', 'title' => 'أخفّ على باقتكم', 'body' => 'لا تُحمَّل الصفحة من جديد كل مرة — ما حُفظ يبقى محفوظاً.'],
                    ] as $benefit)
                        <li class="flex items-start gap-3">
                            <span class="icon-tile tone {{ $benefit['tone'] }} size-10 shrink-0">
                                <x-ui.icon :name="$benefit['icon']" class="size-5"/>
                            </span>
                            <span class="min-w-0">
                                <span class="block font-bold">{{ $benefit['title'] }}</span>
                                <span class="block text-sm leading-relaxed text-ink-soft">{{ $benefit['body'] }}</span>
                            </span>
                        </li>
                    @endforeach
                </ul>

                {{-- الزرّ الحقيقي: أندرويد وسطح المكتب حين يمنحنا المتصفّح نافذة التثبيت --}}
                <button type="button" x-show="canPrompt" @click="install()" :disabled="busy"
                        class="btn btn-primary btn-lg btn-block">
                    <x-ui.icon name="plus-square" class="size-5"/>
                    <span x-text="busy ? 'جارٍ التثبيت…' : 'ثبّتوا التطبيق الآن'">ثبّتوا التطبيق الآن</span>
                </button>

                {{-- iOS: لا واجهة برمجية للتثبيت، فالخطوات هي الحل الوحيد --}}
                <div x-show="needsManualSteps && platform === 'ios'" x-cloak class="flex flex-col gap-3">
                    <p class="font-bold">على الآيفون والآيباد — ثلاث خطوات:</p>
                    <ol class="m-0 flex list-none flex-col gap-3 p-0">
                        <li class="flex items-center gap-3 rounded-2xl bg-brand-50/70 px-4 py-3">
                            <span class="grid size-8 shrink-0 place-items-center rounded-full bg-brand-600 font-bold text-white">١</span>
                            <span class="flex flex-1 flex-wrap items-center gap-1.5">
                                اضغطوا زرّ المشاركة
                                <x-ui.icon name="share-ios" class="size-5 text-brand-700"/>
                                أسفل الشاشة
                            </span>
                        </li>
                        <li class="flex items-center gap-3 rounded-2xl bg-brand-50/70 px-4 py-3">
                            <span class="grid size-8 shrink-0 place-items-center rounded-full bg-brand-600 font-bold text-white">٢</span>
                            <span class="flex flex-1 flex-wrap items-center gap-1.5">
                                اختاروا «إضافة إلى الشاشة الرئيسية»
                                <x-ui.icon name="plus-square" class="size-5 text-brand-700"/>
                            </span>
                        </li>
                        <li class="flex items-center gap-3 rounded-2xl bg-brand-50/70 px-4 py-3">
                            <span class="grid size-8 shrink-0 place-items-center rounded-full bg-brand-600 font-bold text-white">٣</span>
                            <span class="flex-1">اضغطوا «إضافة» — وتظهر أيقونة بَهْجَة بين تطبيقاتكم</span>
                        </li>
                    </ol>
                </div>

                {{-- متصفّحات iOS غير سفاري: التثبيت مستحيل فيها، ونقولها بوضوح --}}
                <div x-show="platform === 'ios-other-browser'" x-cloak
                     class="flex items-start gap-2 rounded-2xl bg-amber-50 px-4 py-3 text-amber-900">
                    <x-ui.icon name="light-bulb" class="mt-0.5 size-5 shrink-0"/>
                    <span>
                        التثبيت على الآيفون لا يتم إلا من متصفّح <b>Safari</b>.
                        افتحوا bahjagaza.com في سفاري ثم أعيدوا الخطوات.
                    </span>
                </div>

                {{-- أندرويد بلا نافذة تثبيت (فايرفوكس مثلاً) وسطح المكتب --}}
                <div x-show="needsManualSteps && (platform === 'android' || platform === 'desktop')" x-cloak
                     class="flex items-start gap-2 rounded-2xl bg-brand-50/70 px-4 py-3">
                    <x-ui.icon name="light-bulb" class="mt-0.5 size-5 shrink-0 text-brand-700"/>
                    <span>
                        <span x-show="platform === 'android'">
                            افتحوا قائمة المتصفّح (⋮) ثم اختاروا «تثبيت التطبيق» أو «إضافة إلى الشاشة الرئيسية».
                        </span>
                        <span x-show="platform === 'desktop'">
                            ابحثوا عن أيقونة التثبيت في شريط العنوان، أو من قائمة المتصفّح اختاروا «تثبيت بَهْجَة».
                        </span>
                    </span>
                </div>

                <p class="text-xs text-ink-soft">
                    لا نطلب صلاحيات ولا موقعاً، ولا تأخذ بَهْجَة من مساحة جهازكم إلا قدر صورة واحدة.
                </p>
            </div>
        </template>
    </div>
</div>
