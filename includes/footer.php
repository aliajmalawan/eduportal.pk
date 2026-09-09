<?php
/** @var array<int, string> $extraBodyScripts */
$extraBodyScripts = $extraBodyScripts ?? [];
require __DIR__ . '/partials/footer.php';
require __DIR__ . '/partials/site-scripts.php';
foreach ($extraBodyScripts as $src): ?>
<script src="<?= ep_h(ep_asset_url($src)) ?>" defer></script>
<?php endforeach; ?>
<?php
$epTrackPage = $epTrackPage ?? basename($_SERVER['SCRIPT_NAME'] ?? 'unknown');
require __DIR__ . '/partials/public-track.php';
?>
</body>
</html>
