<x-mail::message>
# {{ $notification->title }}

@if($notification->body)
{{ $notification->body }}
@endif

@if($notification->url)
<x-mail::button :url="url($notification->url)">
عرض التفاصيل في المنصّة
</x-mail::button>
@endif

مع أطيب التحيات،<br>
فريق {{ config('app.name') }}
</x-mail::message>
