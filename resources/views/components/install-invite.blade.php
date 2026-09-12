{{-- دعوة خفيفة أعلى الصفحة. لا تظهر للزائر في أول زيارة، ولا تعود قبل
     ثلاثة أسابيع إن رُفضت، ولا تظهر إطلاقاً داخل النسخة المثبَّتة. --}}

<div x-data="installInvite" x-show="visible" x-cloak
     class="install-only border-b border-brand-100 bg-gradient-to-l from-brand-50 to-white">
    <div class="mx-auto flex max-w-7xl flex-wrap items-center gap-3 px-4 py-2.5 sm:px-6">

        <img src="/icons/icon-192.png" alt="" width="36" height="36" class="size-9 shrink-0 rounded-xl">

        <p class="min-w-[9rem] flex-1 text-sm leading-snug">
            <span class="block font-bold">ثبّتوا بَهْجَة على هاتفكم</span>
            <span class="block text-ink-soft">تعمل دون إنترنت</span>
        </p>

        <a href="{{ route('install') }}" class="btn btn-primary btn-sm shrink-0">كيف؟</a>

        <button type="button" @click="dismiss()" aria-label="إخفاء الدعوة"
                class="grid size-8 shrink-0 place-items-center rounded-lg text-ink-soft hover:bg-brand-50 hover:text-ink">
            <x-ui.icon name="x" class="size-4"/>
        </button>
    </div>
</div>
