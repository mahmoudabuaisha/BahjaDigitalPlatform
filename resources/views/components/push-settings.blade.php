@props(['user' => null])

@php
    $publicKey = (string) config('push.public_key');
    $anchored = $user?->hasLocationAnchor() ?? false;
@endphp

{{-- إشعارات الحيّ: الوعد الأساسي للمنصّة — أن تعرف العائلة أن فعالية
     ستُقام قربها غداً دون أن تبحث. --}}

<div x-data="pushNotifications('{{ $publicKey }}')" x-cloak
     {{ $attributes->merge(['class' => 'card overflow-hidden p-0']) }}>

    <div class="flex items-center gap-4 bg-gradient-to-l from-brand-700 to-brand-500 px-6 py-5">
        <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-white/20 text-white">
            <x-ui.icon name="megaphone" class="size-6"/>
        </span>
        <div class="min-w-0">
            <h2 class="text-xl font-bold text-white">إشعار حين تصل فعالية إلى حيّكم</h2>
            <p class="mt-0.5 text-sm text-brand-50">يصلكم على الهاتف ولو كانت بَهْجَة مغلقة</p>
        </div>
    </div>

    <div class="flex flex-col gap-4 p-6">

        @if($publicKey === '')
            {{-- الخادم بلا مفاتيح: نقولها للإدارة بلغة مفهومة بدل زرّ لا يعمل --}}
            <p class="flex items-start gap-2 rounded-2xl bg-amber-50 px-4 py-3 text-amber-900">
                <x-ui.icon name="light-bulb" class="mt-0.5 size-5 shrink-0"/>
                الإشعارات غير مفعَّلة على الخادم بعد. تُفعَّل بتوليد مفاتيح
                <span dir="ltr" class="font-mono text-sm">VAPID</span> ووضعها في إعدادات الاستضافة.
            </p>
        @else

            <template x-if="! supported">
                <p class="flex items-start gap-2 rounded-2xl bg-brand-50/70 px-4 py-3 text-ink-soft">
                    <x-ui.icon name="light-bulb" class="mt-0.5 size-5 shrink-0 text-brand-700"/>
                    هذا المتصفّح لا يدعم الإشعارات. على الآيفون تعمل بعد
                    <a href="{{ route('install') }}" class="font-bold text-brand-700">تثبيت بَهْجَة على الشاشة الرئيسية</a>.
                </p>
            </template>

            <template x-if="supported">
                <div class="flex flex-col gap-4">

                    {{-- ما الذي سيصلكم بالضبط — الوضوح يسبق الإذن --}}
                    <ul class="m-0 flex list-none flex-col gap-2.5 p-0">
                        @foreach([
                            ['icon' => 'map-pin', 'text' => 'فعالية غداً على بُعد دقائق من مكانكم'],
                            ['icon' => 'check', 'text' => 'قبول حجز أطفالكم أو الاعتذار عنه'],
                            ['icon' => 'clock', 'text' => 'تغيّر موعد فعالية حجزتم فيها أو إلغاؤها'],
                        ] as $item)
                            <li class="flex items-start gap-2.5">
                                <x-ui.icon :name="$item['icon']" class="mt-0.5 size-5 shrink-0 text-brand-600"/>
                                <span>{{ $item['text'] }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <p class="text-sm text-ink-soft">
                        لا إعلانات ولا رسائل يومية — ولا نُرسل بين العاشرة مساءً والسابعة صباحاً
                        إلا إن أُلغيت فعالية حجزتم فيها.
                    </p>

                    @if(! $anchored)
                        <a href="{{ route('account.profile') }}"
                           class="flex items-center gap-2 rounded-2xl border border-dashed border-emerald-300 bg-emerald-50/60 px-4 py-3 font-bold text-emerald-700 no-underline hover:bg-emerald-50">
                            <x-ui.icon name="map-pin" class="size-5 shrink-0"/>
                            حدّدوا مكانكم أولاً كي نعرف أي حيّ نُخبركم عنه
                        </a>
                    @endif

                    {{-- حالة الرفض: لا يملك الموقع فكّها، فنقول أين تُفكّ --}}
                    <template x-if="blocked">
                        <p class="flex items-start gap-2 rounded-2xl bg-rose-50 px-4 py-3 text-rose-800">
                            <x-ui.icon name="x" class="mt-0.5 size-5 shrink-0"/>
                            الإشعارات محظورة من إعدادات المتصفّح لهذا الموقع.
                            افتحوا القفل بجانب العنوان ← الإشعارات ← السماح، ثم عودوا.
                        </p>
                    </template>

                    <div class="flex flex-wrap items-center gap-2.5" x-show="! blocked">
                        <button type="button" x-show="! subscribed" @click="enable()" :disabled="busy"
                                class="btn btn-primary">
                            <x-ui.icon name="megaphone" class="size-5"/>
                            <span x-text="busy ? 'لحظة…' : 'فعّلوا الإشعارات'">فعّلوا الإشعارات</span>
                        </button>

                        <template x-if="subscribed">
                            <div class="flex flex-wrap items-center gap-2.5">
                                <span class="flex items-center gap-1.5 rounded-full bg-emerald-50 px-3.5 py-1.5 font-bold text-emerald-700">
                                    <x-ui.icon name="check" class="size-4"/> مفعَّلة على هذا الجهاز
                                </span>
                                <button type="button" @click="sendTest()" :disabled="busy" class="btn btn-outline btn-sm">
                                    جرّبوها الآن
                                </button>
                                <button type="button" @click="disable()" :disabled="busy"
                                        class="text-sm font-semibold text-ink-soft underline-offset-4 hover:text-rose-600 hover:underline">
                                    إيقافها
                                </button>
                            </div>
                        </template>
                    </div>

                    <p x-show="message" x-cloak x-text="message"
                       class="rounded-2xl bg-emerald-50 px-4 py-3 font-medium text-emerald-800"></p>
                    <p x-show="error" x-cloak x-text="error"
                       class="rounded-2xl bg-rose-50 px-4 py-3 font-medium text-rose-700"></p>
                </div>
            </template>
        @endif
    </div>
</div>
