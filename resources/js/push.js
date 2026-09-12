/**
 * إذن الإشعارات والاشتراك فيها.
 *
 * لا نطلب الإذن عند فتح الصفحة: المتصفّحات تعاقب على ذلك، والأهم أن
 * الرفض في كروم وسفاري نهائيّ لا رجعة فيه إلا من إعدادات النظام. فلا
 * نسأل إلا بعد ضغطة صريحة، وبعد أن يُقرأ ما الذي سيصله.
 */

function urlBase64ToUint8Array(base64) {
    const padded = (base64 + '='.repeat((4 - (base64.length % 4)) % 4))
        .replace(/-/g, '+')
        .replace(/_/g, '/');

    const raw = atob(padded);
    const output = new Uint8Array(raw.length);

    for (let i = 0; i < raw.length; i++) {
        output[i] = raw.charCodeAt(i);
    }

    return output;
}

function csrf() {
    return document.querySelector('meta[name=csrf-token]')?.content ?? '';
}

async function post(url, body) {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrf(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify(body ?? {}),
    });

    const data = await response.json().catch(() => ({}));

    if (! response.ok) {
        throw new Error(data.message ?? 'تعذّر إتمام الطلب.');
    }

    return data;
}

export function registerPush(Alpine) {
    Alpine.data('pushNotifications', (publicKey = '') => ({
        supported: false,
        permission: 'default',
        subscribed: false,
        busy: false,
        message: '',
        error: '',

        async init() {
            this.supported = 'serviceWorker' in navigator
                && 'PushManager' in window
                && 'Notification' in window
                && publicKey !== '';

            if (! this.supported) {
                return;
            }

            this.permission = Notification.permission;

            const registration = await navigator.serviceWorker.ready.catch(() => null);
            this.subscribed = Boolean(await registration?.pushManager.getSubscription());

            // المتصفّح قد يُبطل الاشتراك من تلقائه: نجدّده بلا سؤال جديد
            navigator.serviceWorker.addEventListener('message', (event) => {
                if (event.data?.type === 'push-resubscribe' && this.permission === 'granted') {
                    this.enable();
                }
            });
        },

        /** رفض سابق لا نستطيع تجاوزه — نقول للناس أين يفكّونه */
        get blocked() {
            return this.permission === 'denied';
        },

        async enable() {
            this.busy = true;
            this.error = '';
            this.message = '';

            try {
                this.permission = await Notification.requestPermission();

                if (this.permission !== 'granted') {
                    this.error = this.permission === 'denied'
                        ? 'منع المتصفّح الإشعارات. افتحوا إعدادات الموقع في المتصفّح وأعيدوا السماح.'
                        : 'لم يصلنا إذنكم بعد.';

                    return;
                }

                const registration = await navigator.serviceWorker.ready;

                const subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(publicKey),
                });

                const payload = subscription.toJSON();

                const data = await post('/account/push', {
                    endpoint: payload.endpoint,
                    keys: payload.keys,
                });

                this.subscribed = true;
                this.message = data.message ?? 'فُعّلت الإشعارات.';
            } catch (exception) {
                this.error = exception.message || 'تعذّر تفعيل الإشعارات على هذا الجهاز.';
            }

            this.busy = false;
        },

        async disable() {
            this.busy = true;
            this.error = '';
            this.message = '';

            try {
                const registration = await navigator.serviceWorker.ready;
                const subscription = await registration.pushManager.getSubscription();

                await post('/account/push/remove', { endpoint: subscription?.endpoint ?? '' });
                await subscription?.unsubscribe();

                this.subscribed = false;
                this.message = 'أوقفنا الإشعارات على هذا الجهاز.';
            } catch (exception) {
                this.error = exception.message || 'تعذّر إيقاف الإشعارات.';
            }

            this.busy = false;
        },

        async sendTest() {
            this.busy = true;
            this.error = '';
            this.message = '';

            try {
                const data = await post('/account/push/test');
                this.message = data.message;
            } catch (exception) {
                this.error = exception.message;
            }

            this.busy = false;
        },
    }));
}
