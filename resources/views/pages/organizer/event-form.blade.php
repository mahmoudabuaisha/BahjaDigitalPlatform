@extends('layouts.organizer')

@php
    $editing = (bool) ($event ?? null);
    $tips = $editing
        ? [
            ['icon' => 'check', 'text' => 'تأكّدوا من تحديث جميع المعلومات قبل الحفظ.'],
            ['icon' => 'photo', 'text' => 'احرصوا على صورة واضحة وحديثة للفعالية.'],
            ['icon' => 'eye', 'text' => 'راجعوا الشروط والملاحظات قبل الحفظ.'],
            ['icon' => 'megaphone', 'text' => 'تحديث التفاصيل يصل إشعاراً لكل عائلة سجّلت.'],
        ]
        : [
            ['icon' => 'sparkles', 'text' => 'اختاروا عنواناً واضحاً ومبسّطاً يشدّ انتباه الأهالي.'],
            ['icon' => 'book-open', 'text' => 'اكتبوا وصفاً يشرح ما سيتعلّمه الطفل وما سيستمتع به.'],
            ['icon' => 'photo', 'text' => 'أضيفوا صورة مناسبة تعكس جوّ الفعالية.'],
            ['icon' => 'clock', 'text' => 'حدّدوا وقتاً مناسباً لأعمار الأطفال المستهدفين.'],
            ['icon' => 'check', 'text' => 'راجعوا كل المعلومات قبل الإرسال — الاعتماد يتم من الإدارة.'],
        ];
@endphp

@section('title', ($editing ? 'تعديل فعالية' : 'إضافة فعالية جديدة').' — '.\App\Support\Settings::get('site_name'))

@section('organizer')

{{-- مسار التنقّل --}}
<nav aria-label="مسار التنقّل" class="flex flex-wrap items-center gap-2 text-sm text-ink-soft">
    <a href="{{ route('organizer.dashboard') }}" class="no-underline hover:text-brand-600">لوحة التحكّم</a>
    <x-ui.icon name="chevron-start" class="size-4"/>
    <a href="{{ route('organizer.events') }}" class="no-underline hover:text-brand-600">فعالياتي</a>
    <x-ui.icon name="chevron-start" class="size-4"/>
    @if($editing)
        <span class="truncate">{{ $event->title }}</span>
        <x-ui.icon name="chevron-start" class="size-4"/>
    @endif
    <span class="font-medium text-ink">{{ $editing ? 'تعديل فعالية' : 'إضافة فعالية جديدة' }}</span>
</nav>

<div class="mt-4 flex flex-wrap items-start justify-between gap-3">
    <div>
        <h1 class="flex items-center gap-3 text-3xl font-bold">
            <span class="icon-tile tone tone-violet"><x-ui.icon :name="$editing ? 'paint-brush' : 'plus'"/></span>
            {{ $editing ? 'تعديل فعالية' : 'إضافة فعالية جديدة' }}
        </h1>
        <p class="mt-1 max-w-xl text-ink-soft">
            {{ $editing
                ? 'يمكنكم تعديل المعلومات والتفاصيل الخاصة بالفعالية.'
                : 'أضيفوا تفاصيل فعاليتكم بعناية ليتمكّن الأطفال وأولياء الأمور من التعرّف عليها والتسجيل فيها.' }}
        </p>
    </div>

    <a href="{{ $editing && $event->status->isPubliclyVisible() ? route('events.show', $event) : route('organizer.events') }}"
       class="btn btn-outline">
        <x-ui.icon name="chevron-end" class="size-5"/>
        {{ $editing && $event->status->isPubliclyVisible() ? 'العودة إلى الفعالية' : 'العودة إلى قائمة فعالياتي' }}
    </a>
</div>

@if($editing && $event->status === \App\Enums\EventStatus::Approved)
    <p class="mt-4 flex items-start gap-2 rounded-2xl bg-amber-50 px-4 py-3 text-amber-800">
        <x-ui.icon name="clock" class="mt-0.5 size-5 shrink-0"/>
        تعديل الاسم أو الوصف أو الموعد أو المكان يُحفظ كنسخة تنتظر اعتماد الإدارة،
        وتبقى النسخة المنشورة الحالية ظاهرة للعائلات حتى الاعتماد. تعديل المقاعد والشروط يُطبَّق مباشرة.
    </p>
@endif

@if($errors->any())
    <p class="mt-4 rounded-2xl bg-rose-50 px-4 py-3 font-medium text-rose-700">
        راجعوا الحقول المُعلَّمة بالأحمر — بعض المعلومات ناقصة أو غير صحيحة.
    </p>
@endif

<div class="mt-5 grid gap-5 xl:grid-cols-[minmax(0,1fr)_260px]">

    <form method="POST" enctype="multipart/form-data" class="flex flex-col gap-5"
          action="{{ $editing ? route('organizer.events.update', $event) : route('organizer.events.store') }}">
        @csrf
        @if($editing) @method('PUT') @endif

        {{-- ① المعلومات الأساسية --}}
        <section class="card gap-4 p-6">
            <h2 class="flex items-center gap-2 text-lg font-bold text-brand-700">
                <x-ui.icon name="sparkles" class="size-5"/> معلومات الفعالية الأساسية
            </h2>

            <label class="field block">
                <span>اسم الفعالية <b class="text-rose-500">*</b></span>
                <input type="text" name="title" required maxlength="120"
                       value="{{ old('title', $event->title ?? '') }}"
                       class="input @error('title') border-rose-300 @enderror"
                       placeholder="مثل: بطولة كرة القدم للأطفال">
                @error('title') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <div class="grid gap-4 sm:grid-cols-2">
                <label class="field block">
                    <span>نوع الفعالية <b class="text-rose-500">*</b></span>
                    <select name="category_id" required class="input @error('category_id') border-rose-300 @enderror">
                        <option value="">اختاروا نوع الفعالية</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}"
                                @selected(old('category_id', $event->category_id ?? '') == $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </label>

                <label class="field block">
                    <span>الفئة المستهدفة <b class="text-rose-500">*</b></span>
                    <select name="audience" required class="input">
                        @foreach(\App\Enums\Audience::cases() as $case)
                            <option value="{{ $case->value }}"
                                @selected(old('audience', ($event->audience ?? \App\Enums\Audience::All)->value) === $case->value)>
                                {{ $case->getLabel() }}
                            </option>
                        @endforeach
                    </select>
                </label>
            </div>

            <label class="field block" x-data="{ length: {{ mb_strlen(old('description', $event->description ?? '')) }} }">
                <span>وصف الفعالية <b class="text-rose-500">*</b></span>
                <textarea name="description" required maxlength="1000" rows="5" @input="length = $el.value.length"
                          class="input @error('description') border-rose-300 @enderror"
                          placeholder="اكتبوا وصفاً واضحاً عن الفعالية وأهدافها وما الذي سيستفيده منه المشاركون…">{{ old('description', $event->description ?? '') }}</textarea>
                <span class="mt-1 block text-start text-xs text-ink-soft" dir="ltr">
                    <span x-text="length">0</span>/1000
                </span>
                @error('description') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>
        </section>

        {{-- ② التاريخ والوقت والمكان --}}
        <section class="card gap-4 p-6">
            <h2 class="flex items-center gap-2 text-lg font-bold text-brand-700">
                <x-ui.icon name="calendar" class="size-5"/> التاريخ والوقت والمكان
            </h2>

            <div class="grid gap-4 sm:grid-cols-3">
                <label class="field block">
                    <span>تاريخ الفعالية <b class="text-rose-500">*</b></span>
                    <input type="date" name="start_date" required
                           value="{{ old('start_date', isset($event) ? $event->start_date->toDateString() : '') }}"
                           class="input @error('start_date') border-rose-300 @enderror">
                    @error('start_date') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </label>

                <label class="field block">
                    <span>وقت البداية <b class="text-rose-500">*</b></span>
                    <input type="time" name="start_time" required
                           value="{{ old('start_time', isset($event) ? substr($event->start_time, 0, 5) : '') }}"
                           class="input @error('start_time') border-rose-300 @enderror">
                    @error('start_time') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </label>

                <label class="field block">
                    <span>وقت النهاية</span>
                    <input type="time" name="end_time"
                           value="{{ old('end_time', isset($event) && $event->end_time ? substr($event->end_time, 0, 5) : '') }}"
                           class="input @error('end_time') border-rose-300 @enderror">
                    @error('end_time') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </label>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <label class="field block">
                    <span>المكان <b class="text-rose-500">*</b></span>
                    <select name="area_id" required class="input @error('area_id') border-rose-300 @enderror">
                        <option value="">اختاروا المحافظة</option>
                        @foreach($areas as $area)
                            <option value="{{ $area->id }}" @selected(old('area_id', $event->area_id ?? '') == $area->id)>
                                {{ $area->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('area_id') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </label>

                <label class="field block">
                    <span>مركز الإيواء</span>
                    <select name="shelter_center_id" class="input">
                        <option value="">بلا مركز محدَّد</option>
                        @foreach($centers as $center)
                            <option value="{{ $center->id }}"
                                @selected(old('shelter_center_id', $event->shelter_center_id ?? '') == $center->id)>
                                {{ $center->name }}
                            </option>
                        @endforeach
                    </select>
                </label>
            </div>

            <label class="field block">
                <span>العنوان التفصيلي</span>
                <input type="text" name="location_details" maxlength="180"
                       value="{{ old('location_details', $event->location_details ?? '') }}"
                       class="input" placeholder="أدخلوا العنوان التفصيلي للمكان">
            </label>

            @unless($editing)
                {{-- التكرار الأسبوعي: سلسلة مواعيد مستقلة حتى 8 أسابيع --}}
                <div x-data="{ repeat: {{ old('repeat_weekly') ? 'true' : 'false' }} }"
                     class="rounded-2xl bg-brand-50/60 p-4">
                    <label class="flex items-center gap-3">
                        <input type="checkbox" name="repeat_weekly" value="1" x-model="repeat" class="size-5 accent-brand-600">
                        <span class="font-medium">تكرار أسبوعي — نفس اليوم والوقت كل أسبوع</span>
                    </label>

                    <div x-show="repeat" x-cloak class="mt-3">
                        <label class="field block sm:max-w-56">
                            <span>عدد الأسابيع (حتى 8)</span>
                            <select name="repeat_count" class="input">
                                @foreach(range(2, 8) as $count)
                                    <option value="{{ $count }}" @selected((int) old('repeat_count', 4) === $count)>{{ $count }} أسابيع</option>
                                @endforeach
                            </select>
                        </label>
                        <p class="mt-2 text-xs text-ink-soft">
                            كل موعد يُنشأ فعاليةً مستقلة: تُراجع وتُلغى ويسجَّل حضورها وحدها.
                        </p>
                    </div>
                </div>
            @endunless
        </section>

        {{-- ③ الفئة المستهدفة والمشاركة --}}
        <section class="card gap-4 p-6">
            <h2 class="flex items-center gap-2 text-lg font-bold text-brand-700">
                <x-ui.icon name="users" class="size-5"/> الفئة العمرية والمشاركة
            </h2>

            <div class="grid gap-4 sm:grid-cols-3">
                <label class="field block">
                    <span>الفئة العمرية</span>
                    <select name="age_range" class="input">
                        <option value="">بلا تحديد</option>
                        @foreach($ageRanges as $key => $label)
                            <option value="{{ $key }}" @selected(old('age_range', $ageRange) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="field block">
                    <span>عدد المقاعد المتاحة <b class="text-rose-500">*</b></span>
                    <input type="number" name="expected_children" required min="1" max="2000"
                           value="{{ old('expected_children', $event->expected_children ?? '') }}"
                           class="input @error('expected_children') border-rose-300 @enderror" placeholder="أدخلوا عدد المقاعد">
                    @error('expected_children') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </label>

                <label class="field block">
                    <span>رسوم المشاركة (اختياري)</span>
                    <input type="number" name="fee" min="0" max="9999" step="0.5"
                           value="{{ old('fee', isset($event) && $event->fee > 0 ? (float) $event->fee : '') }}"
                           class="input @error('fee') border-rose-300 @enderror" placeholder="مجاناً">
                    @error('fee') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </label>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                @php $mode = old('registration_mode', ($event->registration_mode ?? \App\Enums\RegistrationMode::Approval)->value); @endphp
                <label class="field block" x-data="{ mode: '{{ $mode }}' }">
                    <span>طريقة التسجيل <b class="text-rose-500">*</b></span>
                    <select name="registration_mode" required class="input" x-model="mode">
                        @foreach(\App\Enums\RegistrationMode::cases() as $case)
                            <option value="{{ $case->value }}" @selected($mode === $case->value)>{{ $case->getLabel() }}</option>
                        @endforeach
                    </select>
                    <span class="mt-1 block text-xs text-ink-soft">
                        @foreach(\App\Enums\RegistrationMode::cases() as $case)
                            <span x-show="mode === '{{ $case->value }}'">{{ $case->hint() }}</span>
                        @endforeach
                    </span>
                </label>
            </div>

            <label class="field block">
                <span>الشروط والملاحظات (اختياري)</span>
                <textarea name="terms" maxlength="500" rows="3" class="input"
                          placeholder="اكتبوا أيّ شروط أو متطلّبات للمشاركة…">{{ old('terms', $event->terms ?? '') }}</textarea>
            </label>
        </section>

        {{-- ④ صورة الفعالية --}}
        <section class="card gap-4 p-6"
                 x-data="{
                     preview: '{{ $editing && $event->imageUrl() ? $event->imageUrl() : '' }}',
                     dragging: false,
                     pick(files) {
                         if (! files || ! files.length) return;
                         this.preview = URL.createObjectURL(files[0]);
                         this.$refs.input.files = files;
                     },
                 }">
            <h2 class="flex items-center gap-2 text-lg font-bold text-brand-700">
                <x-ui.icon name="photo" class="size-5"/> صورة الفعالية
            </h2>

            <div class="grid gap-4 sm:grid-cols-2">
                {{-- منطقة الرفع --}}
                <label @dragover.prevent="dragging = true" @dragleave="dragging = false"
                       @drop.prevent="dragging = false; pick($event.dataTransfer.files)"
                       :class="dragging ? 'border-brand-500 bg-brand-50' : 'border-brand-200 bg-white'"
                       class="grid min-h-44 cursor-pointer place-items-center rounded-2xl border-2 border-dashed p-6 text-center transition">
                    <div>
                        <span class="icon-tile tone tone-violet mx-auto"><x-ui.icon name="upload-cloud"/></span>
                        <p class="mt-2 font-medium" x-text="preview ? 'تغيير الصورة' : 'اسحبوا الصورة هنا أو انقروا لرفعها'"></p>
                        <p class="mt-1 text-xs text-ink-soft">PNG أو JPG — الحد الأقصى 2MB</p>
                    </div>
                    <input type="file" name="image" accept="image/png,image/jpeg" x-ref="input" class="sr-only"
                           @change="pick($event.target.files)">
                </label>

                {{-- المعاينة --}}
                <div class="relative grid min-h-44 place-items-center overflow-hidden rounded-2xl border border-brand-100 bg-brand-50/60">
                    <template x-if="preview">
                        <img :src="preview" alt="معاينة صورة الفعالية" class="size-full object-cover">
                    </template>
                    <div x-show="! preview" class="p-6 text-center text-ink-soft">
                        <x-ui.icon name="photo" class="mx-auto size-10 text-brand-300"/>
                        <p class="mt-2 text-sm">معاينة الصورة</p>
                    </div>
                </div>
            </div>

            @error('image') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
        </section>

        {{-- الأزرار --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            @if($editing)
                <button type="submit" form="delete-event" class="btn btn-outline text-rose-600">
                    <x-ui.icon name="trash" class="size-5"/> حذف الفعالية
                </button>
            @else
                <span></span>
            @endif

            {{-- سلسلة أسبوعية: سريان التعديل على المواعيد القادمة (القسم 6.4) --}}
            @if($editing && ($futureSiblingsCount ?? 0) > 0)
                <label class="flex w-full cursor-pointer items-center gap-2 rounded-2xl bg-sky-50 px-4 py-3 text-sm font-medium text-sky-800">
                    <input type="checkbox" name="apply_to_future" value="1" class="size-5 accent-brand-600">
                    طبّقوا هذا التعديل أيضاً على {{ $futureSiblingsCount }} من المواعيد القادمة في السلسلة
                    <span class="font-normal">(كل موعد معتمد يمرّ باعتماد الإدارة كالمعتاد)</span>
                </label>
            @endif

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('organizer.events') }}" class="btn btn-outline">
                    <x-ui.icon name="x" class="size-5"/> إلغاء
                </a>

                @if(! $editing || $event->status === \App\Enums\EventStatus::Draft)
                    <button type="submit" name="action" value="draft" class="btn btn-outline">
                        <x-ui.icon name="save" class="size-5"/> حفظ كمسودة
                    </button>
                @endif

                <button type="submit" name="action" value="publish" class="btn btn-primary">
                    <x-ui.icon :name="$editing ? 'save' : 'plus'" class="size-5"/>
                    {{ $editing ? 'حفظ التغييرات' : 'إضافة الفعالية' }}
                </button>
            </div>
        </div>
    </form>

    {{-- نصائح --}}
    <aside class="card h-fit gap-3 p-5 xl:sticky xl:top-24">
        <h2 class="flex items-center gap-2 font-bold text-brand-700">
            <x-ui.icon name="light-bulb" class="size-5"/>
            {{ $editing ? 'نصائح لتعديل فعالية ناجحة' : 'نصائح لإضافة فعالية ناجحة' }}
        </h2>

        <ul class="flex flex-col gap-3">
            @foreach($tips as $tip)
                <li class="flex items-start gap-2 text-sm text-ink-soft">
                    <span class="icon-tile tone tone-violet size-8 shrink-0"><x-ui.icon :name="$tip['icon']"/></span>
                    {{ $tip['text'] }}
                </li>
            @endforeach
        </ul>
    </aside>
</div>

@if($editing)
    <form id="delete-event" method="POST" action="{{ route('organizer.events.destroy', $event) }}" class="hidden"
          onsubmit="return confirm('حذف «{{ $event->title }}»؟ إن كانت فيها حجوزات ستُلغى الفعالية ويصل الأهل إشعار.')">
        @csrf
        @method('DELETE')
    </form>
@endif

@endsection
