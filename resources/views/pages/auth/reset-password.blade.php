@extends('layouts.app')

@section('title', 'تعيين كلمة مرور جديدة — '.\App\Support\Settings::get('site_name'))

@section('content')

<section class="surface-tint relative overflow-hidden">
    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
        <div class="absolute top-16 start-10 size-40 rounded-full bg-brand-200/50 blur-3xl"></div>
        <div class="absolute bottom-10 end-16 size-52 rounded-full bg-pink-200/40 blur-3xl"></div>
    </div>

    <div class="relative mx-auto flex max-w-md flex-col items-center px-4 py-14 sm:px-6">
        <div class="card w-full gap-5 p-8">
            <div class="flex flex-col items-center gap-3 text-center">
                <span class="icon-tile icon-tile-lg tone tone-violet"><x-ui.icon name="shield-check"/></span>
                <h1 class="text-3xl font-bold">كلمة مرور جديدة</h1>
                <p class="text-ink-soft">اختاروا كلمة مرور جديدة لحسابكم (8 أحرف على الأقل).</p>
            </div>

            <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-4">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <label class="field">
                    <span>البريد الإلكتروني</span>
                    <span class="relative block">
                        <x-ui.icon name="envelope" class="absolute top-1/2 start-4 size-5 -translate-y-1/2 text-brand-400"/>
                        <input type="email" name="email" value="{{ old('email', $email) }}" required dir="ltr"
                               class="input ps-12" placeholder="you@example.com">
                    </span>
                    @error('email') <span class="mt-1 block text-sm text-rose-600">{{ $message }}</span> @enderror
                </label>

                <label class="field" x-data="{ show: false }">
                    <span>كلمة المرور الجديدة</span>
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

                <label class="field">
                    <span>تأكيد كلمة المرور</span>
                    <input type="password" name="password_confirmation" required dir="ltr"
                           class="input" placeholder="••••••••">
                </label>

                <button type="submit" class="btn btn-primary btn-block">
                    <x-ui.icon name="check" class="size-5"/> حفظ كلمة المرور
                </button>
            </form>
        </div>

        <a href="{{ route('login') }}" class="btn btn-ghost mt-4">
            <x-ui.icon name="chevron-start" class="size-4"/> عودة لتسجيل الدخول
        </a>
    </div>
</section>

@endsection
