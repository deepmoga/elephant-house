<?php
require_once __DIR__ . '/includes/header.php';

$banners = getActiveBanners();
$offers = getActiveOffers();
$parentCats = getParentCategories();

$apiCategories = getCategories();

$featuredProducts = getProducts();
$products = $featuredProducts['data'] ?? [];
?>

<!-- Hero Section -->
<?php if (!empty($banners)): ?>
<section class="hero-slider">
    <?php foreach ($banners as $i => $banner): ?>
    <div class="hero-slide <?php echo $i === 0 ? 'active' : ''; ?>"
         style="background-image: url('<?php echo UPLOAD_URL . 'banners/' . htmlspecialchars($banner['image']); ?>');">
        <div class="hero-overlay">
            <div class="container">
                <div class="hero-content">
                    <?php if (!empty($banner['title'])): ?>
                    <h1><?php echo htmlspecialchars($banner['title']); ?></h1>
                    <?php endif; ?>
                    <?php if (!empty($banner['subtitle'])): ?>
                    <p><?php echo htmlspecialchars($banner['subtitle']); ?></p>
                    <?php endif; ?>
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

<!-- Main Content with Sidebar -->
<section class="section">
    <div class="container">
        <div class="home-layout">
            <!-- Left Sidebar - Category List -->
            <aside class="home-sidebar">
                <div class="sidebar-cat-header">
                    <i class="fas fa-th-list"></i> All Categories
                </div>
                <div class="sidebar-cat-list">
                    <?php if (!empty($parentCats)): ?>
                        <?php foreach ($parentCats as $pCat): ?>
                        <div class="sidebar-cat-group">
                            <a href="<?php echo SITE_URL; ?>/category.php?id=<?php echo $pCat['id']; ?>" class="sidebar-cat-parent">
                                <?php echo htmlspecialchars($pCat['api_category_name']); ?>
                                <?php if (!empty($pCat['sub_api_ids'])): ?>
                                <i class="fas fa-chevron-right"></i>
                                <?php endif; ?>
                            </a>
                            <?php if (!empty($pCat['sub_api_names'])): ?>
                            <div class="sidebar-cat-subs">
                                <?php
                                $subIds = explode(',', $pCat['sub_api_ids']);
                                $subNames = explode('||', $pCat['sub_api_names']);
                                foreach ($subIds as $idx => $subId):
                                    $subName = trim($subNames[$idx] ?? '');
                                ?>
                                <a href="<?php echo SITE_URL; ?>/products.php?category=<?php echo urlencode(trim($subId)); ?>">
                                    <?php echo htmlspecialchars($subName); ?>
                                </a>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php foreach ($apiCategories as $cat): ?>
                        <a href="<?php echo SITE_URL; ?>/products.php?category=<?php echo urlencode($cat['id']); ?>" class="sidebar-cat-parent">
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </aside>

            <!-- Right Content -->
            <div class="home-main">
                <!-- Category Cards -->
                <div class="section-header">
                    <h2>Shop by Category</h2>
                    <p>Browse our wide range of authentic products</p>
                    <div class="accent-line"></div>
                </div>

                <div class="category-grid">
                    <?php if (!empty($parentCats)): ?>
                        <?php foreach ($parentCats as $pCat): ?>
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
                                <?php
                                $subCount = !empty($pCat['sub_api_ids']) ? count(explode(',', $pCat['sub_api_ids'])) : 0;
                                if ($subCount > 0): ?>
                                <span class="sub-count"><?php echo $subCount; ?> subcategories</span>
                                <?php endif; ?>
                            </h3>
                        </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php
                        $displayCats = array_slice($apiCategories, 0, 12);
                        foreach ($displayCats as $cat):
                        ?>
                        <a href="<?php echo SITE_URL; ?>/products.php?category=<?php echo urlencode($cat['id']); ?>" class="category-card">
                            <div class="category-card-img">
                                <i class="fas fa-utensils"></i>
                            </div>
                            <h3><?php echo htmlspecialchars($cat['name']); ?></h3>
                        </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="view-all-wrap">
                    <a href="<?php echo SITE_URL; ?>/categories.php" class="btn-view-all">View All Categories <i class="fas fa-arrow-right" style="margin-left:8px;"></i></a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Offers Section -->
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
                        <?php if (!empty($offer['title'])): ?>
                        <h3><?php echo htmlspecialchars($offer['title']); ?></h3>
                        <?php endif; ?>
                        <?php if (!empty($offer['description'])): ?>
                        <p><?php echo htmlspecialchars($offer['description']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Featured Products -->
<?php if (!empty($products)): ?>
<section class="section">
    <div class="container">
        <div class="section-header">
            <h2>Featured Products</h2>
            <p>Discover our most popular items</p>
            <div class="accent-line"></div>
        </div>

        <div class="product-grid">
            <?php
            $activeProducts = array_filter($products, function($p) { return !empty($p['is_active']); });
            $displayProducts = array_slice($activeProducts, 0, 8);
            foreach ($displayProducts as $product):
                $price = $product['price_including_tax'] ?? 0;
                $imgUrl = $product['image_url'] ?? '';
                $brand = $product['brand']['name'] ?? '';
                $catName = $product['product_category']['name'] ?? '';
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
                    <?php if (!empty($catName)): ?>
                    <span class="product-category-tag"><?php echo htmlspecialchars($catName); ?></span>
                    <?php endif; ?>
                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                    <div class="product-price">
                        <span class="price">$<?php echo number_format($price, 2); ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="view-all-wrap">
            <a href="<?php echo SITE_URL; ?>/categories.php" class="btn-view-all">Browse All Products <i class="fas fa-arrow-right" style="margin-left:8px;"></i></a>
        </div>
    </div>
</section>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
