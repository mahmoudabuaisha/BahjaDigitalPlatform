@extends('layouts.account')

@section('title', 'ملفي الشخصي — '.\App\Support\Settings::get('site_name'))

@section('account')

<h1 class="text-3xl font-bold">الملف الشخصي</h1>
<p class="mt-1 text-ink-soft">بياناتكم وأطفالكم — تُستعمل في الحجز وحده، ولا تظهر لأحد خارج فريق الفعالية.</p>

@if(session('profile_saved'))
    <p class="mt-4 flex items-center gap-2 rounded-2xl bg-emerald-50 px-4 py-3 font-medium text-emerald-700">
        <x-ui.icon name="check" class="size-5"/> حُفظت بياناتكم.
    </p>
@endif

{{-- ═══ المعلومات الشخصية ═══ --}}
<form method="POST" action="{{ route('account.profile.update') }}" class="card mt-6 gap-4 p-6">
    @csrf
    @method('PUT')

    <h2 class="text-xl font-bold">المعلومات الشخصية</h2>

    <div class="grid gap-4 sm:grid-cols-2">
        <label class="field">
            <span>الاسم الكامل <span class="text-brand-500">*</span></span>
            <input type="text" name="name" value="{{ old('name', $user->name) }}" required maxlength="120" class="input">
            @error('name') <span class="mt-1 block text-sm text-rose-600">{{ $message }}</span> @enderror
        </label>

        <label class="field">
            <span>البريد الإلكتروني <span class="text-brand-500">*</span></span>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" required dir="ltr" class="input">
            @error('email') <span class="mt-1 block text-sm text-rose-600">{{ $message }}</span> @enderror
        </label>

        <label class="field">
            <span>رقم الجوال</span>
            <input type="tel" name="phone" value="{{ old('phone', $user->phone) }}" maxlength="20" dir="ltr" class="input">
        </label>

        <label class="field">
            <span>تاريخ الميلاد</span>
            <input type="date" name="birth_date" value="{{ old('birth_date', $user->birth_date?->toDateString()) }}" class="input">
            @error('birth_date') <span class="mt-1 block text-sm text-rose-600">{{ $message }}</span> @enderror
        </label>

        <label class="field">
            <span>الجنس</span>
            <select name="gender" class="input">
                <option value="">—</option>
                <option value="male" @selected(old('gender', $user->gender) === 'male')>ذكر</option>
                <option value="female" @selected(old('gender', $user->gender) === 'female')>أنثى</option>
            </select>
        </label>

        <label class="field">
            <span>المحافظة</span>
            <select name="area_id" class="input">
                <option value="">—</option>
                @foreach($areas as $area)
                    <option value="{{ $area->id }}" @selected((int) old('area_id', $user->area_id) === $area->id)>{{ $area->name }}</option>
                @endforeach
            </select>
        </label>

        <label class="field sm:col-span-2">
            <span>العنوان</span>
            <input type="text" name="address" value="{{ old('address', $user->address) }}" maxlength="200" class="input"
                   placeholder="مثال: مركز إيواء مدرسة الشاطئ — خيمة 12">
        </label>
    </div>

    <button type="submit" class="btn btn-primary self-start">
        <x-ui.icon name="check" class="size-5"/> حفظ التغييرات
    </button>
</form>

{{-- ═══ الأطفال ═══ --}}
<section class="card mt-6 gap-4 p-6" x-data="{ adding: {{ $children->isEmpty() ? 'true' : 'false' }} }">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold">الأطفال المسجَّلون</h2>
            <p class="text-sm text-ink-soft">أضيفوا أطفالكم مرة واحدة، ثم احجزوا لهم بضغطة.</p>
        </div>
        <button type="button" @click="adding = ! adding" class="btn btn-outline btn-sm">
            <x-ui.icon name="plus" class="size-4"/> إضافة طفل
        </button>
    </div>

    @if(session('child_saved'))
        <p class="rounded-2xl bg-emerald-50 px-4 py-3 font-medium text-emerald-700">حُفظت بيانات الطفل.</p>
    @endif
    @if(session('child_deleted'))
        <p class="rounded-2xl bg-brand-50 px-4 py-3 text-brand-700">حُذف الطفل من قائمتكم.</p>
    @endif

    <form method="POST" action="{{ route('children.store') }}" x-show="adding" x-cloak
          class="grid gap-4 rounded-2xl bg-brand-50/60 p-4 sm:grid-cols-2">
        @csrf

        <label class="field">
            <span>اسم الطفل <span class="text-brand-500">*</span></span>
            <input type="text" name="name" required maxlength="80" class="input" placeholder="مثال: أحمد">
            @error('name') <span class="mt-1 block text-sm text-rose-600">{{ $message }}</span> @enderror
        </label>

        <label class="field">
            <span>تاريخ الميلاد</span>
            <input type="date" name="birth_date" class="input">
            @error('birth_date') <span class="mt-1 block text-sm text-rose-600">{{ $message }}</span> @enderror
        </label>

        <label class="field">
            <span>الجنس</span>
            <select name="gender" class="input">
                <option value="">—</option>
                <option value="male">ذكر</option>
                <option value="female">أنثى</option>
            </select>
        </label>

        <label class="field">
            <span>الصف الدراسي</span>
            <input type="text" name="grade" maxlength="60" class="input" placeholder="مثال: الصف الثالث">
        </label>

        <button type="submit" class="btn btn-primary sm:col-span-2">حفظ الطفل</button>
    </form>

    @if($children->isNotEmpty())
        <div class="grid gap-3 sm:grid-cols-2">
            @foreach($children as $child)
                <article class="card tone tone-sky flex-row items-center gap-3 p-4">
                    <span class="icon-tile icon-tile-lg"><x-ui.icon name="cake"/></span>

                    <div class="min-w-0 flex-1">
                        <p class="truncate font-bold">{{ $child->name }}</p>
                        <p class="text-sm text-ink-soft">
                            @if($child->birth_date)
                                {{ $child->birth_date->translatedFormat('j F Y') }} · {{ $child->age() }} سنوات
                            @else
                                لم يُحدَّد تاريخ الميلاد
                            @endif
                        </p>
                        @if($child->grade)
                            <p class="text-sm text-ink-soft">{{ $child->grade }}</p>
                        @endif
                    </div>

                    <form method="POST" action="{{ route('children.destroy', $child) }}"
                          onsubmit="return confirm('حذف {{ $child->name }} من قائمة أطفالكم؟')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-ghost btn-sm text-rose-600" aria-label="حذف">
                            <x-ui.icon name="arrow-back" class="size-4"/>
                        </button>
                    </form>
                </article>
            @endforeach
        </div>
    @endif
</section>

{{-- ═══ كلمة المرور ═══ --}}
<form method="POST" action="{{ route('account.password') }}" class="card mt-6 gap-4 p-6">
    @csrf
    @method('PUT')

    <h2 class="text-xl font-bold">تغيير كلمة المرور</h2>

    @if(session('password_saved'))
        <p class="rounded-2xl bg-emerald-50 px-4 py-3 font-medium text-emerald-700">تم تغيير كلمة المرور.</p>
    @endif

    <div class="grid gap-4 sm:grid-cols-3">
        <label class="field">
            <span>كلمة المرور الحالية</span>
            <input type="password" name="current_password" required dir="ltr" class="input">
            @error('current_password') <span class="mt-1 block text-sm text-rose-600">{{ $message }}</span> @enderror
        </label>

        <label class="field">
            <span>كلمة المرور الجديدة</span>
            <input type="password" name="password" required minlength="8" dir="ltr" class="input">
            @error('password') <span class="mt-1 block text-sm text-rose-600">{{ $message }}</span> @enderror
        </label>

        <label class="field">
            <span>تأكيد كلمة المرور</span>
            <input type="password" name="password_confirmation" required minlength="8" dir="ltr" class="input">
        </label>
    </div>

    <button type="submit" class="btn btn-primary self-start">تغيير كلمة المرور</button>
</form>

@endsection
