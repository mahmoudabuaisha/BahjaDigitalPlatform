#!/usr/bin/env bash
#
# تجهيز خادم أوبونتو (24.04 / 22.04) لمنصّة بَهْجَة من الصفر.
# يُنفَّذ مرة واحدة بصلاحية root على خادم جديد:
#
#   wget -O bootstrap.sh https://raw.githubusercontent.com/<user>/<repo>/<branch>/deploy/bootstrap-vps.sh
#   chmod +x bootstrap.sh
#   DOMAIN=example.com REPO_URL=https://github.com/<user>/<repo> ./bootstrap.sh
#
# متغيّرات اختيارية:
#   BRANCH=main            الفرع المنشور
#   SEED_DEMO=1            زرع بيانات تجريبية (للعرض على العميل)
#   ADMIN_EMAIL=..         بريد حساب المدير الأول
#   DEPLOY_SSH_KEY="ssh-ed25519 AAAA..."   مفتاح عام يُضاف للمستخدم deploy (لنشر GitHub Actions)
#
set -euo pipefail

DOMAIN="${DOMAIN:?لازم تحديد DOMAIN، مثال: DOMAIN=example.com}"
REPO_URL="${REPO_URL:?لازم تحديد REPO_URL}"
BRANCH="${BRANCH:-main}"
SEED_DEMO="${SEED_DEMO:-0}"
ADMIN_EMAIL="${ADMIN_EMAIL:-admin@${DOMAIN}}"
DEPLOY_SSH_KEY="${DEPLOY_SSH_KEY:-}"

APP_DIR=/var/www/bahja
PHP_V=8.3
SECRETS_FILE=/root/bahja-secrets.txt

log() { printf '\n\033[1;36m==> %s\033[0m\n' "$1"; }

[[ $EUID -eq 0 ]] || { echo "شغّل السكربت بصلاحية root"; exit 1; }
[[ -e "$APP_DIR" ]] && { echo "$APP_DIR موجود مسبقاً — أوقفت التنفيذ كي لا أطمس تثبيتاً قائماً"; exit 1; }

DB_PASSWORD="$(openssl rand -base64 24 | tr -d '/+=' | head -c 24)"
ADMIN_PASSWORD="$(openssl rand -base64 18 | tr -d '/+=' | head -c 16)"

log "تحديث النظام وتثبيت الحزم"
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get upgrade -y -qq
apt-get install -y -qq nginx mysql-server git unzip curl ca-certificates openssl \
    "php${PHP_V}-fpm" "php${PHP_V}-mysql" "php${PHP_V}-mbstring" "php${PHP_V}-xml" \
    "php${PHP_V}-curl" "php${PHP_V}-zip" "php${PHP_V}-bcmath" "php${PHP_V}-intl" "php${PHP_V}-gd"
#            ^ bcmath ليست اختيارية: laravel-lang تعتمدها و composer install يفشل بدونها

if ! command -v composer >/dev/null; then
    log "تثبيت Composer"
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

if ! command -v node >/dev/null; then
    log "تثبيت Node 22"
    curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
    apt-get install -y -qq nodejs
fi

log "إنشاء مستخدم النشر"
id deploy >/dev/null 2>&1 || adduser --disabled-password --gecos "" deploy
usermod -aG www-data deploy
if [[ -n "$DEPLOY_SSH_KEY" ]]; then
    install -d -m 700 -o deploy -g deploy /home/deploy/.ssh
    echo "$DEPLOY_SSH_KEY" >> /home/deploy/.ssh/authorized_keys
    chown deploy:deploy /home/deploy/.ssh/authorized_keys
    chmod 600 /home/deploy/.ssh/authorized_keys
fi

log "قاعدة البيانات"
mysql <<SQL
CREATE DATABASE IF NOT EXISTS bahja CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'bahja'@'127.0.0.1' IDENTIFIED BY '${DB_PASSWORD}';
GRANT ALL PRIVILEGES ON bahja.* TO 'bahja'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL

log "جلب المشروع"
git clone --branch "$BRANCH" "$REPO_URL" "$APP_DIR"
cd "$APP_DIR"

log "تثبيت الاعتماديات وبناء الأصول"
sudo -u deploy composer install --no-dev --optimize-autoloader --no-interaction
npm ci --no-audit --no-fund
npm run build

log "ملف البيئة"
cp .env.example .env
sed -i \
    -e "s|^APP_ENV=.*|APP_ENV=production|" \
    -e "s|^APP_DEBUG=.*|APP_DEBUG=false|" \
    -e "s|^APP_URL=.*|APP_URL=https://${DOMAIN}|" \
    -e "s|^DB_USERNAME=.*|DB_USERNAME=bahja|" \
    -e "s|^DB_PASSWORD=.*|DB_PASSWORD=${DB_PASSWORD}|" \
    -e "s|^BAHJA_ADMIN_EMAIL=.*|BAHJA_ADMIN_EMAIL=${ADMIN_EMAIL}|" \
    -e "s|^BAHJA_ADMIN_PASSWORD=.*|BAHJA_ADMIN_PASSWORD=${ADMIN_PASSWORD}|" \
    -e "s|^LOG_LEVEL=.*|LOG_LEVEL=warning|" \
    .env
php artisan key:generate --force

log "الهجرات والبيانات الأولى"
php artisan migrate --force
php artisan db:seed --force
if [[ "$SEED_DEMO" == "1" ]]; then
    php artisan db:seed --class="Database\\Seeders\\DemoSeeder" --force
fi
php artisan optimize

log "الصلاحيات"
chown -R deploy:www-data "$APP_DIR"
find "$APP_DIR" -type f -exec chmod 664 {} \;
find "$APP_DIR" -type d -exec chmod 775 {} \;
chmod -R ug+rwx "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" "$APP_DIR/public/uploads"
chmod +x "$APP_DIR/artisan" "$APP_DIR/deploy/"*.sh 2>/dev/null || true

log "شهادة TLS للأصل"
# شهادة ذاتية التوقيع تكفي لوضع Cloudflare = Full (مشفّر بين Cloudflare والأصل).
# قبل الإطلاق الحقيقي: استبدلها بشهادة Cloudflare Origin CA وحوّل الوضع إلى Full (strict).
install -d -m 755 /etc/ssl/bahja
openssl req -x509 -nodes -days 825 -newkey rsa:2048 \
    -keyout /etc/ssl/bahja/origin.key -out /etc/ssl/bahja/origin.pem \
    -subj "/CN=${DOMAIN}" >/dev/null 2>&1
chmod 600 /etc/ssl/bahja/origin.key

log "إعداد Nginx"
cat > /etc/nginx/sites-available/bahja <<NGINX
server {
    listen 80;
    server_name ${DOMAIN} www.${DOMAIN};
    return 301 https://${DOMAIN}\$request_uri;
}

server {
    listen 443 ssl;
    http2 on;
    server_name ${DOMAIN} www.${DOMAIN};

    ssl_certificate     /etc/ssl/bahja/origin.pem;
    ssl_certificate_key /etc/ssl/bahja/origin.key;

    if (\$host = www.${DOMAIN}) { return 301 https://${DOMAIN}\$request_uri; }

    root ${APP_DIR}/public;
    index index.php;
    charset utf-8;
    client_max_body_size 12M;

    add_header X-Content-Type-Options nosniff;
    add_header X-Frame-Options SAMEORIGIN;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php${PHP_V}-fpm.sock;
    }

    # الأصول المبنية والخطوط — بصمتها في اسم الملف
    location ~* ^/(build|fonts|icons)/ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        try_files \$uri =404;
    }

    # الـ Service Worker يُولَّد من Blade — لا يُخدَم كملف ثابت
    location = /sw.js {
        try_files \$uri /index.php?\$query_string;
    }

    location ~ /\.(?!well-known) { deny all; }
}
NGINX

ln -sf /etc/nginx/sites-available/bahja /etc/nginx/sites-enabled/bahja
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl reload nginx

log "المجدوِل اليومي"
# events:mark-completed الساعة 00:15 بتوقيت غزة — تحويل الفعاليات الماضية إلى «منفَّذة»
cat > /etc/cron.d/bahja <<CRON
* * * * * deploy cd ${APP_DIR} && php artisan schedule:run >> /dev/null 2>&1
CRON
chmod 644 /etc/cron.d/bahja

log "جدار الحماية"
ufw allow OpenSSH >/dev/null
ufw allow 80,443/tcp >/dev/null
ufw --force enable >/dev/null

cat > "$SECRETS_FILE" <<TXT
منصّة بَهْجَة — بيانات التثبيت (${DOMAIN})
تاريخ التثبيت: $(date -u +%F\ %T) UTC

قاعدة البيانات : bahja / bahja / ${DB_PASSWORD}
لوحة الإدارة   : https://${DOMAIN}/admin
  البريد       : ${ADMIN_EMAIL}
  كلمة المرور  : ${ADMIN_PASSWORD}   ← غيّرها بعد أول دخول

مسار المشروع  : ${APP_DIR}
TXT
chmod 600 "$SECRETS_FILE"

cat <<DONE

════════════════════════════════════════════════════════════
تم التثبيت.

الخطوة التالية في Cloudflare:
  1. سجل A لـ ${DOMAIN} و www نحو عنوان هذا الخادم، بالوكيل (سحابة برتقالية).
  2. SSL/TLS ← وضع التشفير = Full   (وليس Flexible).
  3. فعّل Always Use HTTPS.
  4. Speed ← أوقف Rocket Loader وتصغير JS التلقائي.
  5. Caching ← قاعدة Bypass لـ /admin و /team و /livewire و /sw.js

البيانات السرّية محفوظة في: ${SECRETS_FILE}
لوحة الإدارة: https://${DOMAIN}/admin

قبل الإطلاق الحقيقي: استبدل الشهادة الذاتية بشهادة Cloudflare Origin CA
وحوّل وضع التشفير إلى Full (strict).
════════════════════════════════════════════════════════════
DONE
