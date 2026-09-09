<?php
/**
 * Admin — Application Form (Task 7 follow-up).
 *
 * Controls the public job-application form (includes/partials/application-form.php)
 * end to end: every field's label/hint, whether it is Required, Optional or
 * Hidden, and lets the admin add brand-new custom fields or delete ones they
 * added earlier.
 *
 * This is the single source of truth for that form: both the rendered HTML
 * and the server-side validation in includes/careers.php read
 * ep_application_field_defs()/ep_application_fields(), so the form, the
 * validator and this screen can never disagree about what a field is called
 * or whether it is mandatory.
 *
 * The 12 built-in fields map to real columns on ep_job_applications, so their
 * key and type are fixed — only their label/hint/status can be edited here.
 * Custom fields have no dedicated column; their answers are stored together
 * as JSON in ep_job_applications.extra_fields (see ep_careers_extra_fields_display()).
 */
require_once __DIR__ . '/includes/layout.php';
global $m;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cms_require_permission('applications.manage');
    if (!cms_verify_csrf($_POST['_csrf'] ?? null)) {
        cms_flash('error', 'Invalid request token.');
        header('Location: application-form.php');
        exit;
    }

    $action = (string) ($_POST['action'] ?? 'save');
    // The per-row Delete button lives inside the same <form> as Save (a
    // second nested <form> would be invalid HTML), so it identifies itself
    // by which submit button was clicked rather than by a distinct action.
    if (!empty($_POST['delete_key'])) {
        $action = 'delete_field';
        $_POST['key'] = $_POST['delete_key'];
    }

    // --- Delete a custom field --------------------------------------------
    if ($action === 'delete_field') {
        $key = trim((string) ($_POST['key'] ?? ''));
        $custom = ep_application_custom_fields();

        if (!isset($custom[$key])) {
            cms_flash('error', 'That field could not be found (built-in fields cannot be deleted, only hidden).');
            header('Location: application-form.php');
            exit;
        }

        $label = $custom[$key]['label'];
        unset($custom[$key]);

        $list = [];
        foreach ($custom as $k => $def) {
            $list[] = ['key' => $k] + $def;
        }
        ep_careers_save_setting('application_custom_fields', json_encode($list, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        // Drop its saved required/optional/hidden state too, so it does not
        // linger if a field with the same key is ever added again.
        $states = json_decode((string) ep_setting('application_form_fields', ''), true);
        if (is_array($states) && isset($states[$key])) {
            unset($states[$key]);
            ep_careers_save_setting('application_form_fields', json_encode($states, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        cms_flash('success', '"' . $label . '" was deleted. Existing applications that answered it keep that answer on file.');
        header('Location: application-form.php');
        exit;
    }

    // --- Add a new custom field ---------------------------------------------
    if ($action === 'add_field') {
        $label = trim((string) ($_POST['new_label'] ?? ''));
        $type = (string) ($_POST['new_type'] ?? 'text');
        $hint = trim((string) ($_POST['new_hint'] ?? ''));
        $initialState = ep_careers_pick((string) ($_POST['new_state'] ?? 'optional'), ep_application_field_states(), 'optional');
        $types = ep_application_custom_field_types();

        if ($label === '') {
            cms_flash('error', 'Give the new field a label before adding it.');
            header('Location: application-form.php');
            exit;
        }
        if (!isset($types[$type])) {
            $type = 'text';
        }

        $options = [];
        if ($type === 'select') {
            foreach (preg_split('/\r\n|\r|\n/', (string) ($_POST['new_options'] ?? '')) as $line) {
                $line = trim($line);
                if ($line !== '') {
                    $options[] = $line;
                }
            }
            if (count($options) < 2) {
                cms_flash('error', 'A dropdown field needs at least two options, one per line.');
                header('Location: application-form.php');
                exit;
            }
        }

        // Key is generated from the label, never typed directly, so it is
        // always a safe array/HTML-name-friendly slug.
        $existingKeys = array_keys(ep_application_field_defs());
        $base = substr(cms_slugify($label) ?: 'field', 0, 60);
        $key = $base;
        $suffix = 2;
        while (in_array($key, $existingKeys, true)) {
            $key = $base . '-' . $suffix;
            $suffix++;
        }

        $custom = ep_application_custom_fields();
        $custom[$key] = ['label' => $label, 'hint' => $hint, 'type' => $type, 'options' => $options];

        $list = [];
        foreach ($custom as $k => $def) {
            $list[] = ['key' => $k] + $def;
        }
        ep_careers_save_setting('application_custom_fields', json_encode($list, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $states = json_decode((string) ep_setting('application_form_fields', ''), true);
        $states = is_array($states) ? $states : [];
        $states[$key] = $initialState;
        ep_careers_save_setting('application_form_fields', json_encode($states, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        cms_flash('success', '"' . $label . '" was added to the application form.');
        header('Location: application-form.php');
        exit;
    }

    // --- Reset every field's status back to default -------------------------
    if ($action === 'reset') {
        ep_careers_save_setting('application_form_fields', '');
        cms_flash('success', 'Application form reset to its default required/optional fields.');
        header('Location: application-form.php');
        exit;
    }

    // --- Save labels, hints and statuses for every field --------------------
    $states = ep_application_field_states();
    $postedState = (array) ($_POST['field'] ?? []);
    $postedLabel = (array) ($_POST['label'] ?? []);
    $postedHint = (array) ($_POST['hint'] ?? []);
    $notes = [];
    $out = [];
    $labelOverrides = [];
    $customUpdated = ep_application_custom_fields();

    foreach (ep_application_field_defs() as $key => $def) {
        $state = ep_careers_pick((string) ($postedState[$key] ?? 'optional'), $states, 'optional');

        // Locks are enforced here regardless of what was posted — this is
        // the actual gate, the disabled controls in the form are only a hint.
        if (($def['lock'] ?? null) === 'no-optional' && $state === 'optional') {
            $state = 'required';
            $notes[] = $def['label'] . ' cannot be optional, so it was set to Required.';
        }
        $out[$key] = $state;

        $newLabel = trim((string) ($postedLabel[$key] ?? ''));
        $newHint = trim((string) ($postedHint[$key] ?? ''));

        if (!empty($def['core'])) {
            if ($newLabel !== '') {
                $labelOverrides[$key] = ['label' => $newLabel, 'hint' => $newHint];
            }
        } elseif (isset($customUpdated[$key])) {
            if ($newLabel !== '') {
                $customUpdated[$key]['label'] = $newLabel;
            }
            $customUpdated[$key]['hint'] = $newHint;
        }
    }

    // HR must be able to reach every applicant somehow.
    if ($out['email'] !== 'required' && $out['whatsapp'] !== 'required') {
        $out['whatsapp'] = 'required';
        $notes[] = 'Email and WhatsApp cannot both be non-required — WhatsApp was set to Required so HR can always contact an applicant.';
    }

    ep_careers_save_setting('application_form_fields', json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    ep_careers_save_setting('application_field_labels', json_encode($labelOverrides, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    $customList = [];
    foreach ($customUpdated as $k => $def) {
        $customList[] = ['key' => $k] + $def;
    }
    ep_careers_save_setting('application_custom_fields', json_encode($customList, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    if ($notes) {
        cms_flash('error', 'Saved, with adjustments: ' . implode(' ', $notes));
    } else {
        cms_flash('success', 'Application form updated.');
    }
    header('Location: application-form.php');
    exit;
}

$current = ep_application_fields();
$defs = ep_application_field_defs();
$types = ep_application_custom_field_types();

// Presentation only — which Bootstrap Icon glyph represents each field type
// in the redesigned field cards below.
$typeIcons = [
    'text' => 'input-cursor-text', 'textarea' => 'text-paragraph',
    'email' => 'envelope', 'tel' => 'telephone', 'url' => 'link-45deg',
    'number' => 'hash', 'select' => 'list-ul', 'checkbox' => 'check-square',
    'file' => 'paperclip',
];

// A live job to preview the form against, if one exists.
$m->orderBy('id', 'DESC');
$previewJob = $m->getOne('ep_jobs');
$previewUrl = $previewJob ? ep_job_url($previewJob) . '#apply' : ep_url('careers') . '?general=1#apply';

cms_page_start('Application Form', 'application-form', 'applications.manage', false, 'Edit, add or remove fields on the public job-application form');
?>

<style>
/* Application Form Builder — visual redesign only, scoped to this page's
   own delivered stylesheet so no other CMS page is affected. No selector
   here changes behaviour: every rule targets appearance (color, spacing,
   radius, shadow, typography) of the same controls that already existed. */
.ep-topbar-page-title{ font-size:1.4rem; letter-spacing:-.02em; }
.ep-topbar-page-sub{ font-size:.85rem; }

.af-wrap{ --af-orange:#f28c28; --af-orange-dark:#d97a1f; }

.af-info{
  background:linear-gradient(135deg,#fff8f0,#fffdfb);
  border:1px solid #fde3c4;
  border-left:4px solid var(--af-orange);
  border-radius:14px;
  padding:1rem 1.25rem;
  box-shadow:0 1px 2px rgba(15,23,42,.04),0 8px 24px rgba(15,23,42,.05);
}
.af-info i{ color:var(--af-orange); font-size:1.15rem; }
.af-info, .af-info p{ font-size:.875rem; line-height:1.65; color:#475569; margin:0; }
.af-info strong{ color:#1e293b; }

.af-wrap .card.ep-card{ border-radius:16px; box-shadow:0 1px 2px rgba(15,23,42,.04),0 10px 28px rgba(15,23,42,.05); }
.af-wrap .card-header{ padding:1.15rem 1.4rem; }
.af-wrap .card-header h5{ font-size:1.05rem; letter-spacing:-.01em; font-weight:700; }
.af-wrap .card-header small{ font-size:.8rem; }

.af-field-list{ display:flex; flex-direction:column; gap:.85rem; padding:1.25rem; min-width:0; }

.af-card{
  display:flex; gap:.9rem; align-items:flex-start; min-width:0;
  background:#fff; border:1px solid #e6e9ef; border-radius:12px;
  padding:1.1rem 1.25rem;
  transition:box-shadow .15s ease, border-color .15s ease, transform .15s ease;
}
.af-card:hover{ box-shadow:0 8px 22px rgba(15,23,42,.08); border-color:#e2c9a8; transform:translateY(-1px); }

.af-card-icon{
  flex:0 0 auto; width:38px; height:38px; border-radius:10px;
  background:#fff4e6; color:var(--af-orange); display:flex; align-items:center; justify-content:center;
  font-size:1.05rem;
}
.af-card.af-core .af-card-icon{ background:#f1f5f9; color:#64748b; }

.af-card-body{ flex:1 1 auto; min-width:0; display:flex; flex-direction:column; gap:.5rem; }

.af-card-row1{ display:flex; align-items:center; gap:.75rem; flex-wrap:wrap; }
.af-label-input{
  flex:1 1 220px; min-width:0; border:1px solid transparent; background:transparent;
  font-size:.98rem; font-weight:650; color:#0f172a; padding:.3rem .5rem; border-radius:8px;
  transition:border-color .15s, background .15s;
}
.af-label-input:hover{ background:#f8fafc; border-color:#e6e9ef; }
.af-label-input:focus{ background:#fff; border-color:#f6b477; box-shadow:0 0 0 3px rgba(242,140,40,.15); outline:none; }

.af-hint-input{
  border:1px solid transparent; background:transparent; width:100%;
  font-size:.8125rem; color:#64748b; padding:.25rem .5rem; border-radius:8px;
  transition:border-color .15s, background .15s;
}
.af-hint-input:hover{ background:#f8fafc; border-color:#e6e9ef; }
.af-hint-input:focus{ background:#fff; border-color:#f6b477; box-shadow:0 0 0 3px rgba(242,140,40,.12); outline:none; color:#334155; }

.af-note{ font-size:.75rem; padding:.05rem .5rem; }
.af-note.af-lock{ color:#b45309; }
.af-note.af-options{ color:#64748b; }

.af-card-footer{
  display:flex; align-items:center; justify-content:space-between; gap:.75rem; flex-wrap:wrap;
  padding-top:.5rem; margin-top:.15rem; border-top:1px dashed #eef1f5;
}
.af-card-footer-left{ display:flex; align-items:center; gap:.65rem; flex-wrap:wrap; min-width:0; }

.af-type-badge{
  display:inline-flex; align-items:center; gap:.35rem; font-size:.7rem; font-weight:700;
  text-transform:uppercase; letter-spacing:.04em; padding:.3rem .65rem; border-radius:999px;
  background:#f1f5f9; color:#475569; border:1px solid #e2e8f0; white-space:nowrap;
}
.af-type-badge.af-custom{ background:#fff4e6; color:#c2680f; border-color:#fde3c4; }
.af-custom-tag{ font-size:.7rem; color:#c2680f; font-weight:600; }

.af-status-group{ display:inline-flex; max-width:100%; min-width:0; border-radius:10px; overflow:hidden; border:1px solid #e6e9ef; }
.af-status-group .btn{
  border-radius:0 !important; border:none !important; font-size:.78rem; font-weight:600;
  padding:.4rem .85rem; color:#64748b !important; background:#fff !important; min-width:0; white-space:nowrap;
  box-shadow:none !important; transition:background .12s,color .12s;
}
.af-status-group .btn + .btn{ border-left:1px solid #e6e9ef !important; }

/* Unselected: quiet neutral hover. */
.af-status-group .btn:hover{ background:#f1f5f9 !important; color:#334155 !important; }

/* Selected: keep its own colour on hover too, only a touch darker — never
   falls back to the plain unselected hover above. */
.af-status-group .btn-check:checked + .btn-outline-danger{ background:var(--af-orange) !important; color:#fff !important; }
.af-status-group .btn-check:checked + .btn-outline-danger:hover{ background:var(--af-orange-dark) !important; color:#fff !important; }
.af-status-group .btn-check:checked + .btn-outline-primary,
.af-status-group .btn-check:checked + .btn-outline-secondary{ background:#e2e8f0 !important; color:#1e293b !important; }
.af-status-group .btn-check:checked + .btn-outline-primary:hover,
.af-status-group .btn-check:checked + .btn-outline-secondary:hover{ background:#cbd5e1 !important; color:#1e293b !important; }
.af-status-group .btn-check:disabled + .btn{ opacity:.45; cursor:not-allowed; }
.af-status-group .btn-check:disabled + .btn:hover{ background:#fff !important; color:#64748b !important; }

/* Remove — replaces "Hidden" for admin-added custom fields, since those can
   really be deleted (built-in fields only ever get hidden, never deleted). */
.af-status-group .af-remove-btn{ color:#b91c1c !important; }
.af-status-group .af-remove-btn:hover{ background:#fef2f2 !important; color:#dc2626 !important; }

.af-delete-btn{
  border-radius:8px !important; width:34px; height:34px; padding:0 !important;
  display:inline-flex; align-items:center; justify-content:center;
  border-color:#fecaca !important; color:#dc2626 !important; background:#fff !important;
}
.af-delete-btn:hover{ background:#fef2f2 !important; }

.af-actions{ display:flex; gap:.65rem; flex-wrap:wrap; align-items:center; }
.af-actions .btn-primary{ background:var(--af-orange) !important; border-color:var(--af-orange) !important; color:#fff !important; border-radius:10px; padding:.6rem 1.35rem; font-weight:600; box-shadow:0 4px 14px rgba(242,140,40,.28); }
.af-actions .btn-primary:hover{ background:var(--af-orange-dark) !important; border-color:var(--af-orange-dark) !important; }
.af-actions .btn-outline-secondary{
  background:#fff !important; color:#334155 !important; border:1px solid #cbd5e1 !important;
  border-radius:10px; padding:.6rem 1.25rem; font-weight:600;
}
.af-actions .btn-outline-secondary:hover{ background:#f8fafc !important; border-color:#94a3b8 !important; color:#1e293b !important; }

.af-reset-link{
  background:transparent !important; color:#64748b !important; border:none !important;
  padding:0 !important; font-size:.8125rem; font-weight:500; text-decoration:underline; text-underline-offset:2px;
  box-shadow:none !important;
}
.af-reset-link:hover{ background:transparent !important; color:var(--af-orange) !important; }

.af-add-card .form-label{ font-size:.8rem; font-weight:600; color:#334155; }
.af-add-card .form-control, .af-add-card .form-select{ border-radius:9px; border-color:#e2e8f0; }
.af-add-card .form-control:focus, .af-add-card .form-select:focus{ border-color:#f6b477; box-shadow:0 0 0 3px rgba(242,140,40,.12); }
.af-add-card .btn-primary{ background:var(--af-orange); border-color:var(--af-orange); border-radius:10px; font-weight:600; box-shadow:0 4px 14px rgba(242,140,40,.25); }
.af-add-card .btn-primary:hover{ background:var(--af-orange-dark); border-color:var(--af-orange-dark); }

@media (max-width:991.98px){
  .af-card-row1{ flex-direction:column; align-items:stretch; }
  .af-label-input{ flex:0 0 auto; width:100%; }
}

@media (max-width:767.98px){
  .af-field-list{ padding:1rem; gap:.7rem; }
  .af-card{ padding:1rem; }
  .af-card-footer{ flex-direction:column; align-items:stretch; }
  .af-card-footer-left{ justify-content:space-between; }
  .af-status-group{ width:100%; }
  .af-status-group .btn{ flex:1 1 0; padding-left:.5rem; padding-right:.5rem; font-size:.72rem; }
}

@media (max-width:479.98px){
  .af-card{ flex-direction:column; }
  .af-card-icon{ margin-bottom:.15rem; }
  .af-actions{ flex-direction:column; align-items:stretch; }
  .af-actions .btn{ width:100%; text-align:center; }
}
</style>

<div class="af-wrap">

<div class="alert alert-info af-info d-flex align-items-start gap-2 mb-4">
  <i class="bi bi-info-circle-fill mt-1"></i>
  <div>
    <strong>This is the only place the application form is configured.</strong>
    The public form at <code>/careers/&lt;job&gt;#apply</code> and the server that checks each
    submission both read this configuration, so a field marked Required here is enforced on both
    sides — an applicant can never slip through with a field the form itself says is mandatory.
  </div>
</div>

<form method="post">
  <input type="hidden" name="_csrf" value="<?= ep_h(cms_csrf()) ?>">
  <input type="hidden" name="action" value="save">

  <div class="card ep-card shadow-sm border-0 mb-4">
    <div class="card-header bg-white border-bottom py-3 px-4">
      <h5 class="mb-0 fw-semibold">Fields</h5>
      <small class="text-muted">Edit a field's label or hint text directly below it. Required fields must be filled in to submit, Optional fields may be left blank, Hidden fields are removed from the form entirely.</small>
    </div>
    <div class="card-body p-0">
      <div class="af-field-list">
        <?php foreach ($defs as $key => $def):
          $state = $current[$key] ?? 'optional';
          $locked = $def['lock'] ?? null;
          $isCore = !empty($def['core']);
          $icon = $typeIcons[$def['type']] ?? 'question-circle';
        ?>
        <div class="af-card<?= $isCore ? ' af-core' : '' ?>">
          <div class="af-card-icon"><i class="bi bi-<?= ep_h($icon) ?>"></i></div>
          <div class="af-card-body">
            <div class="af-card-row1">
              <input type="text" class="af-label-input" name="label[<?= ep_h($key) ?>]" value="<?= ep_h($def['label']) ?>" maxlength="150" placeholder="Label">
              <span class="af-type-badge<?= $isCore ? '' : ' af-custom' ?>">
                <?= ep_h($types[$def['type']] ?? ucfirst($def['type'])) ?>
              </span>
            </div>
            <input type="text" class="af-hint-input" name="hint[<?= ep_h($key) ?>]" value="<?= ep_h($def['hint']) ?>" maxlength="255" placeholder="Hint text shown under the field (optional)">
            <?php if ($locked === 'no-optional'): ?>
            <div class="af-note af-lock"><i class="bi bi-lock-fill me-1"></i>Cannot be set to Optional</div>
            <?php endif; ?>
            <?php if (!empty($def['options'])): ?>
            <div class="af-note af-options">Options: <?= ep_h(implode(', ', $def['options'])) ?></div>
            <?php endif; ?>

            <div class="af-card-footer">
              <div class="af-card-footer-left">
                <div class="btn-group af-status-group" role="group" aria-label="Status for <?= ep_h($def['label']) ?>">
                  <?php
                  // Custom fields can really be deleted, so their third
                  // option is a Remove button, not a Hidden radio. Built-in
                  // fields have no delete path (real database columns other
                  // code depends on), so they keep Required/Optional/Hidden.
                  $radioOptions = $isCore ? ep_application_field_states() : ['required', 'optional'];
                  foreach ($radioOptions as $option):
                    $disabled = ($locked === 'no-optional' && $option === 'optional');
                    $inputId = 'field-' . $key . '-' . $option;
                  ?>
                  <input type="radio" class="btn-check" name="field[<?= ep_h($key) ?>]" id="<?= ep_h($inputId) ?>"
                         value="<?= ep_h($option) ?>" autocomplete="off"
                         <?= $state === $option ? ' checked' : '' ?><?= $disabled ? ' disabled' : '' ?>>
                  <label class="btn btn-sm <?= $option === 'required' ? 'btn-outline-danger' : ($option === 'hidden' ? 'btn-outline-secondary' : 'btn-outline-primary') ?>" for="<?= ep_h($inputId) ?>">
                    <?= ep_h(ucfirst($option)) ?>
                  </label>
                  <?php endforeach; ?>
                  <?php if (!$isCore): ?>
                  <button type="submit" name="delete_key" value="<?= ep_h($key) ?>" class="btn btn-sm af-remove-btn"
                          onclick="return confirm('Remove the field \'<?= ep_h(addslashes($def['label'])) ?>\'? It will disappear from the live form immediately. Applications already received keep their answer on file.');"
                          title="Remove this field from the form" aria-label="Remove this field from the form">
                    Remove
                  </button>
                  <?php endif; ?>
                </div>
                <?php if (!$isCore): ?>
                <span class="af-custom-tag">Custom field</span>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="d-flex gap-2 af-actions">
    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save</button>
    <a href="<?= ep_h($previewUrl) ?>" target="_blank" rel="noopener" class="btn btn-outline-secondary">
      <i class="bi bi-box-arrow-up-right me-1"></i>Preview the live form
    </a>
  </div>
</form>

<form method="post" class="mt-3" onsubmit="return confirm('Reset every field\'s status to its default Required/Optional state? This does not delete custom fields.');">
  <input type="hidden" name="_csrf" value="<?= ep_h(cms_csrf()) ?>">
  <input type="hidden" name="action" value="reset">
  <button type="submit" class="btn btn-link text-muted p-0 af-reset-link"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset field statuses to defaults</button>
</form>

<div class="card ep-card shadow-sm border-0 mt-4 af-add-card">
  <div class="card-header bg-white border-bottom py-3 px-4">
    <h5 class="mb-0 fw-semibold">Add a field</h5>
    <small class="text-muted">Adds a brand-new question to the application form. Its answers are saved with every application and shown on the application's detail page.</small>
  </div>
  <div class="card-body p-4">
    <form method="post" class="row g-3">
      <input type="hidden" name="_csrf" value="<?= ep_h(cms_csrf()) ?>">
      <input type="hidden" name="action" value="add_field">

      <div class="col-md-5">
        <label class="form-label">Label</label>
        <input type="text" class="form-control" name="new_label" maxlength="150" placeholder="e.g. Portfolio link" required>
      </div>

      <div class="col-md-3">
        <label class="form-label">Type</label>
        <select class="form-select" name="new_type">
          <?php foreach ($types as $typeKey => $typeLabel): ?>
          <option value="<?= ep_h($typeKey) ?>"><?= ep_h($typeLabel) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-4">
        <label class="form-label">Initial status</label>
        <select class="form-select" name="new_state">
          <option value="optional">Optional</option>
          <option value="required">Required</option>
          <option value="hidden">Hidden</option>
        </select>
      </div>

      <div class="col-md-6">
        <label class="form-label">Hint text (optional)</label>
        <input type="text" class="form-control" name="new_hint" maxlength="255" placeholder="Shown under the field on the form">
      </div>

      <div class="col-md-6">
        <label class="form-label">Options — Dropdown fields only, one per line</label>
        <textarea class="form-control" name="new_options" rows="2" placeholder="e.g.&#10;Morning&#10;Evening"></textarea>
      </div>

      <div class="col-12">
        <button type="submit" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add field</button>
      </div>
    </form>
  </div>
</div>

</div><!-- /.af-wrap -->

<?php cms_page_end(); ?>
