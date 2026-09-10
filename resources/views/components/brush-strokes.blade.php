{{-- شحطات فرشاة جدارية بتأليف «ثنائيات»: عند كل مستوى رأسي سحبة كبيرة بلونين
     متراكبين على جهة، يقابلها صدى أصغر على الجهة الأخرى — وتتبادل الجهتان
     الهيمنة نزولاً فيتوازن الجانبان. شكلية بحتة، خلف المحتوى، وللشاشات الواسعة --}}
@php
    // كل ثنائية: [نسبة الارتفاع، جهة السحبة الكبيرة، لوناها، لون الصدى المقابل]
    $duets = [
        ['top' => '5%',  'echoTop' => '9%',  'lead' => 'right', 'main' => '#f9a8d4', 'accent' => '#fde68a', 'echo' => '#93c5fd'],
        ['top' => '30%', 'echoTop' => '34%', 'lead' => 'left',  'main' => '#93c5fd', 'accent' => '#f9a8d4', 'echo' => '#fde68a'],
        ['top' => '56%', 'echoTop' => '60%', 'lead' => 'right', 'main' => '#fde68a', 'accent' => '#93c5fd', 'echo' => '#f9a8d4'],
        ['top' => '84%', 'echoTop' => '88%', 'lead' => 'left',  'main' => '#f9a8d4', 'accent' => '#93c5fd', 'echo' => '#93c5fd'],
    ];
@endphp
<div class="pointer-events-none absolute inset-0 hidden overflow-hidden lg:block" aria-hidden="true">

    @foreach($duets as $i => $d)
        @php $mirror = $d['lead'] === 'left'; @endphp

        {{-- السحبة الكبيرة: جسم عريض بلونين متراكبين وخطوط فرشاة جافة --}}
        <svg data-parallax="0.05" class="absolute {{ $mirror ? '-left-24' : '-right-24' }} h-auto w-80 opacity-75 xl:w-96"
             style="top: {{ $d['top'] }}; transform: rotate({{ $mirror ? '' : '-' }}8deg) {{ $mirror ? 'scaleX(-1)' : '' }};"
             viewBox="0 0 340 110" fill="none">
            <path d="M8 62 Q60 22 140 36 T270 32 Q318 34 332 44 Q294 70 216 66 T64 82 Q22 82 8 62 Z" fill="{{ $d['main'] }}"/>
            <path d="M46 40 Q120 10 210 24 T330 26 Q300 44 226 40 T88 52 Q58 52 46 40 Z" fill="{{ $d['accent'] }}" opacity=".65"/>
            <path d="M40 46 Q120 22 204 32" stroke="{{ $d['main'] }}" stroke-opacity=".6" stroke-width="9" stroke-linecap="round"/>
            <path d="M92 88 Q170 76 244 82" stroke="{{ $d['main'] }}" stroke-opacity=".45" stroke-width="6" stroke-linecap="round"/>
            <path d="M150 14 Q210 6 262 12" stroke="{{ $d['accent'] }}" stroke-opacity=".5" stroke-width="5" stroke-linecap="round"/>
        </svg>

        {{-- الصدى المقابل: سحبة أصغر بلون ثالث تعادل الكفة --}}
        <svg data-parallax="0.09" class="absolute {{ $mirror ? '-right-16' : '-left-16' }} h-auto w-56 opacity-65 xl:w-64"
             style="top: {{ $d['echoTop'] }}; transform: rotate({{ $mirror ? '-' : '' }}10deg) {{ $mirror ? '' : 'scaleX(-1)' }};"
             viewBox="0 0 220 70" fill="none">
            <path d="M6 40 Q40 14 92 24 T176 22 Q204 24 214 30 Q188 46 138 44 T44 52 Q16 52 6 40 Z" fill="{{ $d['echo'] }}"/>
            <path d="M28 30 Q76 12 128 20" stroke="{{ $d['echo'] }}" stroke-opacity=".55" stroke-width="7" stroke-linecap="round"/>
        </svg>

        {{-- رذاذ يتبع السحبة الكبيرة --}}
        <span class="absolute {{ $mirror ? 'left-16' : 'right-16' }} size-3 rounded-full opacity-80"
              style="top: calc({{ $d['top'] }} + 6.5rem); background: {{ $d['accent'] }};"></span>
        <span class="absolute {{ $mirror ? 'left-32' : 'right-32' }} size-2 rounded-full opacity-70"
              style="top: calc({{ $d['top'] }} + 8rem); background: {{ $d['main'] }};"></span>
        <span class="absolute {{ $mirror ? 'right-14' : 'left-14' }} size-2 rotate-45 opacity-60"
              style="top: calc({{ $d['echoTop'] }} + 5rem); background: {{ $d['echo'] }};"></span>
    @endforeach
</div>
