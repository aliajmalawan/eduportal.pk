/* Product tour — scroll-driven zoom on the real dashboard screenshot.
 *
 * The markup is complete and readable without this file: every step renders
 * at full opacity and the frame shows the whole dashboard. All this adds is
 * the zoom and the dimming, so a script failure degrades to a plain list
 * rather than an empty section.
 */
(function () {
  var tour = document.getElementById('productTour');
  if (!tour) return;

  var img = document.getElementById('tourImage');
  var steps = Array.prototype.slice.call(tour.querySelectorAll('.tour-step'));
  if (!img || !steps.length) return;

  // Honour the visitor's motion preference: no zoom, no dimming, everything
  // legible at once. Matches the CSS guard rather than fighting it.
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)');
  if (reduce && reduce.matches) {
    steps.forEach(function (s) { s.classList.add('is-current'); });
    return;
  }

  if (!('IntersectionObserver' in window)) {
    steps.forEach(function (s) { s.classList.add('is-current'); });
    return;
  }

  function activate(step) {
    if (step.classList.contains('is-current')) return;
    steps.forEach(function (s) { s.classList.remove('is-current'); });
    step.classList.add('is-current');
    // translate() must come before scale(): transforms apply right to left,
    // so the image is scaled first and then panned by a percentage of its
    // own unscaled width, which is what the precomputed values assume.
    img.style.transform =
      'translate(' + (step.dataset.tx || '0%') + ', ' + (step.dataset.ty || '0%') + ')' +
      ' scale(' + (step.dataset.scale || '1') + ')';
  }

  // A narrow band across the middle of the viewport: a step becomes current
  // when it crosses the centre, so the zoom tracks reading position rather
  // than firing as soon as a step's top edge appears.
  var obs = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) activate(entry.target);
    });
  }, { rootMargin: '-45% 0px -45% 0px', threshold: 0 });

  steps.forEach(function (s) { obs.observe(s); });
})();
