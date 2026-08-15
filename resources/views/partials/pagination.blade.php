@if($paginator->hasPages())
    <nav class="flex flex-wrap items-center justify-center gap-2" aria-label="تنقّل بين الصفحات">
        {{-- السابق --}}
        @if($paginator->onFirstPage())
            <span class="chip opacity-45" aria-disabled="true">السابق</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="chip" rel="prev">السابق</a>
        @endif

        @foreach($elements as $element)
            @if(is_string($element))
                <span class="px-2 text-ink-soft">{{ $element }}</span>
            @endif

            @if(is_array($element))
                @foreach($element as $page => $url)
                    @if($page == $paginator->currentPage())
                        <span class="chip is-active" aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="chip">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- التالي --}}
        @if($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="chip" rel="next">التالي</a>
        @else
            <span class="chip opacity-45" aria-disabled="true">التالي</span>
        @endif
    </nav>
@endif
