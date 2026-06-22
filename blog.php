<?php
require_once __DIR__ . '/includes/header.php';

$slug = $_GET['slug'] ?? '';
$post = getBlogBySlug($slug);

if (!$post) {
    echo '<div class="page-header"><div class="container"><h1>Post Not Found</h1></div></div>';
    echo '<section class="section"><div class="container" style="text-align:center;padding:60px;"><p style="color:var(--text-muted);">This blog post does not exist.</p><a href="' . SITE_URL . '/blogs.php" class="btn-view-all" style="margin-top:20px;display:inline-block;">Back to Blog</a></div></section>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}
?>

<div class="page-header">
    <div class="container">
        <h1><?php echo htmlspecialchars($post['title']); ?></h1>
        <div class="breadcrumb"><a href="<?php echo SITE_URL; ?>">Home</a> / <a href="<?php echo SITE_URL; ?>/blogs.php">Blog</a> / <?php echo htmlspecialchars($post['title']); ?></div>
    </div>
</div>

<section class="page-content">
    <div class="container">
        <div class="content-area">
            <div class="blog-meta" style="margin-bottom:20px;">
                <?php if (!empty($post['author'])): ?>
                <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($post['author']); ?></span>
                <?php endif; ?>
                <span><i class="fas fa-calendar"></i> <?php echo date('d M Y', strtotime($post['created_at'])); ?></span>
            </div>

            <?php if (!empty($post['image'])): ?>
            <img src="<?php echo UPLOAD_URL . 'blogs/' . htmlspecialchars($post['image']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" style="width:100%;border-radius:var(--radius);margin-bottom:25px;">
            <?php endif; ?>

            <?php echo $post['content']; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
