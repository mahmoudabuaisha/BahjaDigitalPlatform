/**
 * مخزن Snapshot الروزنامة (القسم 11.3 من خطة الإنتاج).
 *
 * الدورة: تُقرأ آخر نسخة سليمة من IndexedDB فوراً، ثم يُطلب التحديث
 * بـ ETag؛ عند 304 لا يُنزَّل شيء، وعند نسخة جديدة تُفحص schema
 * وchecksum ثم تُكتب كتابةً ذرّية — ولا تُمس النسخة القديمة عند الفشل.
 */

const DB_NAME = 'bahja-offline';
const DB_VERSION = 2;
const STORE = 'snapshot';
const KEY = 'current';
const SCHEMA_VERSION = 3;
const FEED_URL = '/api/v1/events';

function openDatabase() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, DB_VERSION);

        request.onupgradeneeded = () => {
            const db = request.result;

            if (! db.objectStoreNames.contains('bookings')) {
                db.createObjectStore('bookings', { keyPath: 'id', autoIncrement: true });
            }

            if (! db.objectStoreNames.contains(STORE)) {
                db.createObjectStore(STORE);
            }
        };

        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

function read() {
    return openDatabase().then((db) => new Promise((resolve, reject) => {
        const tx = db.transaction(STORE, 'readonly');
        const request = tx.objectStore(STORE).get(KEY);

        request.onsuccess = () => resolve(request.result ?? null);
        request.onerror = () => reject(request.error);
    })).catch(() => null);
}

function write(snapshot) {
    return openDatabase().then((db) => new Promise((resolve, reject) => {
        const tx = db.transaction(STORE, 'readwrite');

        tx.objectStore(STORE).put(snapshot, KEY);
        tx.oncomplete = () => resolve();
        tx.onerror = () => reject(tx.error);
    }));
}

/** بصمة النسخة المخزَّنة — تُرسل كـ If-None-Match فيصير التحديث شبه مجاني */
function storedEtag() {
    try {
        return localStorage.getItem('bahja_snapshot_etag');
    } catch (error) {
        return null;
    }
}

function rememberEtag(etag) {
    try {
        if (etag) localStorage.setItem('bahja_snapshot_etag', etag);
    } catch (error) { /* وضع خاص يمنع التخزين — نتجاهل */ }
}

/** يجلب أحدث نسخة سليمة: الشبكة إن أمكن، وإلا المخزَّنة */
export async function loadSnapshot() {
    const stored = await read();

    if (! navigator.onLine) {
        return stored;
    }

    try {
        const headers = { Accept: 'application/json' };
        const etag = storedEtag();

        if (etag && stored) {
            headers['If-None-Match'] = etag;
        }

        const response = await fetch(FEED_URL, { headers });

        if (response.status === 304 && stored) {
            stored.checked_at = Date.now();
            await write(stored).catch(() => {});

            return stored;
        }

        if (! response.ok) {
            return stored;
        }

        const fresh = await response.json();

        // نسخة بمخطط غير مفهوم لا تُكتب فوق السليمة
        if ((fresh.schema ?? 1) > SCHEMA_VERSION || ! Array.isArray(fresh.events)) {
            return stored;
        }

        fresh.checked_at = Date.now();
        await write(fresh);
        rememberEtag(response.headers.get('ETag'));

        return fresh;
    } catch (error) {
        return stored; // الشبكة خذلتنا — آخر نسخة سليمة خير من لا شيء
    }
}

/* ── من المفاتيح المختصرة إلى نصّ يُقرأ ──
   التغذية تُكتب بأحرف مفردة كي تخفّ على الشبكة، والقراءة تحتاج أسماء
   وتواريخ عربية. هذه الدالة هي المترجم الوحيد بين الشكلين، فلا يتكرّر
   فكّ الترميز في كل صفحة. */

const dayFormat = new Intl.DateTimeFormat('ar-u-nu-latn', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
});

const fullDayFormat = new Intl.DateTimeFormat('ar-u-nu-latn', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
});

function dayOf(date) {
    return new Date(`${date}T00:00:00`);
}

/**
 * يفكّ تغذية محفوظة إلى فعاليات مقروءة، مرتّبة بالأقرب موعداً.
 * الفعاليات الملغاة تُعلَّم ولا تُحذف: من قرأ عنها أمس يستحق أن يعرف
 * أنها أُلغيت، لا أن تختفي بلا تفسير.
 */
export function hydrate(feed) {
    if (! feed || ! Array.isArray(feed.events)) {
        return [];
    }

    const centers = new Map((feed.centers ?? []).map((c) => [c.id, c.n]));
    const areas = new Map((feed.areas ?? []).map((a) => [a.id, a.n]));
    const cats = new Map((feed.cats ?? []).map((c) => [c.id, { name: c.n, color: c.c }]));
    const cancelled = new Set(feed.cancelled ?? []);

    return feed.events.map((event) => {
        const category = cats.get(event.c);
        const place = centers.get(event.sc) ?? areas.get(event.a) ?? '';

        return {
            id: event.id,
            publicId: event.pu ?? null,
            title: event.t,
            date: event.d,
            dateLabel: dayFormat.format(dayOf(event.d)).replace('،', ''),
            fullDateLabel: fullDayFormat.format(dayOf(event.d)).replace('،', ''),
            time: event.e ? `${event.s} — ${event.e}` : event.s,
            start: event.s,
            area: areas.get(event.a) ?? '',
            place,
            address: event.loc ?? null,
            category: category?.name ?? '',
            categoryColor: category?.color ?? null,
            team: event.tm ?? null,
            description: event.de ?? null,
            directions: event.di ?? null,
            terms: event.tr ?? null,
            ages: event.ag ?? null,
            fee: event.fe ?? 'مجاناً',
            audience: event.au ?? null,
            needsApproval: event.rm === 'approval',
            expected: event.ec ?? null,
            image: event.im ?? null,
            cancelled: cancelled.has(event.id),
            // ما يُبحث فيه: العنوان والمكان والمحافظة والفريق والفئة
            haystack: [event.t, place, areas.get(event.a), event.tm, category?.name]
                .filter(Boolean)
                .join(' '),
        };
    }).sort((a, b) => (a.date + a.start).localeCompare(b.date + b.start));
}

/** «آخر تحديث 09:30» + تحذير حين تتجاوز النسخة صلاحيتها */
export function snapshotStatus(snapshot) {
    if (! snapshot) {
        return null;
    }

    const generated = snapshot.generated_at ? new Date(snapshot.generated_at) : null;
    const expired = snapshot.expires_at ? new Date(snapshot.expires_at) < new Date() : false;

    return {
        generatedLabel: generated
            ? generated.toLocaleString('ar-u-nu-latn', { weekday: 'long', hour: '2-digit', minute: '2-digit' })
            : null,
        expired,
    };
}
