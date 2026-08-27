// فحص الوصول (WCAG 2.2 AA) بمحرك axe-core على صفحات الموقع العامة.
//
// التشغيل:  BASE_URL=http://127.0.0.1:8000 node scripts/axe-audit.mjs
// يحتاج متصفح Playwright — مرِّروا مساره عبر PLAYWRIGHT_MODULE عند الحاجة.
// رمز الخروج 1 عند وجود أي مخالفة، والنتائج تُطبع صفحةً صفحة.

import { createRequire } from 'node:module';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';

const require = createRequire(import.meta.url);
const root = dirname(dirname(fileURLToPath(import.meta.url)));

const { chromium } = await import(process.env.PLAYWRIGHT_MODULE ?? 'playwright');
const axeSource = readFileSync(join(root, 'node_modules/axe-core/axe.min.js'), 'utf8');

const BASE = process.env.BASE_URL ?? 'http://127.0.0.1:8000';

const PAGES = [
    '/',
    '/events',
    process.env.EVENT_PATH ?? '/events/1',
    '/login',
    '/register',
    '/forgot-password',
    '/join-team',
    '/contact',
    '/guide',
    '/faq',
    '/about',
    '/privacy',
    '/photo-policy',
    '/feedback',
    '/offline',
];

const browser = await chromium.launch({
    executablePath: process.env.CHROMIUM_PATH || undefined,
});
const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });

let totalViolations = 0;

for (const path of PAGES) {
    await page.goto(BASE + path, { waitUntil: 'networkidle' });
    await page.addScriptTag({ content: axeSource });

    const result = await page.evaluate(async () => await window.axe.run(document, {
        runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21aa', 'wcag22aa'] },
    }));

    if (result.violations.length === 0) {
        console.log(`✓ ${path} — بلا مخالفات (${result.passes.length} قاعدة ناجحة)`);
        continue;
    }

    totalViolations += result.violations.length;
    console.log(`✗ ${path} — ${result.violations.length} مخالفة:`);

    for (const violation of result.violations) {
        console.log(`   [${violation.impact}] ${violation.id}: ${violation.help}`);
        for (const node of violation.nodes.slice(0, 3)) {
            console.log(`      → ${node.target.join(' ')} :: ${node.html.slice(0, 120)}`);
        }
    }
}

await browser.close();

console.log(totalViolations === 0
    ? '\nالنتيجة: كل الصفحات المفحوصة خالية من مخالفات WCAG 2.2 AA.'
    : `\nالنتيجة: ${totalViolations} مخالفة تحتاج إصلاحاً.`);

process.exit(totalViolations === 0 ? 0 : 1);
