/**
 * تثبيت بَهْجَة على الجهاز، وتحديثها دون أن تُقطع على أحد عمله.
 *
 * لا متصفّح يتيح تثبيتاً برمجياً واحداً: أندرويد يمنحنا حدثاً نلتقطه،
 * وiOS لا يمنح شيئاً فنشرح الخطوات بالصورة، ومتصفّحات iOS غير سفاري
 * لا تستطيع التثبيت أصلاً فنقول ذلك صراحةً بدل أن نترك المستخدم يحاول.
 */

const SNOOZE_KEY = 'bahja_install_snoozed_until';
const VISITS_KEY = 'bahja_visits';
const SNOOZE_DAYS = 21;
const VISITS_BEFORE_INVITE = 2;

/** الوعد المؤجَّل من المتصفّح — يصل مرة واحدة وقد يسبق تركيب Alpine */
let deferredPrompt = null;
const promptListeners = new Set();

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    deferredPrompt = event;
    promptListeners.forEach((notify) => notify());
});

function store() {
    try {
        return window.localStorage;
    } catch (error) {
        return null; // وضع التصفّح الخاص: نتصرّف كأن لا ذاكرة
    }
}

function isStandalone() {
    return window.matchMedia('(display-mode: standalone)').matches
        || window.matchMedia('(display-mode: minimal-ui)').matches
        || window.navigator.standalone === true;
}

function detectPlatform() {
    const ua = navigator.userAgent;

    // iPadOS الحديث يقدّم نفسه كـ Macintosh — نكشفه باللمس
    const isIos = /iPad|iPhone|iPod/.test(ua)
        || (/Macintosh/.test(ua) && navigator.maxTouchPoints > 1);

    if (isIos) {
        return /CriOS|FxiOS|EdgiOS|OPiOS/.test(ua) ? 'ios-other-browser' : 'ios';
    }

    if (/Android/.test(ua)) {
        return 'android';
    }

    return 'desktop';
}

export function registerPwa(Alpine) {
    /**
     * بطاقة التثبيت الكاملة — تعرف منصّتها وتقول للناس ما يفعلونه فعلاً.
     */
    Alpine.data('installApp', () => ({
        platform: 'desktop',
        installed: false,
        canPrompt: false,
        showSteps: false,
        busy: false,

        init() {
            this.platform = detectPlatform();
            this.installed = isStandalone();
            this.canPrompt = deferredPrompt !== null;

            const sync = () => { this.canPrompt = deferredPrompt !== null; };
            promptListeners.add(sync);

            window.addEventListener('appinstalled', () => {
                deferredPrompt = null;
                this.installed = true;
                this.canPrompt = false;
                this.showSteps = false;
            });
        },

        /** يُعرض الشرح اليدوي حين لا يمنحنا المتصفّح زرّاً حقيقياً */
        get needsManualSteps() {
            return ! this.canPrompt;
        },

        async install() {
            if (! deferredPrompt) {
                this.showSteps = true;

                return;
            }

            this.busy = true;

            try {
                deferredPrompt.prompt();
                const choice = await deferredPrompt.userChoice;

                if (choice?.outcome === 'accepted') {
                    this.installed = true;
                }
            } catch (error) {
                this.showSteps = true; // رفض المتصفّح النافذة: نعود للشرح
            }

            deferredPrompt = null;
            this.canPrompt = false;
            this.busy = false;
        },
    }));

    /**
     * الدعوة العابرة أعلى الصفحة: لا تظهر للزائر أول مرة، ولا تعود
     * قبل ثلاثة أسابيع إن رفضها — الإلحاح يطرد الناس ولا يجلبهم.
     */
    Alpine.data('installInvite', () => ({
        visible: false,
        platform: 'desktop',

        init() {
            if (isStandalone()) {
                return;
            }

            this.platform = detectPlatform();

            if (this.platform === 'ios-other-browser') {
                return; // لا حيلة لنا في هذه المتصفّحات: لا نعد بما لا يكون
            }

            const memory = store();
            const snoozedUntil = Number(memory?.getItem(SNOOZE_KEY) ?? 0);

            if (snoozedUntil > Date.now()) {
                return;
            }

            const visits = Number(memory?.getItem(VISITS_KEY) ?? 0) + 1;
            memory?.setItem(VISITS_KEY, String(visits));

            if (visits < VISITS_BEFORE_INVITE) {
                return;
            }

            // أندرويد: لا ندعو قبل أن يؤكّد المتصفّح أن التثبيت ممكن
            if (this.platform === 'android' && deferredPrompt === null) {
                promptListeners.add(() => { this.visible = true; });

                return;
            }

            this.visible = true;

            window.addEventListener('appinstalled', () => { this.visible = false; });
        },

        dismiss() {
            this.visible = false;
            store()?.setItem(SNOOZE_KEY, String(Date.now() + SNOOZE_DAYS * 86400000));
        },
    }));
}

/**
 * تحديث التطبيق: النسخة الجديدة تنتظر، ولا تحلّ محلّ القديمة إلا
 * بضغطة من المستخدم — كي لا تُسحب الصفحة من تحت يد أمٍّ تملأ حجزاً.
 */
export function watchForUpdates(registration) {
    const banner = document.getElementById('update-banner');
    const button = document.getElementById('update-now');

    if (! banner || ! button) {
        return;
    }

    let waiting = registration.waiting ?? null;

    const reveal = (worker) => {
        waiting = worker;
        banner.classList.remove('hidden');
    };

    if (waiting && navigator.serviceWorker.controller) {
        reveal(waiting);
    }

    registration.addEventListener('updatefound', () => {
        const installing = registration.installing;

        if (! installing) {
            return;
        }

        installing.addEventListener('statechange', () => {
            // بلا controller فهذا أول تثبيت، لا تحديث: لا داعي لإزعاج أحد
            if (installing.state === 'installed' && navigator.serviceWorker.controller) {
                reveal(installing);
            }
        });
    });

    let awaitingReload = false;

    button.addEventListener('click', () => {
        button.disabled = true;
        awaitingReload = true;
        waiting?.postMessage({ type: 'skip-waiting' });
    });

    navigator.serviceWorker.addEventListener('controllerchange', () => {
        // أول تثبيت يُطلق هذا الحدث أيضاً (clients.claim). إعادة التحميل
        // هناك تعني أن كل زائر جديد تُسحب صفحته فجأة — فلا نُعيد التحميل
        // إلا إن كان المستخدم هو من طلب التحديث.
        if (! awaitingReload) {
            return;
        }

        awaitingReload = false;
        window.location.reload();
    });
}

/**
 * شارة العدّاد على أيقونة التطبيق — الإشعارات غير المقروءة تظهر
 * رقماً على الأيقونة في الشاشة الرئيسية، كما في أي تطبيق.
 */
export function syncAppBadge() {
    if (! ('setAppBadge' in navigator)) {
        return;
    }

    const unread = Number(document.body.dataset.unread ?? 0);

    // الفشل هنا لا يعني شيئاً للمستخدم: نتجاهله بصمت
    (unread > 0 ? navigator.setAppBadge(unread) : navigator.clearAppBadge())
        ?.catch?.(() => {});
}
