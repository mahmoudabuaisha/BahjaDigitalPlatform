@php
    $ogTitle = $ogTitle ?? \App\Support\Settings::get('site_name').' — روزنامة فعاليات أطفال غزة';
    $ogDescription = $ogDescription ?? 'اعرفوا مكان وموعد أقرب فعالية ترفيه ودعم نفسي لأطفالكم — تصفح يعمل حتى دون إنترنت.';
    $ogImagePath = $ogImage ?? \App\Support\Settings::get('og_default_image');
    $ogImageUrl = $ogImagePath
        ? (str_starts_with($ogImagePath, 'http') ? $ogImagePath : \Illuminate\Support\Facades\Storage::disk('public')->url($ogImagePath))
        : asset('images/og-default.png');
@endphp
{{-- واتساب يقرأ هذه الوسوم من الـ HTML المُصيَّر في السيرفر --}}
<meta property="og:site_name" content="{{ \App\Support\Settings::get('site_name') }}">
<meta property="og:type" content="{{ $ogType ?? 'website' }}">
<meta property="og:title" content="{{ $ogTitle }}">
<meta property="og:description" content="{{ $ogDescription }}">
<meta property="og:image" content="{{ $ogImageUrl }}">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:locale" content="ar_AR">
<meta name="twitter:card" content="summary_large_image">
