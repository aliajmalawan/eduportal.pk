import { readFileSync } from 'fs';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';
import { spawnSync } from 'child_process';

const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..');

const raw = readFileSync(join(ROOT, 'js/faqs-data.js'), 'utf8');
const catMatch = raw.match(/window\.EDUPORTAL_FAQ_CATEGORIES = (\[[\s\S]*?\]);/);
const faqMatch = raw.match(/window\.EDUPORTAL_FAQS = (\[[\s\S]*?\]);\s*\n\s*window\.EDUPORTAL_FAQS_FEATURED/);
const categories = eval(catMatch[1]);
const faqs = eval(faqMatch[1]);

console.log('FAQs loaded:', faqs.length, 'questions in', categories.length, 'categories');
spawnSync(process.execPath, [join(dirname(fileURLToPath(import.meta.url)), 'inject-schemas.mjs')], {
  cwd: ROOT,
  stdio: 'inherit',
});

export { categories, faqs };
