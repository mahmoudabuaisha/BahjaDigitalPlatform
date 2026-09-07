{{-- شحطات فرشاة جدارية على أطراف الصفحة: لمسات لونية من هوية المنصّة تتوزع
     رأسياً بالتناوب بين الجانبين — شكلية بحتة، خلف المحتوى، وللشاشات الواسعة فقط --}}
<div class="pointer-events-none absolute inset-0 hidden overflow-hidden lg:block" aria-hidden="true">

    {{-- سحبة فرشاة عريضة متدرجة الطرفين --}}
    @foreach([
        ['side' => 'right', 'top' => '6%',  'rot' => '-14deg', 'w' => 'w-56', 'color' => '#f9a8d4', 'flip' => false, 'op' => 'opacity-70'],
        ['side' => 'left',  'top' => '22%', 'rot' => '10deg',  'w' => 'w-64', 'color' => '#93c5fd', 'flip' => true,  'op' => 'opacity-60'],
        ['side' => 'right', 'top' => '45%', 'rot' => '8deg',   'w' => 'w-48', 'color' => '#fde68a', 'flip' => false, 'op' => 'opacity-80'],
        ['side' => 'left',  'top' => '58%', 'rot' => '-9deg',  'w' => 'w-52', 'color' => '#f9a8d4', 'flip' => true,  'op' => 'opacity-55'],
        ['side' => 'right', 'top' => '78%', 'rot' => '-12deg', 'w' => 'w-60', 'color' => '#93c5fd', 'flip' => false, 'op' => 'opacity-60'],
        ['side' => 'left',  'top' => '90%', 'rot' => '7deg',   'w' => 'w-44', 'color' => '#fbcfe8', 'flip' => true,  'op' => 'opacity-70'],
    ] as $s)
        <svg class="absolute {{ $s['side'] === 'right' ? '-right-14' : '-left-14' }} {{ $s['w'] }} {{ $s['op'] }} h-auto"
             style="top: {{ $s['top'] }}; transform: rotate({{ $s['rot'] }}) {{ $s['flip'] ? 'scaleX(-1)' : '' }};"
             viewBox="0 0 220 70" fill="none">
            <path d="M6 40 Q40 14 92 24 T176 22 Q204 24 214 30 Q188 46 138 44 T44 52 Q16 52 6 40 Z" fill="{{ $s['color'] }}"/>
            <path d="M28 30 Q76 12 128 20" stroke="{{ $s['color'] }}" stroke-opacity=".55" stroke-width="7" stroke-linecap="round"/>
            <path d="M60 56 Q104 48 150 52" stroke="{{ $s['color'] }}" stroke-opacity=".4" stroke-width="5" stroke-linecap="round"/>
        </svg>
    @endforeach

    {{-- رذاذ نقاط صغير قرب بعض الشحطات --}}
    <span class="absolute right-10 top-[9.5%] size-2.5 rounded-full bg-pink-200 opacity-80"></span>
    <span class="absolute right-24 top-[7%] size-1.5 rounded-full bg-pink-300 opacity-70"></span>
    <span class="absolute left-12 top-[25%] size-2 rounded-full bg-sky-200 opacity-80"></span>
    <span class="absolute right-16 top-[48%] size-2 rotate-45 bg-amber-200 opacity-70"></span>
    <span class="absolute left-16 top-[61%] size-1.5 rounded-full bg-pink-200 opacity-70"></span>
    <span class="absolute right-12 top-[81%] size-2 rounded-full bg-sky-200 opacity-60"></span>
</div>
