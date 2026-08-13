{
    "name": "{{ \App\Support\Settings::get('site_name') }} — روزنامة فعاليات الأطفال",
    "short_name": "{{ \App\Support\Settings::get('site_name') }}",
    "description": "فعاليات الترفيه والدعم النفسي لأطفال غزة — تعمل دون إنترنت",
    "lang": "ar",
    "dir": "rtl",
    "start_url": "/?src=pwa",
    "scope": "/",
    "display": "standalone",
    "orientation": "portrait",
    "background_color": "#fff8ed",
    "theme_color": "#f06406",
    "icons": [
        {
            "src": "/icons/icon-192.png",
            "sizes": "192x192",
            "type": "image/png"
        },
        {
            "src": "/icons/icon-512.png",
            "sizes": "512x512",
            "type": "image/png"
        },
        {
            "src": "/icons/icon-512-maskable.png",
            "sizes": "512x512",
            "type": "image/png",
            "purpose": "maskable"
        }
    ]
}
