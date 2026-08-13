@extends('layouts.app')

@section('title', 'رأيكم يهمنا — '.\App\Support\Settings::get('site_name'))

@section('content')
<div class="mx-auto max-w-md">

    <header class="mb-6 text-center">
        <p class="text-4xl">💬</p>
        <h1 class="mt-2 text-2xl font-bold text-gray-900">رأيكم يهمنا</h1>
        <p class="mt-2 text-sm text-gray-500">
            تقييمكم يساعد فريق بهجة والفرق التطوعية على تحسين الفعاليات القادمة
        </p>
    </header>

    @if(session('feedback_sent'))
        <div class="mb-5 rounded-2xl bg-calm-100 px-4 py-4 text-center text-sm font-bold text-calm-800">
            وصلنا تقييمكم — شكراً جزيلاً 💚
        </div>
    @endif

    <form method="POST" action="{{ route('feedback.store') }}"
          class="space-y-5 rounded-3xl border border-joy-100 bg-white p-6 shadow-sm">
        @csrf

        {{-- فخ البوتات — مخفي عن البشر --}}
        <input type="text" name="website" value="" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">

        <div class="text-center">
            <label class="mb-2 block text-sm font-bold text-gray-600">ما تقييمكم لتجربة المنصة والفعاليات؟</label>
            <x-star-rating/>
            @error('rating')
                <p class="mt-1 text-sm font-semibold text-red-600">اختاروا عدد النجوم أولاً</p>
            @enderror
        </div>

        <label class="block">
            <span class="mb-1 block text-sm font-bold text-gray-600">أنتم؟</span>
            <select name="source" class="w-full rounded-xl border-gray-200 text-sm focus:border-joy-400 focus:ring-joy-300">
                <option value="family">عائلة / وليّ أمر</option>
                <option value="team">فريق ترفيهي</option>
            </select>
        </label>

        <label class="block">
            <span class="mb-1 block text-sm font-bold text-gray-600">ملاحظاتكم واقتراحاتكم (اختياري)</span>
            <textarea name="message" rows="4" maxlength="1000"
                      placeholder="ما الذي أعجبكم؟ ما الذي نحسّنه؟"
                      class="w-full rounded-xl border-gray-200 text-sm focus:border-joy-400 focus:ring-joy-300">{{ old('message') }}</textarea>
        </label>

        <div class="grid grid-cols-2 gap-3">
            <label class="block">
                <span class="mb-1 block text-sm font-bold text-gray-600">الاسم (اختياري)</span>
                <input type="text" name="contact_name" value="{{ old('contact_name') }}" maxlength="100"
                       class="w-full rounded-xl border-gray-200 text-sm focus:border-joy-400 focus:ring-joy-300">
            </label>
            <label class="block">
                <span class="mb-1 block text-sm font-bold text-gray-600">الجوال (اختياري)</span>
                <input type="tel" name="contact_phone" value="{{ old('contact_phone') }}" maxlength="20" dir="ltr"
                       class="w-full rounded-xl border-gray-200 text-sm focus:border-joy-400 focus:ring-joy-300">
            </label>
        </div>

        <button type="submit"
                class="w-full rounded-xl bg-joy-500 py-3 font-bold text-white shadow-sm transition hover:bg-joy-600">
            إرسال التقييم
        </button>
    </form>

</div>
@endsection
