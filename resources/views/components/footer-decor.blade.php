{{-- زخارف الفوتر الاحتفالية: قبعة حفلة وقصاصات وألعاب نارية — شكلية بحتة --}}
<div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">

    {{-- قبعة الحفلة مع شرائط متطايرة — الزاوية العلوية (يسار الشاشة) --}}
    <svg class="absolute -top-2 left-4 hidden h-28 w-28 -rotate-12 sm:block lg:left-10 lg:h-32 lg:w-32" viewBox="0 0 120 120" fill="none">
        <path d="M60 14 L92 88 Q60 102 28 88 Z" fill="#fcd34d"/>
        <path d="M60 14 L92 88 Q76 95 60 96 Z" fill="#fbbf24"/>
        <circle cx="60" cy="13" r="8" fill="#ec4899"/>
        <circle cx="52" cy="46" r="4" fill="#ec4899"/>
        <circle cx="70" cy="62" r="4" fill="#38bdf8"/>
        <circle cx="48" cy="76" r="4" fill="#f43f5e"/>
        <circle cx="66" cy="34" r="3" fill="#38bdf8"/>
        <path d="M92 30 Q104 24 100 12 Q112 20 118 8" stroke="#f43f5e" stroke-width="4" stroke-linecap="round"/>
        <path d="M20 26 Q10 18 16 6" stroke="#38bdf8" stroke-width="4" stroke-linecap="round"/>
    </svg>

    {{-- لطخة فرشاة وردية — أسفل الجهة اليسرى توازن القبعة --}}
    <svg class="absolute -left-10 bottom-28 hidden h-16 w-48 -rotate-[6deg] opacity-60 sm:block lg:w-64" viewBox="0 0 200 40" fill="none">
        <path d="M4 24 Q30 8 60 18 T120 16 T196 20 Q160 34 100 30 T4 24 Z" fill="#ec4899"/>
        <path d="M30 14 Q70 4 110 12" stroke="#f9a8d4" stroke-width="5" stroke-linecap="round"/>
    </svg>

    {{-- ألعاب نارية منقّطة — أسفل الجهة اليمنى --}}
    <svg class="absolute bottom-24 right-6 hidden h-32 w-32 opacity-50 md:block lg:h-40 lg:w-40" viewBox="0 0 100 100" fill="none">
        @foreach([[50,10],[78,22],[90,50],[78,78],[50,90],[22,78],[10,50],[22,22]] as [$x, $y])
            <line x1="50" y1="50" x2="{{ $x }}" y2="{{ $y }}" stroke="#fcd34d" stroke-width="2" stroke-linecap="round" stroke-dasharray="2 6"/>
            <circle cx="{{ $x }}" cy="{{ $y }}" r="2.5" fill="#f9a8d4"/>
        @endforeach
        <circle cx="50" cy="50" r="4" fill="#fcd34d"/>
    </svg>

    {{-- قصاصات متناثرة --}}
    <span class="absolute left-[16%] top-10 size-3 rounded-full bg-amber-300/80"></span>
    <span class="absolute left-[30%] top-24 size-2 rounded-full bg-pink-400/80"></span>
    <span class="absolute left-[8%] top-1/2 size-2.5 rounded-full bg-sky-300/70"></span>
    <span class="absolute right-[22%] top-14 size-2 rounded-full bg-amber-200/80"></span>
    <span class="absolute right-[38%] top-6 size-2.5 rotate-45 bg-pink-300/70"></span>
    <span class="absolute bottom-32 left-[42%] size-2 rotate-12 bg-amber-300/60"></span>
    <span class="absolute right-[10%] top-1/3 size-3 rounded-full bg-white/25"></span>
    <span class="absolute bottom-40 right-[46%] size-1.5 rounded-full bg-sky-200/70"></span>
</div>
