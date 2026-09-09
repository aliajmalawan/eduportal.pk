/* Scroll reveal — one implementation for the whole site.
 *
 * Replaces three separate observers (an inline one in index.php,
 * js/feature-detail.js and js/cs-reveal.js) that used different thresholds and
 * different easing, so the same scroll gesture felt different from page to
 * page. Six of ten pages had no reveal at all.
 *
 * Purpose, not decoration: content entering in reading order is easier to
 * follow than a whole section appearing at once. Everything here is additive —
 * the markup is fully readable before this runs, and .js gates the parked
 * state so no-JS visitors never see hidden content.
 */
(function () {
  var SEL = '.reveal, .reveal-fd, .reveal-cs';
  var els = Array.prototype.slice.call(document.querySelectorAll(SEL));
  if (!els.length) return;

  function showAll() {
    els.forEach(function (el) { el.classList.add('visible'); });
  }

  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)');
  if ((reduce && reduce.matches) || !('IntersectionObserver' in window)) {
    showAll();
    return;
  }

  /* Stagger siblings that share a parent, so a row of cards arrives as one
   * movement read left to right rather than all at once. Capped at three
   * steps: beyond that the last item feels late rather than sequenced. */
  var order = new WeakMap();
  els.forEach(function (el) {
    var parent = el.parentNode;
    if (!parent) return;
    var n = order.get(parent) || 0;
    order.set(parent, n + 1);
    el.dataset.revealIndex = String(Math.min(n, 2));
  });

  var obs = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (!entry.isIntersecting) return;
      var el = entry.target;
      var delay = parseInt(el.dataset.revealIndex || '0', 10) * 60;
      if (delay) {
        setTimeout(function () { el.classList.add('visible'); }, delay);
      } else {
        el.classList.add('visible');
      }
      // Reveal once. Leaving elements observed meant the callback kept firing
      // for the life of the page, and re-entering a section could re-trigger
      // work for no visible gain.
      obs.unobserve(el);
    });
  }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

  els.forEach(function (el) { obs.observe(el); });
})();
