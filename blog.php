<?php
require_once __DIR__ . '/includes/cms.php';

$navActive = 'blog';
$blogs = ep_get_blogs();
$pageTitle = 'EduPortal Blog — EdTech & School ERP Insights';
$pageDescription = 'EduPortal blog — expert insights on edtech, school ERP, parent engagement, and digital fee collection.';
$canonicalUrl = ep_canonical_url();
$extraStylesheets = ['css/shared.css'];
$extraBodyScripts = [ep_versioned_asset('js/reveal.js')];
$inlineStyles = <<<'CSS'
.nav-links a.active, .mobile-menu a.active { color: var(--color-primary); font-weight: 600; }
.blog-grid { display: grid; gap: 2rem; }
@media (min-width: 768px) { .blog-grid { grid-template-columns: repeat(2, 1fr); } }
@media (min-width: 1024px) { .blog-grid { grid-template-columns: repeat(3, 1fr); } }
.blog-card { display: flex; flex-direction: column; border-radius: 20px; overflow: hidden; border: 1px solid var(--color-border); background: #fff; box-shadow: 0 8px 30px rgba(15, 23, 42, 0.06); transition: transform var(--transition), box-shadow var(--transition); }
.blog-card:hover { transform: translateY(-6px); box-shadow: 0 20px 50px rgba(15, 23, 42, 0.12); }
.blog-card-thumb { position: relative; aspect-ratio: 16 / 9; overflow: hidden; display: block; }
.blog-card-thumb img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; display: block; }
.blog-card-thumb--gradient { display: flex; align-items: center; justify-content: center; }
.blog-card-thumb--gradient i { width: 48px; height: 48px; color: rgba(255, 255, 255, 0.95); }
.blog-card-thumb--ai { background: linear-gradient(135deg, #1e293b, #334155, #f28c28); }
.blog-card-thumb--parents { background: linear-gradient(135deg, #2563eb, #60a5fa, #93c5fd); }
.blog-card-thumb--fees { background: linear-gradient(135deg, #059669, #34d399, #6ee7b7); }
.blog-card-body { display: flex; flex-direction: column; flex: 1; padding: 1.5rem; }
.blog-meta { display: flex; flex-wrap: wrap; gap: 0.5rem 1rem; font-size: 0.8125rem; color: var(--color-text-muted); margin-bottom: 0.75rem; }
.blog-tag { font-weight: 600; color: var(--color-primary); text-transform: uppercase; letter-spacing: 0.04em; font-size: 0.75rem; }
.blog-card h2 { font-size: 1.25rem; font-weight: 700; letter-spacing: -0.02em; line-height: 1.35; margin-bottom: 0.75rem; }
.blog-card p { color: var(--color-text-muted); font-size: 0.9375rem; line-height: 1.65; margin-bottom: 1.25rem; flex: 1; }
.blog-read-more { display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.9375rem; font-weight: 600; color: var(--color-primary); }
.blog-empty { text-align: center; padding: 3rem; color: var(--color-text-muted); }
CSS;

require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>

  <main>
    <section class="page-hero">
      <div class="container">
        <span class="section-label">Insights &amp; Resources</span>
        <h1>EduPortal Blog</h1>
        <p>Practical ideas on edtech, school operations, and parent engagement for leaders building modern campuses.</p>
      </div>
    </section>

    <section class="section">
      <div class="container">
        <?php if (!$blogs): ?>
          <p class="blog-empty">No blog posts published yet. Check back soon.</p>
        <?php else: ?>
        <div class="blog-grid">
          <?php foreach ($blogs as $blog):
            $postUrl = ep_blog_post_url($blog);
            $readLabel = $blog['read_time_minutes'] ? (int) $blog['read_time_minutes'] . ' min read' : '';
          ?>
          <article class="blog-card reveal">
            <?= ep_blog_thumb_html($blog, $postUrl) ?>
            <div class="blog-card-body">
              <div class="blog-meta">
                <?php if (!empty($blog['category'])): ?><span class="blog-tag"><?= ep_h($blog['category']) ?></span><?php endif; ?>
                <span><?= ep_h(ep_format_date($blog['published_at'])) ?></span>
                <?php if ($readLabel): ?><span><?= ep_h($readLabel) ?></span><?php endif; ?>
              </div>
              <h2><a href="<?= ep_h($postUrl) ?>"><?= ep_h($blog['title']) ?></a></h2>
              <p><?= ep_h($blog['excerpt']) ?></p>
              <a href="<?= ep_h($postUrl) ?>" class="blog-read-more">Read more <i data-lucide="arrow-right"></i></a>
            </div>
          </article>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </section>
  </main>

<?php
$epTrackPage = 'blog-list';
require __DIR__ . '/includes/footer.php';
