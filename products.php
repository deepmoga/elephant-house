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

$allParentCats = getParentCategories();
$db = getDB();
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
        <div class="products-layout">
            <!-- Left Sidebar -->
            <aside class="products-sidebar">
                <div class="sidebar-cat-header">
                    <i class="fas fa-th-list"></i> Categories
                </div>
                <div class="sidebar-cat-list">
                    <?php foreach ($allParentCats as $pCat):
                        $subs = $db->prepare("SELECT api_category_id, api_category_name FROM category_mapping WHERE parent_category_id = ? ORDER BY sort_order ASC");
                        $subs->execute([$pCat['id']]);
                        $subList = $subs->fetchAll();
                        $isCurrentParent = ($pCat['api_category_id'] === $categoryId);
                        $hasCurrentSub = false;
                        foreach ($subList as $s) { if ($s['api_category_id'] === $categoryId) { $hasCurrentSub = true; break; } }
                        $isOpen = $isCurrentParent || $hasCurrentSub;
                    ?>
                    <div class="sidebar-cat-group <?php echo $isOpen ? 'open' : ''; ?>">
                        <div class="sidebar-cat-parent" onclick="this.parentElement.classList.toggle('open')" style="cursor:pointer;">
                            <span><?php echo htmlspecialchars($pCat['api_category_name']); ?></span>
                            <?php if (!empty($subList)): ?>
                            <i class="fas fa-chevron-down sidebar-cat-toggle"></i>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($subList)): ?>
                        <div class="sidebar-cat-subs-collapse">
                            <a href="<?php echo SITE_URL; ?>/products.php?category=<?php echo urlencode($pCat['api_category_id']); ?>" class="<?php echo $isCurrentParent ? 'active' : ''; ?>">
                                All <?php echo htmlspecialchars($pCat['api_category_name']); ?>
                            </a>
                            <?php foreach ($subList as $sub): ?>
                            <a href="<?php echo SITE_URL; ?>/products.php?category=<?php echo urlencode($sub['api_category_id']); ?>" class="<?php echo $sub['api_category_id'] === $categoryId ? 'active' : ''; ?>">
                                <?php echo htmlspecialchars($sub['api_category_name']); ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </aside>

            <!-- Right Products -->
            <div class="products-main">
                <?php if (!empty($products)): ?>
                <p style="margin-bottom:20px;color:var(--text-light);font-size:14px;">Showing <?php echo count($products); ?> product(s)</p>
                <div class="product-grid">
                    <?php foreach ($products as $product):
                        $catId = $product['product_type_id'] ?? '';
                        $rawPrice = $product['price_including_tax'] ?? 0;
                        $price = applyPriceMarkup($rawPrice, $catId);
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
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
