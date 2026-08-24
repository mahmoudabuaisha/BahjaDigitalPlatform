<x-filament-panels::page>
    <div style="display:grid;gap:1rem;grid-template-columns:repeat(auto-fill,minmax(280px,1fr))">
        @foreach($this->checks() as $check)
            <div class="fi-section" style="padding:1.1rem 1.25rem;border-inline-start:4px solid {{ $check['ok'] ? '#10b981' : '#f59e0b' }}">
                <p class="fi-section-header-description" style="font-size:.85rem">{{ $check['label'] }}</p>
                <p style="margin-top:.3rem;font-size:1.6rem;font-weight:700">{{ $check['value'] }}</p>
                <p class="fi-section-header-description" style="margin-top:.3rem;font-size:.78rem">{{ $check['hint'] }}</p>
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
