import { readFileSync, writeFileSync, readdirSync, statSync } from 'fs';
import { join } from 'path';

const ROOT = join(import.meta.dirname, '..');

const NAV_BLOCK = `        <a href="case-studies.html">Case Studies</a>`;
const NAV_BLOCK_SUB = `        <a href="../case-studies.html">Case Studies</a>`;

function walk(dir, files = []) {
  for (const name of readdirSync(dir)) {
    const p = join(dir, name);
    if (statSync(p).isDirectory()) {
      if (name !== 'node_modules' && name !== 'scripts') walk(p, files);
    } else if (name.endsWith('.html')) files.push(p);
  }
  return files;
}

let updated = 0;
for (const file of walk(ROOT)) {
  let html = readFileSync(file, 'utf8');
  if (!html.includes('nav-links') || html.includes('case-studies.html')) continue;

  const isSub = file.replace(/\\/g, '/').includes('/case-studies/');
  const block = isSub ? NAV_BLOCK_SUB : NAV_BLOCK;
  const before = html;

  html = html.replace(
    /(<a href="(?:\.\.\/)?blog\.html"[^>]*>Blogs<\/a>)\s*\n(\s*<a href="(?:\.\.\/)?contact\.html")/g,
    `$1\n${block}\n$2`
  );

  if (html !== before) {
    writeFileSync(file, html, 'utf8');
    updated++;
  }
}
console.log(`Case Studies nav added to ${updated} files (if any were missing).`);
