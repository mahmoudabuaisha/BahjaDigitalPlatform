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

// صفحات تخصّ شخصاً بعينه: لا تُخزَّن أبداً. الهاتف في غزة يتشاركه أكثر
// من فرد، وصفحة حساب محفوظة قد تُعرض لمن سجّل دخوله بعده.
const PRIVATE_PATHS = ['/account', '/organizer', '/notifications'];

function isPrivate(pathname) {
    return PRIVATE_PATHS.some((prefix) => pathname === prefix || pathname.startsWith(prefix + '/'));
}

// قائمة التخزين المسبق: هيكل التطبيق + الأصول المبنية (تُحقن من السيرفر)
const PRECACHE = [
    '/',
    '/events',
    '/organizers',
    '/contact',
    '/guide',
    '/feedback',
    '/app',
    OFFLINE_URL,
    '/manifest.webmanifest',
    '/fonts/tajawal-arabic-400-normal.woff2',
    '/fonts/tajawal-arabic-500-normal.woff2',
    '/fonts/tajawal-arabic-700-normal.woff2',
    '/brand/logo.png',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
    '/images/og-default.png',
    '/images/team-placeholder.svg',
@foreach($buildAssets as $asset)
    '{{ $asset }}',
@endforeach
];

/**
 * تخزين مسبق متسامح: cache.addAll تسقط كلها إن سقط ملف واحد، فيفشل
 * تثبيت الـ Service Worker ويبقى الموقع بلا أوفلاين. نخزّن ملفاً ملفاً
 * ونمضي — ما نقص اليوم يُجلب عند أول طلب له.
 */
async function precache() {
    const cache = await caches.open(STATIC_CACHE);

    await Promise.all(PRECACHE.map((url) => cache.add(new Request(url, { cache: 'reload' }))
        .catch(() => null)));
}

self.addEventListener('install', (event) => {
    // بلا skipWaiting: النسخة الجديدة تنتظر إذن المستخدم في الصفحة
    event.waitUntil(precache());
});

self.addEventListener('activate', (event) => {
    event.waitUntil((async () => {
        // التحميل المسبق للتنقّل: المتصفّح يبدأ الطلب قبل أن نستيقظ
        if (self.registration.navigationPreload) {
            await self.registration.navigationPreload.enable().catch(() => {});
        }

        const keys = await caches.keys();

        await Promise.all(
            keys.filter((key) => ! key.endsWith(VERSION)).map((key) => caches.delete(key))
        );

        await self.clients.claim();
    })());
});

// الصفحة وحدها تقرّر متى تحلّ النسخة الجديدة محلّ القديمة
self.addEventListener('message', (event) => {
    if (event.data?.type === 'skip-waiting') {
        self.skipWaiting();
    }
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
async function handleNavigation(request, preloadResponse) {
    const cache = await caches.open(PAGES_CACHE);
    const cacheable = ! isPrivate(new URL(request.url).pathname);

    try {
        const response = (await preloadResponse) || await fetchWithTimeout(request, NETWORK_TIMEOUT_MS);

        if (response && response.ok && cacheable) {
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

/* ── طابور الحجز دون اتصال ──
   الصفحة تحفظ الطلب في IndexedDB، وهذا الحدث يُرسله في الخلفية فور
   عودة الشبكة حتى لو أغلق وليّ الأمر الموقع (كروم/أندرويد).
   المتصفّحات التي لا تدعم Background Sync تُرسله الصفحة عند فتحها. */
const QUEUE_DB = 'bahja-offline';
const QUEUE_STORE = 'bookings';
const SYNC_TAG = 'bahja-bookings';

function queueDatabase() {
    return new Promise((resolve, reject) => {
        // بلا رقم إصدار: يفتح الإصدار الحالي كائناً ما كان
        const request = indexedDB.open(QUEUE_DB);

        request.onupgradeneeded = () => {
            if (! request.result.objectStoreNames.contains(QUEUE_STORE)) {
                request.result.createObjectStore(QUEUE_STORE, { keyPath: 'id', autoIncrement: true });
            }
        };

        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

function queueTransact(mode, run) {
    return queueDatabase().then((db) => new Promise((resolve, reject) => {
        const tx = db.transaction(QUEUE_STORE, mode);
        const request = run(tx.objectStore(QUEUE_STORE));

        tx.oncomplete = () => resolve(request?.result);
        tx.onerror = () => reject(tx.error);
    }));
}

async function flushQueue() {
    let pending = [];

    try {
        pending = await queueTransact('readonly', (store) => store.getAll());
    } catch (error) {
        return; // المخزن لم يُنشأ بعد
    }

    let sent = 0;

    for (const item of pending) {
        const body = new FormData();

        body.append('_token', item.token ?? '');
        body.append('note', item.note ?? '');
        (item.children ?? []).forEach((child) => body.append('children[]', child));

        let response;

        try {
            response = await fetch(item.url, {
                method: 'POST',
                body,
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
        } catch (error) {
            throw error; // الشبكة ما زالت مقطوعة: يُعيد المتصفح المحاولة لاحقاً
        }

        // جلسة منتهية: نُبقي الطلب حتى يسجّل وليّ الأمر دخوله من الصفحة
        if (response.status === 419 || response.status === 401) {
            continue;
        }

        await queueTransact('readwrite', (store) => store.delete(item.id));

        if (response.ok) {
            sent++;
        }
    }

    if (sent > 0) {
        const clients = await self.clients.matchAll({ includeUncontrolled: true });

        clients.forEach((client) => client.postMessage({
            type: 'bookings-synced',
            message: sent === 1 ? 'أُرسل حجزكم المحفوظ.' : 'أُرسلت ' + sent + ' من حجوزاتكم المحفوظة.',
        }));
    }
}

self.addEventListener('sync', (event) => {
    if (event.tag === SYNC_TAG) {
        event.waitUntil(flushQueue());
    }
});

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
        event.respondWith(handleNavigation(request, event.preloadResponse));

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
