@props(['title', 'url', 'qrUrl' => null, 'shortUrl' => null])

@php
    $shareText = $title.' — '.($shortUrl ?? $url);
@endphp

<div x-data="{ toast: '' }" class="flex flex-col gap-2">
    <a href="https://wa.me/?text={{ urlencode($shareText) }}" target="_blank" rel="noopener"
       class="btn btn-primary btn-block min-h-[48px] text-[17px]">
        شاركوا على واتساب
    </a>

    <div class="flex flex-wrap gap-2">
        <button type="button" class="btn btn-secondary flex-1"
                @click="navigator.clipboard?.writeText(@js($shortUrl ?? $url)).then(() => { toast = 'تم نسخ الرابط: ' + @js($shortUrl ?? $url); setTimeout(() => toast = '', 2600); })">
            انسخوا الرابط
        </button>

        @if($qrUrl)
            <details class="flex-1">
                <summary class="btn btn-secondary w-full list-none">كرت QR</summary>
                <div class="mt-2 flex items-center gap-3">
                    <img src="{{ $qrUrl }}" alt="رمز QR للفعالية" width="120" height="120" loading="lazy" class="size-[120px] bg-paper">
                    <p class="text-sm leading-relaxed text-ash-800">
                        اطبعوه أو صوّروه — يفتح صفحة الفعالية مباشرة عند مسحه.
                    </p>
                </div>
            </details>
        @endif
    </div>

    <p x-show="toast" x-cloak x-text="toast" class="bg-cyan-100 px-3 py-2 text-sm text-cyan-800"></p>
</div>
