<x-mail::message>
# رسالة جديدة من «تواصلوا معنا»

**المرسِل:** {{ $contactMessage->name }}

@if($contactMessage->phone)
**الجوال:** {{ $contactMessage->phone }}

@endif
@if($contactMessage->email)
**البريد:** {{ $contactMessage->email }}

@endif
@if($contactMessage->subject)
**الموضوع:** {{ $contactMessage->subject }}

@endif
<x-mail::panel>
{{ $contactMessage->message }}
</x-mail::panel>

<x-mail::button :url="$url">
افتحوا الرسالة في لوحة الإدارة
</x-mail::button>

@if($contactMessage->email)
الردّ على هذا البريد يصل إلى {{ $contactMessage->name }} مباشرة.
@endif

فريق {{ config('app.name') }}
</x-mail::message>
