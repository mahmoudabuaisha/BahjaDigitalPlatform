<x-filament-panels::page>

    @php
        $summary = $this->summary();
        $areas = $this->areas();
        $places = $this->places();
        $peak = max(1, $areas->max('calls'));
    @endphp

    {{-- الخلاصة --}}
    <div style="display:grid;gap:1rem;grid-template-columns:repeat(auto-fit,minmax(220px,1fr))">
        @foreach([
            ['label' => 'نداءات قائمة', 'value' => $summary['standing'], 'hint' => 'عائلات لم تصلها فعالية قريبة بعد', 'color' => '#f59e0b'],
            ['label' => 'أطفال ينتظرون', 'value' => $summary['children'], 'hint' => 'مجموع ما ذكرته العائلات', 'color' => '#db2777'],
            ['label' => 'نداءات لُبّيت', 'value' => $summary['answered'], 'hint' => 'خلال الثلاثين يوماً الماضية', 'color' => '#10b981'],
        ] as $card)
            <div class="fi-section" style="padding:1.1rem 1.25rem;border-inline-start:4px solid {{ $card['color'] }}">
                <p class="fi-section-header-description" style="font-size:.85rem">{{ $card['label'] }}</p>
                <p style="margin-top:.3rem;font-size:1.9rem;font-weight:700;font-variant-numeric:tabular-nums">{{ number_format($card['value']) }}</p>
                <p class="fi-section-header-description" style="margin-top:.3rem;font-size:.78rem">{{ $card['hint'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- الفجوة بين الطلب والعرض في كل محافظة --}}
    <div class="fi-section" style="margin-top:1.25rem;padding:1.25rem">
        <h2 style="font-size:1.05rem;font-weight:700">الطلب والعرض في المحافظات</h2>
        <p class="fi-section-header-description" style="margin-top:.2rem;font-size:.82rem">
            الشريط يقيس النداءات القائمة؛ والرقم إلى جانبه عدد الفعاليات القادمة هناك.
        </p>

        <div style="margin-top:1rem;display:flex;flex-direction:column;gap:.85rem">
            @foreach($areas as $row)
                <div style="display:flex;align-items:center;gap:.9rem;flex-wrap:wrap">
                    <span style="min-width:7.5rem;font-weight:600">{{ $row->area->name }}</span>

                    <span style="flex:1;min-width:10rem;height:.85rem;border-radius:999px;background:#f1f5f9;overflow:hidden">
                        @if($row->calls > 0)
                            <span style="display:block;height:100%;border-radius:999px;
                                         width:{{ max(4, round($row->calls / $peak * 100)) }}%;
                                         background:{{ $row->events === 0 ? '#e11d48' : '#f59e0b' }}"></span>
                        @endif
                    </span>

                    <span style="min-width:11rem;font-size:.85rem;font-variant-numeric:tabular-nums">
                        <b>{{ \App\Models\NeighbourhoodCall::callsLabel($row->calls) }}</b>
                        <span class="fi-section-header-description">
                            — {{ \App\Models\NeighbourhoodCall::childrenLabel($row->children) }}
                        </span>
                    </span>

                    <span style="min-width:8.5rem;font-size:.85rem;font-variant-numeric:tabular-nums"
                          class="fi-section-header-description">
                        {{ $row->events === 1 ? 'فعالية قادمة واحدة' : $row->events.' فعالية قادمة' }}
                    </span>

                    @if($row->calls > 0 && $row->events === 0)
                        <span style="font-size:.75rem;font-weight:700;color:#e11d48;background:#fff1f2;padding:.15rem .6rem;border-radius:999px">
                            طلب بلا عرض
                        </span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- الأماكن الأكثر نداءً --}}
    <div class="fi-section" style="margin-top:1.25rem;padding:1.25rem">
        <h2 style="font-size:1.05rem;font-weight:700">الأماكن الأكثر نداءً</h2>
        <p class="fi-section-header-description" style="margin-top:.2rem;font-size:.82rem">
            الفرق لا ترى مكاناً دون {{ \App\Models\NeighbourhoodCall::TEAM_VISIBILITY_FLOOR }} نداءات — وهذه القائمة كاملة للإدارة.
        </p>

        @if($places->isEmpty())
            <p style="margin-top:1rem" class="fi-section-header-description">لا نداءات قائمة الآن.</p>
        @else
            <div style="margin-top:1rem;overflow-x:auto">
                <table style="width:100%;border-collapse:collapse;font-size:.9rem">
                    <thead>
                        <tr class="fi-section-header-description" style="text-align:start">
                            <th style="text-align:start;padding:.5rem .4rem;font-weight:600">المكان</th>
                            <th style="text-align:start;padding:.5rem .4rem;font-weight:600">المحافظة</th>
                            <th style="text-align:start;padding:.5rem .4rem;font-weight:600">النداءات</th>
                            <th style="text-align:start;padding:.5rem .4rem;font-weight:600">الأطفال</th>
                            <th style="text-align:start;padding:.5rem .4rem;font-weight:600">ظاهر للفرق</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($places as $place)
                            <tr style="border-top:1px solid rgba(148,163,184,.2)">
                                <td style="padding:.6rem .4rem;font-weight:600">{{ $place->center->name }}</td>
                                <td style="padding:.6rem .4rem">{{ $place->center->area?->name }}</td>
                                <td style="padding:.6rem .4rem;font-variant-numeric:tabular-nums">{{ $place->calls }}</td>
                                <td style="padding:.6rem .4rem;font-variant-numeric:tabular-nums">{{ $place->children }}</td>
                                <td style="padding:.6rem .4rem">
                                    @if($place->calls >= \App\Models\NeighbourhoodCall::TEAM_VISIBILITY_FLOOR)
                                        <span style="font-size:.75rem;font-weight:700;color:#047857;background:#ecfdf5;padding:.15rem .6rem;border-radius:999px">نعم</span>
                                    @else
                                        <span style="font-size:.75rem;font-weight:700;color:#64748b;background:#f1f5f9;padding:.15rem .6rem;border-radius:999px">دون العتبة</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</x-filament-panels::page>
