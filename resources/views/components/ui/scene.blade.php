@props(['name' => 'default', 'tone' => '#7c5cff', 'fit' => 'slice'])

{{--
    رسمة الفئة: مشهد مرسوم بـ SVG يحلّ محلّ الأيقونة المجرّدة حين لا تكون
    للفعالية صورة مرفوعة. تُلوَّن بلون الفئة نفسه، وبلا أي ملف خارجي.
--}}

@php
    $sky = '#fff7ed';
    $sun = '#fbbf24';
    $skin = '#f4c8a4';
    $white = '#ffffff';
@endphp

{{-- slice تملأ الإطار بالقصّ، وmeet تُظهر المشهد كاملاً على أرضية السماء نفسها --}}
<svg {{ $attributes->merge(['class' => 'block w-full']) }} viewBox="0 0 320 200" fill="none"
     preserveAspectRatio="xMidYMid {{ $fit }}" style="background:{{ $sky }}" role="img" aria-hidden="true">

    {{-- الخلفية والأرض — مشتركة بين كل المشاهد --}}
    <rect width="320" height="200" fill="{{ $sky }}"/>
    <circle cx="272" cy="40" r="24" fill="{{ $sun }}" opacity=".55"/>
    <circle cx="60" cy="34" r="14" fill="{{ $white }}" opacity=".9"/>
    <circle cx="78" cy="34" r="18" fill="{{ $white }}" opacity=".9"/>
    <circle cx="98" cy="36" r="12" fill="{{ $white }}" opacity=".9"/>
    <path d="M0 150c46-22 84-22 124 0s74 14 118-6 50-10 78 2v54H0Z" fill="{{ $tone }}" opacity=".16"/>
    <path d="M0 172c52-16 92-14 132 4s86 6 122-8 44-6 66 2v30H0Z" fill="{{ $tone }}" opacity=".28"/>

    @switch($name)

        {{-- ألعاب ترفيهية: مكعّبات وقطعة أحجية وكرة --}}
        @case('games')
            <rect x="46" y="96" width="46" height="46" rx="10" fill="{{ $tone }}"/>
            <rect x="58" y="108" width="22" height="22" rx="6" fill="{{ $white }}" opacity=".85"/>
            <rect x="96" y="112" width="34" height="30" rx="8" fill="{{ $sun }}"/>
            <path d="M170 92h34a8 8 0 0 1 8 8v10a10 10 0 0 1 0 20v10a8 8 0 0 1-8 8h-34a8 8 0 0 1-8-8v-10a10 10 0 0 0 0-20v-10a8 8 0 0 1 8-8Z"
                  fill="{{ $tone }}" opacity=".75"/>
            <circle cx="248" cy="124" r="20" fill="{{ $white }}"/>
            <path d="M248 104a20 20 0 0 1 17 30c-14-4-22-16-17-30Z" fill="{{ $tone }}"/>
            <path d="M248 144a20 20 0 0 1-17-30c14 4 22 16 17 30Z" fill="#0ea5e9" opacity=".75"/>
            <circle cx="248" cy="124" r="20" stroke="{{ $tone }}" stroke-width="3"/>
            @break

        {{-- دعم نفسي اجتماعي: طفلان متعانقان وقلب --}}
        @case('psychosocial')
            <path d="M160 62c6-12 26-11 26 4 0 12-16 20-26 27-10-7-26-15-26-27 0-15 20-16 26-4Z" fill="{{ $tone }}"/>
            {{-- طفلة على اليمين --}}
            <rect x="112" y="150" width="9" height="26" rx="4.5" fill="#8b6f47"/>
            <rect x="126" y="150" width="9" height="26" rx="4.5" fill="#8b6f47"/>
            <path d="M107 118h33a6 6 0 0 1 6 7l-5 30h-35l-5-30a6 6 0 0 1 6-7Z" fill="{{ $tone }}"/>
            <path d="M110 124l-14 20M137 124l14 20" stroke="{{ $skin }}" stroke-width="8" stroke-linecap="round"/>
            <circle cx="123" cy="102" r="16" fill="{{ $skin }}"/>
            <path d="M123 84c-11 0-18 7-18 16 0 4 2 6 4 7-1-8 4-13 14-13s15 5 14 13c2-1 4-3 4-7 0-9-7-16-18-16Z" fill="#5b4636"/>
            <circle cx="117" cy="103" r="2.4" fill="#3f3325"/>
            <circle cx="129" cy="103" r="2.4" fill="#3f3325"/>
            <path d="M117 110c4 4 8 4 12 0" stroke="#3f3325" stroke-width="2.4" stroke-linecap="round"/>

            {{-- طفل على اليسار --}}
            <rect x="188" y="152" width="9" height="24" rx="4.5" fill="#4b5563"/>
            <rect x="202" y="152" width="9" height="24" rx="4.5" fill="#4b5563"/>
            <path d="M183 120h33a6 6 0 0 1 6 7l-5 30h-35l-5-30a6 6 0 0 1 6-7Z" fill="{{ $sun }}"/>
            <path d="M186 126l-14 18M213 126l14 18" stroke="{{ $skin }}" stroke-width="8" stroke-linecap="round"/>
            <circle cx="199" cy="104" r="16" fill="{{ $skin }}"/>
            <path d="M199 86c-10 0-17 6-18 14 6-4 11-5 18-5s12 1 18 5c-1-8-8-14-18-14Z" fill="#3f3325"/>
            <circle cx="193" cy="105" r="2.4" fill="#3f3325"/>
            <circle cx="205" cy="105" r="2.4" fill="#3f3325"/>
            <path d="M193 112c4 4 8 4 12 0" stroke="#3f3325" stroke-width="2.4" stroke-linecap="round"/>
            @break

        {{-- رسم وأشغال يدوية: لوحة ألوان وفرشاة وورقة --}}
        @case('arts-crafts')
            <rect x="44" y="70" width="80" height="96" rx="10" fill="{{ $white }}"/>
            <path d="M58 140c10-22 20-32 30-30s14 20 24 20 14-8 14-8" stroke="{{ $tone }}" stroke-width="5" stroke-linecap="round"/>
            <circle cx="72" cy="96" r="10" fill="{{ $sun }}"/>
            <path d="M204 82c30 0 52 20 52 40 0 14-12 17-20 19-7 2-12 4-12 11 0 9-7 14-20 14-30 0-54-20-54-42s24-42 54-42Z" fill="{{ $tone }}"/>
            <circle cx="186" cy="106" r="7" fill="{{ $white }}"/>
            <circle cx="210" cy="98" r="7" fill="{{ $sun }}"/>
            <circle cx="232" cy="112" r="7" fill="#f43f5e"/>
            <circle cx="192" cy="132" r="7" fill="#10b981"/>
            <path d="M262 66l16 10-34 56-16-10Z" fill="#a16207"/>
            <path d="M228 122l-16-10-8 26Z" fill="{{ $tone }}"/>
            @break

        {{-- مسرح ودمى: ستارة ودمية --}}
        @case('theatre')
            <rect x="52" y="52" width="216" height="18" rx="9" fill="{{ $tone }}"/>
            <path d="M52 66h44c0 40-10 62-10 96H52Z" fill="#f43f5e" opacity=".8"/>
            <path d="M268 66h-44c0 40 10 62 10 96h34Z" fill="#f43f5e" opacity=".8"/>
            <circle cx="160" cy="112" r="24" fill="{{ $sun }}"/>
            <circle cx="152" cy="108" r="3.4" fill="#3f3325"/>
            <circle cx="169" cy="108" r="3.4" fill="#3f3325"/>
            <path d="M150 120c4 6 16 6 20 0" stroke="#3f3325" stroke-width="3" stroke-linecap="round"/>
            <path d="M136 146c6-8 14-12 24-12s18 4 24 12l-6 30h-36Z" fill="{{ $tone }}"/>
            <path d="M138 90 128 70M182 90l10-20" stroke="{{ $white }}" stroke-width="2.5" stroke-linecap="round" opacity=".9"/>
            <path d="M136 146 122 70M184 146l14-76" stroke="{{ $white }}" stroke-width="2" stroke-linecap="round" opacity=".55"/>
            @break

        {{-- أناشيد وموسيقى: دفّ ونوتات --}}
        @case('music')
            <circle cx="112" cy="122" r="38" fill="{{ $white }}"/>
            <circle cx="112" cy="122" r="38" stroke="{{ $tone }}" stroke-width="6"/>
            <circle cx="112" cy="122" r="22" stroke="{{ $tone }}" stroke-width="3" opacity=".5"/>
            <circle cx="76" cy="98" r="6" fill="{{ $sun }}"/>
            <circle cx="148" cy="98" r="6" fill="{{ $sun }}"/>
            <circle cx="76" cy="146" r="6" fill="{{ $sun }}"/>
            <circle cx="148" cy="146" r="6" fill="{{ $sun }}"/>
            <path d="M206 62v58" stroke="{{ $tone }}" stroke-width="6" stroke-linecap="round"/>
            <path d="M206 62c14 2 24 8 24 18" stroke="{{ $tone }}" stroke-width="6" stroke-linecap="round"/>
            <ellipse cx="196" cy="124" rx="14" ry="10" fill="{{ $tone }}"/>
            <path d="M258 88v42" stroke="{{ $sun }}" stroke-width="5" stroke-linecap="round"/>
            <ellipse cx="250" cy="134" rx="11" ry="8" fill="{{ $sun }}"/>
            @break

        {{-- رياضة وحركة: كرة ومرمى --}}
        @case('sports')
            <path d="M186 92h84v70h-84Z" fill="{{ $white }}" opacity=".7"/>
            <path d="M186 92h84v70h-84Z" stroke="{{ $tone }}" stroke-width="5"/>
            <path d="M204 92v70M228 92v70M252 92v70M186 116h84M186 140h84" stroke="{{ $tone }}" stroke-width="2" opacity=".45"/>
            <circle cx="104" cy="130" r="32" fill="{{ $white }}"/>
            <path d="M104 114l13 9-5 15h-16l-5-15Z" fill="{{ $tone }}"/>
            <path d="M104 100v14M131 121l-14 2M120 155l-8-17M88 155l8-17M77 121l14 2" stroke="{{ $tone }}" stroke-width="3" stroke-linecap="round"/>
            <circle cx="104" cy="130" r="32" stroke="{{ $tone }}" stroke-width="4"/>
            @break

        {{-- حكايات وقصص: كتاب مفتوح ونجوم --}}
        @case('stories')
            <path d="M160 92c-16-12-38-14-58-8v70c20-6 42-4 58 8Z" fill="{{ $white }}"/>
            <path d="M160 92c16-12 38-14 58-8v70c-20-6-42-4-58 8Z" fill="{{ $white }}"/>
            <path d="M160 92c-16-12-38-14-58-8v70c20-6 42-4 58 8 16-12 38-14 58-8V84c-20-6-42-4-58 8Z"
                  stroke="{{ $tone }}" stroke-width="5" stroke-linejoin="round"/>
            <path d="M160 92v70" stroke="{{ $tone }}" stroke-width="5"/>
            <path d="M116 106h30M116 122h30M174 106h30M174 122h30" stroke="{{ $tone }}" stroke-width="3" opacity=".45" stroke-linecap="round"/>
            <path d="M244 62l4 10 10 4-10 4-4 10-4-10-10-4 10-4Z" fill="{{ $sun }}"/>
            <path d="M72 74l3 8 8 3-8 3-3 8-3-8-8-3 8-3Z" fill="{{ $sun }}"/>
            @break

        {{-- مناسبات خاصة: هدية وبالونات --}}
        @case('special')
            <rect x="112" y="106" width="96" height="60" rx="10" fill="{{ $tone }}"/>
            <rect x="104" y="92" width="112" height="24" rx="8" fill="{{ $tone }}" opacity=".85"/>
            <path d="M154 92v74M166 92v74" stroke="{{ $sun }}" stroke-width="10"/>
            <path d="M160 92c-10-4-20-8-20-18s14-10 20 4c6-14 20-14 20-4s-10 14-20 18Z" fill="{{ $sun }}"/>
            <ellipse cx="68" cy="86" rx="20" ry="24" fill="#f43f5e" opacity=".85"/>
            <path d="M68 110c4 14-6 22-4 36" stroke="{{ $tone }}" stroke-width="2.5"/>
            <ellipse cx="256" cy="76" rx="17" ry="21" fill="#0ea5e9" opacity=".85"/>
            <path d="M256 97c-4 14 6 20 4 34" stroke="{{ $tone }}" stroke-width="2.5"/>
            <rect x="36" y="132" width="8" height="8" rx="2" fill="{{ $sun }}" transform="rotate(24 36 132)"/>
            <rect x="284" y="120" width="8" height="8" rx="2" fill="#f43f5e" transform="rotate(-18 284 120)"/>
            @break

        {{-- الافتراضي: طائرة ورقية وطفلان --}}
        @default
            <path d="M214 56l30 26-26 30-30-26Z" fill="{{ $tone }}"/>
            <path d="M214 56l4 56M188 86l56 -4" stroke="{{ $white }}" stroke-width="2.5" opacity=".8"/>
            <path d="M218 112c-8 12-22 16-30 30" stroke="{{ $tone }}" stroke-width="2.5"/>
            <path d="M188 142c6 2 6 8 0 10s-6-8 0-10Z" fill="{{ $sun }}"/>
            <rect x="94" y="152" width="9" height="24" rx="4.5" fill="#4b5563"/>
            <rect x="108" y="152" width="9" height="24" rx="4.5" fill="#4b5563"/>
            <path d="M89 120h33a6 6 0 0 1 6 7l-5 30H88l-5-30a6 6 0 0 1 6-7Z" fill="{{ $tone }}"/>
            <path d="M92 126l-13 16" stroke="{{ $skin }}" stroke-width="8" stroke-linecap="round"/>
            <path d="M119 126l22-14" stroke="{{ $skin }}" stroke-width="8" stroke-linecap="round"/>
            <path d="M143 108c22-2 40-6 52-14" stroke="{{ $tone }}" stroke-width="2.5" stroke-linecap="round"/>
            <circle cx="105" cy="104" r="16" fill="{{ $skin }}"/>
            <path d="M105 86c-10 0-17 6-18 14 6-4 11-5 18-5s12 1 18 5c-1-8-8-14-18-14Z" fill="#5b4636"/>
            <circle cx="99" cy="105" r="2.4" fill="#3f3325"/>
            <circle cx="111" cy="105" r="2.4" fill="#3f3325"/>
            <path d="M99 112c4 4 8 4 12 0" stroke="#3f3325" stroke-width="2.4" stroke-linecap="round"/>
    @endswitch
</svg>
