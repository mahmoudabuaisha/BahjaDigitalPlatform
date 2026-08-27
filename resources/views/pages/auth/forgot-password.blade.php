@extends('layouts.app')

@section('title', 'استعادة كلمة المرور — '.\App\Support\Settings::get('site_name'))

@section('content')

<section class="surface-tint relative overflow-hidden">
    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
        <div class="absolute top-16 start-10 size-40 rounded-full bg-brand-200/50 blur-3xl"></div>
        <div class="absolute bottom-10 end-16 size-52 rounded-full bg-pink-200/40 blur-3xl"></div>
    </div>

    <div class="relative mx-auto flex max-w-md flex-col items-center px-4 py-14 sm:px-6">
        <div class="card w-full gap-5 p-8">
            <div class="flex flex-col items-center gap-3 text-center">
                <span class="icon-tile icon-tile-lg tone tone-violet"><x-ui.icon name="envelope"/></span>
                <h1 class="text-3xl font-bold">استعادة كلمة المرور</h1>
                <p class="text-ink-soft">اكتبوا بريدكم المسجَّل وسنرسل لكم رابط تعيين كلمة مرور جديدة.</p>
            </div>

            @if(session('status'))
                <p class="rounded-2xl bg-emerald-50 px-4 py-3 text-center font-medium text-emerald-700">{{ session('status') }}</p>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-4">
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

                <button type="submit" class="btn btn-primary btn-block">
                    <x-ui.icon name="envelope" class="size-5"/> أرسلوا رابط الاستعادة
                </button>
            </form>

            <p class="text-center text-ink-soft">
                تذكّرتم كلمة المرور؟
                <a href="{{ route('login') }}" class="font-bold text-brand-700 no-underline hover:underline">عودة لتسجيل الدخول</a>
            </p>
        </div>

        <a href="{{ route('home') }}" class="btn btn-ghost mt-4">
            <x-ui.icon name="chevron-start" class="size-4"/> العودة للرئيسية
        </a>
    </div>
</section>

@endsection
