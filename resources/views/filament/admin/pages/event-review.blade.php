<x-filament-panels::page>

    @php
        $counts = $this->tabCounts();

        // ألوان مكتوبة هنا لا كأصناف Tailwind: ملف CSS الخاص بـ Filament مبنيّ
        // مسبقاً، فالأصناف التي لا يستعملها هو نفسه لا وجود لها في المتصفّح.
        $cards = [
            'all' => ['label' => 'إجمالي الفعاليات', 'icon' => 'heroicon-o-calendar-days', 'color' => '#3b93e4'],
            'rejected' => ['label' => 'مرفوضة', 'icon' => 'heroicon-o-x-circle', 'color' => '#f43f5e'],
            'approved' => ['label' => 'مقبولة', 'icon' => 'heroicon-o-check-circle', 'color' => '#10b981'],
            'pending' => ['label' => 'قيد المراجعة', 'icon' => 'heroicon-o-clock', 'color' => '#f59e0b'],
        ];
    @endphp

    {{-- بطاقات الأرقام: نقرة على البطاقة تفتح تبويبها --}}
    <div style="display:flex;flex-wrap:wrap;gap:1rem">
        @foreach($cards as $key => $card)
            <button type="button" wire:click="selectTab('{{ $key }}')" class="fi-section"
                    style="flex:1 1 200px;text-align:start;padding:1.15rem 1.25rem;cursor:pointer;
                           border:1.5px solid {{ $this->statusTab === $key ? $card['color'] : 'transparent' }}">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:.75rem">
                    <div>
                        <p class="fi-section-header-description" style="font-size:.875rem">{{ $card['label'] }}</p>
                        <p style="margin-top:.25rem;font-size:1.875rem;font-weight:700;line-height:1.2">
                            {{ number_format($counts[$key] ?? 0) }}
                        </p>
                        <p style="margin-top:.25rem;font-size:.75rem;color:{{ $card['color'] }}">عرض التفاصيل</p>
                    </div>

                    <span style="display:grid;place-items:center;width:2.75rem;height:2.75rem;border-radius:.85rem;
                                 color:{{ $card['color'] }};background:{{ $card['color'] }}1f">
                        <x-filament::icon :icon="$card['icon']" style="width:1.5rem;height:1.5rem"/>
                    </span>
                </div>
            </button>
        @endforeach
    </div>

    {{-- التبويبات --}}
    <x-filament::tabs>
        @foreach($this->tabs() as $key => $tab)
            <x-filament::tabs.item
                :active="$this->statusTab === $key"
                :badge="$counts[$key] ?? 0"
                wire:click="selectTab('{{ $key }}')">
                {{ $tab['label'] }}
            </x-filament::tabs.item>
        @endforeach
    </x-filament::tabs>

    {{ $this->table }}

</x-filament-panels::page>
