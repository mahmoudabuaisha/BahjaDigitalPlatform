{{-- علامة المنصّة بروح الشعار: وجه أزرق مبتسم بتاج صغير وخدود وردية — SVG بلا ملف صورة --}}
<svg {{ $attributes->merge(['class' => 'size-11']) }} viewBox="0 0 48 48" fill="none" role="img" aria-label="{{ \App\Support\Settings::get('site_name') }}">
    <defs>
        <linearGradient id="bahja-mark" x1="0" y1="0" x2="48" y2="48" gradientUnits="userSpaceOnUse">
            <stop stop-color="#8ec8f2"/>
            <stop offset="0.55" stop-color="#4d9fe8"/>
            <stop offset="1" stop-color="#1f62a7"/>
        </linearGradient>
    </defs>

    <rect width="48" height="48" rx="15" fill="url(#bahja-mark)"/>

    {{-- تاج صغير بجواهر وردية وزرقاء --}}
    <path d="M15 13.5l3.4 2.8 5.6-4.3 5.6 4.3 3.4-2.8-1.3 6H16.3l-1.3-6Z" fill="#fde68a"/>
    <circle cx="18.4" cy="15.6" r="1.15" fill="#f472b6"/>
    <circle cx="24" cy="13.2" r="1.15" fill="#7dd3fc"/>
    <circle cx="29.6" cy="15.6" r="1.15" fill="#f472b6"/>

    {{-- عينان وخدّان وابتسامة --}}
    <circle cx="18" cy="27" r="2.6" fill="#fff"/>
    <circle cx="30" cy="27" r="2.6" fill="#fff"/>
    <circle cx="13.4" cy="32" r="2" fill="#f9a8d4" opacity=".85"/>
    <circle cx="34.6" cy="32" r="2" fill="#f9a8d4" opacity=".85"/>
    <path d="M17 33.5c1.9 3 4.2 4.5 7 4.5s5.1-1.5 7-4.5" stroke="#fff" stroke-width="3.2" stroke-linecap="round"/>
</svg>
