<?php

namespace App\Services\Push;

use RuntimeException;

/**
 * تعمية رسائل Web Push وتوقيع VAPID — بلا مكتبات خارجية.
 *
 * الاستضافة المشتركة بلا gmp ولا bcmath، والمكتبات الجاهزة تشترطهما؛
 * وإضافة حزمة جديدة تعني أن نشراً لا يشغّل composer install يُسقط الموقع.
 * فالتعمية مكتوبة هنا على openssl وحده: منحنى P-256 وHKDF وAES-128-GCM.
 *
 * المراجع: RFC 8291 (تعمية Web Push) وRFC 8188 (aes128gcm) وRFC 8292 (VAPID).
 */
class WebPushCrypto
{
    /** معرّف المنحنى prime256v1 في ترميز DER */
    private const CURVE_OID = "\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07";

    private const RECORD_SIZE = 4096;

    /** هل تملك هذه النسخة من PHP ما يلزم للإرسال؟ */
    public static function isSupported(): bool
    {
        return extension_loaded('openssl')
            && function_exists('openssl_pkey_derive')
            && in_array('prime256v1', openssl_get_curve_names() ?: [], true)
            && in_array('aes-128-gcm', openssl_get_cipher_methods(), true);
    }

    public static function base64UrlEncode(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    public static function base64UrlDecode(string $encoded): string
    {
        $decoded = base64_decode(strtr($encoded, '-_', '+/'), true);

        if ($decoded === false) {
            throw new RuntimeException('تعذّر فكّ ترميز base64url.');
        }

        return $decoded;
    }

    /**
     * توليد زوج مفاتيح VAPID جديد.
     *
     * @return array{public: string, private: string} بترميز base64url
     */
    public static function generateVapidKeys(): array
    {
        $key = openssl_pkey_new([
            'curve_name' => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ]);

        if ($key === false) {
            throw new RuntimeException('تعذّر توليد مفتاح P-256.');
        }

        $details = openssl_pkey_get_details($key)['ec'];

        return [
            'public' => self::base64UrlEncode(self::publicPoint($details['x'], $details['y'])),
            'private' => self::base64UrlEncode(str_pad($details['d'], 32, "\0", STR_PAD_LEFT)),
        ];
    }

    /** النقطة العامة بصيغة غير مضغوطة: 0x04 ثم X ثم Y */
    private static function publicPoint(string $x, string $y): string
    {
        return "\x04".str_pad($x, 32, "\0", STR_PAD_LEFT).str_pad($y, 32, "\0", STR_PAD_LEFT);
    }

    /** مفتاح عام خام (65 بايت) إلى PEM كي يقبله openssl */
    public static function publicKeyToPem(string $raw): string
    {
        if (strlen($raw) !== 65 || $raw[0] !== "\x04") {
            throw new RuntimeException('المفتاح العام يجب أن يكون 65 بايت بصيغة غير مضغوطة.');
        }

        // SubjectPublicKeyInfo: SEQUENCE { SEQUENCE { OID ecPublicKey, OID prime256v1 }, BIT STRING }
        $algorithm = self::sequence("\x06\x07\x2a\x86\x48\xce\x3d\x02\x01".self::CURVE_OID);
        $der = self::sequence($algorithm.self::tag("\x03", "\x00".$raw));

        return self::pem('PUBLIC KEY', $der);
    }

    /** مفتاح خاص خام (32 بايت) إلى PEM بصيغة SEC1 */
    public static function privateKeyToPem(string $rawPrivate, string $rawPublic): string
    {
        if (strlen($rawPrivate) !== 32) {
            throw new RuntimeException('المفتاح الخاص يجب أن يكون 32 بايت.');
        }

        $der = self::sequence(
            "\x02\x01\x01"                                  // version
            .self::tag("\x04", $rawPrivate)                 // privateKey
            .self::tag("\xa0", self::CURVE_OID)             // [0] parameters
            .self::tag("\xa1", self::tag("\x03", "\x00".$rawPublic))  // [1] publicKey
        );

        return self::pem('EC PRIVATE KEY', $der);
    }

    /**
     * تعمية الحمولة لمشترك بعينه بترميز aes128gcm.
     *
     * @param  string  $userPublicKey  مفتاح المتصفّح (p256dh) خاماً
     * @param  string  $authSecret  سرّ المصادقة (auth) خاماً، 16 بايت
     * @param  string|null  $salt  للاختبار فقط — يُولَّد عشوائياً في الإنتاج
     * @param  string|null  $serverPrivate  للاختبار فقط
     */
    public static function encrypt(
        string $payload,
        string $userPublicKey,
        string $authSecret,
        ?string $salt = null,
        ?string $serverPrivate = null,
    ): string {
        $salt ??= random_bytes(16);

        if ($serverPrivate === null) {
            $ephemeral = openssl_pkey_new([
                'curve_name' => 'prime256v1',
                'private_key_type' => OPENSSL_KEYTYPE_EC,
            ]);

            $details = openssl_pkey_get_details($ephemeral)['ec'];
            $serverPublic = self::publicPoint($details['x'], $details['y']);
        } else {
            $serverPublic = self::derivePublicPoint($serverPrivate);
            $ephemeral = openssl_pkey_get_private(self::privateKeyToPem($serverPrivate, $serverPublic));
        }

        if ($ephemeral === false) {
            throw new RuntimeException('تعذّر تجهيز المفتاح العابر.');
        }

        $shared = openssl_pkey_derive(self::publicKeyToPem($userPublicKey), $ephemeral, 32);

        if ($shared === false) {
            throw new RuntimeException('تعذّر اشتقاق السرّ المشترك (ECDH).');
        }

        // RFC 8291 §3.4: السرّ المشترك يُمزج بسرّ المصادقة ومفتاحَي الطرفين
        $ikm = self::hkdf(
            $authSecret,
            $shared,
            "WebPush: info\x00".$userPublicKey.$serverPublic,
            32,
        );

        $contentKey = self::hkdf($salt, $ikm, "Content-Encoding: aes128gcm\x00", 16);
        $nonce = self::hkdf($salt, $ikm, "Content-Encoding: nonce\x00", 12);

        // 0x02 يعلّم آخر سجلّ — والحشو بعده اختياري ونتركه فارغاً
        $tag = '';
        $ciphertext = openssl_encrypt(
            $payload."\x02",
            'aes-128-gcm',
            $contentKey,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
        );

        if ($ciphertext === false) {
            throw new RuntimeException('تعذّرت تعمية الحمولة.');
        }

        // ترويسة RFC 8188: salt(16) + حجم السجلّ(4) + طول المفتاح(1) + المفتاح
        return $salt
            .pack('N', self::RECORD_SIZE)
            .chr(strlen($serverPublic))
            .$serverPublic
            .$ciphertext.$tag;
    }

    /**
     * ترويسة Authorization لطلب الدفع — توقيع ES256 على مطالبات VAPID.
     *
     * @return array{Authorization: string, Crypto-Key?: string}
     */
    public static function vapidHeaders(
        string $endpointOrigin,
        string $subject,
        string $publicKey,
        string $privateKey,
        ?int $expiresAt = null,
    ): array {
        $header = self::base64UrlEncode(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
        $claims = self::base64UrlEncode(json_encode([
            'aud' => $endpointOrigin,
            'exp' => $expiresAt ?? (time() + 12 * 3600),
            'sub' => $subject,
        ], JSON_UNESCAPED_SLASHES));

        $rawPublic = self::base64UrlDecode($publicKey);
        $rawPrivate = str_pad(self::base64UrlDecode($privateKey), 32, "\0", STR_PAD_LEFT);

        $key = openssl_pkey_get_private(self::privateKeyToPem($rawPrivate, $rawPublic));

        if ($key === false) {
            throw new RuntimeException('مفتاح VAPID الخاص غير صالح.');
        }

        $der = '';

        if (! openssl_sign($header.'.'.$claims, $der, $key, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('تعذّر توقيع مطالبات VAPID.');
        }

        $jwt = $header.'.'.$claims.'.'.self::base64UrlEncode(self::derSignatureToRaw($der));

        return ['Authorization' => 'vapid t='.$jwt.', k='.$publicKey];
    }

    /** استخراج النقطة العامة من مفتاح خاص خام */
    private static function derivePublicPoint(string $rawPrivate): string
    {
        // نبني مفتاحاً مؤقتاً بنقطة صفرية ثم نقرأ ما يحسبه openssl
        $temporary = openssl_pkey_new([
            'curve_name' => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ]);

        $details = openssl_pkey_get_details($temporary)['ec'];
        $details['d'] = $rawPrivate;

        $key = openssl_pkey_get_private(self::pem('EC PRIVATE KEY', self::sequence(
            "\x02\x01\x01"
            .self::tag("\x04", $rawPrivate)
            .self::tag("\xa0", self::CURVE_OID)
        )));

        if ($key === false) {
            throw new RuntimeException('تعذّرت قراءة المفتاح الخاص.');
        }

        $ec = openssl_pkey_get_details($key)['ec'];

        return self::publicPoint($ec['x'], $ec['y']);
    }

    /** HKDF كما تستخدمه مواصفة Web Push: استخراج ثم توسيع بجولة واحدة */
    private static function hkdf(string $salt, string $ikm, string $info, int $length): string
    {
        $prk = hash_hmac('sha256', $ikm, $salt, true);

        return substr(hash_hmac('sha256', $info."\x01", $prk, true), 0, $length);
    }

    /** توقيع ECDSA من DER إلى 64 بايت (r ثم s) كما يطلب JWS */
    private static function derSignatureToRaw(string $der): string
    {
        $offset = 2;

        if (ord($der[1]) > 0x80) {
            $offset += ord($der[1]) - 0x80;
        }

        $read = static function (string $der, int &$offset): string {
            $offset++; // 0x02
            $length = ord($der[$offset++]);
            $value = substr($der, $offset, $length);
            $offset += $length;

            return str_pad(ltrim($value, "\0"), 32, "\0", STR_PAD_LEFT);
        };

        return $read($der, $offset).$read($der, $offset);
    }

    private static function tag(string $tag, string $content): string
    {
        return $tag.self::length(strlen($content)).$content;
    }

    private static function sequence(string $content): string
    {
        return self::tag("\x30", $content);
    }

    private static function length(int $length): string
    {
        if ($length < 0x80) {
            return chr($length);
        }

        $bytes = ltrim(pack('N', $length), "\0");

        return chr(0x80 | strlen($bytes)).$bytes;
    }

    private static function pem(string $label, string $der): string
    {
        return "-----BEGIN {$label}-----\n"
            .chunk_split(base64_encode($der), 64, "\n")
            ."-----END {$label}-----\n";
    }
}
