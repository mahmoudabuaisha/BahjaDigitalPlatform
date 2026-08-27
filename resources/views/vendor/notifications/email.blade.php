<x-mail::message>
{{-- التحية --}}
@if (! empty($greeting))
# {{ $greeting }}
@else
@if ($level === 'error')
# عذراً!
@else
# مرحباً!
@endif
@endif

{{-- سطور المقدمة --}}
@foreach ($introLines as $line)
{{ $line }}

@endforeach

{{-- زر الإجراء --}}
@isset($actionText)
<?php
    $color = match ($level) {
        'success', 'error' => $level,
        default => 'primary',
    };
?>
<x-mail::button :url="$actionUrl" :color="$color">
{{ $actionText }}
</x-mail::button>
@endisset

{{-- سطور الخاتمة --}}
@foreach ($outroLines as $line)
{{ $line }}

@endforeach

{{-- التوقيع --}}
@if (! empty($salutation))
{{ $salutation }}
@else
مع أطيب التحيات،<br>
فريق {{ config('app.name') }}
@endif

{{-- رابط بديل تحت الرسالة --}}
@isset($actionText)
<x-slot:subcopy>
إذا تعذّر الضغط على زر «{{ $actionText }}»، انسخوا الرابط التالي والصقوه في المتصفح:
<span class="break-all" dir="ltr">[{{ $displayableActionUrl }}]({{ $actionUrl }})</span>
</x-slot:subcopy>
@endisset
</x-mail::message>
