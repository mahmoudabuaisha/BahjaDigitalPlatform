/**
 * طابور الحجز دون اتصال.
 *
 * شبكة غزة تنقطع في منتصف الطلب، فلا يُفقد حجز وليّ الأمر: يُحفظ في
 * IndexedDB داخل الجهاز، ويُرسل تلقائياً فور عودة الإنترنت — أو عند
 * فتح الموقع مرّة أخرى إن كان المتصفّح لا يدعم المزامنة في الخلفية.
 *
 * القاعدة نفسها يقرأها الـ Service Worker، فلا يُعرَّف اسمها إلا هنا.
 */

const DB_NAME = 'bahja-offline';
const DB_VERSION = 1;
const STORE = 'bookings';
const SYNC_TAG = 'bahja-bookings';

function openDatabase() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, DB_VERSION);

        request.onupgradeneeded = () => {
            if (! request.result.objectStoreNames.contains(STORE)) {
                request.result.createObjectStore(STORE, { keyPath: 'id', autoIncrement: true });
            }
        };

        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

function transact(store, mode, run) {
    return openDatabase().then((db) => new Promise((resolve, reject) => {
        const tx = db.transaction(store, mode);
        const request = run(tx.objectStore(store));

        tx.oncomplete = () => resolve(request?.result);
        tx.onerror = () => reject(tx.error);
    }));
}

export const queue = {
    add: (item) => transact(STORE, 'readwrite', (store) => store.add(item)),
    all: () => transact(STORE, 'readonly', (store) => store.getAll()),
    remove: (id) => transact(STORE, 'readwrite', (store) => store.delete(id)),
};

/** رسالة عائمة أسفل الشاشة — لا نعتمد على alert كي لا تُقاطع الأهل */
function toast(message, tone = 'brand') {
    const box = document.createElement('div');

    box.className = [
        'fixed bottom-4 inset-x-4 z-50 mx-auto max-w-md rounded-2xl px-4 py-3 text-center',
        'font-medium shadow-lg transition',
        tone === 'error' ? 'bg-rose-600 text-white' : 'bg-brand-700 text-white',
    ].join(' ');
    box.setAttribute('role', 'status');
    box.textContent = message;

    document.body.appendChild(box);

    setTimeout(() => {
        box.style.opacity = '0';
        setTimeout(() => box.remove(), 400);
    }, 5000);
}

/** شارة «حجوزات بانتظار الإرسال» في أعلى صفحة الفعالية */
async function refreshBadge() {
    const badge = document.querySelector('[data-queued-bookings]');

    if (! badge) return;

    const pending = await queue.all().catch(() => []);

    badge.classList.toggle('hidden', pending.length === 0);

    const count = badge.querySelector('[data-queued-count]');

    if (count) {
        count.textContent = pending.length;
    }
}

function currentToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

async function send(item) {
    const body = new FormData();

    body.append('_token', currentToken() || item.token);
    body.append('note', item.note ?? '');
    item.children.forEach((child) => body.append('children[]', child));

    return fetch(item.url, {
        method: 'POST',
        body,
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
    });
}

/** يُنادى عند الإقلاع وعند عودة الشبكة */
export async function replayQueue() {
    if (! navigator.onLine) return;

    const pending = await queue.all().catch(() => []);

    for (const item of pending) {
        let response;

        try {
            response = await send(item);
        } catch (error) {
            return; // الشبكة ما زالت مقطوعة — نُبقي الطلب لمحاولة لاحقة
        }

        // 419 جلسة منتهية أو 401: يحتاج وليّ الأمر تسجيل دخول، نُبقي الطلب
        if (response.status === 419 || response.status === 401) {
            toast('انتهت الجلسة — سجّلوا الدخول ليُرسل الحجز المحفوظ.', 'error');

            return;
        }

        await queue.remove(item.id);

        const payload = await response.json().catch(() => ({}));

        toast(payload.message ?? (response.ok ? 'أُرسل حجزكم.' : 'تعذّر إتمام الحجز.'), response.ok ? 'brand' : 'error');
    }

    refreshBadge();
}

/** اعتراض نموذج الحجز: التسليم عبر fetch، والحفظ عند فشل الشبكة */
function bindBookingForm() {
    const form = document.querySelector('[data-booking-form]');

    if (! form) return;

    form.addEventListener('submit', async (event) => {
        const children = [...form.querySelectorAll('input[name="children[]"]:checked')].map((input) => input.value);

        if (children.length === 0) {
            return; // يتكفّل السيرفر برسالة "اختاروا طفلاً"
        }

        // على اتصال: نترك النموذج يعمل كما هو — تجربة الأهل المعتادة
        if (navigator.onLine) {
            return;
        }

        event.preventDefault();

        await queue.add({
            url: form.action,
            token: currentToken(),
            children,
            note: form.querySelector('input[name="note"]')?.value ?? '',
            createdAt: Date.now(),
        });

        form.reset();
        refreshBadge();
        toast('حُفظ طلب الحجز على جهازكم، وسيُرسل فور عودة الإنترنت.');

        // المزامنة في الخلفية حيث تتوفّر (كروم/أندرويد)، وإلا فعند فتح الموقع
        const registration = await navigator.serviceWorker?.ready?.catch(() => null);

        registration?.sync?.register(SYNC_TAG).catch(() => {});
    });
}

document.addEventListener('DOMContentLoaded', () => {
    bindBookingForm();
    refreshBadge();
    replayQueue();
});

window.addEventListener('online', () => replayQueue());

// الـ Service Worker ينجح أحياناً في الإرسال قبل الصفحة — يُبلغها لتحدّث الشارة
navigator.serviceWorker?.addEventListener('message', (event) => {
    if (event.data?.type === 'bookings-synced') {
        refreshBadge();
        toast(event.data.message ?? 'أُرسلت حجوزاتكم المحفوظة.');
    }
});
