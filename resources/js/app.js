import Alpine from 'alpinejs';
import './offline-queue';
import { loadSnapshot, snapshotStatus } from './snapshot';
import { registerOfflineViews } from './offline-views';
import { registerPwa, syncAppBadge, watchForUpdates } from './pwa';
import { registerPush } from './push';

window.Alpine = Alpine;

// تثبيت التطبيق وتحديثه وإشعاراته وشاشاته دون إنترنت — في وحدات مستقلة لطولها
registerPwa(Alpine);
registerPush(Alpine);
registerOfflineViews(Alpine);

/**
 * عدّاد متصاعد لأرقام الإحصاءات: يبدأ حين تدخل البطاقة مجال الرؤية،
 * يتسارع ثم يتباطأ قرب الهدف (easeOutCubic)، مع تأخير متدرّج بين
 * البطاقات ونبضة صغيرة عند الوصول. «تقليل الحركة» يعرض الرقم فوراً.
 */
Alpine.data('countUp', (target, delay = 0, duration = 1600) => ({
    shown: 0,
    done: false,

    init() {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || target === 0) {
            this.shown = target;
            this.done = true;

            return;
        }

        const observer = new IntersectionObserver((entries) => {
            if (entries.some((entry) => entry.isIntersecting)) {
                observer.disconnect();
                setTimeout(() => this.animate(), delay);
            }
        }, { threshold: 0.4 });

        observer.observe(this.$el);
    },

    animate() {
        const start = performance.now();
        const easeOutCubic = (progress) => 1 - Math.pow(1 - progress, 3);

        const tick = (now) => {
            const progress = Math.min(1, (now - start) / duration);
            this.shown = Math.round(target * easeOutCubic(progress));

            if (progress < 1) {
                requestAnimationFrame(tick);
            } else {
                this.done = true;
            }
        };

        requestAnimationFrame(tick);
    },

    get display() {
        return this.shown.toLocaleString('en-US');
    },
}));

Alpine.start();

/**
 * حركة الموقع: ظهور تدريجي للأقسام والبطاقات عند دخولها مجال الرؤية،
 * وإزاحة أبطأ لشحطات الفرشاة مع التمرير فتبدو جدارية خلف الصفحة.
 * «تقليل الحركة» يعطّل كل شيء، ومن دون جافاسكربت تبقى الصفحة كاملة الظهور.
 */
if (! window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    const setupMotion = () => {
        const heroSection = document.querySelector('main section .hero-enter')?.closest('section');

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('revealed');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.08, rootMargin: '0px 0px -6% 0px' });

        document.querySelectorAll('main section, body > footer').forEach((target) => {
            // البطل له افتتاحيته الخاصة، وأقسام النماذج تبقى ظاهرة دائماً
            // كي لا يبدو حقل مخفياً على من يملأ استمارة طويلة
            if (target === heroSection || target.closest('form')) {
                return;
            }

            target.classList.add('reveal');

            target.querySelectorAll(':scope .grid > *').forEach((item, index) => {
                item.classList.add('reveal-item');
                item.style.setProperty('--stagger', String(Math.min(index * 70, 490)));
            });

            observer.observe(target);
        });

        const strokes = [...document.querySelectorAll('[data-parallax]')];

        if (strokes.length > 0) {
            let ticking = false;

            const drift = () => {
                strokes.forEach((stroke) => {
                    stroke.style.translate = `0 ${window.scrollY * Number(stroke.dataset.parallax)}px`;
                });
                ticking = false;
            };

            window.addEventListener('scroll', () => {
                if (! ticking) {
                    ticking = true;
                    requestAnimationFrame(drift);
                }
            }, { passive: true });
        }
    };

    document.readyState === 'loading'
        ? document.addEventListener('DOMContentLoaded', setupMotion)
        : setupMotion();
}

/* شريط حالة الشبكة — الانقطاع دائم الظهور، والعودة تومض ثم تختفي.
   عند الأوفلاين يُذكر وقت آخر تحديث، ويُحذَّر بقوة إن تجاوزت النسخة صلاحيتها. */
const offlineBanner = document.getElementById('offline-banner');
const onlineBanner = document.getElementById('online-banner');
let wasOffline = ! navigator.onLine;

const updateOnlineStatus = async () => {
    if (! offlineBanner) return;

    offlineBanner.classList.toggle('hidden', navigator.onLine);

    if (! navigator.onLine) {
        const status = snapshotStatus(await loadSnapshot());

        offlineBanner.textContent = status?.expired
            ? 'تنبيه: أنتم دون اتصال والبيانات المحفوظة قديمة — قد تكون المواعيد تغيّرت'
            : 'أنتم الآن دون اتصال — تُعرض آخر البيانات المحفوظة'
              + (status?.generatedLabel ? ' (آخر تحديث: ' + status.generatedLabel + ')' : '');

        offlineBanner.classList.toggle('bg-rose-700', Boolean(status?.expired));
    }

    if (navigator.onLine && wasOffline && onlineBanner) {
        loadSnapshot(); // العودة للاتصال: تُجلب النسخة الأحدث فوراً
        onlineBanner.classList.remove('hidden');
        setTimeout(() => onlineBanner.classList.add('hidden'), 4000);
    }

    wasOffline = ! navigator.onLine;
};

window.addEventListener('online', updateOnlineStatus);
window.addEventListener('offline', updateOnlineStatus);
updateOnlineStatus();

// تحميل النسخة مرة عند كل زيارة متصلة — كي تجد العائلةُ الروزنامةَ حاضرةً حين تنقطع الشبكة
if (navigator.onLine) {
    window.addEventListener('load', () => loadSnapshot());
}

/* عدّاد المشاهدات — sendBeacon كي لا يؤخر التصفح */
const trackEl = document.querySelector('[data-track-event]');
if (trackEl && navigator.sendBeacon) {
    navigator.sendBeacon(`/t/e/${trackEl.dataset.trackEvent}`);
}

/* تسجيل الـ Service Worker ومراقبة التحديثات */
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then((registration) => {
                watchForUpdates(registration);

                // النسخة المثبَّتة قد تبقى مفتوحة أياماً: نسأل عن جديد بين حين وآخر
                setInterval(() => registration.update().catch(() => {}), 60 * 60 * 1000);
            })
            .catch(() => {
                // الوضع المحلي بدون بناء أصول — تجاهل بصمت
            });
    });

    syncAppBadge();
}
