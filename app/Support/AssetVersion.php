<?php

namespace App\Support;

/**
 * بصمة نسخة لملفات public الثابتة.
 *
 * الأيقونات تُخزَّن في المتصفّحات مدداً طويلة، فتغييرها لا يصل الناس:
 * بقي كثيرون يرون أيقونة قديمة بعد تحديثها فعلاً. إضافة بصمة مشتقّة من
 * الملف نفسه تجعل العنوان يتغيّر متى تغيّر الملف — فيُجلب جديداً — ويبقى
 * ثابتاً ما دام الملف كما هو، فلا يُعاد تنزيله بلا سبب.
 *
 * البصمة من زمن التعديل لا من محتوى الملف: استدعاء واحد لنظام الملفات
 * بدل قراءة مئات الكيلوبايتات في كل طلب، وgit لا يمسّ زمن ملف لم يتغيّر.
 */
class AssetVersion
{
    /** @var array<string, string> */
    private static array $stamps = [];

    public static function url(string $path): string
    {
        $clean = '/'.ltrim($path, '/');

        $stamp = self::$stamps[$clean] ??= self::stamp($clean);

        return $stamp === '' ? $clean : $clean.'?v='.$stamp;
    }

    private static function stamp(string $path): string
    {
        $file = public_path(ltrim($path, '/'));

        if (! is_file($file)) {
            return '';
        }

        return substr(md5((string) filemtime($file)), 0, 8);
    }
}
