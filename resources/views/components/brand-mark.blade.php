{{-- علامة المنصّة: مربّع بنفسجي متدرّج بوجه مبتسم — مرسومة بـ SVG، بلا ملف صورة --}}
<svg {{ $attributes->merge(['class' => 'size-11']) }} viewBox="0 0 48 48" fill="none" role="img" aria-label="{{ \App\Support\Settings::get('site_name') }}">
    <defs>
        <linearGradient id="bahja-mark" x1="0" y1="0" x2="48" y2="48" gradientUnits="userSpaceOnUse">
            <stop stop-color="#a78bfa"/>
            <stop offset="0.55" stop-color="#8b5cf6"/>
            <stop offset="1" stop-color="#6d28d9"/>
        </linearGradient>
    </defs>

    <rect width="48" height="48" rx="15" fill="url(#bahja-mark)"/>

    {{-- شرارة صغيرة أعلى اليمين --}}
    <path d="M36.5 10.5c.4 1.6.9 2.1 2.5 2.5-1.6.4-2.1.9-2.5 2.5-.4-1.6-.9-2.1-2.5-2.5 1.6-.4 2.1-.9 2.5-2.5Z" fill="#fde68a"/>

    {{-- عينان وابتسامة --}}
    <circle cx="18" cy="21" r="2.6" fill="#fff"/>
    <circle cx="30" cy="21" r="2.6" fill="#fff"/>
    <path d="M16.5 28.5c1.9 3.2 4.5 4.8 7.5 4.8s5.6-1.6 7.5-4.8" stroke="#fff" stroke-width="3.2" stroke-linecap="round"/>
</svg>
