#!/usr/bin/env bash
#
# نشر تحديث على خادم مُجهَّز مسبقاً بـ bootstrap-vps.sh.
# يُنفَّذ بالمستخدم deploy:  cd /var/www/bahja && ./deploy/update.sh
# ويستدعيه أيضاً GitHub Actions بعد كل دفعة على الفرع المنشور.
#
set -euo pipefail

cd "$(dirname "$0")/.."

# ملفات المشروع مملوكة للمستخدم deploy؛ حين يُشغَّل السكربت بـ root يرفض git
# العمل بحجّة "dubious ownership" فتفشل كل الأوامر بصمت وتُبنى الشيفرة القديمة
git config --global --add safe.directory "$(pwd)" 2>/dev/null || true

echo "==> جلب آخر الشيفرة"
git fetch --prune origin
git reset --hard "origin/$(git rev-parse --abbrev-ref HEAD)"

echo "==> الاعتماديات"
composer install --no-dev --optimize-autoloader --no-interaction
npm ci --no-audit --no-fund
npm run build

echo "==> الهجرات"
php artisan migrate --force

echo "==> إعادة بناء الكاش"
# ضرورية بعد كل بناء: بصمة أصول Vite تتغيّر، ومنها يشتقّ الـ Service Worker
# رقم إصداره — فتتحدّث نسخة الأجهزة المحفوظة تلقائياً
php artisan optimize

echo "==> تصحيح الصلاحيات"
# git بـ root يكتب ملفات مملوكة لـ root — نعيدها إلى مالكها كي تعمل الرفوعات
if [ "$(id -u)" -eq 0 ]; then
    chown -R deploy:www-data .
fi
chmod -R ug+rwx storage bootstrap/cache public/uploads

echo "تم النشر: $(git rev-parse --short HEAD) — $(git log -1 --pretty=%s)"
