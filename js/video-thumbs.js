/**
 * Apply optimized EduPortal video thumbnails (fusion / pace) to testimonial cards.
 */
(function () {
  'use strict';

  var THUMB_480 = ['assets/thumbnail/fusion-480.jpg', 'assets/thumbnail/pace-480.jpg'];
  var THUMB_720 = ['assets/thumbnail/fusion-720.jpg', 'assets/thumbnail/pace-720.jpg'];
  var IMG_SIZES = '(min-width: 992px) 33vw, (min-width: 640px) 50vw, 100vw';

  function thumbImgHtml(index) {
    var i = index % 2;
    return (
      '<img src="' +
      THUMB_480[i] +
      '" srcset="' +
      THUMB_480[i] +
      ' 480w, ' +
      THUMB_720[i] +
      ' 720w" sizes="' +
      IMG_SIZES +
      '" alt="EduPortal school testimonial video thumbnail" width="480" height="320" loading="lazy" decoding="async">'
    );
  }

  function upgradePlayButton(el) {
    if (!el || el.querySelector('.play-pulse')) return;
    el.innerHTML = '<span class="play-pulse" aria-hidden="true"></span><i data-lucide="play"></i>';
    el.setAttribute('aria-hidden', 'true');
  }

  window.initEduportalVideoThumbs = function (root) {
    root = root || document;
    var selectors = ['.video-thumb', '.testimonial-bg', '.lp-video-bg'];
    var containers = root.querySelectorAll(selectors.join(', '));
    var index = 0;

    containers.forEach(function (thumb) {
      if (thumb.querySelector('img') || thumb.getAttribute('data-custom-thumb') === '1') return;
      thumb.removeAttribute('style');
      thumb.innerHTML = thumbImgHtml(index);
      index += 1;

      var card =
        thumb.closest('.video-card') ||
        thumb.closest('.testimonial-card') ||
        thumb.closest('.lp-video-card');
      if (!card) return;

      var play = card.querySelector('.video-play, .testimonial-play, .lp-video-play');
      upgradePlayButton(play);
    });

    if (typeof lucide !== 'undefined') lucide.createIcons();
  };
})();
