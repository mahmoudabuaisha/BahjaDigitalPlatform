import Alpine from 'alpinejs';

window.Alpine = Alpine;

/** تنسيق التواريخ بالعربية مع أرقام لاتينية — كما في التصميم */
const arabicDate = new Intl.DateTimeFormat('ar-u-nu-latn', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
});

/**
 * مخزن تصفية الروزنامة — تصفية محلية على DOM المعروض من السيرفر:
 * صفر طلبات شبكة، وتعمل بكامل قدرتها دون إنترنت.
 */
Alpine.data('eventCalendar', (initial = {}) => ({
    area: initial.area ?? '',
    center: initial.center ?? '',
    category: initial.category ?? '',
    day: initial.day || 'today',
    visibleCount: 0,

    get hasFilters() {
        return this.area !== '' || this.center !== '' || this.category !== '' || this.day !== 'all';
    },

    get listHeading() {
        if (this.day === 'today') return 'فعاليات اليوم';
        if (this.day === 'tomorrow') return 'فعاليات الغد';

        return 'الأيام الأربعة عشر القادمة';
    },

    get listCount() {
        if (this.visibleCount === 0) return 'لا فعاليات';
        if (this.visibleCount === 1) return 'فعالية واحدة';
        if (this.visibleCount === 2) return 'فعاليتان';
        if (this.visibleCount <= 10) return `${this.visibleCount} فعاليات`;

        return `${this.visibleCount} فعالية`;
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
        let visible = 0;

        document.querySelectorAll('[data-event-card]').forEach((el) => {
            const shown = this.matches(el);

            el.classList.toggle('hidden', ! shown);

            if (shown) visible++;
        });

        this.visibleCount = visible;

        // إخفاء أيام أصبحت فارغة بعد التصفية
        document.querySelectorAll('[data-day-group]').forEach((group) => {
            const shown = group.querySelectorAll('[data-event-card]:not(.hidden)').length;
            group.classList.toggle('hidden', shown === 0);
        });

        const empty = document.getElementById('empty-results');
        if (empty) {
            empty.classList.toggle('hidden', visible > 0);
        }

        this.syncUrl();
    },

    resetFilters() {
        this.area = this.center = this.category = '';
        this.day = 'all';
        this.apply();
    },

    syncUrl() {
        const params = new URLSearchParams();
        if (this.area) params.set('area', this.area);
        if (this.center) params.set('center', this.center);
        if (this.category) params.set('cat', this.category);
        if (this.day !== 'today') params.set('day', this.day);

        const query = params.toString();
        history.replaceState(null, '', query ? `?${query}` : location.pathname);
    },

    init() {
        this.$watch('area', () => { this.center = ''; this.apply(); });
        this.$watch('center', () => this.apply());
        this.$watch('category', () => this.apply());
        this.$watch('day', () => this.apply());

        this.apply();
    },
}));

/**
 * دعوة تثبيت التطبيق — لا تظهر إلا إذا عرضها المتصفح فعلاً،
 * والرفض يُحفظ في الجهاز كي لا نزعج الأهالي مرة أخرى.
 */
Alpine.data('installPrompt', () => ({
    available: false,
    deferred: null,

    init() {
        if (localStorage.getItem('bahja_install_dismissed') === '1') {
            return;
        }

        window.addEventListener('beforeinstallprompt', (event) => {
            event.preventDefault();
            this.deferred = event;
            this.available = true;
        });

        window.addEventListener('appinstalled', () => {
            this.available = false;
            localStorage.setItem('bahja_install_dismissed', '1');
        });
    },

    async install() {
        if (! this.deferred) return;

        this.available = false;
        this.deferred.prompt();
        await this.deferred.userChoice;
        this.deferred = null;
    },

    dismiss() {
        this.available = false;
        localStorage.setItem('bahja_install_dismissed', '1');
    },
}));

/**
 * الفعاليات المحفوظة على الجهاز — تُقرأ من تغذية الـ Service Worker،
 * فتعمل صفحة "دون اتصال" على ما خُزّن في آخر زيارة.
 */
Alpine.data('savedEvents', () => ({
    events: [],
    loaded: false,

    async init() {
        try {
            const response = await fetch('/api/v1/events');
            const feed = await response.json();

            const centers = new Map((feed.centers ?? []).map((c) => [c.id, c.n]));
            const areas = new Map((feed.areas ?? []).map((a) => [a.id, a.n]));
            const cats = new Map((feed.cats ?? []).map((c) => [c.id, c.n]));
            const today = new Date().toISOString().slice(0, 10);

            this.events = (feed.events ?? [])
                .filter((event) => event.d >= today)
                .slice(0, 12)
                .map((event) => ({
                    id: event.id,
                    title: event.t,
                    time: event.s,
                    category: cats.get(event.c) ?? '',
                    place: [centers.get(event.sc) ?? areas.get(event.a) ?? '', event.loc]
                        .filter(Boolean)
                        .join(' — '),
                    // بلا فاصلة بين اليوم والتاريخ — كما تُطبع في بقية الصفحات
                    dateLabel: arabicDate.format(new Date(`${event.d}T00:00:00`)).replace('،', ''),
                }));
        } catch (error) {
            this.events = [];
        }

        this.loaded = true;
    },
}));

Alpine.start();

/* شريط حالة الشبكة — الانقطاع دائم الظهور، والعودة تومض ثم تختفي */
const offlineBanner = document.getElementById('offline-banner');
const onlineBanner = document.getElementById('online-banner');
let wasOffline = ! navigator.onLine;

const updateOnlineStatus = () => {
    if (! offlineBanner) return;

    offlineBanner.classList.toggle('hidden', navigator.onLine);

    if (navigator.onLine && wasOffline && onlineBanner) {
        onlineBanner.classList.remove('hidden');
        setTimeout(() => onlineBanner.classList.add('hidden'), 4000);
    }

    wasOffline = ! navigator.onLine;
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
