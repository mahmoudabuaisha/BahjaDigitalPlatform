import Alpine from 'alpinejs';
import './offline-queue';

window.Alpine = Alpine;

/** تنسيق التواريخ بالعربية مع أرقام لاتينية — كما في التصميم */
const arabicDate = new Intl.DateTimeFormat('ar-u-nu-latn', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
});

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
