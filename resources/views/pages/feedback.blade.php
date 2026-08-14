@extends('layouts.app')

@section('title', 'رأيكم يهمنا — '.\App\Support\Settings::get('site_name'))

@section('content')
<div class="max-w-xl">

    <h1 class="text-[32px] leading-tight">رأيكم يهمنا</h1>
    <p class="mt-2 leading-relaxed text-ash-800">
        تقييمكم يساعد فريق بَهْجَة والفرق التطوعية على تحسين الفعاليات القادمة.
    </p>

    @if(session('feedback_sent'))
        <p class="mt-3 bg-cyan-100 px-3 py-2 text-cyan-800">وصلنا تقييمكم — شكراً جزيلاً.</p>
    @endif

    <form method="POST" action="{{ route('feedback.store') }}" class="mt-6 flex flex-col gap-4">
        @csrf

        {{-- فخ البوتات — مخفي عن البشر --}}
        <input type="text" name="website" value="" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">

        <div>
            <p class="mb-1 text-sm text-ash-700">ما تقييمكم لتجربة المنصة والفعاليات؟</p>
            <x-star-rating/>
            @error('rating')
                <p class="mt-1 text-sm text-magenta-700">اختاروا عدد النجوم أولاً</p>
            @enderror
        </div>

        <label class="field block">
            <span>أنتم؟</span>
            <select name="source" class="input">
                <option value="family">عائلة / وليّ أمر</option>
                <option value="team">فريق ترفيهي</option>
            </select>
        </label>

        <label class="field block">
            <span>ملاحظاتكم واقتراحاتكم (اختياري)</span>
            <textarea name="message" rows="4" maxlength="1000"
                      placeholder="ما الذي أعجبكم؟ ما الذي نحسّنه؟" class="input">{{ old('message') }}</textarea>
        </label>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <label class="field block">
                <span>الاسم (اختياري)</span>
                <input type="text" name="contact_name" value="{{ old('contact_name') }}" maxlength="100" class="input">
            </label>
            <label class="field block">
                <span>الجوال (اختياري)</span>
                <input type="tel" name="contact_phone" value="{{ old('contact_phone') }}" maxlength="20" dir="ltr" class="input">
            </label>
        </div>

        <button type="submit" class="btn btn-primary btn-block max-w-xs">إرسال التقييم</button>
    </form>

</div>
@endsection
