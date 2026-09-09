<?php
/**
 * Admin — Documentation (Task 8).
 *
 * Guides are published one at a time as they are written. A guide cannot be
 * set Active without at least two real steps: a "step-by-step guide" with no
 * steps in it sends the reader back to the phone, which is the opposite of
 * what these pages are for.
 */
require_once __DIR__ . '/includes/layout.php';
global $m;

/**
 * Rebuilds the steps array from the posted rows.
 * @return array<int, array{title: string, body: string}>
 */
function cms_docs_steps_from_post(array $post): array
{
    $titles = (array) ($post['step_title'] ?? []);
    $bodies = (array) ($post['step_body'] ?? []);

    $steps = [];
    foreach ($titles as $i => $title) {
        $title = mb_substr(trim((string) $title), 0, 190);
        if ($title === '') {
            continue;   // a step needs a heading to be a step
        }
        $steps[] = [
            'title' => $title,
            'body'  => trim((string) ($bodies[$i] ?? '')),
        ];
    }
    return $steps;
}

/**
 * Rebuilds the FAQ array from the posted rows.
 * @return array<int, array{q: string, a: string}>
 */
function cms_docs_faqs_from_post(array $post): array
{
    $qs = (array) ($post['faq_q'] ?? []);
    $as = (array) ($post['faq_a'] ?? []);

    $faqs = [];
    foreach ($qs as $i => $q) {
        $q = trim((string) $q);
        $a = trim((string) ($as[$i] ?? ''));
        if ($q === '' || $a === '') {
            continue;
        }
        $faqs[] = ['q' => $q, 'a' => $a];
    }
    return $faqs;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cms_require_permission('docs.manage');
    if (!cms_verify_csrf($_POST['_csrf'] ?? null)) {
        cms_flash('error', 'Invalid request token.');
        header('Location: docs.php');
        exit;
    }

    $id = (int) ($_POST['id'] ?? 0);

    if ((string) ($_POST['action'] ?? '') === 'delete' && $id > 0) {
        $m->where('id', $id);
        $m->delete('ep_docs');
        cms_flash('success', 'Guide deleted.');
        header('Location: docs.php');
        exit;
    }

    // Only rewrite steps/FAQs when the form actually carried those fields.
    // Clearing every row in the UI still posts the (empty) inputs, so an
    // absent key means a partial submission, not an intentional wipe —
    // rebuilding from it would silently delete content.
    $existing = null;
    if ($id > 0) {
        $m->where('id', $id);
        $existing = $m->getOne('ep_docs');
    }
    $steps = array_key_exists('step_title', $_POST)
        ? cms_docs_steps_from_post($_POST)
        : ep_docs_decode($existing['steps_json'] ?? '');
    $faqs = array_key_exists('faq_q', $_POST)
        ? cms_docs_faqs_from_post($_POST)
        : ep_docs_decode($existing['faqs_json'] ?? '');
    $requested = ep_careers_pick((string) ($_POST['status'] ?? 'Draft'), ['Draft', 'Active'], 'Draft');

    // A guide with fewer than two steps is not a step-by-step guide.
    $blocked = $requested === 'Active' && count($steps) < 2;

    $data = [
        'title'            => mb_substr(trim((string) ($_POST['title'] ?? '')), 0, 190),
        'category'         => mb_substr(trim((string) ($_POST['category'] ?? '')), 0, 80),
        'summary'          => mb_substr(trim((string) ($_POST['summary'] ?? '')), 0, 400),
        'intro_html'       => trim((string) ($_POST['intro_html'] ?? '')),
        'tips_html'        => trim((string) ($_POST['tips_html'] ?? '')),
        'steps_json'       => $steps ? (string) json_encode($steps, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
        'faqs_json'        => $faqs ? (string) json_encode($faqs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
        'meta_title'       => mb_substr(trim((string) ($_POST['meta_title'] ?? '')), 0, 190),
        'meta_description' => mb_substr(trim((string) ($_POST['meta_description'] ?? '')), 0, 320),
        'sort_order'       => (int) ($_POST['sort_order'] ?? 0),
        'status'           => $blocked ? 'Draft' : $requested,
    ];

    if ($data['title'] === '') {
        cms_flash('error', 'A title is required.');
        header('Location: docs.php' . ($id ? '?edit=' . $id : ''));
        exit;
    }

    if ($id > 0) {
        $m->where('id', $id);
        $m->update('ep_docs', $data);
    } else {
        $data['slug'] = cms_slugify($data['title']);
        $data['feature_script'] = mb_substr(trim((string) ($_POST['feature_script'] ?? '')), 0, 120);
        $id = (int) $m->insert('ep_docs', $data);
    }

    if ($blocked) {
        cms_flash('error', 'Saved as Draft. A guide needs at least two steps before it can be published.');
    } else {
        cms_flash('success', 'Guide saved' . ($data['status'] === 'Active' ? ' and published.' : ' as a draft.'));
    }

    header('Location: docs.php?edit=' . $id);
    exit;
}

$edit = null;
if (isset($_GET['edit'])) {
    $m->where('id', (int) $_GET['edit']);
    $edit = $m->getOne('ep_docs');
}

$m->orderBy('category', 'ASC');
$m->orderBy('sort_order', 'ASC');
$m->orderBy('title', 'ASC');
$docs = $m->get('ep_docs') ?: [];

$editSteps = $edit ? ep_docs_decode($edit['steps_json'] ?? '') : [];
while (count($editSteps) < 6) {
    $editSteps[] = ['title' => '', 'body' => ''];
}
$editFaqs = $edit ? ep_docs_decode($edit['faqs_json'] ?? '') : [];
while (count($editFaqs) < 5) {
    $editFaqs[] = ['q' => '', 'a' => ''];
}

$ev = static function (string $f, $d = '') use ($edit) {
    $v = $edit[$f] ?? $d;
    return $v === null ? '' : (string) $v;
};

$written = 0;
foreach ($docs as $d) {
    if (trim((string) ($d['steps_json'] ?? '')) !== '') {
        $written++;
    }
}

cms_page_start('Documentation', 'docs.manage', 'docs.manage', false, 'Public step-by-step guides at /docs');
?>

<div class="alert alert-info d-flex align-items-start gap-2 mb-4">
  <i class="bi bi-info-circle-fill mt-1"></i>
  <div>
    <strong><?= (int) $written ?> of <?= count($docs) ?> guides have steps written.</strong>
    Each guide needs the real screens and button names from the product. A guide that describes
    screens which do not exist will generate support calls rather than prevent them, so guides
    without at least two steps cannot be published.
  </div>
</div>

<div class="card ep-card shadow-sm border-0 mb-4">
  <div class="card-header bg-white border-bottom py-3 px-4">
    <h5 class="mb-0 fw-semibold">All guides</h5>
  </div>
  <div class="card-body p-0">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr><th>Guide</th><th>Category</th><th>URL</th><th class="text-center">Steps</th><th class="text-center">FAQs</th><th>Status</th><th class="text-end"></th></tr>
      </thead>
      <tbody>
        <?php foreach ($docs as $d):
          $st = ep_docs_decode($d['steps_json'] ?? '');
          $fq = ep_docs_decode($d['faqs_json'] ?? '');
        ?>
        <tr>
          <td class="fw-semibold"><?= ep_h($d['title']) ?></td>
          <td class="small text-muted"><?= ep_h($d['category']) ?></td>
          <td class="small"><code>/docs/<?= ep_h($d['slug']) ?></code></td>
          <td class="text-center">
            <?php if (count($st) >= 2): ?>
            <span class="badge bg-success"><?= count($st) ?></span>
            <?php else: ?>
            <span class="badge bg-warning text-dark" title="Needs at least 2 to publish"><?= count($st) ?></span>
            <?php endif; ?>
          </td>
          <td class="text-center small"><?= count($fq) ?></td>
          <td><span class="badge <?= (string) $d['status'] === 'Active' ? 'bg-success' : 'bg-secondary' ?>"><?= ep_h($d['status']) ?></span></td>
          <td class="text-end text-nowrap">
            <?php if ((string) $d['status'] === 'Active'): ?>
            <a class="btn btn-sm btn-outline-secondary" href="<?= ep_h(ep_doc_url($d)) ?>" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right"></i></a>
            <?php endif; ?>
            <a class="btn btn-sm btn-outline-primary" href="docs.php?edit=<?= (int) $d['id'] ?>"><i class="bi bi-pencil"></i></a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($edit): ?>
<form method="post" class="card ep-card shadow-sm border-0">
  <div class="card-header bg-white border-bottom py-3 px-4">
    <h5 class="mb-0 fw-semibold"><?= ep_h($edit['title']) ?></h5>
    <small class="text-muted"><code>/docs/<?= ep_h($edit['slug']) ?></code></small>
  </div>
  <div class="card-body px-4 py-4">
    <input type="hidden" name="_csrf" value="<?= ep_h(cms_csrf()) ?>">
    <input type="hidden" name="id" value="<?= (int) $edit['id'] ?>">

    <div class="row g-3 mb-4">
      <div class="col-md-5">
        <label class="form-label">Title</label>
        <input type="text" name="title" class="form-control" maxlength="190" value="<?= ep_h($ev('title')) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Category</label>
        <input type="text" name="category" class="form-control" maxlength="80" value="<?= ep_h($ev('category')) ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label">Sort order</label>
        <input type="number" name="sort_order" class="form-control" value="<?= ep_h($ev('sort_order', '0')) ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
          <?php foreach (['Draft', 'Active'] as $st): ?>
          <option value="<?= ep_h($st) ?>"<?= $ev('status', 'Draft') === $st ? ' selected' : '' ?>><?= ep_h($st) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12">
        <label class="form-label">Summary</label>
        <input type="text" name="summary" class="form-control" maxlength="400" value="<?= ep_h($ev('summary')) ?>">
      </div>
      <div class="col-12">
        <label class="form-label">Intro <span class="text-muted small">(HTML allowed)</span></label>
        <textarea name="intro_html" class="form-control" rows="3"><?= ep_h($ev('intro_html')) ?></textarea>
      </div>
    </div>

    <h6 class="fw-semibold">Steps</h6>
    <p class="form-text mb-2">
      Write what the user actually clicks, using the real screen and button names.
      Leave the heading blank to drop a step. At least two are needed to publish.
    </p>
    <?php foreach ($editSteps as $i => $step): ?>
    <div class="row g-2 mb-2 align-items-start">
      <div class="col-md-4">
        <input type="text" name="step_title[]" class="form-control form-control-sm" maxlength="190"
               placeholder="Step <?= $i + 1 ?> heading" value="<?= ep_h((string) ($step['title'] ?? '')) ?>">
      </div>
      <div class="col-md-8">
        <textarea name="step_body[]" class="form-control form-control-sm" rows="2"
                  placeholder="What to do, and what the user should see afterwards"><?= ep_h((string) ($step['body'] ?? '')) ?></textarea>
      </div>
    </div>
    <?php endforeach; ?>

    <div class="row g-3 mt-3">
      <div class="col-12">
        <label class="form-label">Things worth knowing <span class="text-muted small">(gotchas support gets asked about)</span></label>
        <textarea name="tips_html" class="form-control" rows="3"><?= ep_h($ev('tips_html')) ?></textarea>
      </div>
    </div>

    <h6 class="fw-semibold mt-4">Common questions</h6>
    <p class="form-text mb-2">Seeded from the module's feature page. Both fields are needed for a question to show.</p>
    <?php foreach ($editFaqs as $faq): ?>
    <div class="row g-2 mb-2">
      <div class="col-md-4"><input type="text" name="faq_q[]" class="form-control form-control-sm" placeholder="Question" value="<?= ep_h((string) ($faq['q'] ?? '')) ?>"></div>
      <div class="col-md-8"><textarea name="faq_a[]" class="form-control form-control-sm" rows="2" placeholder="Answer"><?= ep_h((string) ($faq['a'] ?? '')) ?></textarea></div>
    </div>
    <?php endforeach; ?>

    <div class="row g-3 mt-3">
      <div class="col-md-6">
        <label class="form-label">Meta title</label>
        <input type="text" name="meta_title" class="form-control" maxlength="190" value="<?= ep_h($ev('meta_title')) ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Meta description</label>
        <input type="text" name="meta_description" class="form-control" maxlength="320" value="<?= ep_h($ev('meta_description')) ?>">
      </div>
    </div>
  </div>
  <div class="card-footer bg-white border-top py-3 px-4 d-flex gap-2">
    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save</button>
    <a href="docs.php" class="btn btn-outline-secondary">Close</a>
  </div>
</form>
<?php endif; ?>

<?php cms_page_end(); ?>
