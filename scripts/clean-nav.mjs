import { readFileSync, writeFileSync, readdirSync, statSync } from 'fs';
import { join } from 'path';

const ROOT = join(import.meta.dirname, '..');

function walk(dir, files = []) {
  for (const name of readdirSync(dir)) {
    const p = join(dir, name);
    if (statSync(p).isDirectory()) {
      if (name !== 'node_modules' && name !== 'scripts') walk(p, files);
    } else if (name.endsWith('.html')) files.push(p);
  }
  return files;
}

const patterns = [
  /\s*<a href="(?:\.\.\/)?videos\.html"[^>]*>Videos<\/a>\s*\n/g,
  /\s*<a href="(?:\.\.\/)?faqs\.html"[^>]*>FAQs<\/a>\s*\n/g,
  /\s*<a href="https:\/\/play\.google\.com\/store\/apps\/details\?id=com\.educationportal"[^>]*class="btn btn-ghost"[^>]*>Download App<\/a>\s*\n/g,
  /\s*<a href="https:\/\/play\.google\.com\/store\/apps\/details\?id=com\.educationportal"[^>]*>Download App<\/a>\s*\n/g,
];

let updated = 0;
for (const file of walk(ROOT)) {
  let html = readFileSync(file, 'utf8');
  const before = html;
  for (const re of patterns) {
    html = html.replace(re, '\n');
  }
  if (html !== before) {
    writeFileSync(file, html, 'utf8');
    updated++;
    console.log('Cleaned:', file.replace(ROOT + join('', ''), '').replace(/\\/g, '/'));
  }
}
console.log(`\nNavbar cleaned in ${updated} files.`);
