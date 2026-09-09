/* Subtle parallax — a background image drifts slightly slower than the
 * page scrolls, so it reads as sitting behind the content rather than
 * printed on it. Deliberately restrained: a few percent of scroll delta,
 * capped, never enough to separate the image from its frame.
 *
 * Opt-in via data-parallax="0.15" (the fraction of scroll delta to apply —
 * higher is more movement). No markup change needed anywhere else: a
 * failure to find any [data-parallax] elements is a silent no-op.
 */
(function () {
  var els = Array.prototype.slice.call(document.querySelectorAll('[data-parallax]'));
  if (!els.length) return;

  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)');
  if (reduce && reduce.matches) return;

  var items = els.map(function (el) {
    return { el: el, factor: parseFloat(el.dataset.parallax) || 0.15 };
  });

  var ticking = false;

  function update() {
    ticking = false;
    var vh = window.innerHeight;
    items.forEach(function (item) {
      var rect = item.el.getBoundingClientRect();
      // Only move while at least part of the element is on screen —
      // no work happens for anything above or below the viewport.
      if (rect.bottom < 0 || rect.top > vh) return;
      // Distance of the element's centre from the viewport's centre,
      // normalised to roughly [-1, 1]. This is what "slower than scroll"
      // actually means: the offset tracks position-in-viewport, not the
      // raw scroll position, so it settles at 0 whether the visitor
      // scrolled there or loaded straight onto it.
      var centre = rect.top + rect.height / 2 - vh / 2;
      var offset = Math.max(-60, Math.min(60, -centre * item.factor));
      item.el.style.transform = 'translate3d(0,' + offset.toFixed(1) + 'px,0)';
    });
  }

  function onScroll() {
    if (!ticking) {
      ticking = true;
      window.requestAnimationFrame(update);
    }
  }

  window.addEventListener('scroll', onScroll, { passive: true });
  window.addEventListener('resize', onScroll);
  update();
})();
