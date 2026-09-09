/**
 * EduPortal CMS — Manage Jobs
 *
 *  1. Bullet-point builder for Requirements / Responsibilities / Benefits.
 *     A text box plus an "Add" button; every added line becomes a row with a
 *     delete button. Each row carries a hidden input named e.g.
 *     requirements[], so PHP receives a plain array and stores it with
 *     json_encode() — no textarea anywhere in this flow.
 *
 *  2. Inline status dropdown in the jobs list, which submits its own row form
 *     as soon as the value changes.
 */
(function () {
  'use strict';

  /* ── Bullet-point builder ── */
  function initBulletBuilders() {
    document.querySelectorAll('[data-bullet-builder]').forEach(function (builder) {
      var field = builder.getAttribute('data-field');
      var input = builder.querySelector('[data-bullet-input]');
      var addBtn = builder.querySelector('[data-bullet-add]');
      var list = builder.querySelector('[data-bullet-list]');
      var empty = builder.querySelector('[data-bullet-empty]');
      if (!field || !input || !addBtn || !list) return;

      function refreshEmpty() {
        if (!empty) return;
        empty.hidden = list.children.length > 0;
      }

      function makeRow(text) {
        var row = document.createElement('li');
        row.className = 'cms-bullet-row';

        var handle = document.createElement('span');
        handle.className = 'cms-bullet-dot';
        handle.setAttribute('aria-hidden', 'true');

        var label = document.createElement('span');
        label.className = 'cms-bullet-text';
        // textContent, never innerHTML — the value is admin input and must
        // never be parsed as markup.
        label.textContent = text;

        var hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = field + '[]';
        hidden.value = text;

        var del = document.createElement('button');
        del.type = 'button';
        del.className = 'btn btn-sm btn-outline-danger cms-bullet-delete';
        del.innerHTML = '<i class="bi bi-trash"></i>';
        del.setAttribute('aria-label', 'Remove "' + text + '"');
        del.addEventListener('click', function () {
          row.remove();
          refreshEmpty();
        });

        row.appendChild(handle);
        row.appendChild(label);
        row.appendChild(hidden);
        row.appendChild(del);
        return row;
      }

      function addBullet() {
        var value = (input.value || '').trim();
        if (!value) {
          input.focus();
          return;
        }
        if (value.length > 300) value = value.slice(0, 300);
        list.appendChild(makeRow(value));
        input.value = '';
        input.focus();
        refreshEmpty();
      }

      addBtn.addEventListener('click', addBullet);

      // Enter adds a bullet instead of submitting the whole job form, which
      // would otherwise save a half-finished record.
      input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
          e.preventDefault();
          addBullet();
        }
      });

      // Rows rendered server-side (editing an existing job) need their delete
      // buttons wired up too.
      list.querySelectorAll('[data-bullet-existing]').forEach(function (row) {
        var del = row.querySelector('.cms-bullet-delete');
        if (!del) return;
        del.addEventListener('click', function () {
          row.remove();
          refreshEmpty();
        });
      });

      refreshEmpty();
    });
  }

  /* ── Inline status dropdown in the jobs list ── */
  function initInlineStatus() {
    document.querySelectorAll('[data-inline-status]').forEach(function (select) {
      select.addEventListener('change', function () {
        var form = select.closest('form');
        if (form) form.submit();
      });
    });
  }

  /* ── Clickable rows in the applications list ── */
  function initRowLinks() {
    document.querySelectorAll('[data-row-href]').forEach(function (row) {
      row.addEventListener('click', function (e) {
        // Let real controls inside the row keep their own behaviour.
        if (e.target.closest('a, button, select, input, textarea, label, form')) return;
        window.location.href = row.getAttribute('data-row-href');
      });
      row.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') window.location.href = row.getAttribute('data-row-href');
      });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    initBulletBuilders();
    initInlineStatus();
    initRowLinks();
  });
})();
