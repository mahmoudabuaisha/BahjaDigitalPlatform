/**
 * الواجهات التي تعمل والشبكة مقطوعة.
 *
 * كل ما تعرضه هاتان الشاشتان يُقرأ من نسخة الروزنامة المحفوظة داخل
 * الجهاز — لا طلب واحد للسيرفر. هذا هو الفرق بين موقع «يفتح» دون
 * إنترنت وموقع «يُفيد» دونه: العائلة تعرف الموعد والمكان وطريق الوصول
 * وهي في بيتها بلا شبكة، لا أن ترى رسالة اعتذار.
 */

import { hydrate, loadSnapshot, snapshotStatus } from './snapshot';

/** آخر نسخة محفوظة مفكوكة — تُحمَّل مرّة وتتشاركها الشاشتان */
async function readCalendar() {
    const feed = await loadSnapshot();

    return {
        events: hydrate(feed),
        status: snapshotStatus(feed),
    };
}

export function registerOfflineViews(Alpine) {
    /**
     * روزنامة الأوفلاين: كل ما في النسخة المحفوظة مجمّعاً بالأيام، مع
     * بحث وتصفية بالمحافظة. تُغني عن الشبكة لا تعتذر عن غيابها.
     */
    Alpine.data('offlineCalendar', () => ({
        events: [],
        query: '',
        area: '',
        loaded: false,
        generatedLabel: null,
        expired: false,

        async init() {
            const { events, status } = await readCalendar();
            const today = new Date().toISOString().slice(0, 10);

            this.events = events.filter((event) => event.date >= today);
            this.generatedLabel = status?.generatedLabel ?? null;
            this.expired = Boolean(status?.expired);
            this.loaded = true;
        },

        /** المحافظات الموجودة فعلاً في النسخة — لا نعرض خياراً فارغاً */
        get areas() {
            return [...new Set(this.events.map((event) => event.area).filter(Boolean))];
        },

        get matches() {
            const needle = this.query.trim();

            return this.events.filter((event) => {
                if (this.area && event.area !== this.area) {
                    return false;
                }

                return needle === '' || event.haystack.includes(needle);
            });
        },

        /** مجموعات يومية: العنوان يُكتب مرّة، والفعاليات تحته */
        get days() {
            const groups = new Map();

            this.matches.forEach((event) => {
                const day = groups.get(event.date)
                    ?? { date: event.date, label: event.dateLabel, events: [] };

                day.events.push(event);
                groups.set(event.date, day);
            });

            return [...groups.values()];
        },

        get isFiltered() {
            return this.query.trim() !== '' || this.area !== '';
        },

        clear() {
            this.query = '';
            this.area = '';
        },
    }));

    /**
     * صفحة فعالية دون إنترنت. الـ Service Worker يقدّم هذا الهيكل لأي
     * ‎/events/{id} تعذّر جلبها، فنقرأ الرقم من المسار نفسه — وهكذا يبقى
     * العنوان في شريط المتصفّح صحيحاً، وتعمل المشاركة وزر الرجوع كالمعتاد.
     */
    Alpine.data('offlineEvent', () => ({
        event: null,
        loaded: false,
        generatedLabel: null,
        expired: false,
        online: navigator.onLine,

        async init() {
            const id = Number(window.location.pathname.match(/\/events\/(\d+)/)?.[1] ?? 0);
            const { events, status } = await readCalendar();

            this.event = events.find((event) => event.id === id) ?? null;
            this.generatedLabel = status?.generatedLabel ?? null;
            this.expired = Boolean(status?.expired);
            this.loaded = true;

            // عادت الشبكة ونحن على النسخة المحفوظة: نجلب الصفحة الحقيقية
            window.addEventListener('online', () => {
                this.online = true;
                window.location.reload();
            });
        },

        get shareUrl() {
            return this.event?.publicId
                ? `${window.location.origin}/r/${this.event.publicId}`
                : window.location.href;
        },

        retry() {
            window.location.reload();
        },
    }));
}
