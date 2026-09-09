<script>
document.addEventListener('DOMContentLoaded', () => {
  if (typeof lucide !== 'undefined') lucide.createIcons();
  const navbar = document.getElementById('navbar');
  const navToggle = document.getElementById('navToggle');
  const mobileMenu = document.getElementById('mobileMenu');
  window.addEventListener('scroll', () => navbar?.classList.toggle('scrolled', window.scrollY > 20));
  navToggle?.addEventListener('click', () => {
    mobileMenu?.classList.toggle('open');
    const icon = navToggle.querySelector('[data-lucide]');
    if (icon) icon.setAttribute('data-lucide', mobileMenu?.classList.contains('open') ? 'x' : 'menu');
    lucide?.createIcons();
  });
  mobileMenu?.querySelectorAll('a').forEach(a => a.addEventListener('click', () => mobileMenu.classList.remove('open')));
});
</script>
<?php // GA4 conversion events (demo request, contact click, job application). ?>
<script src="<?= ep_h(ep_asset_url('js/conversions.js')) ?>" defer></script>
