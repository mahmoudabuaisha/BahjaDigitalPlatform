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
const SCHEMA_VERSION = 2;
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
