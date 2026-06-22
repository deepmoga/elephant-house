<?php
require_once __DIR__ . '/includes/header.php';

$productId = $_GET['id'] ?? '';
$product = null;

if (!empty($productId)) {
    $product = getProductById($productId);
}

if (!$product) {
    echo '<div class="page-header"><div class="container"><h1>Product Not Found</h1></div></div>';
    echo '<section class="section"><div class="container" style="text-align:center;padding:60px 20px;">';
    echo '<i class="fas fa-box-open" style="font-size:60px;color:var(--text-muted);margin-bottom:20px;display:block;"></i>';
    echo '<h3 style="color:var(--text-light);">This product could not be found.</h3>';
    echo '<a href="' . SITE_URL . '/categories.php" class="btn-view-all" style="margin-top:20px;display:inline-block;">Browse Categories</a>';
    echo '</div></section>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$price = $product['price_including_tax'] ?? 0;
$imgUrl = $product['image_url'] ?? '';
$brand = $product['brand']['name'] ?? '';
$catName = $product['product_category']['name'] ?? '';
$catId = $product['product_type_id'] ?? '';
$description = $product['description'] ?? '';
$sku = $product['sku'] ?? '';
$images = $product['images'] ?? [];
$isActive = !empty($product['is_active']);
$weight = $product['weight'] ?? null;
$weightUnit = $product['weight_unit'] ?? '';
$cartAllowed = isCategoryCartAllowed($catId);
?>

<div class="page-header">
    <div class="container">
        <h1><?php echo htmlspecialchars($product['name']); ?></h1>
        <div class="breadcrumb">
            <a href="<?php echo SITE_URL; ?>">Home</a>
            <?php if (!empty($catName)): ?>
            / <a href="<?php echo SITE_URL; ?>/products.php?category=<?php echo urlencode($catId); ?>"><?php echo htmlspecialchars($catName); ?></a>
            <?php endif; ?>
            / <?php echo htmlspecialchars($product['name']); ?>
        </div>
    </div>
</div>

<section class="section">
    <div class="container">
        <div class="product-detail">
            <div class="product-gallery">
                <div class="gallery-main">
                    <?php if (!empty($images[0]['sizes']['original'])): ?>
                    <img id="mainImage" src="<?php echo htmlspecialchars($images[0]['sizes']['original']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <?php elseif (!empty($imgUrl)): ?>
                    <img id="mainImage" src="<?php echo htmlspecialchars($imgUrl); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <?php else: ?>
                    <div class="no-img" style="height:400px;"><i class="fas fa-image"></i></div>
                    <?php endif; ?>
                </div>
                <?php if (count($images) > 1): ?>
                <div class="gallery-thumbs">
                    <?php foreach ($images as $img): ?>
                    <img src="<?php echo htmlspecialchars($img['sizes']['thumb'] ?? $img['url']); ?>"
                         data-full="<?php echo htmlspecialchars($img['sizes']['original'] ?? $img['url']); ?>"
                         alt="" class="gallery-thumb" onclick="document.getElementById('mainImage').src=this.dataset.full;document.querySelectorAll('.gallery-thumb').forEach(t=>t.classList.remove('active'));this.classList.add('active');">
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="product-meta">
                <?php if (!empty($brand)): ?>
                <span class="product-brand" style="font-size:13px;"><?php echo htmlspecialchars($brand); ?></span>
                <?php endif; ?>

                <h1 style="font-family:'Playfair Display',serif;font-size:28px;color:var(--text);margin:10px 0 15px;"><?php echo htmlspecialchars($product['name']); ?></h1>

                <div class="product-price" style="border:none;padding:0;margin-bottom:20px;">
                    <span class="price" style="font-size:32px;">$<?php echo number_format($price, 2); ?></span>
                    <?php if (!$isActive): ?>
                    <span style="background:var(--warm);color:#fff;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;margin-left:10px;">Out of Stock</span>
                    <?php else: ?>
                    <span style="background:#28a745;color:#fff;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;margin-left:10px;">In Stock</span>
                    <?php endif; ?>
                </div>

                <?php if (!empty($catName)): ?>
                <p style="margin-bottom:8px;font-size:14px;color:var(--text-light);"><strong>Category:</strong> <a href="<?php echo SITE_URL; ?>/products.php?category=<?php echo urlencode($catId); ?>" style="color:var(--primary);"><?php echo htmlspecialchars($catName); ?></a></p>
                <?php endif; ?>
                <?php if (!empty($sku)): ?>
                <p style="margin-bottom:8px;font-size:14px;color:var(--text-light);"><strong>SKU:</strong> <?php echo htmlspecialchars($sku); ?></p>
                <?php endif; ?>
                <?php if ($weight): ?>
                <p style="margin-bottom:8px;font-size:14px;color:var(--text-light);"><strong>Weight:</strong> <?php echo htmlspecialchars($weight . ' ' . $weightUnit); ?></p>
                <?php endif; ?>

                <?php if ($isActive && $cartAllowed): ?>
                <div style="margin:25px 0;padding:20px 0;border-top:1px solid var(--border);border-bottom:1px solid var(--border);">
                    <div class="quantity-selector">
                        <button type="button" class="qty-btn qty-minus">-</button>
                        <input type="number" id="productQty" value="1" min="1" max="99" class="qty-input">
                        <button type="button" class="qty-btn qty-plus">+</button>
                    </div>
                    <button class="btn-add-cart"
                        data-id="<?php echo htmlspecialchars($product['id']); ?>"
                        data-name="<?php echo htmlspecialchars($product['name']); ?>"
                        data-price="<?php echo $price; ?>"
                        data-image="<?php echo htmlspecialchars($imgUrl); ?>">
                        <i class="fas fa-shopping-cart"></i> Add to Cart
                    </button>
                </div>
                <?php elseif ($isActive && !$cartAllowed): ?>
                <div style="margin:25px 0;padding:15px 20px;border-top:1px solid var(--border);border-bottom:1px solid var(--border);background:var(--cream);border-radius:8px;">
                    <p style="color:var(--text-light);font-size:14px;margin:0;"><i class="fas fa-store" style="margin-right:8px;color:var(--primary);"></i> This product is available in-store only. Visit us or call to order.</p>
                </div>
                <?php endif; ?>

                <?php if (!empty($description)): ?>
                <div style="margin-top:20px;">
                    <h3 style="font-size:16px;color:var(--primary);margin-bottom:10px;">Description</h3>
                    <div style="color:var(--text-light);line-height:1.8;font-size:14px;"><?php echo $description; ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php
        if (!empty($catId)) {
            $related = getProductsByCategory($catId);
            $relatedProducts = array_filter($related['data'] ?? [], function($p) use ($product) {
                return $p['id'] !== $product['id'] && !empty($p['is_active']);
            });
            $relatedProducts = array_slice($relatedProducts, 0, 4);
            if (!empty($relatedProducts)):
        ?>
        <div style="margin-top:60px;">
            <div class="section-header">
                <h2>Related Products</h2>
                <div class="accent-line"></div>
            </div>
            <div class="product-grid">
                <?php foreach ($relatedProducts as $rp):
                    $rpPrice = $rp['price_including_tax'] ?? 0;
                    $rpImg = $rp['image_url'] ?? '';
                    $rpBrand = $rp['brand']['name'] ?? '';
                ?>
                <a href="<?php echo SITE_URL; ?>/product.php?id=<?php echo urlencode($rp['id']); ?>" class="product-card">
                    <div class="product-img">
                        <?php if (!empty($rpImg)): ?>
                        <img src="<?php echo htmlspecialchars($rpImg); ?>" alt="<?php echo htmlspecialchars($rp['name']); ?>" loading="lazy">
                        <?php else: ?>
                        <div class="no-img"><i class="fas fa-image"></i></div>
                        <?php endif; ?>
                    </div>
                    <div class="product-info">
                        <?php if (!empty($rpBrand)): ?>
                        <span class="product-brand"><?php echo htmlspecialchars($rpBrand); ?></span>
                        <?php endif; ?>
                        <h3><?php echo htmlspecialchars($rp['name']); ?></h3>
                        <div class="product-price">
                            <span class="price">$<?php echo number_format($rpPrice, 2); ?></span>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; } ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
