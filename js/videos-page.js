/**
 * videos.php — filters, YouTube modal, uploaded HTML5 video playback
 */
(function () {
  'use strict';

  var DEFAULT_YT_ID = 'ar637Gcm3K0';

  function loadPlaybackMap() {
    var el = document.getElementById('epVideoPlaybackData');
    if (!el) return {};
    try {
      var data = JSON.parse(el.textContent || '{}');
      return data && typeof data === 'object' ? data : {};
    } catch (e) {
      return {};
    }
  }

  function resolveUploadSrc(card, playbackMap) {
    if (!card) return '';
    var id = card.getAttribute('data-video-id');
    if (id && playbackMap[id]) {
      return playbackMap[id];
    }
    var src = (card.getAttribute('data-video-src') || '').trim();
    if (src) return src;
    var rel = (card.getAttribute('data-video-path') || '').trim();
    if (rel && window.EP_SITE_BASE) {
      return String(window.EP_SITE_BASE).replace(/\/?$/, '/') + rel.replace(/^\//, '');
    }
    return '';
  }

  function cardHasUploadedVideo(card, playbackMap) {
    return resolveUploadSrc(card, playbackMap).length > 0;
  }

  document.addEventListener('DOMContentLoaded', function () {
    if (window.initEduportalVideoThumbs) {
      window.initEduportalVideoThumbs();
    }

    var playbackMap = loadPlaybackMap();

    var pills = document.querySelectorAll('.filter-pill');
    var cards = document.querySelectorAll('.video-card');
    pills.forEach(function (pill) {
      pill.addEventListener('click', function () {
        var filter = pill.dataset.filter;
        pills.forEach(function (p) {
          p.classList.toggle('active', p === pill);
          p.setAttribute('aria-pressed', p === pill ? 'true' : 'false');
        });
        cards.forEach(function (card) {
          var role = card.dataset.role;
          card.classList.toggle('is-hidden', filter !== 'all' && role !== filter);
        });
      });
    });

    var modal = document.getElementById('videoModal');
    var frame = document.getElementById('youtubeFrame');
    var player = document.getElementById('html5VideoPlayer');
    if (!modal) return;

    var savedScrollY = 0;
    function lockScroll() {
      savedScrollY = window.scrollY;
      document.body.classList.add('modal-open');
      document.body.style.top = '-' + savedScrollY + 'px';
    }
    function unlockScroll() {
      document.body.classList.remove('modal-open');
      document.body.style.top = '';
      window.scrollTo(0, savedScrollY);
    }

    function showYoutube(card) {
      if (player) {
        player.pause();
        player.removeAttribute('src');
        player.load();
        player.style.display = 'none';
      }
      if (!frame) return;
      var videoId = (card.getAttribute('data-youtube-id') || card.dataset.youtubeId || '').trim();
      if (!videoId) {
        videoId = DEFAULT_YT_ID;
      }
      var start = card.getAttribute('data-youtube-start') || card.dataset.youtubeStart || '0';
      frame.style.display = 'block';
      frame.src =
        'https://www.youtube.com/embed/' +
        encodeURIComponent(videoId) +
        '?start=' +
        encodeURIComponent(start) +
        '&autoplay=1&rel=0';
    }

    function clearVideoError() {
      var old = modal.querySelector('.ep-video-error');
      if (old) old.remove();
    }

    function showVideoError(src) {
      if (!player) return;
      player.style.display = 'none';
      clearVideoError();
      var ext = (src.split('?')[0].split('.').pop() || '').toLowerCase();
      var msg = ext === 'mov'
        ? 'MOV format is not supported in this browser. Please ask the admin to convert this video to MP4.'
        : 'This video format could not be played in your browser.';
      var div = document.createElement('div');
      div.className = 'ep-video-error';
      div.style.cssText = 'position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;background:#000;color:#fff;text-align:center;padding:2rem;gap:1rem;';
      div.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="opacity:.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z"/></svg>'
        + '<p style="margin:0;font-size:.9rem;opacity:.85;max-width:320px;">' + msg + '</p>'
        + '<a href="' + src + '" download style="color:#f28c28;font-size:.85rem;opacity:.9;">Download video</a>';
      var videoModal = document.querySelector('.video-modal');
      if (videoModal) videoModal.appendChild(div);
    }

    function showUpload(card) {
      if (frame) {
        frame.src = '';
        frame.style.display = 'none';
      }
      if (!player) return;
      clearVideoError();
      var src = resolveUploadSrc(card, playbackMap);
      if (!src) {
        showYoutube(card);
        return;
      }
      player.onerror = function () { showVideoError(src); };
      player.style.display = 'block';
      player.src = src;
      player.load();
      player.play().catch(function () {});
    }

    function openVideo(e) {
      e.preventDefault();
      var card = e.currentTarget;
      var isUpload =
        (card.getAttribute('data-video-type') || '') === 'upload' ||
        cardHasUploadedVideo(card, playbackMap);
      if (isUpload && cardHasUploadedVideo(card, playbackMap)) {
        showUpload(card);
      } else {
        showYoutube(card);
      }
      modal.classList.add('open');
      modal.setAttribute('aria-hidden', 'false');
      lockScroll();
    }

    function closeVideo() {
      modal.classList.remove('open');
      modal.setAttribute('aria-hidden', 'true');
      clearVideoError();
      if (frame) {
        frame.src = '';
        frame.style.display = 'none';
      }
      if (player) {
        player.onerror = null;
        player.pause();
        player.removeAttribute('src');
        player.load();
        player.style.display = 'none';
      }
      unlockScroll();
    }

    cards.forEach(function (card) {
      card.addEventListener('click', openVideo);
      card.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          openVideo(e);
        }
      });
      if (!card.hasAttribute('tabindex')) {
        card.setAttribute('tabindex', '0');
      }
    });

    document.getElementById('videoClose')?.addEventListener('click', closeVideo);
    modal.addEventListener('click', function (e) {
      if (e.target === modal) closeVideo();
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && modal.classList.contains('open')) closeVideo();
    });
  });
})();
