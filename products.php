<?php
require_once __DIR__ . '/includes/header.php';

$categoryId = $_GET['category'] ?? '';
$categoryName = 'Products';

if (!empty($categoryId)) {
    $apiCategories = getCategories();
    foreach ($apiCategories as $cat) {
        if ($cat['id'] === $categoryId) {
            $categoryName = $cat['name'];
            break;
        }
    }
    $result = getProductsByCategory($categoryId);
    $products = $result['data'] ?? [];
} else {
    $result = getProducts();
    $products = $result['data'] ?? [];
}
?>

<div class="page-header">
    <div class="container">
        <h1><?php echo htmlspecialchars($categoryName); ?></h1>
        <div class="breadcrumb">
            <a href="<?php echo SITE_URL; ?>">Home</a> / <a href="<?php echo SITE_URL; ?>/categories.php">Categories</a> / <?php echo htmlspecialchars($categoryName); ?>
        </div>
    </div>
</div>

<section class="section">
    <div class="container">
        <?php if (!empty($products)): ?>
        <p style="margin-bottom:25px;color:var(--text-light);">Showing <?php echo count($products); ?> product(s)</p>
        <div class="product-grid">
            <?php foreach ($products as $product):
                $price = $product['price_including_tax'] ?? 0;
                $imgUrl = $product['image_url'] ?? '';
                $brand = $product['brand']['name'] ?? '';
            ?>
            <a href="<?php echo SITE_URL; ?>/product.php?id=<?php echo urlencode($product['id']); ?>" class="product-card">
                <div class="product-img">
                    <?php if (!empty($imgUrl)): ?>
                    <img src="<?php echo htmlspecialchars($imgUrl); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" loading="lazy">
                    <?php else: ?>
                    <div class="no-img"><i class="fas fa-image"></i></div>
                    <?php endif; ?>
                    <?php if (empty($product['is_active'])): ?>
                    <span class="badge">Out of Stock</span>
                    <?php endif; ?>
                </div>
                <div class="product-info">
                    <?php if (!empty($brand)): ?>
                    <span class="product-brand"><?php echo htmlspecialchars($brand); ?></span>
                    <?php endif; ?>
                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                    <div class="product-price">
                        <span class="price">$<?php echo number_format($price, 2); ?></span>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div style="text-align:center;padding:60px 20px;">
            <i class="fas fa-box-open" style="font-size:60px;color:var(--text-muted);margin-bottom:20px;display:block;"></i>
            <h3 style="color:var(--text-light);margin-bottom:10px;">No products found</h3>
            <p style="color:var(--text-muted);">Check back later for new products in this category.</p>
            <a href="<?php echo SITE_URL; ?>/categories.php" class="btn-view-all" style="margin-top:20px;">Browse Categories</a>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
