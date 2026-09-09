/**
 * EduPortal CMS — sidebar, charts, modal helpers
 */
(function () {
  'use strict';

  /* ── Sidebar ── */
  function initSidebar() {
    var sidebar = document.getElementById('epSidebar');
    var overlay = document.getElementById('epSidebarOverlay');
    var toggle  = document.getElementById('epSidebarToggle');

    function close() {
      sidebar && sidebar.classList.remove('show');
      overlay && overlay.classList.remove('show');
      document.body.classList.remove('ep-sidebar-open');
    }
    function open() {
      sidebar && sidebar.classList.add('show');
      overlay && overlay.classList.add('show');
      document.body.classList.add('ep-sidebar-open');
    }

    toggle  && toggle.addEventListener('click', function () {
      sidebar && sidebar.classList.contains('show') ? close() : open();
    });
    overlay && overlay.addEventListener('click', close);
    window.addEventListener('resize', function () { if (window.innerWidth >= 992) close(); });
  }

  /* ── Chart defaults ── */
  function chartDefaults() {
    if (typeof Chart === 'undefined') return;
    Chart.defaults.font.family  = "'Inter', system-ui, sans-serif";
    Chart.defaults.font.size    = 12;
    Chart.defaults.color        = '#64748b';
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.legend.labels.boxWidth = 8;
    Chart.defaults.plugins.legend.labels.padding  = 16;
    Chart.defaults.animation.duration = 800;
    Chart.defaults.animation.easing   = 'easeInOutQuart';
  }

  /* ── Dashboard charts ── */
  window.epInitDashboardCharts = function (config) {
    if (typeof Chart === 'undefined' || !config) return;
    chartDefaults();

    /* Visitor line chart */
    var visitorEl = document.getElementById('chartVisitors');
    if (visitorEl && config.visitors) {
      var visCtx = visitorEl.getContext('2d');
      var visGrad = visCtx.createLinearGradient(0, 0, 0, 200);
      visGrad.addColorStop(0, 'rgba(37,99,235,0.18)');
      visGrad.addColorStop(1, 'rgba(37,99,235,0)');
      new Chart(visitorEl, {
        type: 'line',
        data: {
          labels: config.visitors.labels,
          datasets: [{
            label: 'Unique visitors',
            data: config.visitors.data,
            borderColor: '#2563eb',
            backgroundColor: visGrad,
            fill: true,
            tension: 0.45,
            borderWidth: 2.5,
            pointRadius: 4,
            pointHoverRadius: 7,
            pointBackgroundColor: '#fff',
            pointBorderColor: '#2563eb',
            pointBorderWidth: 2,
          }],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: {
              backgroundColor: '#0f172a',
              titleColor: '#94a3b8',
              bodyColor: '#fff',
              padding: 12,
              cornerRadius: 10,
              callbacks: {
                label: function (ctx) { return '  ' + ctx.parsed.y + ' visitors'; }
              }
            }
          },
          scales: {
            x: { grid: { display: false }, ticks: { font: { size: 11 } } },
            y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { precision: 0, font: { size: 11 } } },
          },
        },
      });
    }

    /* Content doughnut chart */
    var contentEl = document.getElementById('chartContent');
    if (contentEl && config.content) {
      new Chart(contentEl, {
        type: 'doughnut',
        data: {
          labels: config.content.labels,
          datasets: [{
            data: config.content.data,
            backgroundColor: ['#2563eb', '#059669', '#7c3aed', '#f59e0b'],
            hoverBackgroundColor: ['#1d4ed8', '#047857', '#6d28d9', '#d97706'],
            borderWidth: 3,
            borderColor: '#fff',
            hoverOffset: 8,
          }],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          cutout: '70%',
          plugins: {
            legend: { position: 'right', labels: { font: { size: 12 } } },
            tooltip: {
              backgroundColor: '#0f172a',
              titleColor: '#94a3b8',
              bodyColor: '#fff',
              padding: 12,
              cornerRadius: 10,
            }
          },
        },
      });
    }

    /* Lead pipeline horizontal bar chart */
    var leadsEl = document.getElementById('chartLeads');
    if (leadsEl && config.leads) {
      new Chart(leadsEl, {
        type: 'bar',
        data: {
          labels: config.leads.labels,
          datasets: [{
            label: 'Leads',
            data: config.leads.data,
            backgroundColor: ['#3b82f6', '#f59e0b', '#06b6d4', '#22c55e', '#94a3b8'],
            hoverBackgroundColor: ['#2563eb', '#d97706', '#0891b2', '#16a34a', '#64748b'],
            borderRadius: 8,
            borderSkipped: false,
          }],
        },
        options: {
          indexAxis: 'y',
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: {
              backgroundColor: '#0f172a',
              titleColor: '#94a3b8',
              bodyColor: '#fff',
              padding: 12,
              cornerRadius: 10,
            }
          },
          scales: {
            x: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { precision: 0, font: { size: 11 } } },
            y: { grid: { display: false }, ticks: { font: { size: 12 } } },
          },
        },
      });
    }
  };

  /* ── Record modal ── */
  function initRecordModal() {
    var modalEl = document.getElementById('cmsRecordModal');
    if (!modalEl) return;

    var titleEl       = document.getElementById('cmsRecordModalLabel');
    var form          = document.getElementById('cmsRecordForm');
    var idInput       = form && (form.querySelector('[name="id"]') || form.querySelector('#cmsRecordUserId'));
    var actionInput   = form && form.querySelector('[name="action"]');
    var passwordInput = form && form.querySelector('[name="password"]');

    function setTitle(mode) {
      if (!titleEl) return;
      var addTitle  = modalEl.getAttribute('data-modal-title-add')  || 'Add record';
      var editTitle = modalEl.getAttribute('data-modal-title-edit') || 'Edit record';
      titleEl.textContent = mode === 'edit' ? editTitle : addTitle;
    }

    document.querySelectorAll('[data-cms-form-mode="add"]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        if (!form) return;
        form.reset();
        if (idInput) idInput.value = '0';
        if (actionInput && actionInput.dataset.createValue) actionInput.value = actionInput.dataset.createValue;
        if (passwordInput) { passwordInput.required = true; passwordInput.placeholder = ''; }
        setTitle('add');
      });
    });

    if (modalEl.getAttribute('data-auto-open') === '1') {
      var isEdit = (idInput && parseInt(idInput.value, 10) > 0) ||
        (actionInput && actionInput.value === (actionInput.dataset.updateValue || 'update'));
      if (passwordInput && isEdit) {
        passwordInput.required = false;
        passwordInput.placeholder = 'Leave blank to keep current';
      }
      setTitle(isEdit ? 'edit' : 'add');
      var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
      modal.show();
      if (window.history.replaceState) window.history.replaceState({}, '', window.location.pathname);
    }
  }

  /* ── Upload modal ── */
  function initUploadModal() {
    var uploadModal = document.getElementById('cmsUploadModal');
    if (!uploadModal || uploadModal.getAttribute('data-auto-open') !== '1') return;
    bootstrap.Modal.getOrCreateInstance(uploadModal).show();
    if (window.history.replaceState) window.history.replaceState({}, '', window.location.pathname);
  }

  document.addEventListener('DOMContentLoaded', function () {
    initSidebar();
    initRecordModal();
    initUploadModal();
  });
})();
