@extends('layouts.app')

@section('title', 'سجّلوا فريقكم — '.\App\Support\Settings::get('site_name'))

@section('content')

<section class="surface-tint py-12">
    <div class="mx-auto max-w-3xl px-4 sm:px-6">

        <nav aria-label="مسار التنقّل" class="flex items-center gap-2 text-sm text-ink-soft">
            <a href="{{ route('home') }}" class="no-underline hover:text-brand-600">الرئيسية</a>
            <x-ui.icon name="chevron-start" class="size-4"/>
            <span class="font-medium text-ink">سجّلوا فريقكم</span>
        </nav>

        <h1 class="mt-4 flex items-center gap-3 text-3xl font-bold">
            <span class="icon-tile tone tone-emerald"><x-ui.icon name="users"/></span>
            انضمّوا كفريق تطوعي
        </h1>
        <p class="mt-2 max-w-xl text-ink-soft">
            قدّموا طلب الانضمام وتراجعه إدارة المبادرة — بعد الاعتماد يصلكم حساب لوحة الفريق
            وتبدؤون برفع فعالياتكم.
        </p>

        @if(session('application_sent'))
            <div class="mt-6 rounded-3xl border border-emerald-200 bg-emerald-50 p-8 text-center">
                <span class="icon-tile tone tone-emerald icon-tile-lg mx-auto"><x-ui.icon name="check"/></span>
                <p class="mt-3 text-lg font-bold text-emerald-800">وصل طلبكم</p>
                <p class="mt-1 text-emerald-700">
                    تراجعه الإدارة وتتواصل معكم على البريد أو الجوال الذي أدخلتموه.
                </p>
                <a href="{{ route('home') }}" class="btn btn-primary mt-4">العودة إلى الرئيسية</a>
            </div>
        @else
            <form method="POST" action="{{ route('teams.join.store') }}" class="card mt-6 gap-4 p-6">
                @csrf

                {{-- مصيدة سبام — لا تظهر للبشر --}}
                <input type="text" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">

                {{-- ١) بيانات الفريق الأساسية --}}
                <h2 class="flex items-center gap-2 text-lg font-bold"><span class="grid size-7 place-items-center rounded-full bg-brand-100 text-sm text-brand-700">١</span> بيانات الفريق</h2>

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="field block">
                        <span>اسم الفريق / المؤسسة <b class="text-rose-500">*</b></span>
                        <input type="text" name="team_name" required maxlength="120" value="{{ old('team_name') }}"
                               class="input @error('team_name') border-rose-300 @enderror" placeholder="مثل: فريق بسمة أمل">
                        @error('team_name') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </label>

                    <label class="field block">
                        <span>نوع الجهة <b class="text-rose-500">*</b></span>
                        <select name="org_type" required class="input @error('org_type') border-rose-300 @enderror">
                            <option value="" disabled {{ old('org_type') ? '' : 'selected' }}>اختاروا النوع…</option>
                            @foreach($orgTypes as $value => $label)
                                <option value="{{ $value }}" @selected(old('org_type') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('org_type') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </label>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="field block">
                        <span>المحافظة <b class="text-rose-500">*</b></span>
                        <select name="area_id" required class="input @error('area_id') border-rose-300 @enderror">
                            <option value="" disabled {{ old('area_id') ? '' : 'selected' }}>اختاروا المحافظة…</option>
                            @foreach($areas as $area)
                                <option value="{{ $area->id }}" @selected((int) old('area_id') === $area->id)>{{ $area->name }}</option>
                            @endforeach
                        </select>
                        @error('area_id') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </label>

                    <label class="field block">
                        <span>المقر / منطقة التواجد</span>
                        <input type="text" name="base_location" maxlength="160" value="{{ old('base_location') }}"
                               class="input" placeholder="مثل: مخيم النصيرات — قرب السوق">
                    </label>
                </div>

                {{-- ٢) مسؤول الميدان --}}
                <h2 class="mt-2 flex items-center gap-2 text-lg font-bold"><span class="grid size-7 place-items-center rounded-full bg-brand-100 text-sm text-brand-700">٢</span> مسؤول الميدان</h2>

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="field block">
                        <span>اسم مسؤول الفريق <b class="text-rose-500">*</b></span>
                        <input type="text" name="contact_name" required maxlength="120" value="{{ old('contact_name') }}"
                               class="input @error('contact_name') border-rose-300 @enderror">
                        @error('contact_name') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </label>

                    <label class="field block">
                        <span>البريد الإلكتروني الرسمي <b class="text-rose-500">*</b></span>
                        <input type="email" name="contact_email" required value="{{ old('contact_email') }}"
                               class="input @error('contact_email') border-rose-300 @enderror" dir="ltr">
                        <span class="mt-1 block text-xs text-ink-soft">عليه يصلكم حساب اللوحة والإشعارات والتقارير.</span>
                        @error('contact_email') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </label>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="field block">
                        <span>رقم الواتساب الشغّال <b class="text-rose-500">*</b></span>
                        <input type="tel" name="contact_phone" required maxlength="30" value="{{ old('contact_phone') }}"
                               class="input @error('contact_phone') border-rose-300 @enderror" dir="ltr" placeholder="0599000000">
                        <span class="mt-1 block text-xs text-ink-soft">مهم جداً للتواصل السريع مع الإدارة والعائلات.</span>
                        @error('contact_phone') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </label>

                    <label class="field block">
                        <span>رقم إضافي للطوارئ</span>
                        <input type="tel" name="emergency_phone" maxlength="30" value="{{ old('emergency_phone') }}"
                               class="input" dir="ltr" placeholder="0568000000">
                    </label>
                </div>

                {{-- ٣) الإمكانيات الميدانية --}}
                <h2 class="mt-2 flex items-center gap-2 text-lg font-bold"><span class="grid size-7 place-items-center rounded-full bg-brand-100 text-sm text-brand-700">٣</span> إمكانيات الفريق الميدانية</h2>

                <label class="field block">
                    <span>وصف نشاط الفريق <b class="text-rose-500">*</b></span>
                    <textarea name="description" required maxlength="1000" rows="4"
                              class="input @error('description') border-rose-300 @enderror"
                              placeholder="ماذا يقدّم فريقكم للأطفال؟ منذ متى تعملون؟ …">{{ old('description') }}</textarea>
                    @error('description') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </label>

                <fieldset class="field block">
                    <legend class="mb-2">محافظات القدرة على الوصول</legend>
                    <div class="flex flex-wrap gap-2">
                        @foreach($areas as $area)
                            <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-brand-100 px-3 py-2 text-sm has-checked:border-brand-400 has-checked:bg-brand-50">
                                <input type="checkbox" name="coverage_areas[]" value="{{ $area->id }}" class="size-4 accent-brand-600"
                                       @checked(in_array($area->id, array_map('intval', old('coverage_areas', []))))>
                                {{ $area->name }}
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <label class="field block">
                    <span>مراكز الإيواء / المخيمات التي تغطونها</span>
                    <input type="text" name="geographic_scope" maxlength="160" value="{{ old('geographic_scope') }}"
                           class="input" placeholder="مثل: مراكز الإيواء الغربية في خان يونس، مخيم النصيرات">
                </label>

                <fieldset class="field block">
                    <legend class="mb-2">الأنشطة التي تتقنونها <b class="text-rose-500">*</b></legend>
                    <div class="flex flex-wrap gap-2">
                        @foreach($activityOptions as $key => $label)
                            <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-brand-100 px-3 py-2 text-sm has-checked:border-brand-400 has-checked:bg-brand-50">
                                <input type="checkbox" name="activities[]" value="{{ $key }}" class="size-4 accent-brand-600"
                                       @checked(in_array($key, old('activities', [])))>
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                    @error('activities') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </fieldset>

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="field block">
                        <span>عدد المتطوعين لديكم <b class="text-rose-500">*</b></span>
                        <input type="number" name="volunteers_count" required min="1" max="5000" value="{{ old('volunteers_count') }}"
                               class="input @error('volunteers_count') border-rose-300 @enderror" dir="ltr">
                        @error('volunteers_count') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </label>

                    <label class="field block">
                        <span>كم طفلاً تستوعبون في النشاط الواحد؟ <b class="text-rose-500">*</b></span>
                        <input type="number" name="capacity_per_event" required min="1" max="5000" value="{{ old('capacity_per_event') }}"
                               class="input @error('capacity_per_event') border-rose-300 @enderror" dir="ltr">
                        @error('capacity_per_event') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </label>
                </div>

                {{-- ٤) التعهد --}}
                <label class="flex items-start gap-3 rounded-2xl bg-brand-50/60 p-4">
                    <input type="checkbox" name="terms" value="1" required class="mt-1 size-5 accent-brand-600">
                    <span class="text-sm">
                        <b>نتعهّد بسلامة الأطفال أولاً وبالالتزام بالأنشطة الترفيهية فقط</b> —
                        ونلتزم بسياسات المنصّة وأولها
                        <a href="{{ route('photo-policy') }}" target="_blank" class="font-bold text-brand-700">سياسة صور الأطفال</a>
                        (لا نشر لصورة طفل دون موافقة وليّ أمره)،
                        ودقّة مواعيد الفعاليات، وتسجيل الحضور الفعلي بعد كل فعالية.
                    </span>
                </label>
                @error('terms') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror

                <button type="submit" class="btn btn-primary btn-block">أرسلوا طلب الانضمام</button>
            </form>
        @endif
    </div>
</section>

@endsection
