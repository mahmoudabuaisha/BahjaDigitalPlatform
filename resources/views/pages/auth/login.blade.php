@extends('layouts.app')

@section('title', 'تسجيل الدخول — '.\App\Support\Settings::get('site_name'))

@section('content')

<section class="surface-tint relative overflow-hidden">
    {{-- أشكال زخرفية خفيفة على الجانبين --}}
    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
        <div class="absolute top-16 start-10 size-40 rounded-full bg-brand-200/50 blur-3xl"></div>
        <div class="absolute bottom-10 end-16 size-52 rounded-full bg-pink-200/40 blur-3xl"></div>
    </div>

    <div class="relative mx-auto flex max-w-md flex-col items-center px-4 py-14 sm:px-6">
        <div class="card w-full gap-5 p-8">
            <div class="flex flex-col items-center gap-3 text-center">
                <span class="icon-tile icon-tile-lg tone tone-violet"><x-ui.icon name="shield-check"/></span>
                <h1 class="text-3xl font-bold">تسجيل الدخول</h1>
                <p class="text-ink-soft">مرحباً بكم مجدداً — ادخلوا لمتابعة حجوزاتكم.</p>
            </div>

            @if(session('status'))
                <p class="rounded-2xl bg-emerald-50 px-4 py-3 text-center font-medium text-emerald-700">{{ session('status') }}</p>
            @endif

            <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-4">
                @csrf

                <label class="field">
                    <span>البريد الإلكتروني</span>
                    <span class="relative block">
                        <x-ui.icon name="envelope" class="absolute top-1/2 start-4 size-5 -translate-y-1/2 text-brand-400"/>
                        <input type="email" name="email" value="{{ old('email') }}" required autofocus dir="ltr"
                               class="input ps-12" placeholder="you@example.com">
                    </span>
                    @error('email') <span class="mt-1 block text-sm text-rose-600">{{ $message }}</span> @enderror
                </label>

                <label class="field" x-data="{ show: false }">
                    <span>كلمة المرور</span>
                    <span class="relative block">
                        <input :type="show ? 'text' : 'password'" type="password" name="password" required dir="ltr"
                               class="input pe-12" placeholder="••••••••">
                        <button type="button" @click="show = ! show" class="absolute top-1/2 end-3 -translate-y-1/2 rounded-lg p-1 text-ink-soft"
                                :aria-label="show ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور'">
                            <x-ui.icon name="eye" class="size-5" x-show="! show"/>
                            <x-ui.icon name="eye-off" class="size-5" x-show="show" x-cloak/>
                        </button>
                    </span>
                    @error('password') <span class="mt-1 block text-sm text-rose-600">{{ $message }}</span> @enderror
                </label>

                <div class="flex flex-wrap items-center justify-between gap-2">
                    <label class="flex cursor-pointer items-center gap-2 text-sm">
                        <input type="checkbox" name="remember" value="1" class="size-5 accent-brand-600">
                        تذكّروني
                    </label>

                    {{-- استعادة كلمة المرور تحتاج خادم بريد — تُفعَّل حين تُضبط بيانات SMTP --}}
                    <a href="{{ route('contact') }}" class="text-sm text-brand-700 no-underline hover:underline">نسيتم كلمة المرور؟ تواصلوا معنا</a>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <x-ui.icon name="arrow-back" class="size-5 rtl:rotate-180"/> تسجيل الدخول
                </button>
            </form>

            <div class="flex items-center gap-3 text-sm text-ink-soft">
                <span class="h-px flex-1 bg-brand-100"></span> أو <span class="h-px flex-1 bg-brand-100"></span>
            </div>

            {{-- الدخول بحساب Google يحتاج مفاتيح من Google Cloud — الزر معطّل حتى تصل --}}
            <span class="btn btn-outline btn-block" aria-disabled="true" title="قريباً — يحتاج ربط حساب Google">
                تسجيل الدخول باستخدام Google
                <span class="rounded-full bg-brand-50 px-2 text-[11px] text-brand-700">قريباً</span>
            </span>

            <p class="text-center text-ink-soft">
                ليس لديكم حساب؟
                <a href="{{ route('register') }}" class="font-bold text-brand-700 no-underline hover:underline">أنشئوا حساباً جديداً</a>
            </p>
        </div>

        <a href="{{ route('home') }}" class="btn btn-ghost mt-4">
            <x-ui.icon name="chevron-start" class="size-4"/> العودة للرئيسية
        </a>
    </div>
</section>

@endsection
