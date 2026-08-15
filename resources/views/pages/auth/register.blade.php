@extends('layouts.app')

@section('title', 'إنشاء حساب — '.\App\Support\Settings::get('site_name'))

@section('content')

<section class="surface-tint relative overflow-hidden">
    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
        <div class="absolute top-20 end-12 size-40 rounded-full bg-brand-200/50 blur-3xl"></div>
        <div class="absolute bottom-8 start-10 size-52 rounded-full bg-pink-200/40 blur-3xl"></div>
    </div>

    <div class="relative mx-auto flex max-w-lg flex-col items-center px-4 py-14 sm:px-6">
        <div class="card w-full gap-5 p-8">
            <div class="flex flex-col items-center gap-3 text-center">
                <span class="icon-tile icon-tile-lg tone tone-violet"><x-ui.icon name="users"/></span>
                <h1 class="text-3xl font-bold">إنشاء حساب</h1>
                <p class="text-ink-soft">احجزوا مقاعد لأطفالكم، وتابعوا فعالياتكم في مكان واحد.</p>
            </div>

            <form method="POST" action="{{ route('register') }}" class="flex flex-col gap-4">
                @csrf

                <label class="field">
                    <span>الاسم الكامل <span class="text-brand-500">*</span></span>
                    <span class="relative block">
                        <x-ui.icon name="users" class="absolute top-1/2 start-4 size-5 -translate-y-1/2 text-brand-400"/>
                        <input type="text" name="name" value="{{ old('name') }}" required autofocus maxlength="120"
                               class="input ps-12" placeholder="مثال: أم محمّد">
                    </span>
                    @error('name') <span class="mt-1 block text-sm text-rose-600">{{ $message }}</span> @enderror
                </label>

                <label class="field">
                    <span>البريد الإلكتروني <span class="text-brand-500">*</span></span>
                    <span class="relative block">
                        <x-ui.icon name="envelope" class="absolute top-1/2 start-4 size-5 -translate-y-1/2 text-brand-400"/>
                        <input type="email" name="email" value="{{ old('email') }}" required maxlength="150" dir="ltr"
                               class="input ps-12" placeholder="you@example.com">
                    </span>
                    @error('email') <span class="mt-1 block text-sm text-rose-600">{{ $message }}</span> @enderror
                </label>

                <label class="field">
                    <span>رقم الجوال</span>
                    <span class="relative block">
                        <x-ui.icon name="phone" class="absolute top-1/2 start-4 size-5 -translate-y-1/2 text-brand-400"/>
                        <input type="tel" name="phone" value="{{ old('phone') }}" maxlength="20" dir="ltr"
                               class="input ps-12" placeholder="+970 59 000 0000">
                    </span>
                    @error('phone') <span class="mt-1 block text-sm text-rose-600">{{ $message }}</span> @enderror
                </label>

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="field" x-data="{ show: false }">
                        <span>كلمة المرور <span class="text-brand-500">*</span></span>
                        <span class="relative block">
                            <input :type="show ? 'text' : 'password'" type="password" name="password" required minlength="8" dir="ltr"
                                   class="input pe-12" placeholder="8 أحرف على الأقل">
                            <button type="button" @click="show = ! show" class="absolute top-1/2 end-3 -translate-y-1/2 rounded-lg p-1 text-ink-soft"
                                    :aria-label="show ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور'">
                                <x-ui.icon name="eye" class="size-5" x-show="! show"/>
                                <x-ui.icon name="eye-off" class="size-5" x-show="show" x-cloak/>
                            </button>
                        </span>
                        @error('password') <span class="mt-1 block text-sm text-rose-600">{{ $message }}</span> @enderror
                    </label>

                    <label class="field">
                        <span>تأكيد كلمة المرور <span class="text-brand-500">*</span></span>
                        <input type="password" name="password_confirmation" required minlength="8" dir="ltr" class="input" placeholder="أعيدوا كتابتها">
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <x-ui.icon name="plus" class="size-5"/> إنشاء حساب
                </button>

                <p class="text-center text-sm text-ink-soft">
                    لا نطلب أي بيانات عن أطفالكم، ولا نشارك بياناتكم مع أي جهة.
                </p>
            </form>

            <div class="flex items-center gap-3 text-sm text-ink-soft">
                <span class="h-px flex-1 bg-brand-100"></span> أو <span class="h-px flex-1 bg-brand-100"></span>
            </div>

            {{-- التسجيل بحساب Google يحتاج مفاتيح من Google Cloud — الزر معطّل حتى تصل --}}
            <span class="btn btn-outline btn-block" aria-disabled="true" title="قريباً — يحتاج ربط حساب Google">
                التسجيل باستخدام Google
                <span class="rounded-full bg-brand-50 px-2 text-[11px] text-brand-700">قريباً</span>
            </span>

            <p class="text-center text-ink-soft">
                لديكم حساب بالفعل؟
                <a href="{{ route('login') }}" class="font-bold text-brand-700 no-underline hover:underline">تسجيل الدخول</a>
            </p>

            <p class="text-center text-sm text-ink-soft">
                فريق تطوّعي؟
                <a href="{{ url('/team/register') }}" class="text-brand-700 no-underline hover:underline">سجّلوا من هنا</a>
            </p>
        </div>

        <a href="{{ route('home') }}" class="btn btn-ghost mt-4">
            <x-ui.icon name="chevron-start" class="size-4"/> العودة للرئيسية
        </a>
    </div>
</section>

@endsection
