<script>
document.addEventListener('DOMContentLoaded', () => {
  if (typeof lucide !== 'undefined') lucide.createIcons();
  // Navigation behaviour lives in js/nav.js (sticky state, aria, Escape,
  // focus handling and the scroll lock).
});
</script>
<?php // Shared navigation and scroll-reveal behaviour. ?>
<script src="<?= ep_h(ep_asset_url('js/nav.js')) ?>" defer></script>
<script src="<?= ep_h(ep_asset_url('js/reveal.js')) ?>" defer></script>
<?php // GA4 conversion events (demo request, contact click, job application). ?>
<script src="<?= ep_h(ep_asset_url('js/conversions.js')) ?>" defer></script>
