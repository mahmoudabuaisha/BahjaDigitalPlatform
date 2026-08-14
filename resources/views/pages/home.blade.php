@extends('layouts.app')

@section('content')
@php
    // اليوم النشط عند أول رسم من السيرفر — كي تظهر الصفحة صحيحة قبل عمل Alpine وبدونه
    $activeDay = $initialFilters['day'] ?: 'today';
    $activeArea = $initialFilters['area'];
    $headings = ['today' => 'فعاليات اليوم', 'tomorrow' => 'فعاليات الغد'];
@endphp

<div x-data="eventCalendar(@js($initialFilters))">

    {{-- دعوة التثبيت — تظهر فقط إذا عرض المتصفح التثبيت ولم يُرفض سابقاً --}}
    <div x-data="installPrompt" x-show="available" x-cloak
         class="mb-3 flex flex-wrap items-center gap-2 bg-cyan-100 px-3 py-2">
        <span class="flex-1 text-sm leading-relaxed text-cyan-800">
            ثبّتوا بَهْجَة على الشاشة الرئيسية — تعمل مثل تطبيق، بلا تنزيل.
        </span>
        <button type="button" @click="dismiss()" class="btn btn-ghost min-h-[36px] text-sm">لاحقاً</button>
        <button type="button" @click="install()" class="btn btn-primary min-h-[36px] text-sm">تثبيت</button>
    </div>

    <h1 class="text-[34px] leading-tight sm:text-[42px]">أين نجد الفرح اليوم؟</h1>

    <p class="mt-1 max-w-xl text-ash-800">
        كل فعاليات الترفيه والدعم النفسي في المحافظات الخمس، مرتّبة يوماً بيوم.
    </p>

    {{-- التصفية — تعمل محلياً على ما هو معروض: بلا أي طلب شبكة، ودون اتصال --}}
    <section class="mt-4" aria-label="تصفية الفعاليات">
        <div class="flex max-w-md gap-2" role="tablist" aria-label="اليوم">
            <button type="button" role="tab" class="day-tab" :aria-selected="day === 'today'"
                    @click="day = 'today'" aria-selected="{{ $activeDay === 'today' ? 'true' : 'false' }}">اليوم</button>
            <button type="button" role="tab" class="day-tab" :aria-selected="day === 'tomorrow'"
                    @click="day = 'tomorrow'" aria-selected="{{ $activeDay === 'tomorrow' ? 'true' : 'false' }}">غداً</button>
            <button type="button" role="tab" class="day-tab" :aria-selected="day === 'all'"
                    @click="day = 'all'" aria-selected="{{ $activeDay === 'all' ? 'true' : 'false' }}">كل الأيام</button>
        </div>

        <div class="mt-2 flex flex-wrap gap-2">
            <button type="button" class="chip" :aria-pressed="area === ''" @click="area = ''" aria-pressed="{{ $activeArea === '' ? 'true' : 'false' }}">
                كل المحافظات
            </button>
            @foreach($areas as $areaOption)
                <button type="button" class="chip"
                        :aria-pressed="area === '{{ $areaOption->slug }}'"
                        @click="area = '{{ $areaOption->slug }}'"
                        aria-pressed="{{ $activeArea === $areaOption->slug ? 'true' : 'false' }}">{{ $areaOption->name }}</button>
            @endforeach
        </div>

        {{-- تصفية أدق — مركز الإيواء ونوع النشاط، مطويّة كي تبقى الشاشة الأولى بسيطة --}}
        <details class="mt-3">
            <summary class="inline-flex cursor-pointer list-none items-center gap-1 text-sm text-cyan-700 hover:underline">
                تصفية أدق — مركز الإيواء ونوع النشاط
            </summary>

            <div class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-2">
                <label class="field block">
                    <span class="block">مركز الإيواء / المخيم</span>
                    <select x-model="center" class="input">
                        <option value="">كل المراكز</option>
                        @foreach($areas as $areaOption)
                            @foreach($areaOption->shelterCenters as $shelterCenter)
                                <option value="{{ $shelterCenter->id }}" x-show="area === '' || area === '{{ $areaOption->slug }}'">
                                    {{ $shelterCenter->name }} — {{ $areaOption->name }}
                                </option>
                            @endforeach
                        @endforeach
                    </select>
                </label>

                <label class="field block">
                    <span class="block">نوع النشاط</span>
                    <select x-model="category" class="input">
                        <option value="">كل الأنواع</option>
                        @foreach($categories as $categoryOption)
                            <option value="{{ $categoryOption->slug }}">{{ $categoryOption->name }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
        </details>
    </section>

    <div class="mt-6 flex items-baseline justify-between gap-3">
        <h2 class="text-[22px]" x-text="listHeading">{{ $headings[$activeDay] ?? 'الأيام الأربعة عشر القادمة' }}</h2>
        <span class="font-figure text-sm text-ash-700" x-text="listCount"></span>
    </div>

    {{-- الروزنامة مجمعة حسب اليوم --}}
    @forelse($eventsByDay as $date => $dayEvents)
        @php $carbonDate = \Illuminate\Support\Carbon::parse($date); @endphp
        <section data-day-group class="mt-4">
            <h3 class="flex items-baseline gap-2 text-[19px]" x-show="day === 'all'" @if($activeDay !== 'all') x-cloak @endif>
                <span>
                    @if($carbonDate->isToday()) اليوم
                    @elseif($carbonDate->isTomorrow()) غداً
                    @else {{ $carbonDate->translatedFormat('l') }}
                    @endif
                </span>
                <span class="font-figure text-sm font-normal text-ash-700">{{ $carbonDate->translatedFormat('j F') }}</span>
            </h3>

            <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                @foreach($dayEvents as $event)
                    <x-event-card :event="$event"/>
                @endforeach
            </div>
        </section>
    @empty
        <p class="mt-4 max-w-md text-ash-800">
            لا فعاليات منشورة حالياً — عودوا قريباً، الفرق الترفيهية ترفع جداولها باستمرار.
        </p>
    @endforelse

    {{-- حالة "لا نتائج" بعد التصفية --}}
    <div id="empty-results" class="mt-4 hidden max-w-md">
        <p class="text-ash-800">لا فعاليات تطابق هذه التصفية في الأيام القادمة.</p>
        <button type="button" @click="resetFilters()" class="btn btn-ghost mt-1 px-0">اعرضوا كل الفعاليات</button>
    </div>

    {{-- الطريق حين لا تجد العائلة فعالية قريبة --}}
    <section class="mt-8 max-w-xl">
        <h2 class="text-[19px]">لا تجدون فعالية قريبة؟</h2>
        <p class="mt-1 text-ash-800">
            شاركونا احتياج منطقتكم، أو أخبروا فريقاً ترفيهياً تعرفونه بالانضمام إلى بَهْجَة.
        </p>
        <div class="mt-2 flex flex-wrap gap-2">
            <a href="{{ route('feedback.create') }}" class="btn btn-secondary">أرسلوا ملاحظة</a>
            <a href="{{ url('/team/register') }}" class="btn btn-ghost">انضموا كفريق</a>
        </div>
    </section>

</div>
@endsection
