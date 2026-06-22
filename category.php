<?php
require_once __DIR__ . '/includes/header.php';

$parentId = intval($_GET['id'] ?? 0);
$db = getDB();

$stmt = $db->prepare("SELECT * FROM parent_categories WHERE id = ? AND is_active = 1");
$stmt->execute([$parentId]);
$parentCat = $stmt->fetch();

if (!$parentCat) {
    header('Location: ' . SITE_URL . '/categories.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM category_mapping WHERE parent_category_id = ? ORDER BY sort_order ASC");
$stmt->execute([$parentId]);
$mappings = $stmt->fetchAll();

$parentApiId = $parentCat['api_category_id'];
$parentApiName = $parentCat['api_category_name'];
?>

<div class="page-header">
    <div class="container">
        <h1><?php echo htmlspecialchars($parentApiName); ?></h1>
        <div class="breadcrumb">
            <a href="<?php echo SITE_URL; ?>">Home</a> / <a href="<?php echo SITE_URL; ?>/categories.php">Categories</a> / <?php echo htmlspecialchars($parentApiName); ?>
        </div>
    </div>
</div>

<section class="section">
    <div class="container">
        <?php if (!empty($parentCat['description'])): ?>
        <p style="margin-bottom:30px;color:var(--text-light);font-size:16px;max-width:700px;"><?php echo htmlspecialchars($parentCat['description']); ?></p>
        <?php endif; ?>

        <?php if (!empty($mappings)): ?>
        <!-- Show subcategories as cards -->
        <div class="section-header" style="text-align:left;margin-bottom:25px;">
            <h2 style="font-size:24px;">Browse Subcategories</h2>
            <div class="accent-line" style="margin:10px 0 0;"></div>
        </div>
        <div class="category-grid" style="margin-bottom:40px;">
            <?php foreach ($mappings as $map): ?>
            <a href="<?php echo SITE_URL; ?>/products.php?category=<?php echo urlencode($map['api_category_id']); ?>" class="category-card">
                <div class="category-card-img">
                    <?php if (!empty($map['image'])): ?>
                    <img src="<?php echo UPLOAD_URL . htmlspecialchars($map['image']); ?>" alt="<?php echo htmlspecialchars($map['api_category_name']); ?>">
                    <div class="cat-overlay"></div>
                    <?php else: ?>
                    <i class="fas fa-utensils"></i>
                    <?php endif; ?>
                </div>
                <h3><?php echo htmlspecialchars($map['api_category_name']); ?></h3>
            </a>
            <?php endforeach; ?>
        </div>

        <hr style="border:none;border-top:1px solid var(--border);margin-bottom:40px;">
        <?php endif; ?>

        <!-- Also show the parent category's own products -->
        <div class="section-header" style="text-align:left;margin-bottom:25px;">
            <h2 style="font-size:24px;">All <?php echo htmlspecialchars($parentApiName); ?> Products</h2>
            <div class="accent-line" style="margin:10px 0 0;"></div>
        </div>
        <?php
        $result = getProductsByCategory($parentApiId);
        $products = $result['data'] ?? [];
        ?>
        <?php if (!empty($products)): ?>
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
        <div style="text-align:center;padding:40px 20px;">
            <i class="fas fa-box-open" style="font-size:50px;color:var(--text-muted);margin-bottom:15px;display:block;"></i>
            <p style="color:var(--text-muted);">No products found in this category. Browse subcategories above.</p>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
