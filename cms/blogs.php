<?php
require_once __DIR__ . '/includes/layout.php';
global $m;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!cms_verify_csrf($_POST['_csrf'] ?? null)) {
        cms_flash('error', 'Invalid request token.');
        header('Location: blogs.php');
        exit;
    }
    $action = $_POST['action'] ?? '';
    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $m->where('id', $id);
        $ok = $m->delete('ep_blogs');
        cms_flash($ok ? 'success' : 'error', $ok ? 'Blog deleted.' : 'Delete failed.');
    } else {
        $id = (int) ($_POST['id'] ?? 0);
        $title = trim((string) ($_POST['title'] ?? ''));
        $slugFinal = cms_slugify($title);
        $prev = null;
        if ($id > 0) {
            $m->where('id', $id);
            $prev = $m->getOne('ep_blogs');
            if ($prev && !empty($prev['slug'])) {
                $slugFinal = (string) $prev['slug'];
            }
        }
        $existingImage = trim((string) ($_POST['featured_image'] ?? ''));
        $uploadedImage = cms_upload_blog_featured_image(
            isset($_FILES['featured_image_file']) && is_array($_FILES['featured_image_file']) ? $_FILES['featured_image_file'] : null,
            $existingImage,
            $slugFinal
        );
        $existingThumb = trim((string) ($_POST['blog_thumbnail'] ?? ''));
        $uploadedThumb = cms_upload_blog_card_thumbnail(
            isset($_FILES['blog_thumbnail_file']) && is_array($_FILES['blog_thumbnail_file']) ? $_FILES['blog_thumbnail_file'] : null,
            $existingThumb,
            $slugFinal
        );
        $content = trim((string) ($_POST['content'] ?? ''));
        $excerpt = trim((string) ($_POST['excerpt'] ?? ''));
        $status = trim((string) ($_POST['status'] ?? 'draft'));
        if (!in_array($status, ['draft', 'published', 'archived'], true)) {
            $status = 'draft';
        }

        $publishedAt = null;
        if ($status === 'published') {
            $publishedAt = ($prev && !empty($prev['published_at'])) ? $prev['published_at'] : date('Y-m-d H:i:s');
        }

        $metaDescription = $excerpt !== '' ? $excerpt : mb_substr(trim(strip_tags($content)), 0, 160);
        $featuredImage = $uploadedImage !== '' ? $uploadedImage : $existingImage;
        $cardThumb = $uploadedThumb !== '' ? $uploadedThumb : $existingThumb;

        $data = [
            'title' => $title,
            'slug' => $slugFinal,
            'excerpt' => $excerpt,
            'content' => $content,
            'category' => trim((string) ($_POST['category'] ?? '')),
            'author_name' => 'EduPortal Team',
            'read_time_minutes' => cms_blog_read_minutes($content),
            'featured_image' => $featuredImage,
            'blog_thumbnail' => $cardThumb,
            'thumb_type' => $featuredImage !== '' ? 'image' : 'gradient',
            'thumb_icon' => 'sparkles',
            'thumb_gradient_class' => '',
            'meta_title' => $title,
            'meta_description' => $metaDescription,
            'status' => $status,
            'published_at' => $publishedAt,
        ];
        if ($id > 0) {
            $m->where('id', $id);
            $ok = $m->update('ep_blogs', $data);
            cms_flash($ok ? 'success' : 'error', $ok ? 'Blog updated.' : 'No changes saved.');
        } else {
            $ok = $m->insert('ep_blogs', $data);
            cms_flash($ok ? 'success' : 'error', $ok ? 'Blog created.' : 'Create failed.');
        }
    }
    header('Location: blogs.php');
    exit;
}

$edit = null;
if (isset($_GET['edit'])) {
    $m->where('id', (int) $_GET['edit']);
    $edit = $m->getOne('ep_blogs');
}

$m->orderBy('created_at', 'DESC');
$blogs = $m->get('ep_blogs');

$autoOpen = cms_modal_should_open();
$featuredImg = trim((string) ($edit['featured_image'] ?? ''));
$cardThumbImg = trim((string) ($edit['blog_thumbnail'] ?? ''));

cms_page_start('Blogs', 'blogs', 'blogs.manage');
?>
<?php cms_list_card_start('All blogs', count($blogs), 'Add blog'); ?>
  <table class="table table-hover align-middle mb-0">
    <thead class="table-light"><tr><th style="width:52px">#</th><th>Title</th><th style="width:140px">Featured</th><th style="width:100px">Thumbnail</th><th>Category</th><th>Status</th><th>Published</th><th class="text-end">Actions</th></tr></thead>
    <tbody>
    <?php if (!$blogs): ?><tr><td colspan="8" class="text-center text-muted py-4">No blogs yet.</td></tr><?php endif; ?>
    <?php foreach ($blogs as $i => $blog): ?>
      <?php
        $fi  = trim((string)($blog['featured_image'] ?? ''));
        $thi = trim((string)($blog['blog_thumbnail'] ?? ''));
      ?>
      <tr>
        <td class="text-muted small"><?= $i + 1 ?></td>
        <td class="fw-semibold"><?= ep_h($blog['title']) ?></td>
        <td>
          <?php if ($fi !== ''): ?>
            <img src="../<?= ep_h($fi) ?>" alt="" style="height:44px;width:auto;border-radius:6px;object-fit:cover;border:1px solid #dee2e6">
          <?php else: ?>
            <span class="badge bg-warning text-dark" title="No featured image">Missing</span>
          <?php endif; ?>
        </td>
        <td>
          <?php if ($thi !== ''): ?>
            <img src="../<?= ep_h($thi) ?>" alt="" style="height:44px;width:auto;border-radius:6px;object-fit:cover;border:1px solid #dee2e6">
          <?php else: ?>
            <span class="badge bg-warning text-dark" title="No thumbnail">Missing</span>
          <?php endif; ?>
        </td>
        <td class="small text-muted"><?= ep_h($blog['category'] ?: '—') ?></td>
        <td><span class="badge <?= ($blog['status'] ?? '') === 'published' ? 'bg-success' : 'bg-secondary' ?>"><?= ep_h($blog['status']) ?></span></td>
        <td class="small text-muted"><?= ep_h($blog['published_at'] ?: '—') ?></td>
        <td class="text-end">
          <a class="btn btn-sm btn-outline-primary" href="blogs.php?edit=<?= (int) $blog['id'] ?>"><i class="bi bi-pencil"></i></a>
          <form method="post" class="d-inline" onsubmit="return confirm('Delete this blog?')">
            <input type="hidden" name="_csrf" value="<?= ep_h(cms_csrf()) ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int) $blog['id'] ?>">
            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php cms_list_card_end(); ?>

<?php cms_modal_begin('Blog post', $autoOpen, 'lg', true, true); ?>
  <input type="hidden" name="_csrf" value="<?= ep_h(cms_csrf()) ?>">
  <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
  <input type="hidden" name="featured_image" id="featuredImagePath" value="<?= ep_h($featuredImg) ?>">
  <input type="hidden" name="blog_thumbnail" id="blogThumbnailPath" value="<?= ep_h($cardThumbImg) ?>">
  <div class="row g-3">
    <div class="col-md-8">
      <label class="form-label">Title <span class="text-danger">*</span></label>
      <input type="text" name="title" class="form-control" required value="<?= ep_h($edit['title'] ?? '') ?>" placeholder="Blog post title">
    </div>
    <div class="col-md-4">
      <label class="form-label">Status</label>
      <select name="status" class="form-select">
        <option value="draft"<?= (($edit['status'] ?? 'draft') === 'draft') ? ' selected' : '' ?>>Draft — save for later</option>
        <option value="published"<?= (($edit['status'] ?? '') === 'published') ? ' selected' : '' ?>>Published — live on website</option>
      </select>
    </div>

    <div class="col-md-5">
      <label class="form-label">Featured image <span class="text-muted fw-normal">(blog post hero)</span></label>
      <div id="featuredImagePreview" class="mb-2 ep-thumb-preview">
        <?php if ($featuredImg): ?>
        <img src="../<?= ep_h($featuredImg) ?>" alt="Featured image preview">
        <?php else: ?>
        <div class="ep-thumb-empty">No image yet</div>
        <?php endif; ?>
      </div>
      <input type="file" name="featured_image_file" id="featuredImageFile" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp,image/gif">
      <div class="form-text">JPG, PNG, WebP — max 8 MB. Will be resized to 1200×800 if larger.</div>
    </div>

    <div class="col-md-5">
      <label class="form-label">Card thumbnail <span class="text-muted fw-normal">(blog listing page)</span></label>
      <div id="blogThumbnailPreview" class="mb-2 ep-thumb-preview">
        <?php if ($cardThumbImg): ?>
        <img src="../<?= ep_h($cardThumbImg) ?>" alt="Card thumbnail preview">
        <?php else: ?>
        <div class="ep-thumb-empty">No thumbnail yet</div>
        <?php endif; ?>
      </div>
      <input type="file" name="blog_thumbnail_file" id="blogThumbnailFile" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp,image/gif">
      <div class="form-text">Shown on the blog listing page. Resized to 600×400, compressed automatically.</div>
    </div>

    <div class="col-md-7">
      <label class="form-label">Category <span class="text-muted fw-normal">(optional)</span></label>
      <input type="text" name="category" class="form-control mb-3" value="<?= ep_h($edit['category'] ?? '') ?>" placeholder="e.g. EdTech, Parent Engagement">
      <label class="form-label">Short summary <span class="text-muted fw-normal">(optional)</span></label>
      <textarea name="excerpt" class="form-control" rows="3" placeholder="One or two lines shown on the blog listing page"><?= ep_h($edit['excerpt'] ?? '') ?></textarea>
    </div>

    <div class="col-12">
      <label class="form-label">Blog content <span class="text-danger">*</span></label>
      <p class="form-text mt-0 mb-2">Paste your full article from ChatGPT or Claude. Use headings, bullet lists, and quotes as needed.</p>
      <textarea name="content" id="blogContentEditor" class="form-control" rows="8"><?= ep_h($edit['content'] ?? '') ?></textarea>
    </div>
  </div>
<?php cms_modal_end('Save blog', 'blogs.php'); ?>

<script src="assets/cms-blog-editor.js"></script>
<?php cms_page_end(); ?>
