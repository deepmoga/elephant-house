<?php
require_once __DIR__ . '/includes/header.php';

$query = trim($_GET['q'] ?? '');
$results = [];
$categoryResults = [];

if (!empty($query)) {
    $queryWords = preg_split('/\s+/', strtolower($query), -1, PREG_SPLIT_NO_EMPTY);
    $products = [];
    $cursor = null;
    $pagesChecked = 0;

    do {
        $allProducts = getProducts($cursor);
        $batch = $allProducts['data'] ?? [];
        $products = array_merge($products, $batch);
        $pageInfo = $allProducts['page_info'] ?? [];
        $cursor = $pageInfo['end_cursor'] ?? $pageInfo['next_cursor'] ?? null;
        $hasNext = !empty($pageInfo['has_next_page']) && !empty($cursor);
        $pagesChecked++;
    } while ($hasNext && $pagesChecked < 12);

    foreach ($products as $product) {
        $isActive = $product['is_active'] ?? $product['active'] ?? true;
        if (empty($isActive)) continue;
        $haystack = strtolower(implode(' ', [
            $product['name'] ?? '',
            $product['brand']['name'] ?? '',
            $product['product_category']['name'] ?? '',
            $product['sku'] ?? '',
            $product['description'] ?? '',
        ]));
        $matches = true;
        foreach ($queryWords as $word) {
            if (strpos($haystack, $word) === false) {
                $matches = false;
                break;
            }
        }
        if ($matches) {
            $results[] = $product;
        }
    }

    $parentCats = getParentCategories();
    foreach ($parentCats as $cat) {
        $catName = $cat['name'] ?: $cat['api_category_name'];
        if (stripos($catName, $query) !== false || stripos($cat['sub_api_names'] ?? '', $query) !== false) {
            $categoryResults[] = [
                'name' => $catName,
                'url' => SITE_URL . '/category.php?id=' . $cat['id'],
                'image' => $cat['image'] ?? '',
            ];
        }
    }

    $apiCategories = getCategories();
    foreach ($apiCategories as $cat) {
        if (stripos($cat['name'], $query) !== false) {
            $categoryResults[] = [
                'name' => $cat['name'],
                'url' => SITE_URL . '/products.php?category=' . urlencode($cat['id']),
                'image' => '',
            ];
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

        <?php elseif (empty($results) && empty($categoryResults)): ?>
        <div style="text-align:center;padding:60px 20px;">
            <i class="fas fa-search" style="font-size:60px;color:var(--text-muted);margin-bottom:20px;display:block;"></i>
            <h3 style="color:var(--text-light);margin-bottom:10px;">No results found for "<?php echo htmlspecialchars($query); ?>"</h3>
            <p style="color:var(--text-muted);">Try a different search term or browse our categories.</p>
            <a href="<?php echo SITE_URL; ?>/categories.php" class="btn-view-all" style="margin-top:20px;">Browse Categories</a>
        </div>

        <?php else: ?>
        <p style="margin-bottom:25px;color:var(--text-light);">Found <?php echo count($results) + count($categoryResults); ?> result(s) for "<?php echo htmlspecialchars($query); ?>"</p>

        <?php if (!empty($categoryResults)): ?>
        <div class="section-header" style="text-align:left;margin-bottom:20px;">
            <h2 style="font-size:24px;">Matching Categories</h2>
            <div class="accent-line" style="margin:10px 0 0;"></div>
        </div>
        <div class="category-grid" style="margin-bottom:40px;">
            <?php foreach ($categoryResults as $cat): ?>
            <a href="<?php echo htmlspecialchars($cat['url']); ?>" class="category-card">
                <div class="category-card-img">
                    <?php if (!empty($cat['image'])): ?>
                    <img src="<?php echo UPLOAD_URL . htmlspecialchars($cat['image']); ?>" alt="<?php echo htmlspecialchars($cat['name']); ?>">
                    <div class="cat-overlay"></div>
                    <?php else: ?>
                    <i class="fas fa-utensils"></i>
                    <?php endif; ?>
                </div>
                <h3><?php echo htmlspecialchars($cat['name']); ?></h3>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($results)): ?>
        <div class="section-header" style="text-align:left;margin-bottom:20px;">
            <h2 style="font-size:24px;">Matching Products</h2>
            <div class="accent-line" style="margin:10px 0 0;"></div>
        </div>
        <div class="product-grid">
            <?php foreach ($results as $product):
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
        <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
