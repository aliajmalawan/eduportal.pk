<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (cms_is_logged_in()) {
    header('Location: ' . cms_landing_page());
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!cms_verify_csrf($_POST['_csrf'] ?? null)) {
        $error = 'Invalid request token.';
    } else {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        // Checked before the password is even compared, so a locked-out caller
        // learns nothing about whether the address exists.
        $wait = cms_login_lockout_seconds($email);
        if ($wait > 0) {
            $minutes = (int) ceil($wait / 60);
            $error = 'Too many failed attempts. Try again in '
                . $minutes . ' minute' . ($minutes === 1 ? '' : 's') . '.';
        } elseif (cms_login($email, $password)) {
            header('Location: ' . cms_landing_page());
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sign in · EduPortal CMS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/cms-theme.css">
</head>
<body>
<div class="ep-login-page">
  <div class="ep-login-left">
    <div>
      <div class="d-inline-flex align-items-center justify-content-center rounded-3 mb-4" style="width:56px;height:56px;background:rgba(255,255,255,.15)">
        <i class="bi bi-mortarboard-fill fs-2"></i>
      </div>
      <h1 class="display-6 fw-bold mb-3">EduPortal Website CMS</h1>
      <p class="lead opacity-90 mb-4">Manage blogs, case studies, testimonials, leads, and analytics — all in one professional dashboard built for your marketing team.</p>
      <ul class="list-unstyled opacity-90">
        <li class="mb-2"><i class="bi bi-check-circle me-2"></i> Real-time visitor analytics</li>
        <li class="mb-2"><i class="bi bi-check-circle me-2"></i> Role-based team access</li>
        <li class="mb-2"><i class="bi bi-check-circle me-2"></i> Media library & content tools</li>
      </ul>
    </div>
  </div>
  <div class="ep-login-right">
    <div class="ep-login-card">
      <div class="text-center mb-4 d-lg-none">
        <span class="brand-icon d-inline-flex align-items-center justify-content-center rounded-3 mb-2" style="width:48px;height:48px;background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff">
          <i class="bi bi-mortarboard-fill fs-4"></i>
        </span>
      </div>
      <h1 class="h4 fw-bold mb-1">Welcome back</h1>
      <p class="text-muted small mb-4">Sign in to your CMS account</p>
      <?php if ($error): ?>
      <div class="alert alert-danger py-2 small border-0"><i class="bi bi-exclamation-triangle me-1"></i><?= ep_h($error) ?></div>
      <?php endif; ?>
      <form method="post">
        <input type="hidden" name="_csrf" value="<?= ep_h(cms_csrf()) ?>">
        <div class="mb-3">
          <label class="form-label small fw-semibold">Email address</label>
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-muted"></i></span>
            <input type="email" name="email" class="form-control border-start-0" required placeholder="you@eduportal.pk" autocomplete="username">
          </div>
        </div>
        <div class="mb-4">
          <label class="form-label small fw-semibold">Password</label>
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
            <input type="password" name="password" class="form-control border-start-0" required placeholder="••••••••" autocomplete="current-password">
          </div>
        </div>
        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
          <i class="bi bi-box-arrow-in-right me-2"></i>Sign in
        </button>
      </form>
      <p class="text-muted text-center mt-4 mb-0" style="font-size:0.75rem">Secure admin access only · Change default password after first login</p>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
