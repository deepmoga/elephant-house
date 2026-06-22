<?php
require_once __DIR__ . '/includes/header.php';

$query = trim($_GET['q'] ?? '');
$results = [];

if (!empty($query)) {
    $allProducts = getProducts();
    $products = $allProducts['data'] ?? [];

    foreach ($products as $product) {
        if (stripos($product['name'], $query) !== false ||
            stripos($product['brand']['name'] ?? '', $query) !== false ||
            stripos($product['product_category']['name'] ?? '', $query) !== false) {
            $results[] = $product;
        }
    }
}
?>

<div class="page-header">
    <div class="container">
        <h1>Search Results</h1>
        <div class="breadcrumb">
            <a href="<?php echo SITE_URL; ?>">Home</a> / Search
            <?php if (!empty($query)): ?> / "<?php echo htmlspecialchars($query); ?>"<?php endif; ?>
        </div>
    </div>
</div>

<section class="section">
    <div class="container">
        <?php if (empty($query)): ?>
        <div style="text-align:center;padding:60px 20px;">
            <i class="fas fa-search" style="font-size:60px;color:var(--text-muted);margin-bottom:20px;display:block;"></i>
            <h3 style="color:var(--text-light);">Enter a search term to find products</h3>
        </div>

        <?php elseif (empty($results)): ?>
        <div style="text-align:center;padding:60px 20px;">
            <i class="fas fa-search" style="font-size:60px;color:var(--text-muted);margin-bottom:20px;display:block;"></i>
            <h3 style="color:var(--text-light);margin-bottom:10px;">No results found for "<?php echo htmlspecialchars($query); ?>"</h3>
            <p style="color:var(--text-muted);">Try a different search term or browse our categories.</p>
            <a href="<?php echo SITE_URL; ?>/categories.php" class="btn-view-all" style="margin-top:20px;">Browse Categories</a>
        </div>

        <?php else: ?>
        <p style="margin-bottom:25px;color:var(--text-light);">Found <?php echo count($results); ?> result(s) for "<?php echo htmlspecialchars($query); ?>"</p>
        <div class="product-grid">
            <?php foreach ($results as $product):
                $price = $product['price_including_tax'] ?? 0;
                $imgUrl = $product['image_url'] ?? '';
                $brand = $product['brand']['name'] ?? '';
            ?>
            <div class="product-card">
                <div class="product-img">
                    <?php if (!empty($imgUrl)): ?>
                    <img src="<?php echo htmlspecialchars($imgUrl); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" loading="lazy">
                    <?php else: ?>
                    <div class="no-img"><i class="fas fa-image"></i></div>
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
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
