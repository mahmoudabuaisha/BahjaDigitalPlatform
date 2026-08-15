// Service Worker منصّة بَهْجَة — تصفح الروزنامة دون إنترنت
// الإصدار يُشتق من بصمة Vite manifest: كل بناء جديد = تحديث تلقائي للكاش
const VERSION = '{{ $version }}';

const PAGES_CACHE = 'pages-' + VERSION;
const DATA_CACHE = 'data-' + VERSION;
const STATIC_CACHE = 'static-' + VERSION;

const OFFLINE_URL = '/offline';
const FEED_URL = '/api/v1/events';
const NETWORK_TIMEOUT_MS = 3500;
const MAX_UPLOAD_ENTRIES = 60;

// قائمة التخزين المسبق: هيكل التطبيق + الأصول المبنية (تُحقن من السيرفر)
const PRECACHE = [
    '/',
    '/events',
    '/organizers',
    '/contact',
    '/guide',
    '/feedback',
    OFFLINE_URL,
    '/manifest.webmanifest',
    '/fonts/tajawal-arabic-400-normal.woff2',
    '/fonts/tajawal-arabic-500-normal.woff2',
    '/fonts/tajawal-arabic-700-normal.woff2',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
    '/images/og-default.png',
    '/images/team-placeholder.svg',
@foreach($buildAssets as $asset)
    '{{ $asset }}',
@endforeach
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => cache.addAll(PRECACHE))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(
                keys.filter((key) => ! key.endsWith(VERSION)).map((key) => caches.delete(key))
            ))
            .then(() => self.clients.claim())
    );
});

/** جلب مع مهلة — شبكات غزة قد "تعلق" طويلاً، لا ننتظر أكثر من 3.5 ثانية */
function fetchWithTimeout(request, timeoutMs) {
    return new Promise((resolve, reject) => {
        const timer = setTimeout(() => reject(new Error('timeout')), timeoutMs);

        fetch(request).then(
            (response) => { clearTimeout(timer); resolve(response); },
            (error) => { clearTimeout(timer); reject(error); }
        );
    });
}

/** التنقلات: الشبكة أولاً بمهلة ← الكاش ← صفحة "دون اتصال" */
async function handleNavigation(request) {
    const cache = await caches.open(PAGES_CACHE);

    try {
        const response = await fetchWithTimeout(request, NETWORK_TIMEOUT_MS);

        if (response && response.ok) {
            cache.put(request, response.clone());
        }

        return response;
    } catch (error) {
        const cached = await cache.match(request, { ignoreSearch: false })
            || await cache.match(new URL(request.url).pathname);

        if (cached) {
            return cached;
        }

        const staticCache = await caches.open(STATIC_CACHE);
        const precachedPage = await staticCache.match(new URL(request.url).pathname);

        if (precachedPage) {
            return precachedPage;
        }

        return (await staticCache.match(OFFLINE_URL)) || Response.error();
    }
}

/** تغذية الفعاليات: نعرض المخزَّن فوراً ونحدّث بالخلفية (stale-while-revalidate) */
async function handleFeed(request) {
    const cache = await caches.open(DATA_CACHE);
    const cached = await cache.match(request);

    const refresh = fetch(request)
        .then((response) => {
            if (response && response.ok) {
                cache.put(request, response.clone());
            }

            return response;
        })
        .catch(() => null);

    return cached || (await refresh) || new Response('{"events":[]}', {
        headers: { 'Content-Type': 'application/json' },
    });
}

/** الأصول الثابتة: الكاش أولاً، مع حد أقصى لصور الفعاليات المرفوعة */
async function handleStatic(request) {
    const cache = await caches.open(STATIC_CACHE);
    const cached = await cache.match(request);

    if (cached) {
        return cached;
    }

    try {
        const response = await fetch(request);

        if (response && response.ok) {
            cache.put(request, response.clone());

            if (new URL(request.url).pathname.startsWith('/uploads/')) {
                trimUploads(cache);
            }
        }

        return response;
    } catch (error) {
        return Response.error();
    }
}

async function trimUploads(cache) {
    const keys = await cache.keys();
    const uploads = keys.filter((key) => new URL(key.url).pathname.startsWith('/uploads/'));

    if (uploads.length > MAX_UPLOAD_ENTRIES) {
        await Promise.all(
            uploads.slice(0, uploads.length - MAX_UPLOAD_ENTRIES).map((key) => cache.delete(key))
        );
    }
}

self.addEventListener('fetch', (event) => {
    const request = event.request;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    if (url.origin !== self.location.origin) {
        return;
    }

    // لوحات الإدارة والفرق و Livewire خارج نطاق الـ SW
    if (
        url.pathname.startsWith('/admin')
        || url.pathname.startsWith('/team')
        || url.pathname.startsWith('/livewire')
    ) {
        return;
    }

    if (url.pathname === FEED_URL) {
        event.respondWith(handleFeed(request));

        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(handleNavigation(request));

        return;
    }

    if (
        url.pathname.startsWith('/build/')
        || url.pathname.startsWith('/fonts/')
        || url.pathname.startsWith('/icons/')
        || url.pathname.startsWith('/images/')
        || url.pathname.startsWith('/uploads/')
    ) {
        event.respondWith(handleStatic(request));
    }
});
