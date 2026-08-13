# دليل نشر منصّة بَهْجَة — استضافة مشتركة (بدون SSH)

مكتوب ليناسب ميزانية المبادرة (~400 شيكل/سنة ≈ 110$) على استضافة مشتركة بلوحة cPanel.

## 1. اختيار الاستضافة والدومين

**الحد الأدنى المطلوب قبل الشراء — تأكدوا من دعم المزود لـ:**
- PHP **8.3** مع امتدادات: `intl` (إلزامي لـ Filament)، `pdo_mysql`, `mbstring`, `zip`
- قاعدة MySQL/MariaDB واحدة
- شهادة SSL مجانية (AutoSSL / Let's Encrypt) — **إلزامية: الـ Service Worker لا يعمل بدون HTTPS**
- إمكانية تغيير document root للدومين (أو استخدام subdomain) ليشير إلى مجلد `public`
- Cron Jobs من cPanel

**خيارات ضمن الميزانية**: Hostinger Premium (~3$/شهر)، Namecheap Stellar، أو مزود محلي موثوق.
**الدومين**: ‎.com (~12$/سنة).

**إضافة مجانية قوية**: فعّلوا **Cloudflare (الخطة المجانية)** أمام الموقع — كاش حافة يسرّع الوصول
من شبكات غزة الضعيفة ويخفف حمل السيرفر.

## 2. تجهيز الملفات (على جهازكم)

```bash
npm run build                       # بناء الأصول — Node لا يعمل على السيرفر
composer install --no-dev --optimize-autoloader
```

ارفعوا عبر File Manager أو FTP **كل المشروع** (بما فيه `vendor/` و `public/build/`) إلى مجلد
خارج جذر الويب، مثل `/home/USER/bahja/`.

> 💡 الأسرع: اضغطوا المشروع zip محلياً (استثنوا `node_modules/` و `.git/`) وارفعوه وفكّوه من File Manager.

## 3. توجيه الدومين إلى مجلد public

- من cPanel → Domains: اجعلوا document root للدومين يشير إلى `/home/USER/bahja/public`
- **لا تضعوا المشروع كاملاً داخل `public_html` أبداً** — ملف `.env` سيصبح مكشوفاً

## 4. قاعدة البيانات

1. cPanel → MySQL Databases: أنشئوا قاعدة + مستخدماً بكل الصلاحيات
2. عدّلوا `.env` على السيرفر:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_DATABASE=user_bahja
DB_USERNAME=user_bahja
DB_PASSWORD=***

CACHE_STORE=file
QUEUE_CONNECTION=sync
SESSION_DRIVER=database

BAHJA_ADMIN_EMAIL=البريد الحقيقي للإدارة
BAHJA_ADMIN_PASSWORD=كلمة مرور قوية جديدة
```

## 5. تنفيذ أوامر الإعداد (بدون SSH)

أضيفوا مؤقتاً هذا المسار في نهاية `routes/web.php`، مع سرّ طويل عشوائي خاص بكم:

```php
Route::get('/deploy/السر-الطويل-هنا', function () {
    Artisan::call('migrate', ['--force' => true]);
    Artisan::call('db:seed', ['--class' => 'AreaSeeder', '--force' => true]);
    Artisan::call('db:seed', ['--class' => 'CategorySeeder', '--force' => true]);
    Artisan::call('db:seed', ['--class' => 'AdminUserSeeder', '--force' => true]);
    Artisan::call('config:cache');
    Artisan::call('route:cache');
    Artisan::call('view:cache');
    return 'تم الإعداد بنجاح';
});
```

زوروا الرابط مرة واحدة، تأكدوا من النجاح، ثم **احذفوا المسار فوراً وارفعوا الملف من جديد**.

> ملاحظة: `DemoSeeder` لا يعمل في الإنتاج تلقائياً (مقيّد ببيئة local) — لا بيانات تجريبية ستظهر.

## 6. المهمة المجدولة (Cron)

cPanel → Cron Jobs → أضيفوا (كل 5 دقائق أو حتى مرة يومياً 00:20):

```
*/5 * * * * php /home/USER/bahja/artisan schedule:run >> /dev/null 2>&1
```

هذا يشغّل أمر تحويل الفعاليات المنتهية إلى "منفَّذة" (00:15 بتوقيت غزة).
إن لم يتوفر cron لدى المزود: استخدموا خدمة cron-job.org المجانية لتزور رابطاً سرّياً مشابهاً للخطوة 5 يستدعي `schedule:run`.

## 7. الضغط والكاش (.htaccess)

أضيفوا في **نهاية** `public/.htaccess`:

```apache
# ضغط النقل — حاسم لشبكات 2G
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/css application/javascript application/json image/svg+xml application/manifest+json
</IfModule>

# كاش طويل للأصول المبصومة والخطوط والأيقونات
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType font/woff2 "access plus 1 year"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType image/svg+xml "access plus 1 month"
</IfModule>
<IfModule mod_headers.c>
    <FilesMatch "\.(woff2|css|js)$">
        Header set Cache-Control "public, max-age=31536000, immutable"
    </FilesMatch>
</IfModule>
```

## 8. فحوصات ما بعد النشر ✅

| الفحص | كيف |
|---|---|
| HTTPS يعمل | القفل الأخضر بالمتصفح، وجرّبوا http → يحوّل لـ https |
| لوحة الإدارة | `/admin` — سجلوا الدخول وغيّروا كلمة المرور فوراً |
| تسجيل الفرق | `/team/register` — سجلوا فريقاً تجريبياً واعتمدوه من الإدارة ثم احذفوه |
| الـ PWA | من كروم أندرويد: القائمة ⋮ → "إضافة إلى الشاشة الرئيسية"، افتحوا التطبيق |
| **Offline** | افتحوا الروزنامة، فعّلوا وضع الطيران، أعيدوا فتح التطبيق — يجب أن تظهر الفعاليات المحفوظة |
| معاينة واتساب | أرسلوا رابط فعالية لأنفسكم في واتساب — يجب أن تظهر بطاقة بعنوان وصورة (فحص إضافي: opengraph.xyz) |
| رموز QR | من لوحة الإدارة → فعالية → "رمز QR" → نزّلوا واطبعوا وامسحوا بالجوال |
| الجدولة | بعد منتصف الليل تأكدوا أن فعاليات الأمس المعتمدة صارت "منفَّذة" |

## 9. التحديثات اللاحقة

1. محلياً: عدّلوا الكود، `npm run build`، `php artisan test`
2. ارفعوا الملفات المتغيرة + مجلد `public/build` كاملاً (بصمة الملفات تتغير)
3. أعيدوا خطوة الـ deploy route إن كانت هناك migrations جديدة (ثم احذفوها)
4. **تغيّر بصمة `public/build/manifest.json` يحدّث الـ Service Worker تلقائياً لدى كل الزوار**

## استكشاف الأخطاء

| العرض | السبب الغالب | الحل |
|---|---|---|
| صفحة بيضاء / 500 | كاش config قديم | احذفوا `bootstrap/cache/config.php` وأعيدوا زيارة deploy route |
| خطأ 419 عند تسجيل الدخول | جلسات قديمة | تأكدوا من `SESSION_DRIVER=database` وجدول sessions موجود |
| الصور المرفوعة لا تظهر | مسار القرص | القرص يشير لـ `public/uploads` مباشرة — تأكدوا من صلاحيات الكتابة 755 |
| SW لا يسجّل | لا HTTPS | فعّلوا AutoSSL وأعيدوا المحاولة |
| العربية مكسورة في CSV | فتح مباشر بـ Excel قديم | الملفات تتضمن BOM — استخدموا Excel حديثاً أو Google Sheets |
