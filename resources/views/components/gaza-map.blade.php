@props(['areas', 'active' => null, 'homeAreaId' => null])

@php
    // رسم توضيحي لا خريطة جغرافية: شريط مائل من الشمال الشرقي إلى الجنوب
    // الغربي والبحر غرباً — بلا بلاطات ولا طلب شبكة، ويعمل دون اتصال.
    // المقاطع تتبع ترتيب المحافظات من الشمال إلى الجنوب (sort_order).
    $segments = [
        ['points' => '130,40 320,40 306,150 116,150', 'label' => [218, 90]],
        ['points' => '116,150 306,150 292,260 102,260', 'label' => [204, 200]],
        ['points' => '102,260 292,260 278,370 88,370', 'label' => [190, 310]],
        ['points' => '88,370 278,370 263,480 73,480', 'label' => [176, 420]],
        ['points' => '73,480 263,480 225,585 60,585', 'label' => [155, 528]],
    ];

    // ساحل يتبع الحافة الغربية للشريط بدل مستطيل جامد
    $coast = '0,0 130,40 116,150 102,260 88,370 73,480 60,585 0,620';

    $ordered = $areas->sortBy('sort_order')->values();
@endphp

<figure {{ $attributes->merge(['class' => 'relative']) }}>
    <svg viewBox="0 0 360 620" class="block h-auto w-full max-w-[24rem]" role="img"
         aria-label="محافظات قطاع غزة الخمس وعدد الفعاليات القادمة في كل منها">

        <defs>
            <linearGradient id="gaza-sea" x1="0" y1="0" x2="1" y2="0">
                <stop offset="0%" stop-color="#bae6fd"/>
                <stop offset="100%" stop-color="#7dd3fc"/>
            </linearGradient>
            <filter id="gaza-lift" x="-30%" y="-30%" width="160%" height="160%">
                <feDropShadow dx="0" dy="4" stdDeviation="5" flood-color="#1f62a7" flood-opacity=".25"/>
            </filter>
        </defs>

        {{-- البحر غرباً بموجاته --}}
        <polygon points="{{ $coast }}" fill="url(#gaza-sea)" opacity=".55"/>
        @foreach([100, 180, 260, 340, 420, 500, 570] as $waveY)
            <path d="M10 {{ $waveY }} q14 -7 28 0 t28 0" fill="none" stroke="#38bdf8" stroke-opacity=".5" stroke-width="2.5" stroke-linecap="round"/>
        @endforeach
        <text x="52" y="48" fill="#0369a1" font-size="14" font-weight="700" text-anchor="middle" direction="rtl">البحر</text>

        @foreach($ordered as $index => $area)
            @php
                $segment = $segments[$index] ?? null;
                $count = (int) ($area->events_count ?? 0);
                $isActive = $active !== null && $active === $area->slug;
                $isHome = $homeAreaId !== null && (int) $homeAreaId === $area->id;

                // كثافة اللون تتبع عدد الفعاليات — الخريطة تُقرأ بلمحة
                $fill = match (true) {
                    $isActive => '#db2777',
                    $count >= 6 => '#1f62a7',
                    $count >= 3 => '#2678ca',
                    $count >= 1 => '#3b93e4',
                    default => '#cbd8e8',
                };
                $textFill = $count >= 1 || $isActive ? '#ffffff' : '#5e6b80';
            @endphp

            @if($segment)
                <a href="{{ route('events.index', ['area' => $area->slug]) }}"
                   class="gaza-seg" aria-label="{{ $area->name }} — {{ $count }} فعالية قادمة">
                    <polygon points="{{ $segment['points'] }}" fill="{{ $fill }}"
                             stroke="#ffffff" stroke-width="3" stroke-linejoin="round"
                             @if($isActive || $isHome) filter="url(#gaza-lift)" @endif/>

                    <text x="{{ $segment['label'][0] }}" y="{{ $segment['label'][1] }}"
                          fill="{{ $textFill }}" font-size="16.5" font-weight="800"
                          text-anchor="middle" direction="rtl">{{ $area->name }}</text>

                    <text x="{{ $segment['label'][0] }}" y="{{ $segment['label'][1] + 22 }}"
                          fill="{{ $textFill }}" fill-opacity=".9" font-size="13.5" font-weight="600"
                          text-anchor="middle" direction="rtl">
                        {{ $count === 0 ? 'لا فعاليات بعد' : $count.($count === 1 ? ' فعالية' : ' فعاليات') }}
                    </text>

                    @if($isHome)
                        {{-- علامة «أنتم هنا» على محافظة العائلة --}}
                        <g transform="translate({{ $segment['label'][0] + 72 }}, {{ $segment['label'][1] - 26 }})">
                            <circle r="13" fill="#ffffff"/>
                            <circle r="13" fill="none" stroke="#10b981" stroke-width="2.5"/>
                            <path d="M0 -6 a4.5 4.5 0 0 1 4.5 4.5c0 3.5-4.5 8-4.5 8s-4.5-4.5-4.5-8A4.5 4.5 0 0 1 0 -6Z"
                                  fill="#10b981"/>
                        </g>
                    @endif
                </a>
            @endif
        @endforeach

        {{-- اتجاه الشمال --}}
        <g transform="translate(338, 52)" opacity=".7">
            <path d="M0 -14 L7 8 L0 2 L-7 8 Z" fill="#1f62a7"/>
            <text y="24" fill="#1f62a7" font-size="12" font-weight="700" text-anchor="middle">ش</text>
        </g>
    </svg>

    <figcaption class="mt-3 flex flex-wrap items-center justify-center gap-x-4 gap-y-2 text-xs text-ink-soft">
        <span class="flex items-center gap-1.5">
            <span class="size-3 rounded-sm" style="background:#cbd8e8"></span> لا فعاليات
        </span>
        <span class="flex items-center gap-1.5">
            <span class="size-3 rounded-sm" style="background:#3b93e4"></span> قليلة
        </span>
        <span class="flex items-center gap-1.5">
            <span class="size-3 rounded-sm" style="background:#1f62a7"></span> كثيرة
        </span>
        @if($homeAreaId)
            <span class="flex items-center gap-1.5 font-semibold text-emerald-700">
                <x-ui.icon name="map-pin" class="size-3.5"/> مكانكم
            </span>
        @endif
    </figcaption>
</figure>
