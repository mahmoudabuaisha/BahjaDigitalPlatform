@extends('layouts.account')

@section('title', 'الإشعارات — '.\App\Support\Settings::get('site_name'))

@section('account')

<div class="flex flex-wrap items-start justify-between gap-3">
    <div>
        <h1 class="flex items-center gap-2 text-3xl font-bold">
            <x-ui.icon name="megaphone" class="size-7 text-brand-500"/>
            الإشعارات
        </h1>
        <p class="mt-1 text-ink-soft">تابعوا آخر التنبيهات المتعلقة بالحساب والفعاليات.</p>
    </div>

    <div class="flex flex-wrap gap-2">
        @if($counts['unread'])
            <form method="POST" action="{{ route('notifications.read') }}">
                @csrf
                <button type="submit" class="btn btn-outline btn-sm">
                    <x-ui.icon name="check" class="size-4"/> تعيين الكل كمقروء
                </button>
            </form>
        @endif

        @if($counts['all'])
            <form method="POST" action="{{ route('notifications.clear') }}"
                  onsubmit="return confirm('حذف كل الإشعارات؟ لا يمكن التراجع.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline btn-sm text-rose-600">حذف الكل</button>
            </form>
        @endif
    </div>
</div>

@if(session('notifications_read'))
    <p class="mt-4 rounded-2xl bg-emerald-50 px-4 py-3 font-medium text-emerald-700">عُلّمت كل الإشعارات كمقروءة.</p>
@endif
@if(session('notifications_cleared'))
    <p class="mt-4 rounded-2xl bg-brand-50 px-4 py-3 text-brand-700">حُذفت كل الإشعارات.</p>
@endif

{{-- التبويبات --}}
<div class="mt-5 flex flex-wrap gap-2 border-b border-brand-100 pb-3">
    @foreach($tabs as $key => $label)
        <a href="{{ route('notifications', $key === 'all' ? [] : ['tab' => $key]) }}"
           class="chip {{ $activeTab === $key ? 'is-active' : '' }}">
            @if($key === 'unread' && $counts['unread'])
                <span class="size-2 rounded-full {{ $activeTab === $key ? 'bg-white' : 'bg-brand-500' }}"></span>
            @endif
            {{ $label }}
            <span class="{{ $activeTab === $key ? 'text-white/80' : 'text-ink-soft' }}">({{ $counts[$key] ?? 0 }})</span>
        </a>
    @endforeach
</div>

@if($notifications->isEmpty())
    <div class="mt-6 rounded-3xl border border-dashed border-brand-200 bg-brand-50/50 p-12 text-center">
        <span class="icon-tile tone tone-violet icon-tile-lg mx-auto"><x-ui.icon name="megaphone"/></span>
        <p class="mt-3 text-lg font-bold">
            {{ $activeTab === 'all' ? 'لا إشعارات بعد' : 'لا إشعارات في هذا التبويب' }}
        </p>
        <p class="mt-1 text-ink-soft">سنُعلمكم فور ردّ الفريق على حجوزاتكم أو تغيّر موعد فعالية.</p>
    </div>
@else
    <div class="mt-5 flex flex-col gap-3">
        @foreach($notifications as $notification)
            <x-notification-row :notification="$notification"/>
        @endforeach
    </div>

    <div class="mt-6">{{ $notifications->links('partials.pagination') }}</div>
@endif

@endsection
