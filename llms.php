<?php
/**
 * llms.txt — served at /llms.txt (see the rewrite in .htaccess).
 *
 * A plain-text summary of the site for AI/LLM tools, following the llms.txt
 * convention. Generated rather than stored as a static file so the client
 * count, the "as of" date and the contact details stay driven by the Admin
 * Panel: Task 4 made those settings-editable specifically so they are never
 * hardcoded, and a static file would silently go stale the moment someone
 * updated them in Settings.
 */
require_once __DIR__ . '/includes/cms.php';

header('Content-Type: text/plain; charset=UTF-8');

$base = 'https://eduportal.pk';
$siteName = ep_setting('site_name', 'EduPortal');
$clients = ep_site_metric('total_clients');
$asOf = trim((string) ep_setting('metrics_updated_date', ''));

// Real contact details, from the same Contact Items the site itself uses.
$email = '';
$phones = [];
$address = '';
foreach (ep_get_contact_items(false) as $item) {
    $val = trim((string) ($item['value'] ?? ''));
    if ($val === '') {
        continue;
    }
    switch ($item['item_type']) {
        case 'email':
            if ($email === '') { $email = $val; }
            break;
        case 'phone':
        case 'whatsapp':
            $phones[] = $val;
            break;
        case 'address':
            if ($address === '') { $address = trim(preg_replace('/\s*\R\s*/', ', ', $val)); }
            break;
    }
}

$reach = $clients !== '' ? "Serving {$clients} institutions" : 'Serving institutions across Pakistan';
if ($asOf !== '') {
    $reach .= " as of {$asOf}";
}

echo "# {$siteName}\n\n";
echo "> School management ERP software for educational institutions in Pakistan.\n";
if ($address !== '') {
    echo "> Based at {$address}.\n";
}
echo "> {$reach}.\n\n";

echo "## Modules\n\n";
foreach ([
    'Student Information Management',
    'Fee Management and Voucher Printing',
    'Attendance Management',
    'Examination and Result Management',
    'SMS and WhatsApp Communication',
    'Parent Mobile App',
    'Teacher Mobile App',
] as $module) {
    echo "- {$module}\n";
}
echo "\n";

echo "## Key Pages\n\n";
foreach ([
    'Features' => '/features',
    'Pricing' => '/pricing',
    'Case Studies' => '/case-studies',
    'Reviews' => '/reviews.php',
    'Careers' => '/careers',
    'Documentation' => '/docs',
    'Contact' => '/contact.php',
] as $label => $path) {
    echo "- {$label}: {$base}{$path}\n";
}
echo "\n";

echo "## Contact\n\n";
if ($email !== '') {
    echo "Email: {$email}\n";
}
if ($phones) {
    echo 'Phone: ' . implode(', ', $phones) . "\n";
}
if ($address !== '') {
    echo "Address: {$address}\n";
}
