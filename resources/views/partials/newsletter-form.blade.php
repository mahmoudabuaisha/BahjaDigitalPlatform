{{-- نموذج الاشتراك في النشرة: يُستعمل في التذييل (على الأزرق الداكن) وفي صفحة النشرة.
     $formId يميّز حقول كل نسخة، و$dark يضبط ألوان الرسائل على الخلفية الداكنة --}}
@php
    $formId = $formId ?? 'footer';
    $dark = $dark ?? false;
    $areas = $areas ?? \App\Models\Area::orderBy('sort_order')->get(['id', 'name']);
    $errorClass = $dark ? 'text-sm font-medium text-rose-200' : 'text-sm font-medium text-rose-600';
@endphp
<form method="POST" action="{{ route('newsletter.subscribe') }}" class="flex flex-col gap-3" aria-label="الاشتراك في النشرة البريدية">
    @csrf
    {{-- فخ البوتات — مخفي عن البشر --}}
    <input type="text" name="website" value="" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">

    <div class="grid gap-3 sm:grid-cols-[minmax(0,1.3fr)_minmax(11rem,1fr)_auto]">
        <div>
            <label for="newsletter-email-{{ $formId }}" class="sr-only">البريد الإلكتروني</label>
            <input id="newsletter-email-{{ $formId }}" type="email" name="newsletter_email" value="{{ old('newsletter_email') }}"
                   required maxlength="120" dir="ltr" autocomplete="email" placeholder="example@gmail.com" class="input">
        </div>
        <div>
            <label for="newsletter-area-{{ $formId }}" class="sr-only">المحافظة</label>
            <select id="newsletter-area-{{ $formId }}" name="newsletter_area" class="input">
                <option value="">كل المحافظات</option>
                @foreach($areas as $area)
                    <option value="{{ $area->id }}" @selected((string) old('newsletter_area') === (string) $area->id)>{{ $area->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary">
            <x-ui.icon name="envelope" class="size-5"/> اشتركوا
        </button>
    </div>

    @error('newsletter_email') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
    @error('newsletter_area') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror

    @if(session('newsletter_status'))
        <p class="flex items-center gap-2 rounded-2xl px-4 py-3 font-medium {{ $dark ? 'bg-white/15 text-white' : 'bg-emerald-50 text-emerald-700' }}">
            <x-ui.icon name="check" class="size-5 shrink-0"/> {{ session('newsletter_status') }}
        </p>
    @endif

    <p class="text-xs {{ $dark ? 'text-white/70' : 'text-ink-soft' }}">
        رسالة واحدة في الأسبوع، وإلغاء الاشتراك بضغطة من أي رسالة. لا نشارك بريدكم مع أحد.
    </p>
</form>
