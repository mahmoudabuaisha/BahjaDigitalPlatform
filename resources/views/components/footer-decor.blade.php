{{-- زخارف التذييل: توهّج ناعم وقصاصات تنجرف في الفراغات لا فوق النصوص — تذييل قصير لا يحتمل زحاماً --}}
<div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
    <div class="absolute -top-10 end-[12%] size-56 rounded-full bg-white/[.06] blur-3xl"></div>
    <div class="absolute -bottom-16 start-[8%] size-64 rounded-full bg-pink-400/15 blur-3xl"></div>

    {{-- ألعاب نارية منقّطة صغيرة أسفل الجهة اليسرى، تحت آخر الأعمدة --}}
    <svg class="twinkle absolute bottom-9 left-6 hidden h-14 w-14 opacity-40 lg:block" viewBox="0 0 100 100" fill="none">
        @foreach([[50,10],[78,22],[90,50],[78,78],[50,90],[22,78],[10,50],[22,22]] as [$x, $y])
            <line x1="50" y1="50" x2="{{ $x }}" y2="{{ $y }}" stroke="#fcd34d" stroke-width="2" stroke-linecap="round" stroke-dasharray="2 6"/>
            <circle cx="{{ $x }}" cy="{{ $y }}" r="2.5" fill="#f9a8d4"/>
        @endforeach
        <circle cx="50" cy="50" r="4" fill="#fcd34d"/>
    </svg>

    {{-- قصاصات في الشريط العلوي بين الشعار والأزرار، وأخرى فوق الموجة السفلية — على الشاشات المتوسطة فما فوق --}}
    <span class="drift-1 absolute hidden md:block left-[34%] top-5 size-2.5 rounded-full bg-amber-300/80"></span>
    <span class="drift-2 absolute hidden md:block left-[46%] top-14 size-2 rounded-full bg-pink-400/80"></span>
    <span class="drift-3 absolute hidden md:block right-[30%] top-5 size-2 rounded-full bg-sky-300/70"></span>
    <span class="drift-2 absolute hidden md:block right-[46%] top-16 size-2.5 rounded-full bg-white/25"></span>
    <span class="drift-1 absolute hidden md:block bottom-12 left-[14%] size-2 rotate-45 bg-amber-300/70"></span>
    <span class="drift-3 absolute hidden md:block bottom-10 right-[14%] size-2 rounded-full bg-pink-300/70"></span>
    <span class="drift-2 absolute hidden md:block bottom-8 left-[58%] size-1.5 rounded-full bg-sky-200/70"></span>
</div>
