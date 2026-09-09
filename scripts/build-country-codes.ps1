# Builds js/country-codes.js from data/country-options.raw
$rawPath = Join-Path $PSScriptRoot '..\data\country-options.raw'
$outPath = Join-Path $PSScriptRoot '..\js\country-codes.js'
$raw = [IO.File]::ReadAllText($rawPath)
$matches = [regex]::Matches($raw, 'value="([^"]+)"\s+dialling_code="(\d+)"[^>]*>([^<]+)</option>')
$items = foreach ($m in $matches) {
  $name = $m.Groups[3].Value -replace '\s*\(\+\d+\)\s*$', ''
  @{ iso = $m.Groups[1].Value; dial = $m.Groups[2].Value; name = $name.Trim() }
}
$pk = $items | Where-Object { $_.iso -eq 'PK' }
$rest = $items | Where-Object { $_.iso -ne 'PK' } | Sort-Object { $_.name }
$ordered = @($pk) + $rest
$json = ($ordered | ConvertTo-Json -Compress)
$js = @"
/* Auto-generated country dial codes */
window.EDUPORTAL_COUNTRIES = $json;

window.detectDefaultCountryIso = function () {
  try {
    const tz = Intl.DateTimeFormat().resolvedOptions().timeZone || '';
    if (tz === 'Asia/Karachi') return 'PK';
  } catch (e) {}
  const lang = (navigator.language || navigator.userLanguage || '').toLowerCase();
  if (lang === 'en-pk' || lang.endsWith('-pk') || lang === 'ur' || lang === 'ur-pk') return 'PK';
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
  const opt = selectEl?.selectedOptions?.[0];
  return opt?.dataset?.dial || '92';
};
"@
[IO.File]::WriteAllText($outPath, $js)
Write-Host "Wrote $($ordered.Count) countries to $outPath"
