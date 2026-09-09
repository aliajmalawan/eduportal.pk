(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    /* navbar scroll state, hamburger toggle and mobile-menu wiring are
       already handled globally by includes/partials/site-scripts.php */

    document.querySelectorAll('.fd-faq-q').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var item = btn.closest('.fd-faq-item');
        var panel = item.querySelector('.fd-faq-a');
        var isOpen = item.classList.contains('is-open');
        document.querySelectorAll('.fd-faq-item.is-open').forEach(function (openItem) {
          openItem.classList.remove('is-open');
          openItem.querySelector('.fd-faq-a').style.maxHeight = '0';
          openItem.querySelector('.fd-faq-q').setAttribute('aria-expanded', 'false');
        });
        if (!isOpen) {
          item.classList.add('is-open');
          panel.style.maxHeight = panel.scrollHeight + 'px';
          btn.setAttribute('aria-expanded', 'true');
        }
      });
    });

    var obs = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) entry.target.classList.add('visible');
        });
      },
      { threshold: 0.08, rootMargin: '0px 0px -30px 0px' }
    );
    document.querySelectorAll('.reveal-fd').forEach(function (el) {
      obs.observe(el);
    });

    /* Get Started modal */
    var modal = document.getElementById('getStartedModal');
    var form = document.getElementById('getStartedForm');
    var countrySelect = document.getElementById('countryCode');
    var whatsappInput = document.getElementById('whatsapp');
    var whatsappFull = document.getElementById('whatsappFull');
    var savedScrollY = 0;

    var phonePlaceholders = {
      PK: '300 1234567',
      US: '555 123 4567',
      GB: '7700 900123',
      AE: '50 123 4567',
      SA: '50 123 4567',
      IN: '98765 43210',
    };

    function changeCountryCode() {
      var iso = countrySelect?.value || 'PK';
      var dial = window.getSelectedDialCode?.(countrySelect) || '92';
      if (whatsappInput) {
        whatsappInput.placeholder = phonePlaceholders[iso] || 'Phone number';
      }
      if (whatsappFull && whatsappInput) {
        var num = whatsappInput.value.replace(/\D/g, '');
        whatsappFull.value = num ? '+' + dial + num : '';
      }
    }

    function initCountrySelect() {
      if (!countrySelect || !window.populateCountryCodeSelect) return;
      var applyDefault = function (iso) {
        window.populateCountryCodeSelect(countrySelect, iso);
        changeCountryCode();
      };
      applyDefault(window.detectDefaultCountryIso?.() || 'PK');
      countrySelect.addEventListener('change', changeCountryCode);
      whatsappInput?.addEventListener('input', changeCountryCode);
    }

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

    function openModal(e) {
      e?.preventDefault();
      modal?.classList.add('open');
      modal?.setAttribute('aria-hidden', 'false');
      lockScroll();
      lucide?.createIcons();
      requestAnimationFrame(function () {
        document.getElementById('instituteName')?.focus();
      });
    }

    function closeModal() {
      modal?.classList.remove('open');
      modal?.setAttribute('aria-hidden', 'true');
      unlockScroll();
    }

    document.querySelectorAll('.js-open-modal').forEach(function (btn) {
      btn.addEventListener('click', openModal);
    });
    document.getElementById('modalCancel')?.addEventListener('click', closeModal);
    modal?.addEventListener('click', function (e) {
      if (e.target === modal) closeModal();
    });
    form?.addEventListener('submit', function (e) {
      e.preventDefault();
      changeCountryCode();
      if (!window.handleLeadFormSubmit) return;
      window.handleLeadFormSubmit(form, {
        onSuccess: closeModal,
        onReset: initCountrySelect,
      });
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && modal?.classList.contains('open')) closeModal();
    });

    initCountrySelect();
  });
})();
