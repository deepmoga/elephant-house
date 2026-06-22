<?php
require_once __DIR__ . '/includes/header.php';

$slug = $_GET['slug'] ?? '';
$page = getPage($slug);

if (!$page) {
    $page = ['title' => 'Page Not Found', 'content' => '<p>The page you are looking for does not exist.</p>'];
}
?>

<div class="page-header">
    <div class="container">
        <h1><?php echo htmlspecialchars($page['title']); ?></h1>
        <div class="breadcrumb">
            <a href="<?php echo SITE_URL; ?>">Home</a> / <?php echo htmlspecialchars($page['title']); ?>
        </div>
    </div>
</div>

<section class="page-content">
    <div class="container">
        <div class="content-area">
            <?php echo $page['content']; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
