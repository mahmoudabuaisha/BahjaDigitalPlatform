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

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="field block">
                        <span>اسم الفريق <b class="text-rose-500">*</b></span>
                        <input type="text" name="team_name" required maxlength="120" value="{{ old('team_name') }}"
                               class="input @error('team_name') border-rose-300 @enderror" placeholder="مثل: فريق بسمة أمل">
                        @error('team_name') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </label>

                    <label class="field block">
                        <span>اسم المسؤول <b class="text-rose-500">*</b></span>
                        <input type="text" name="contact_name" required maxlength="120" value="{{ old('contact_name') }}"
                               class="input @error('contact_name') border-rose-300 @enderror">
                        @error('contact_name') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </label>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="field block">
                        <span>البريد الإلكتروني <b class="text-rose-500">*</b></span>
                        <input type="email" name="contact_email" required value="{{ old('contact_email') }}"
                               class="input @error('contact_email') border-rose-300 @enderror" dir="ltr">
                        @error('contact_email') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </label>

                    <label class="field block">
                        <span>رقم الجوال (واتساب) <b class="text-rose-500">*</b></span>
                        <input type="tel" name="contact_phone" required maxlength="30" value="{{ old('contact_phone') }}"
                               class="input @error('contact_phone') border-rose-300 @enderror" dir="ltr" placeholder="0599000000">
                        @error('contact_phone') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </label>
                </div>

                <label class="field block">
                    <span>وصف نشاط الفريق <b class="text-rose-500">*</b></span>
                    <textarea name="description" required maxlength="1000" rows="4"
                              class="input @error('description') border-rose-300 @enderror"
                              placeholder="ماذا يقدّم فريقكم للأطفال؟ منذ متى تعملون؟ …">{{ old('description') }}</textarea>
                    @error('description') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </label>

                <label class="field block">
                    <span>النطاق الجغرافي</span>
                    <input type="text" name="geographic_scope" maxlength="160" value="{{ old('geographic_scope') }}"
                           class="input" placeholder="مثل: خان يونس ورفح — مراكز الإيواء الغربية">
                </label>

                <label class="flex items-start gap-3 rounded-2xl bg-brand-50/60 p-4">
                    <input type="checkbox" name="terms" value="1" required class="mt-1 size-5 accent-brand-600">
                    <span class="text-sm">
                        نتعهّد بالالتزام بسياسات المنصّة — وأولها
                        <a href="{{ route('photo-policy') }}" target="_blank" class="font-bold text-brand-700">سياسة صور الأطفال</a>
                        (لا نشر لصورة طفل دون موافقة وليّ أمره) —
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
