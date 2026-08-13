import Alpine from 'alpinejs';

window.Alpine = Alpine;

/**
 * مخزن فلترة الروزنامة — فلترة محلية على DOM المعروض من السيرفر:
 * صفر طلبات شبكة، وتعمل بكامل قدرتها دون إنترنت.
 */
Alpine.data('eventCalendar', (initial = {}) => ({
    area: initial.area ?? '',
    center: initial.center ?? '',
    category: initial.category ?? '',
    day: initial.day ?? '',

    get hasFilters() {
        return this.area !== '' || this.center !== '' || this.category !== '' || this.day !== '';
    },

    matches(el) {
        const d = el.dataset;

        if (this.area && d.area !== this.area) return false;
        if (this.center && d.center !== this.center) return false;
        if (this.category && d.category !== this.category) return false;

        if (this.day === 'today' && d.date !== d.today) return false;
        if (this.day === 'tomorrow' && d.date !== d.tomorrow) return false;

        return true;
    },

    apply() {
        document.querySelectorAll('[data-event-card]').forEach((el) => {
            el.classList.toggle('hidden', ! this.matches(el));
        });

        // إخفاء أيام أصبحت فارغة بعد الفلترة
        document.querySelectorAll('[data-day-group]').forEach((group) => {
            const visible = group.querySelectorAll('[data-event-card]:not(.hidden)').length;
            group.classList.toggle('hidden', visible === 0);
        });

        const empty = document.getElementById('empty-results');
        if (empty) {
            const anyVisible = document.querySelectorAll('[data-event-card]:not(.hidden)').length > 0;
            empty.classList.toggle('hidden', anyVisible);
        }

        this.syncUrl();
    },

    resetFilters() {
        this.area = this.center = this.category = this.day = '';
        this.apply();
    },

    syncUrl() {
        const params = new URLSearchParams();
        if (this.area) params.set('area', this.area);
        if (this.center) params.set('center', this.center);
        if (this.category) params.set('cat', this.category);
        if (this.day) params.set('day', this.day);

        const query = params.toString();
        history.replaceState(null, '', query ? `?${query}` : location.pathname);
    },

    init() {
        this.$watch('area', () => { this.center = ''; this.apply(); });
        this.$watch('center', () => this.apply());
        this.$watch('category', () => this.apply());
        this.$watch('day', () => this.apply());

        if (this.hasFilters) {
            this.apply();
        }
    },
}));

Alpine.start();

/* مؤشر حالة الاتصال */
const updateOnlineStatus = () => {
    const banner = document.getElementById('offline-banner');
    if (banner) {
        banner.classList.toggle('hidden', navigator.onLine);
    }
};

window.addEventListener('online', updateOnlineStatus);
window.addEventListener('offline', updateOnlineStatus);
updateOnlineStatus();

/* عدّاد المشاهدات — sendBeacon كي لا يؤخر التصفح */
const trackEl = document.querySelector('[data-track-event]');
if (trackEl && navigator.sendBeacon) {
    navigator.sendBeacon(`/t/e/${trackEl.dataset.trackEvent}`);
}

/* تسجيل الـ Service Worker */
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {
            // الوضع المحلي بدون بناء أصول — تجاهل بصمت
        });
    });
}
