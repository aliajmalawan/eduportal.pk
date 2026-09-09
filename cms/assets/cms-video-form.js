/**
 * Video testimonial modal — type toggle + thumbnail preview
 */
(function () {
  'use strict';

  function toggleVideoType() {
    var select = document.getElementById('videoTypeSelect');
    var yt = document.getElementById('videoFieldsYoutube');
    var up = document.getElementById('videoFieldsUpload');
    if (!select || !yt || !up) return;
    var isUpload = select.value === 'upload';
    yt.classList.toggle('d-none', isUpload);
    up.classList.toggle('d-none', !isUpload);
    yt.setAttribute('aria-hidden', isUpload ? 'true' : 'false');
    up.setAttribute('aria-hidden', isUpload ? 'false' : 'true');
  }

  function bindThumbPreview() {
    var fileInput = document.getElementById('videoThumbFile');
    var preview = document.getElementById('videoThumbPreview');
    if (!fileInput || !preview || fileInput.dataset.bound === '1') return;
    fileInput.dataset.bound = '1';
    fileInput.addEventListener('change', function () {
      var file = fileInput.files && fileInput.files[0];
      if (!file) return;
      var reader = new FileReader();
      reader.onload = function () {
        preview.innerHTML =
          '<img src="' + reader.result + '" alt="" class="ep-thumb-preview-img">';
      };
      reader.readAsDataURL(file);
    });
  }

  function init() {
    var typeSelect = document.getElementById('videoTypeSelect');
    if (typeSelect) {
      typeSelect.addEventListener('change', toggleVideoType);
      toggleVideoType();
    }
    bindThumbPreview();

    var modal = document.getElementById('cmsRecordModal');
    if (modal) {
      modal.addEventListener('shown.bs.modal', toggleVideoType);
    }
  }

  document.addEventListener('DOMContentLoaded', init);
})();
