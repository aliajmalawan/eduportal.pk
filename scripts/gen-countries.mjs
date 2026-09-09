import https from 'https';
import fs from 'fs';

const url = 'https://raw.githubusercontent.com/dr5hn/countries-states-cities-database/master/json/countries.json';

https.get(url, (res) => {
  let data = '';
  res.on('data', (c) => { data += c; });
  res.on('end', () => {
    const list = JSON.parse(data)
      .filter((c) => c.phonecode)
      .map((c) => ({
        iso: c.iso2,
        dial: String(c.phonecode).replace(/^\+/, ''),
        name: c.name,
      }))
      .sort((a, b) => a.name.localeCompare(b.name));

    const pk = list.find((c) => c.iso === 'PK');
    const ordered = pk ? [pk, ...list.filter((c) => c.iso !== 'PK')] : list;

    const js = `/* Country dial codes for EduPortal Get Started modal */
window.EDUPORTAL_COUNTRIES = ${JSON.stringify(ordered)};

window.detectDefaultCountryIso = function () {
  try {
    const tz = Intl.DateTimeFormat().resolvedOptions().timeZone || '';
    if (tz === 'Asia/Karachi') return 'PK';
  } catch (e) {}
  const lang = (navigator.language || navigator.userLanguage || '').toLowerCase();
  if (lang.endsWith('-pk') || lang === 'ur' || lang === 'ur-pk') return 'PK';
  return 'PK';
};

window.populateCountryCodeSelect = function (selectEl, defaultIso) {
  if (!selectEl || !window.EDUPORTAL_COUNTRIES) return;
  const iso = defaultIso || window.detectDefaultCountryIso();
  selectEl.innerHTML = '';
  window.EDUPORTAL_COUNTRIES.forEach(function (c) {
    const opt = document.createElement('option');
    opt.value = c.iso;
    opt.dataset.dial = c.dial;
    opt.textContent = c.name + ' (+' + c.dial + ')';
    if (c.iso === iso) opt.selected = true;
    selectEl.appendChild(opt);
  });
};

window.getSelectedDialCode = function (selectEl) {
  const opt = selectEl && selectEl.selectedOptions && selectEl.selectedOptions[0];
  return (opt && opt.dataset.dial) || '92';
};
`;

    fs.writeFileSync(new URL('../js/country-codes.js', import.meta.url), js);
    console.log('Wrote', ordered.length, 'countries');
  });
}).on('error', (err) => {
  console.error(err);
  process.exit(1);
});
