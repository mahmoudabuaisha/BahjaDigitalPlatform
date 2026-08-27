// حارس ميزانيات الأداء (القسم 9): يفشل البناء إن تجاوزت الأصول حدودها.
//
// التشغيل: node scripts/check-budgets.mjs   (بعد npm run build)
// الحدود مبنية على واقع شبكات غزة: صفحة أولى خفيفة على 3G ضعيف.

import { readFileSync, statSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';
import { gzipSync } from 'node:zlib';

const root = dirname(dirname(fileURLToPath(import.meta.url)));

/** الحدود بالبايت (قبل الضغط) — عدّلوها هنا فقط، فهي العقد المعلن */
const BUDGETS = {
    'JS الكلي': { pattern: /\.js$/, limit: 90_000 },
    'CSS الكلي': { pattern: /\.css$/, limit: 120_000 },
};

/** وحدّ إضافي للمنقول فعلياً عبر الشبكة (gzip) */
const GZIP_TOTAL_LIMIT = 45_000;

const manifest = JSON.parse(readFileSync(join(root, 'public/build/manifest.json'), 'utf8'));
const files = [...new Set(Object.values(manifest).flatMap((entry) => [entry.file, ...(entry.css ?? [])]))];

let failed = false;
let gzipTotal = 0;

for (const [label, { pattern, limit }] of Object.entries(BUDGETS)) {
    const matching = files.filter((file) => pattern.test(file));
    const total = matching.reduce((sum, file) => sum + statSync(join(root, 'public/build', file)).size, 0);

    const gz = matching.reduce((sum, file) => sum + gzipSync(readFileSync(join(root, 'public/build', file))).length, 0);
    gzipTotal += gz;

    const ok = total <= limit;
    failed ||= ! ok;

    console.log(`${ok ? '✓' : '✗'} ${label}: ${(total / 1024).toFixed(1)}KB (gzip ${(gz / 1024).toFixed(1)}KB) — الحد ${(limit / 1024).toFixed(0)}KB`);
}

const gzOk = gzipTotal <= GZIP_TOTAL_LIMIT;
failed ||= ! gzOk;
console.log(`${gzOk ? '✓' : '✗'} المنقول المضغوط (JS+CSS): ${(gzipTotal / 1024).toFixed(1)}KB — الحد ${(GZIP_TOTAL_LIMIT / 1024).toFixed(0)}KB`);

console.log(failed
    ? '\nتجاوز في الميزانية — راجعوا ما أُضيف قبل النشر.'
    : '\nكل الأصول ضمن ميزانيات الأداء.');

process.exit(failed ? 1 : 0);
