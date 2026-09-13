@php
    $name = \App\Support\Settings::get('site_name');
    // بصمة لكل أيقونة: تغييرها يغيّر العنوان فيجلبها النظام جديدة
    $icon = fn (string $path): string => \App\Support\AssetVersion::url($path);
@endphp
{
    "id": "/?src=pwa",
    "name": "{{ $name }} — روزنامة فعاليات الأطفال",
    "short_name": "{{ $name }}",
    "description": "فعاليات الترفيه والدعم النفسي لأطفال غزة — اعرفوا أقرب فعالية لمكانكم، وتصفّحوا الروزنامة دون إنترنت.",
    "lang": "ar",
    "dir": "rtl",
    "start_url": "/?src=pwa",
    "scope": "/",
    "display": "standalone",
    "display_override": ["standalone", "minimal-ui"],
    "orientation": "portrait",
    "background_color": "#ffffff",
    "theme_color": "#3b93e4",
    "categories": ["education", "lifestyle", "social"],
    "launch_handler": {
        "client_mode": ["navigate-existing", "auto"]
    },
    "icons": [
        {
            "src": "{{ $icon('icons/icon-192.png') }}",
            "sizes": "192x192",
            "type": "image/png",
            "purpose": "any"
        },
        {
            "src": "{{ $icon('icons/icon-512.png') }}",
            "sizes": "512x512",
            "type": "image/png",
            "purpose": "any"
        },
        {
            "src": "{{ $icon('icons/icon-192-maskable.png') }}",
            "sizes": "192x192",
            "type": "image/png",
            "purpose": "maskable"
        },
        {
            "src": "{{ $icon('icons/icon-512-maskable.png') }}",
            "sizes": "512x512",
            "type": "image/png",
            "purpose": "maskable"
        }
    ],
    "shortcuts": [
        {
            "name": "الفعاليات القادمة",
            "short_name": "الفعاليات",
            "description": "كل الفعاليات المعتمدة مرتّبة بالأقرب موعداً",
            "url": "/events?src=shortcut",
            "icons": [{ "src": "{{ $icon('icons/shortcut-events.png') }}", "sizes": "96x96", "type": "image/png" }]
        },
        {
            "name": "الأقرب إلى مكانكم",
            "short_name": "قربكم",
            "description": "الفعاليات مرتّبة بدقائق المشي من مكانكم",
            "url": "/events?sort=near&src=shortcut",
            "icons": [{ "src": "{{ $icon('icons/shortcut-nearby.png') }}", "sizes": "96x96", "type": "image/png" }]
        },
        {
            "name": "حسابي وحجوزات أطفالي",
            "short_name": "حسابي",
            "description": "أطفالكم وحجوزاتكم وإشعاراتكم",
            "url": "/account?src=shortcut",
            "icons": [{ "src": "{{ $icon('icons/shortcut-account.png') }}", "sizes": "96x96", "type": "image/png" }]
        }
    ],
    "screenshots": [
        {
            "src": "{{ $icon('icons/screenshot-mobile-home.png') }}",
            "sizes": "1080x1920",
            "type": "image/png",
            "form_factor": "narrow",
            "label": "الروزنامة: أقرب الفعاليات إلى مكانكم"
        },
        {
            "src": "{{ $icon('icons/screenshot-mobile-events.png') }}",
            "sizes": "1080x1920",
            "type": "image/png",
            "form_factor": "narrow",
            "label": "الفعاليات مرتّبة بدقائق المشي"
        },
        {
            "src": "{{ $icon('icons/screenshot-desktop.png') }}",
            "sizes": "1280x800",
            "type": "image/png",
            "form_factor": "wide",
            "label": "بَهْجَة على الحاسوب"
        }
    ],
    "prefer_related_applications": false
}
