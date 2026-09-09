<?php
require_once __DIR__ . '/includes/cms.php';

$slug = isset($_GET['slug']) ? trim((string) $_GET['slug']) : '';
$blog = $slug ? ep_get_blog_by_slug($slug) : null;

if (!$blog) {
    http_response_code(404);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html><head><title>Not found</title></head><body><h1>Blog post not found</h1><p><a href="' . ep_h(ep_url('blog.php')) . '">Back to blog</a></p></body></html>';
    exit;
}

$navActive = 'blog';
$related = ep_get_blogs(3, (int) $blog['id']);
$pageTitle = $blog['meta_title'] ?: ($blog['title'] . ' — EduPortal Blog');
$pageDescription = $blog['meta_description'] ?: $blog['excerpt'];
$canonicalUrl = ep_canonical_url();
$extraStylesheets = ['css/shared.css', 'css/blog-post.css'];
$bodyClass = 'blog-post';

require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>

  <main class="blog-post">
    <div class="container">
      <a href="<?= ep_h(ep_url('blog.php')) ?>" class="blog-back"><i data-lucide="arrow-left"></i> Back to Blog</a>

      <header class="blog-post-header">
        <?php if (!empty($blog['category'])): ?><span class="blog-tag"><?= ep_h($blog['category']) ?></span><?php endif; ?>
        <h1><?= ep_h($blog['title']) ?></h1>
        <div class="blog-post-meta">
          <span><?= ep_h(ep_format_date($blog['published_at'])) ?></span>
          <?php if ($blog['read_time_minutes']): ?><span><?= (int) $blog['read_time_minutes'] ?> min read</span><?php endif; ?>
          <span>By <?= ep_h($blog['author_name']) ?></span>
        </div>
      </header>

      <?php if (!empty($blog['featured_image']) && ($blog['thumb_type'] ?? '') === 'image'): ?>
      <div class="blog-post-hero">
        <img src="<?= ep_h(ep_asset($blog['featured_image'])) ?>" alt="<?= ep_h($blog['title']) ?>">
      </div>
      <?php elseif (!empty($blog['thumb_gradient_class'])): ?>
      <div class="blog-post-hero <?= ep_h($blog['thumb_gradient_class']) ?>"></div>
      <?php endif; ?>

      <article class="blog-post-content" id="blogArticle">
        <?= $blog['content'] ?>
      </article>
      <script>
        (function () {
          var article = document.getElementById('blogArticle');
          if (!article) return;
          article.querySelectorAll('table').forEach(function (tbl) {
            if (tbl.parentElement.classList.contains('table-wrap')) return;
            var wrap = document.createElement('div');
            wrap.className = 'table-wrap';
            tbl.parentNode.insertBefore(wrap, tbl);
            wrap.appendChild(tbl);
          });
        })();
      </script>

      <div class="blog-post-cta">
        <h3>See EduPortal in action</h3>
        <p>Discover how AI-driven school ERP can simplify your daily operations.</p>
        <a href="<?= ep_h(ep_url('index.php')) ?>" class="btn btn-primary">Get Started</a>
      </div>

      <?php if ($related): ?>
      <section class="blog-related" aria-labelledby="related-heading">
        <h2 id="related-heading">More from the blog</h2>
        <div class="blog-related-grid">
          <?php foreach ($related as $rel):
            $relUrl = ep_blog_post_url($rel);
          ?>
          <a href="<?= ep_h($relUrl) ?>" class="blog-related-card">
            <?php if (!empty($rel['featured_image']) && ($rel['thumb_type'] ?? '') === 'image'): ?>
            <div class="blog-related-thumb"><img src="<?= ep_h(ep_asset($rel['featured_image'])) ?>" alt=""></div>
            <?php else: ?>
            <div class="blog-related-thumb <?= ep_h($rel['thumb_gradient_class'] ?? '') ?>">
              <i data-lucide="<?= ep_h($rel['thumb_icon'] ?? 'sparkles') ?>"></i>
            </div>
            <?php endif; ?>
            <div class="blog-related-body">
              <?php if (!empty($rel['category'])): ?><span class="blog-related-tag"><?= ep_h($rel['category']) ?></span><?php endif; ?>
              <h3><?= ep_h($rel['title']) ?></h3>
              <p><?= ep_h($rel['excerpt']) ?></p>
            </div>
          </a>
          <?php endforeach; ?>
        </div>
      </section>
      <?php endif; ?>
    </div>
  </main>

<?php
$epTrackPage = 'blog:' . ($blog['slug'] ?? 'unknown');
require __DIR__ . '/includes/footer.php';
