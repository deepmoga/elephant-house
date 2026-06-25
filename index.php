<?php
require_once __DIR__ . '/includes/header.php';

$banners = getActiveBanners();
$offers = getActiveOffers();
$parentCats = getParentCategories();
$featuredCats = getFeaturedCategories();
$apiCategories = getCategories();
$blogs = getActiveBlogs(3);

$featuredProducts = getProducts();
$products = $featuredProducts['data'] ?? [];
$activeProducts = array_filter($products, function($p) { return !empty($p['is_active']); });
$latestProducts = array_slice(array_values($activeProducts), 0, 8);

$firstFeaturedId = !empty($featuredCats) ? $featuredCats[0]['api_category_id'] : '';
?>

<!-- Hero Slider -->
<?php if (!empty($banners)): ?>
<section class="hero-slider">
    <?php foreach ($banners as $i => $banner): ?>
    <div class="hero-slide <?php echo $i === 0 ? 'active' : ''; ?>"
         style="background-image: url('<?php echo UPLOAD_URL . 'banners/' . htmlspecialchars($banner['image']); ?>');">
        <div class="hero-overlay">
            <div class="container">
                <div class="hero-content">
                    <?php if (!empty($banner['title'])): ?><h1><?php echo htmlspecialchars($banner['title']); ?></h1><?php endif; ?>
                    <?php if (!empty($banner['subtitle'])): ?><p><?php echo htmlspecialchars($banner['subtitle']); ?></p><?php endif; ?>
                    <a href="<?php echo htmlspecialchars($banner['link'] ?: SITE_URL . '/categories.php'); ?>" class="btn btn-primary">Shop Now</a>
                    <a href="<?php echo SITE_URL; ?>/page.php?slug=about-us" class="btn btn-outline">Learn More</a>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php if (count($banners) > 1): ?>
    <button class="slider-arrow prev"><i class="fas fa-chevron-left"></i></button>
    <button class="slider-arrow next"><i class="fas fa-chevron-right"></i></button>
    <div class="slider-dots">
        <?php foreach ($banners as $i => $b): ?>
        <button class="slider-dot <?php echo $i === 0 ? 'active' : ''; ?>"></button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>
<?php else: ?>
<section class="hero-default">
    <div class="hero-content">
        <h1>Welcome to <?php echo htmlspecialchars($settings['site_name'] ?? 'Elephant House'); ?></h1>
        <p><?php echo htmlspecialchars($settings['site_tagline'] ?? 'Your premium Sri Lankan & Asian grocery store'); ?></p>
        <a href="<?php echo SITE_URL; ?>/categories.php" class="btn btn-primary">Explore Products</a>
    </div>
</section>
<?php endif; ?>

<!-- Featured Categories with AJAX Products -->
<?php if (!empty($featuredCats)): ?>
<section class="section" style="background:var(--white);">
    <div class="container">
        <div class="section-header">
            <h2>Featured Categories</h2>
            <p>Explore our handpicked collections</p>
            <div class="accent-line"></div>
        </div>

        <div class="featured-section">
            <!-- Left: Category Tabs -->
            <div class="featured-tabs">
                <?php foreach ($featuredCats as $i => $fc): ?>
                <button class="featured-tab <?php echo $i === 0 ? 'active' : ''; ?>"
                    data-category="<?php echo htmlspecialchars($fc['api_category_id']); ?>"
                    onclick="loadFeaturedProducts(this)">
                    <?php if (!empty($fc['image'])): ?>
                    <img src="<?php echo UPLOAD_URL . htmlspecialchars($fc['image']); ?>" alt="" class="featured-tab-img">
                    <?php else: ?>
                    <i class="fas fa-utensils featured-tab-icon"></i>
                    <?php endif; ?>
                    <span><?php echo htmlspecialchars($fc['api_category_name']); ?></span>
                    <i class="fas fa-chevron-right featured-tab-arrow"></i>
                </button>
                <?php endforeach; ?>
                <a href="<?php echo SITE_URL; ?>/categories.php" class="featured-tab" style="color:var(--primary);justify-content:center;gap:8px;border:2px dashed var(--border);">
                    <i class="fas fa-th-large"></i> <span>View All Categories</span>
                </a>
            </div>

            <!-- Right: Products Grid -->
            <div class="featured-products">
                <div id="featuredProductsGrid" class="product-grid">
                    <div class="loading-spinner" id="featuredLoading"></div>
                </div>
                <div class="view-all-wrap" style="margin-top:25px;" id="featuredViewAll">
                    <a href="<?php echo SITE_URL; ?>/products.php?category=<?php echo urlencode($firstFeaturedId); ?>" class="btn-view-all" id="featuredViewAllLink">
                        View All Products <i class="fas fa-arrow-right" style="margin-left:8px;"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Shop by Category Grid -->
<section class="section">
    <div class="container">
        <div class="section-header">
            <h2>Shop by Category</h2>
            <p>Browse our wide range of authentic products</p>
            <div class="accent-line"></div>
        </div>
        <div class="category-grid">
            <?php if (!empty($parentCats)): ?>
                <?php foreach (array_slice($parentCats, 0, 8) as $pCat): ?>
                <a href="<?php echo SITE_URL; ?>/category.php?id=<?php echo $pCat['id']; ?>" class="category-card">
                    <div class="category-card-img">
                        <?php if (!empty($pCat['image'])): ?>
                        <img src="<?php echo UPLOAD_URL . htmlspecialchars($pCat['image']); ?>" alt="<?php echo htmlspecialchars($pCat['api_category_name']); ?>">
                        <div class="cat-overlay"></div>
                        <?php else: ?>
                        <i class="fas fa-utensils"></i>
                        <?php endif; ?>
                    </div>
                    <h3>
                        <?php echo htmlspecialchars($pCat['api_category_name']); ?>
                        <?php $subCount = !empty($pCat['sub_api_ids']) ? count(explode(',', $pCat['sub_api_ids'])) : 0; ?>
                        <?php if ($subCount > 0): ?><span class="sub-count"><?php echo $subCount; ?> subcategories</span><?php endif; ?>
                    </h3>
                </a>
                <?php endforeach; ?>
            <?php else: ?>
                <?php foreach (array_slice($apiCategories, 0, 8) as $cat): ?>
                <a href="<?php echo SITE_URL; ?>/products.php?category=<?php echo urlencode($cat['id']); ?>" class="category-card">
                    <div class="category-card-img"><i class="fas fa-utensils"></i></div>
                    <h3><?php echo htmlspecialchars($cat['name']); ?></h3>
                </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="view-all-wrap">
            <a href="<?php echo SITE_URL; ?>/categories.php" class="btn-view-all">View All Categories <i class="fas fa-arrow-right" style="margin-left:8px;"></i></a>
        </div>
    </div>
</section>

<!-- Offer Banners -->
<?php if (!empty($offers)): ?>
<section class="section offers-section">
    <div class="container">
        <div class="section-header">
            <h2>Special Offers</h2>
            <p>Don't miss out on our latest deals</p>
            <div class="accent-line"></div>
        </div>
        <div class="offers-grid">
            <?php foreach ($offers as $offer): ?>
            <a href="<?php echo htmlspecialchars($offer['link'] ?: '#'); ?>" class="offer-card"
               style="background-image: url('<?php echo UPLOAD_URL . 'offers/' . htmlspecialchars($offer['image']); ?>');">
                <div class="offer-card-overlay">
                    <div class="offer-card-content">
                        <?php if (!empty($offer['title'])): ?><h3><?php echo htmlspecialchars($offer['title']); ?></h3><?php endif; ?>
                        <?php if (!empty($offer['description'])): ?><p><?php echo htmlspecialchars($offer['description']); ?></p><?php endif; ?>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Latest Products -->
<?php if (!empty($latestProducts)): ?>
<section class="section">
    <div class="container">
        <div class="section-header">
            <h2>Latest Products</h2>
            <p>Discover our newest arrivals</p>
            <div class="accent-line"></div>
        </div>
        <div class="product-grid">
            <?php foreach ($latestProducts as $product):
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
                    <?php if (!empty($brand)): ?><span class="product-brand"><?php echo htmlspecialchars($brand); ?></span><?php endif; ?>
                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                    <div class="product-price"><span class="price">$<?php echo number_format($price, 2); ?></span></div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <div class="view-all-wrap">
            <a href="<?php echo SITE_URL; ?>/categories.php" class="btn-view-all">Browse All Products <i class="fas fa-arrow-right" style="margin-left:8px;"></i></a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Why Shop With Us -->
<section class="section" style="background:linear-gradient(135deg, var(--primary-dark), var(--primary));color:var(--white);">
    <div class="container">
        <div class="section-header" style="color:var(--white);">
            <h2 style="color:var(--white);">Why Shop With Us</h2>
            <p style="color:rgba(255,255,255,0.7);">Your trusted destination for authentic groceries</p>
            <div class="accent-line" style="background:var(--accent);"></div>
        </div>
        <div class="why-grid">
            <div class="why-item">
                <div class="why-icon"><i class="fas fa-seedling"></i></div>
                <h4>Authentic Products</h4>
                <p>Genuine Sri Lankan & Asian groceries sourced directly from trusted suppliers</p>
            </div>
            <div class="why-item">
                <div class="why-icon"><i class="fas fa-tags"></i></div>
                <h4>Best Prices</h4>
                <p>Competitive pricing on all products with regular special offers</p>
            </div>
            <div class="why-item">
                <div class="why-icon"><i class="fas fa-truck"></i></div>
                <h4>Australia-Wide Delivery</h4>
                <p>Fast, reliable shipping to all states across Australia</p>
            </div>
            <div class="why-item">
                <div class="why-icon"><i class="fas fa-headset"></i></div>
                <h4>Friendly Service</h4>
                <p>Dedicated customer support to help you find what you need</p>
            </div>
        </div>
    </div>
</section>

<!-- Blog Highlights -->
<?php if (!empty($blogs)): ?>
<section class="section">
    <div class="container">
        <div class="section-header">
            <h2>From Our Blog</h2>
            <p>Latest news, recipes and updates</p>
            <div class="accent-line"></div>
        </div>
        <div class="blog-grid">
            <?php foreach ($blogs as $post): ?>
            <a href="<?php echo SITE_URL; ?>/blog.php?slug=<?php echo urlencode($post['slug']); ?>" class="blog-card">
                <div class="blog-card-img">
                    <?php if (!empty($post['image'])): ?>
                    <img src="<?php echo UPLOAD_URL . 'blogs/' . htmlspecialchars($post['image']); ?>" alt="">
                    <?php else: ?>
                    <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,var(--primary),var(--primary-light));color:var(--accent-light);font-size:40px;"><i class="fas fa-newspaper"></i></div>
                    <?php endif; ?>
                </div>
                <div class="blog-card-body">
                    <div class="blog-meta">
                        <span><i class="fas fa-calendar"></i> <?php echo date('d M Y', strtotime($post['created_at'])); ?></span>
                    </div>
                    <h3><?php echo htmlspecialchars($post['title']); ?></h3>
                    <?php if (!empty($post['excerpt'])): ?><p><?php echo htmlspecialchars($post['excerpt']); ?></p><?php endif; ?>
                    <span class="read-more">Read More <i class="fas fa-arrow-right"></i></span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <div class="view-all-wrap">
            <a href="<?php echo SITE_URL; ?>/blogs.php" class="btn-view-all">View All Posts <i class="fas fa-arrow-right" style="margin-left:8px;"></i></a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- AJAX for Featured Products -->
<script>
var _featSiteUrl = '';
(function() {
    var m = document.querySelector('link[rel="stylesheet"][href*="/css/style.css"]');
    if (m) _featSiteUrl = m.getAttribute('href').split('/css/style.css')[0];
})();

function loadFeaturedProducts(btn) {
    if (!btn || !btn.dataset || !btn.dataset.category) return;
    var catId = btn.dataset.category;

    document.querySelectorAll('.featured-tab').forEach(function(t) { t.classList.remove('active'); });
    btn.classList.add('active');

    var grid = document.getElementById('featuredProductsGrid');
    if (!grid) return;
    grid.innerHTML = '<div class="loading-spinner"></div>';

    var link = document.getElementById('featuredViewAllLink');
    if (link) link.href = _featSiteUrl + '/products.php?category=' + encodeURIComponent(catId);

    fetch(_featSiteUrl + '/api/home-products.php?category_id=' + encodeURIComponent(catId) + '&limit=8')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success || data.products.length === 0) {
                grid.innerHTML = '<p style="text-align:center;padding:40px;color:var(--text-muted);grid-column:1/-1;">No products found in this category.</p>';
                return;
            }
            var html = '';
            data.products.forEach(function(p) {
                html += '<a href="' + _featSiteUrl + '/product.php?id=' + encodeURIComponent(p.id) + '" class="product-card">' +
                    '<div class="product-img">' +
                    (p.image ? '<img src="' + p.image.replace(/"/g, '&quot;') + '" alt="" loading="lazy">' : '<div class="no-img"><i class="fas fa-image"></i></div>') +
                    '</div>' +
                    '<div class="product-info">' +
                    (p.brand ? '<span class="product-brand">' + p.brand.replace(/</g, '&lt;') + '</span>' : '') +
                    '<h3>' + p.name.replace(/</g, '&lt;') + '</h3>' +
                    '<div class="product-price"><span class="price">$' + p.price + '</span></div>' +
                    '</div></a>';
            });
            grid.innerHTML = html;
        })
        .catch(function() {
            grid.innerHTML = '<p style="text-align:center;padding:40px;color:var(--text-muted);grid-column:1/-1;">Error loading products.</p>';
        });
}

document.addEventListener('DOMContentLoaded', function() {
    var firstTab = document.querySelector('.featured-tab[data-category]');
    if (firstTab) loadFeaturedProducts(firstTab);
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
