<?php

/**
 * Public job-application form — shared by career.php (a specific role) and
 * careers.php (general CV submission when no roles are open).
 *
 * Which fields are required, optional, or hidden is controlled from the admin
 * panel (Careers -> Application Form), not hard-coded here. Every field below
 * reads its state from ep_application_field_required() / _visible(), and
 * includes/careers.php validates against the exact same configuration — so
 * the form and the server-side check can never disagree about what is
 * mandatory. That mismatch is what used to let a "required" field through
 * unfilled, or reject a submission the form itself marked optional.
 *
 * Fully server-rendered and works with JavaScript disabled: it posts back to
 * the page it is on, which re-renders it with errors and the entered values.
 *
 * Element ids are prefixed "apply-" so they cannot clash with the Get-Started
 * demo modal rendered on the same page; the name attributes are the
 * ep_job_applications column names.
 *
 * @var array<string, mixed>|null $job          Role applied to, or null for a general CV
 * @var array<int, string>         $formErrors
 * @var array<string, string>      $formValues
 */

$job = $job ?? null;

$formErrors = $formErrors ?? [];

$formValues = $formValues ?? [];

$formHeading = $job ? 'Apply for this position' : 'Send us your CV';

$formIntro = $job
    ? 'Fill in the form below and attach your résumé. All applications are reviewed by our HR team.'
    : 'We keep general applications on file and get in touch as soon as a matching role opens.';

$siteName = ep_setting('site_name', 'EduPortal');

/**
 * Previously entered value for a field, so nothing is retyped after an error.
 */
$fv = static function (string $field, string $default = '') use ($formValues): string {
    return ep_h((string) ($formValues[$field] ?? $default));
};

/** Required/optional attribute + marker for one field, read from the admin config. */
$req = static function (string $field): string {
    return ep_application_field_required($field) ? ' required' : '';
};
$mark = static function (string $field): string {
    if (!ep_application_field_visible($field)) {
        return '';
    }
    return ep_application_field_required($field)
        ? ' <span class="req">*</span>'
        : ' <span class="apply-optional">(optional)</span>';
};
$vis = static function (string $field): bool {
    return ep_application_field_visible($field);
};

?>

<section class="apply-section" id="apply">

  <div class="container">

    <div class="apply-card">

      <h2><?= ep_h($formHeading) ?></h2>

      <p class="apply-intro"><?= ep_h($formIntro) ?></p>

      <?php if ($formErrors): ?>

      <div class="apply-alert apply-alert--error" role="alert">

        <strong>Your application was not submitted:</strong>

        <ul>

          <?php foreach ($formErrors as $error): ?>

          <li><?= ep_h($error) ?></li>

          <?php endforeach; ?>

        </ul>

      </div>

      <?php endif; ?>

      <form
        method="post"
        enctype="multipart/form-data"
        action="<?= ep_h(($job ? ep_job_url($job) : ep_careers_url()) . '#apply') ?>"
        class="apply-form"
        novalidate
      >

        <input
          type="hidden"
          name="_csrf"
          value="<?= ep_h(ep_careers_csrf_token()) ?>"
        >

        <input
          type="hidden"
          name="apply"
          value="1"
        >

        <input
          type="hidden"
          name="form_started"
          value="<?= (int) time() ?>"
        >

        <input
          type="hidden"
          name="source_page"
          value="<?= ep_h(basename($_SERVER['SCRIPT_NAME'] ?? '')) ?>"
        >

        <!-- Honeypot: hidden from people, irresistible to bots. Never remove. -->

        <div class="apply-hp" aria-hidden="true">

          <label for="apply-website">Website</label>

          <input
            type="text"
            id="apply-website"
            name="website"
            value=""
            tabindex="-1"
            autocomplete="off"
          >

        </div>

        <div class="apply-grid">

          <!-- 1 -->

          <?php if ($vis('full_name')): ?>
          <div class="apply-field">

            <label for="apply-full_name">
              Full name
              <?= $mark('full_name') ?>
            </label>

            <input
              type="text"
              id="apply-full_name"
              name="full_name"
              value="<?= $fv('full_name') ?>"
              maxlength="150"
              autocomplete="name"
              <?= $req('full_name') ?>
            >

          </div>
          <?php endif; ?>

          <!-- 2 -->

          <?php if ($vis('email')): ?>
          <div class="apply-field">

            <label for="apply-email">
              Email
              <?= $mark('email') ?>
            </label>

            <input
              type="email"
              id="apply-email"
              name="email"
              value="<?= $fv('email') ?>"
              maxlength="150"
              autocomplete="email"
              <?= $req('email') ?>
            >

          </div>
          <?php endif; ?>

          <!-- 3 -->

          <?php if ($vis('whatsapp')): ?>
          <div class="apply-field">

            <label for="apply-whatsapp">
              WhatsApp number
              <?= $mark('whatsapp') ?>
            </label>

            <input
              type="tel"
              id="apply-whatsapp"
              name="whatsapp"
              value="<?= $fv('whatsapp') ?>"
              maxlength="20"
              autocomplete="tel"
              placeholder="923001234567"
              <?= $req('whatsapp') ?>
            >

            <small class="apply-hint">
              Include your country code — this is how our HR team will contact you.
            </small>

          </div>
          <?php endif; ?>

          <!-- 4 -->

          <?php if ($vis('city')): ?>
          <div class="apply-field">

            <label for="apply-city">
              City
              <?= $mark('city') ?>
            </label>

            <input
              type="text"
              id="apply-city"
              name="city"
              value="<?= $fv('city') ?>"
              maxlength="100"
              autocomplete="address-level2"
              <?= $req('city') ?>
            >

          </div>
          <?php endif; ?>

          <!-- 5 -->

          <?php if ($vis('photo')): ?>
          <div class="apply-field">

            <label for="apply-photo">
              Choose Photo
              <?= $mark('photo') ?>
            </label>

            <input
              type="file"
              id="apply-photo"
              name="photo"
              accept=".jpg,.jpeg,.png,image/jpeg,image/png"
              <?= $req('photo') ?>
            >

            <small class="apply-hint">
              JPG or PNG · 2 MB maximum
            </small>

          </div>
          <?php endif; ?>

          <!-- 6 -->

          <?php if ($vis('resume')): ?>
          <div class="apply-field">

            <label for="apply-resume">
              Resume / CV
              <?= $mark('resume') ?>
            </label>

            <input
              type="file"
              id="apply-resume"
              name="resume"
              accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
              <?= $req('resume') ?>
            >

            <small class="apply-hint">
              PDF, DOC or DOCX · 5 MB maximum
            </small>

          </div>
          <?php endif; ?>

          <!-- 7 -->

          <?php if ($vis('years_experience')): ?>
          <div class="apply-field">

            <label for="apply-years_experience">
              Years of experience
              <?= $mark('years_experience') ?>
            </label>

            <input
              type="text"
              id="apply-years_experience"
              name="years_experience"
              value="<?= $fv('years_experience') ?>"
              maxlength="20"
              placeholder="e.g. 3 years"
              <?= $req('years_experience') ?>
            >

          </div>
          <?php endif; ?>

          <!-- 8 — full width, it needs the room -->

          <?php if ($vis('relevant_experience')): ?>
          <div class="apply-field apply-field--full">

            <label for="apply-relevant_experience">
              Relevant experience
              <?= $mark('relevant_experience') ?>
            </label>

            <textarea
              id="apply-relevant_experience"
              name="relevant_experience"
              rows="4"
              maxlength="5000"
              placeholder="Briefly describe the experience most relevant to <?= $job ? 'this role' : 'the work we do' ?>."
              <?= $req('relevant_experience') ?>
            ><?= $fv('relevant_experience') ?></textarea>

          </div>
          <?php endif; ?>

          <!-- 9 -->

          <?php if ($vis('expected_salary')): ?>
          <div class="apply-field">

            <label for="apply-expected_salary">
              Expected salary (<?= ep_h(ep_careers_salary_currency()) ?>)
              <?= $mark('expected_salary') ?>
            </label>

            <input
              type="text"
              id="apply-expected_salary"
              name="expected_salary"
              value="<?= $fv('expected_salary') ?>"
              maxlength="20"
              placeholder="e.g. 150000"
              inputmode="numeric"
              <?= $req('expected_salary') ?>
            >

            <small class="apply-hint">
              Monthly, in <?= ep_h(ep_careers_salary_currency()) ?>.
            </small>

          </div>
          <?php endif; ?>

          <!-- 10 -->

          <?php if ($vis('joining_time')): ?>
          <div class="apply-field">

            <label for="apply-joining_time">
              How soon can you join?
              <?= $mark('joining_time') ?>
            </label>

            <select
              id="apply-joining_time"
              name="joining_time"
              <?= $req('joining_time') ?>
            >

              <option value="">Please choose…</option>

              <?php foreach (ep_careers_joining_times() as $option): ?>

              <option
                value="<?= ep_h($option) ?>"
                <?= ($formValues['joining_time'] ?? '') === $option ? ' selected' : '' ?>
              >
                <?= ep_h($option) ?>
              </option>

              <?php endforeach; ?>

            </select>

          </div>
          <?php endif; ?>

          <!-- 11 -->

          <?php if ($vis('linkedin')): ?>
          <div class="apply-field">

            <label for="apply-linkedin">
              LinkedIn profile
              <?= $mark('linkedin') ?>
            </label>

            <input
              type="url"
              id="apply-linkedin"
              name="linkedin"
              value="<?= $fv('linkedin') ?>"
              maxlength="300"
              placeholder="https://linkedin.com/in/…"
              <?= $req('linkedin') ?>
            >

          </div>
          <?php endif; ?>

          <!-- Admin-added custom fields (Careers -> Application Form -> Add a field) -->

          <?php foreach (ep_application_custom_fields() as $customKey => $customDef):
            if (!$vis($customKey)) { continue; }
            $customId = 'apply-custom-' . $customKey;
            $customValue = (string) ($formValues['custom'][$customKey] ?? '');
            $full = $customDef['type'] === 'textarea';
          ?>
          <div class="apply-field<?= $full ? ' apply-field--full' : '' ?>">

            <label for="<?= ep_h($customId) ?>">
              <?= ep_h($customDef['label']) ?>
              <?= $mark($customKey) ?>
            </label>

            <?php if ($customDef['type'] === 'textarea'): ?>

            <textarea
              id="<?= ep_h($customId) ?>"
              name="custom[<?= ep_h($customKey) ?>]"
              rows="4"
              maxlength="3000"
              <?= $req($customKey) ?>
            ><?= ep_h($customValue) ?></textarea>

            <?php elseif ($customDef['type'] === 'select'): ?>

            <select
              id="<?= ep_h($customId) ?>"
              name="custom[<?= ep_h($customKey) ?>]"
              <?= $req($customKey) ?>
            >
              <option value="">Please choose…</option>
              <?php foreach ($customDef['options'] as $option): ?>
              <option value="<?= ep_h($option) ?>" <?= $customValue === $option ? ' selected' : '' ?>><?= ep_h($option) ?></option>
              <?php endforeach; ?>
            </select>

            <?php elseif ($customDef['type'] === 'checkbox'): ?>

            <div class="apply-consent" style="margin-top:0">
              <input
                type="checkbox"
                id="<?= ep_h($customId) ?>"
                name="custom[<?= ep_h($customKey) ?>]"
                value="1"
                <?= $customValue === '1' ? ' checked' : '' ?>
                <?= $req($customKey) ?>
              >
              <label for="<?= ep_h($customId) ?>"><?= ep_h($customDef['hint'] ?: $customDef['label']) ?></label>
            </div>

            <?php else: ?>

            <input
              type="<?= ep_h(in_array($customDef['type'], ['email', 'tel', 'url', 'number'], true) ? $customDef['type'] : 'text') ?>"
              id="<?= ep_h($customId) ?>"
              name="custom[<?= ep_h($customKey) ?>]"
              value="<?= ep_h($customValue) ?>"
              maxlength="190"
              <?= $req($customKey) ?>
            >

            <?php endif; ?>

            <?php if ($customDef['hint'] !== '' && $customDef['type'] !== 'checkbox'): ?>
            <small class="apply-hint"><?= ep_h($customDef['hint']) ?></small>
            <?php endif; ?>

          </div>
          <?php endforeach; ?>

        </div>

        <!-- 12 -->

        <?php if ($vis('consent')): ?>
        <div class="apply-consent">

          <input
            type="checkbox"
            id="apply-consent"
            name="consent"
            value="1"
            <?= ($formValues['consent'] ?? '') === '1' ? ' checked' : '' ?>
            <?= $req('consent') ?>
          >

          <label for="apply-consent">

            I agree that <?= ep_h($siteName) ?> may store my information for recruitment purposes.

            <?= $mark('consent') ?>

          </label>

        </div>
        <?php endif; ?>

        <div class="apply-actions">

          <button
            type="submit"
            class="btn btn-primary"
          >
            Submit application
          </button>

          <p class="apply-privacy">
            Your résumé is stored privately and is only visible to our HR team.
          </p>

        </div>

        <?php

        // Optional HR WhatsApp contact, set in Settings → Careers Page.

        $hrWhatsapp = (string) preg_replace(
            '/\D+/',
            '',
            (string) ep_setting('careers_hr_whatsapp', '')
        );

        if (strlen($hrWhatsapp) >= 9):

        ?>

        <p class="apply-hr-contact">

          Questions about <?= $job ? 'this role' : 'working with us' ?>?

          <a
            href="https://wa.me/<?= ep_h($hrWhatsapp) ?>"
            target="_blank"
            rel="noopener noreferrer"
          >
            Message our HR team on WhatsApp
          </a>.

        </p>

        <?php endif; ?>

      </form>

    </div>

  </div>

</section>
