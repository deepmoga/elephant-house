<?php
require_once __DIR__ . '/includes/header.php';
$blogs = getActiveBlogs();
?>

<div class="page-header">
    <div class="container">
        <h1>Blog</h1>
        <div class="breadcrumb"><a href="<?php echo SITE_URL; ?>">Home</a> / Blog</div>
    </div>
</div>

<section class="section">
    <div class="container">
        <?php if (empty($blogs)): ?>
        <div style="text-align:center;padding:60px 20px;">
            <i class="fas fa-blog" style="font-size:60px;color:var(--text-muted);margin-bottom:20px;display:block;"></i>
            <h3 style="color:var(--text-light);">No blog posts yet</h3>
            <p style="color:var(--text-muted);">Check back later for updates and articles.</p>
        </div>
        <?php else: ?>
        <div class="blog-grid">
            <?php foreach ($blogs as $post): ?>
            <a href="<?php echo SITE_URL; ?>/blog.php?slug=<?php echo urlencode($post['slug']); ?>" class="blog-card">
                <div class="blog-card-img">
                    <?php if (!empty($post['image'])): ?>
                    <img src="<?php echo UPLOAD_URL . 'blogs/' . htmlspecialchars($post['image']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
                    <?php else: ?>
                    <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,var(--primary),var(--primary-light));color:var(--accent-light);font-size:40px;"><i class="fas fa-newspaper"></i></div>
                    <?php endif; ?>
                </div>
                <div class="blog-card-body">
                    <div class="blog-meta">
                        <?php if (!empty($post['author'])): ?>
                        <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($post['author']); ?></span>
                        <?php endif; ?>
                        <span><i class="fas fa-calendar"></i> <?php echo date('d M Y', strtotime($post['created_at'])); ?></span>
                    </div>
                    <h3><?php echo htmlspecialchars($post['title']); ?></h3>
                    <?php if (!empty($post['excerpt'])): ?>
                    <p><?php echo htmlspecialchars($post['excerpt']); ?></p>
                    <?php endif; ?>
                    <span class="read-more">Read More <i class="fas fa-arrow-right"></i></span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
