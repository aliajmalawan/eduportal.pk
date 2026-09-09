/**
 * Case study modal — TinyMCE rich editor
 */
(function () {
  'use strict';

  var editorInstance = null;
  var TINYMCE_SRC    = 'https://cdn.jsdelivr.net/npm/tinymce@7.6.0/tinymce.min.js';
  var tinymceLoading = null;

  function loadTinyMCE() {
    if (window.tinymce) return Promise.resolve(window.tinymce);
    if (tinymceLoading)  return tinymceLoading;
    tinymceLoading = new Promise(function (resolve, reject) {
      var s = document.createElement('script');
      s.src = TINYMCE_SRC;
      s.referrerPolicy = 'origin';
      s.onload  = function () { resolve(window.tinymce); };
      s.onerror = reject;
      document.head.appendChild(s);
    });
    return tinymceLoading;
  }

  function destroyEditor() {
    if (editorInstance && window.tinymce) {
      window.tinymce.remove('#caseStudyEditor');
      editorInstance = null;
    }
  }

  function initEditor() {
    if (!document.getElementById('caseStudyEditor') || editorInstance) return;

    loadTinyMCE().then(function (tinymce) {
      if (!document.getElementById('caseStudyEditor')) return;
      tinymce.init({
        selector: '#caseStudyEditor',
        base_url: 'https://cdn.jsdelivr.net/npm/tinymce@7.6.0',
        suffix: '.min',
        height: 400,
        min_height: 300,
        max_height: 600,
        menubar: false,
        statusbar: true,
        branding: false,
        promotion: false,
        resize: true,
        plugins: 'lists advlist link autolink code table autoresize',
        toolbar: 'undo redo | blocks | bold italic underline | bullist numlist blockquote | link table | removeformat code',
        block_formats: 'Paragraph=p; Heading 2=h2; Heading 3=h3',
        paste_as_text: false,
        paste_merge_formats: true,
        convert_urls: false,
        relative_urls: false,
        content_style: [
          'body { font-family: Inter, system-ui, sans-serif; font-size: 15px; line-height: 1.75; color: #475569; }',
          'h2 { font-size: 1.375rem; font-weight: 700; color: #0f172a; margin: 1.5rem 0 0.75rem; }',
          'h3 { font-size: 1.125rem; font-weight: 700; color: #0f172a; margin: 1.25rem 0 0.5rem; }',
          'blockquote { border-left: 4px solid #f28c28; padding: 1rem 1.25rem; margin: 1.5rem 0; background: #fff7ed; border-radius: 0 12px 12px 0; font-style: italic; color: #0f172a; }',
          'ul, ol { padding-left: 1.25rem; margin: 0 0 1rem; }',
          'li { margin-bottom: 0.35rem; }',
          'table { width: 100%; border-collapse: collapse; margin: 1.25rem 0; font-size: 0.9375rem; border: 1px solid #e8eaed; }',
          'thead tr { background: #f28c28; }',
          'thead th { padding: 0.75rem 1rem; font-weight: 600; text-align: left; font-size: 0.875rem; color: #fff; border: none; }',
          'tbody tr { border-bottom: 1px solid #e8eaed; }',
          'tbody tr:nth-child(even) { background: #f9fafb; }',
          'td { padding: 0.7rem 1rem; color: #666; vertical-align: top; }',
          'th, td { border-right: 1px solid #e8eaed; }',
          'th:last-child, td:last-child { border-right: none; }',
        ].join('\n'),
        setup: function (editor) {
          editorInstance = editor;
          editor.on('change input undo redo', function () { editor.save(); });
        },
      });
    }).catch(function () {
      console.warn('TinyMCE failed to load — plain textarea remains.');
    });
  }

  function syncEditor() {
    if (window.tinymce) window.tinymce.triggerSave();
  }

  function init() {
    var modalEl = document.getElementById('cmsRecordModal');
    var form    = document.getElementById('cmsRecordForm');
    if (!modalEl || !form) return;

    modalEl.addEventListener('shown.bs.modal', function () { initEditor(); });
    modalEl.addEventListener('hidden.bs.modal', function () { destroyEditor(); });
    form.addEventListener('submit', function () { syncEditor(); });

    document.querySelectorAll('[data-cms-form-mode="add"]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        destroyEditor();
        setTimeout(initEditor, 300);
      });
    });

    if (modalEl.getAttribute('data-auto-open') === '1') {
      setTimeout(initEditor, 400);
    }
  }

  document.addEventListener('DOMContentLoaded', init);
})();
