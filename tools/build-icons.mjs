// Generuje public/assets/css/icons.css z ikon Lucide (licencja ISC) – tylko tych, które są użyte w kodzie.
// Użycie w HTML/PHP/JS:  <i class=i-settings></i>  (bez cudzysłowów – bezpieczne w stringach PHP i JS)
// Ikona dziedziczy kolor (currentColor) i rozmiar (1em) z otaczającego tekstu.
import { readFileSync, writeFileSync, readdirSync, statSync, existsSync } from 'node:fs';
import { join, extname } from 'node:path';

const ROOT = new URL('..', import.meta.url).pathname;
const ICONS = join(ROOT, 'node_modules/lucide-static/icons');
const SCAN = ['src', 'public/assets/js', 'public/index.php'];
const OUT = join(ROOT, 'public/assets/css/icons.css');

const files = [];
const walk = (p) => {
  const full = join(ROOT, p);
  if (!existsSync(full)) return;
  if (statSync(full).isDirectory()) readdirSync(full).forEach((f) => walk(join(p, f)));
  else if (['.php', '.js', '.html', '.css'].includes(extname(full))) files.push(full);
};
SCAN.forEach(walk);

const used = new Set();
const re = /(?<![\w-])i-([a-z0-9]+(?:-[a-z0-9]+)*)/g;
// Wariant "-thin" (np. i-folder-thin): cieńsza linia do dużych rozmiarów (kafelki, puste stany)
const THIN = '-thin';
const baseName = (n) => (n.endsWith(THIN) ? n.slice(0, -THIN.length) : n);
for (const f of files) {
  for (const m of readFileSync(f, 'utf8').matchAll(re)) {
    if (existsSync(join(ICONS, baseName(m[1]) + '.svg'))) used.add(m[1]);
  }
}

const toDataUri = (svg) => {
  const clean = svg.replace(/<!--.*?-->/gs, '').replace(/\s*class="[^"]*"/, '').replace(/\s+/g, ' ').replace(/> </g, '><').trim();
  return 'data:image/svg+xml,' + clean.replace(/"/g, "'").replace(/%/g, '%25').replace(/#/g, '%23').replace(/</g, '%3C').replace(/>/g, '%3E');
};

const names = [...used].sort();
let css = `/* Ikony: Lucide (https://lucide.dev) – ISC License, Copyright (c) Lucide Contributors.
   Plik generowany przez tools/build-icons.mjs (npm run build) – nie edytuj ręcznie. */
[class^="i-"],[class*=" i-"]{display:inline-block;flex:none;width:1em;height:1em;vertical-align:-.125em;background-color:currentColor;-webkit-mask:var(--i,linear-gradient(transparent,transparent)) center/contain no-repeat;mask:var(--i,linear-gradient(transparent,transparent)) center/contain no-repeat}
`;
for (const n of names) {
  let svg = readFileSync(join(ICONS, baseName(n) + '.svg'), 'utf8');
  if (n.endsWith(THIN)) svg = svg.replace('stroke-width="2"', 'stroke-width="1.25"');
  css += `.i-${n}{--i:url("${toDataUri(svg)}")}\n`;
}
writeFileSync(OUT, css);
console.log(`icons.css: ${names.length} ikon, ${(css.length / 1024).toFixed(1)} KB`);
