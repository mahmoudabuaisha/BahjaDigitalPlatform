@extends('layouts.app')

@section('content')
<div x-data="eventCalendar(@js($initialFilters))">

    {{-- الترويسة --}}
    <section class="mb-6 rounded-3xl bg-gradient-to-l from-joy-500 to-joy-400 px-5 py-7 text-white shadow-md">
        <h1 class="text-2xl font-bold leading-snug sm:text-3xl">
            وين الفرح اليوم؟ 🎈
        </h1>
        <p class="mt-2 max-w-lg text-sm leading-relaxed text-joy-50 sm:text-base">
            كل فعاليات الترفيه والدعم النفسي لأطفال غزة في روزنامة واحدة —
            اختاروا منطقتكم وشوفوا أقرب فعالية لأطفالكم.
        </p>
    </section>

    {{-- شريط الفلترة — يعمل محلياً دون إنترنت --}}
    <section class="mb-6 space-y-3 rounded-2xl border border-joy-100 bg-white p-4 shadow-sm">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <label class="block">
                <span class="mb-1 block text-xs font-bold text-gray-500">المنطقة</span>
                <select x-model="area" class="w-full rounded-xl border-gray-200 text-sm focus:border-joy-400 focus:ring-joy-300">
                    <option value="">كل المناطق</option>
                    @foreach($areas as $area)
                        <option value="{{ $area->slug }}">{{ $area->name }}</option>
                    @endforeach
                </select>
            </label>

            <label class="block">
                <span class="mb-1 block text-xs font-bold text-gray-500">مركز الإيواء / المخيم</span>
                <select x-model="center" class="w-full rounded-xl border-gray-200 text-sm focus:border-joy-400 focus:ring-joy-300">
                    <option value="">كل المراكز</option>
                    @foreach($areas as $area)
                        @foreach($area->shelterCenters as $center)
                            <option value="{{ $center->id }}" x-show="area === '' || area === '{{ $area->slug }}'">
                                {{ $center->name }} — {{ $area->name }}
                            </option>
                        @endforeach
                    @endforeach
                </select>
            </label>

            <label class="block">
                <span class="mb-1 block text-xs font-bold text-gray-500">نوع الفعالية</span>
                <select x-model="category" class="w-full rounded-xl border-gray-200 text-sm focus:border-joy-400 focus:ring-joy-300">
                    <option value="">كل الأنواع</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->slug }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </label>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button type="button" @click="day = (day === 'today' ? '' : 'today')"
                    :class="day === 'today' ? 'bg-joy-500 text-white' : 'bg-joy-50 text-joy-700 hover:bg-joy-100'"
                    class="rounded-full px-4 py-1.5 text-sm font-bold transition">اليوم</button>
            <button type="button" @click="day = (day === 'tomorrow' ? '' : 'tomorrow')"
                    :class="day === 'tomorrow' ? 'bg-joy-500 text-white' : 'bg-joy-50 text-joy-700 hover:bg-joy-100'"
                    class="rounded-full px-4 py-1.5 text-sm font-bold transition">غداً</button>

            <button type="button" x-show="hasFilters" x-cloak @click="resetFilters()"
                    class="rounded-full px-4 py-1.5 text-sm font-semibold text-gray-400 underline hover:text-gray-600">
                مسح الفلاتر
            </button>
        </div>
    </section>

    {{-- الروزنامة مجمعة حسب اليوم --}}
    @forelse($eventsByDay as $date => $dayEvents)
        @php $carbonDate = \Illuminate\Support\Carbon::parse($date); @endphp
        <section data-day-group class="mb-6">
            <h2 class="mb-3 flex items-baseline gap-2">
                <span class="text-lg font-bold text-gray-900">
                    @if($carbonDate->isToday()) اليوم
                    @elseif($carbonDate->isTomorrow()) غداً
                    @else {{ $carbonDate->translatedFormat('l') }}
                    @endif
                </span>
                <span class="text-sm text-gray-400">{{ $carbonDate->translatedFormat('j F') }}</span>
            </h2>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                @foreach($dayEvents as $event)
                    <x-event-card :event="$event"/>
                @endforeach
            </div>
        </section>
    @empty
        <section class="rounded-2xl border border-dashed border-joy-200 bg-white px-6 py-14 text-center">
            <p class="text-4xl">🎪</p>
            <h2 class="mt-3 text-lg font-bold text-gray-700">لا فعاليات منشورة حالياً</h2>
            <p class="mt-1 text-sm text-gray-500">عودوا قريباً — الفرق الترفيهية ترفع جداولها باستمرار</p>
        </section>
    @endforelse

    {{-- حالة "لا نتائج" بعد الفلترة --}}
    <section id="empty-results" class="hidden rounded-2xl border border-dashed border-joy-200 bg-white px-6 py-14 text-center">
        <p class="text-4xl">🔍</p>
        <h2 class="mt-3 text-lg font-bold text-gray-700">لا فعاليات تطابق الفلاتر</h2>
        <button type="button" @click="resetFilters()" class="mt-2 text-sm font-bold text-joy-600 underline">عرض كل الفعاليات</button>
    </section>

</div>
@endsection
