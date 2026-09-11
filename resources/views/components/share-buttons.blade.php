@props(['title', 'url', 'qrUrl' => null, 'shortUrl' => null])

@php
    $link = $shortUrl ?? $url;
    $shareText = $title.' — '.$link;
@endphp

{{-- أدوات مشاركة الفعالية: واتساب أولاً (الأكثر استعمالاً عند الأهالي)،
     ثم نسخ الرابط، ثم كرت QR في نافذة مستقلة — كان <details> يتمدّد داخل
     عمود ضيّق فينهار تخطيطه، فصار الكرت نافذة مركزية مصمَّمة للطباعة --}}
<div x-data="{ toast: '', qr: false }" class="flex flex-col gap-2.5">
    <a href="https://wa.me/?text={{ urlencode($shareText) }}" target="_blank" rel="noopener"
       class="btn btn-primary btn-block min-h-[50px] gap-2.5 text-[17px]">
        <x-ui.icon name="whatsapp" class="size-5"/> شاركوا على واتساب
    </a>

    <div class="grid grid-cols-2 gap-2.5">
        <button type="button" class="btn btn-outline min-h-[46px] gap-2"
                @click="navigator.clipboard?.writeText(@js($link)).then(() => { toast = 'تم نسخ رابط الفعالية'; setTimeout(() => toast = '', 2600) })">
            <x-ui.icon name="link" class="size-[18px]"/> انسخوا الرابط
        </button>

        @if($qrUrl)
            <button type="button" class="btn btn-outline min-h-[46px] gap-2" @click="qr = true">
                <x-ui.icon name="qr" class="size-[18px]"/> كرت QR
            </button>
        @endif
    </div>

    <p x-show="toast" x-cloak x-transition.opacity
       class="flex items-center justify-center gap-2 rounded-full bg-emerald-50 px-3 py-2 text-sm font-bold text-emerald-700">
        <x-ui.icon name="check" class="size-4"/> <span x-text="toast"></span>
    </p>

    @if($qrUrl)
        {{-- نافذة كرت QR: تصميم قابل للطباعة مباشرة (شعار + عنوان + رمز + رابط) --}}
        <div x-show="qr" x-cloak @keydown.escape.window="qr = false"
             class="fixed inset-0 z-50 grid place-items-center overflow-y-auto bg-ink/55 p-4 backdrop-blur-sm print:static print:bg-transparent print:p-0 print:backdrop-blur-none"
             role="dialog" aria-modal="true" aria-label="كرت QR للفعالية">
            <div @click.outside="qr = false" x-transition
                 class="qr-card relative w-full max-w-sm rounded-3xl bg-white p-6 text-center shadow-2xl print:max-w-none print:shadow-none">
                <button type="button" @click="qr = false" aria-label="إغلاق"
                        class="absolute top-3 end-3 grid size-9 place-items-center rounded-full text-ink-soft transition hover:bg-brand-50 hover:text-brand-700 print:hidden">
                    <x-ui.icon name="x" class="size-5"/>
                </button>

                @if(file_exists(public_path('brand/logo.png')))
                    <img src="{{ asset('brand/logo.png') }}" alt="{{ \App\Support\Settings::get('site_name') }}" class="mx-auto h-12 w-auto">
                @endif

                <p class="mt-3 text-base leading-snug font-extrabold text-ink">{{ $title }}</p>

                <span class="mx-auto mt-5 block w-fit rounded-2xl border-[3px] border-brand-100 bg-white p-3 shadow-sm">
                    <img src="{{ $qrUrl }}" alt="رمز QR يفتح صفحة الفعالية" width="200" height="200" class="block size-[200px]">
                </span>

                <p class="mt-4 text-sm leading-relaxed text-ink-soft">
                    وجّهوا كاميرا الهاتف نحو الرمز لتفتح صفحة الفعالية مباشرة.
                </p>

                <p dir="ltr" class="mt-2 truncate rounded-lg bg-brand-50 px-3 py-2 text-[13px] font-semibold text-brand-700">{{ $link }}</p>

                <div class="mt-5 grid grid-cols-2 gap-2.5 print:hidden">
                    <button type="button" @click="window.print()" class="btn btn-primary min-h-[44px] gap-2">
                        <x-ui.icon name="printer" class="size-[18px]"/> اطبعوه
                    </button>
                    <a href="{{ $qrUrl }}" download="bahja-qr.svg" class="btn btn-outline min-h-[44px] gap-2">
                        <x-ui.icon name="download" class="size-[18px]"/> حمّلوه
                    </a>
                </div>
            </div>
        </div>
    @endif
</div>
