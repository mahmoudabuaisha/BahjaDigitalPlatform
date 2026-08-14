#!/usr/bin/env bash
#
# نشر تحديث على خادم مُجهَّز مسبقاً بـ bootstrap-vps.sh.
# يُنفَّذ بالمستخدم deploy:  cd /var/www/bahja && ./deploy/update.sh
# ويستدعيه أيضاً GitHub Actions بعد كل دفعة على الفرع المنشور.
#
set -euo pipefail

cd "$(dirname "$0")/.."

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
chmod -R ug+rwx storage bootstrap/cache public/uploads

echo "تم النشر: $(git rev-parse --short HEAD) — $(git log -1 --pretty=%s)"
