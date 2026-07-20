<?php
require_once __DIR__ . '/includes/header.php';

$parentCats = getParentCategories();
$apiCategories = getCategories();
$subcategoryGroups = [];
$apiCategoryImages = [];
foreach ($parentCats as $parentCat) {
    $subcategories = getSubcategoryImages($parentCat['id']);
    $subcategoryGroups[$parentCat['id']] = $subcategories;
    foreach ($subcategories as $subcategory) {
        if (!empty($subcategory['image']) && empty($apiCategoryImages[$subcategory['api_category_id']])) {
            $apiCategoryImages[$subcategory['api_category_id']] = $subcategory['image'];
        }
    }
}
?>

<div class="page-header">
    <div class="container">
        <h1>All Categories</h1>
        <div class="breadcrumb">
            <a href="<?php echo SITE_URL; ?>">Home</a> / Categories
        </div>
    </div>
</div>

<section class="section">
    <div class="container">
        <?php if (!empty($parentCats)): ?>
            <?php foreach ($parentCats as $pCat): ?>
            <div style="margin-bottom: 50px;">
                <div class="section-header" style="text-align:left;margin-bottom:25px;">
                    <h2 style="font-size:28px;">
                        <a href="<?php echo SITE_URL; ?>/category.php?id=<?php echo $pCat['id']; ?>" style="color:var(--primary);">
                            <?php echo htmlspecialchars($pCat['name'] ?: $pCat['api_category_name']); ?>
                        </a>
                    </h2>
                    <?php if (!empty($pCat['description'])): ?>
                    <p style="margin:0;"><?php echo htmlspecialchars($pCat['description']); ?></p>
                    <?php endif; ?>
                    <div class="accent-line" style="margin:12px 0 0;"></div>
                </div>

                <?php $subcategories = $subcategoryGroups[$pCat['id']] ?? []; ?>
                <?php if (!empty($subcategories)): ?>
                <div class="category-grid">
                    <?php foreach ($subcategories as $subcategory): ?>
                    <a href="<?php echo SITE_URL; ?>/products.php?category=<?php echo urlencode($subcategory['api_category_id']); ?>" class="category-card">
                        <div class="category-card-img">
                            <?php if (!empty($subcategory['image'])): ?>
                            <img src="<?php echo UPLOAD_URL . htmlspecialchars($subcategory['image']); ?>" alt="<?php echo htmlspecialchars($subcategory['api_category_name']); ?>" loading="lazy">
                            <div class="cat-overlay"></div>
                            <?php else: ?>
                            <i class="fas fa-utensils"></i>
                            <?php endif; ?>
                        </div>
                        <h3><?php echo htmlspecialchars($subcategory['api_category_name']); ?></h3>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <?php if (strpos($pCat['api_category_id'], 'custom-') === 0): ?>
                <p style="color:var(--text-muted);">No subcategories have been added yet.</p>
                <?php else: ?>
                <a href="<?php echo SITE_URL; ?>/products.php?category=<?php echo urlencode($pCat['api_category_id']); ?>" class="btn-view-all" style="display:inline-block;">
                    View All <?php echo htmlspecialchars($pCat['name'] ?: $pCat['api_category_name']); ?> Products <i class="fas fa-arrow-right" style="margin-left:5px;"></i>
                </a>
                <?php endif; ?>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div style="margin-top: 30px;">
            <div class="section-header" style="text-align:left;margin-bottom:25px;">
                <h2 style="font-size:28px;">All Product Categories</h2>
                <p style="margin:0;">Browse all available categories from our store</p>
                <div class="accent-line" style="margin:12px 0 0;"></div>
            </div>
            <div class="category-grid">
                <?php foreach ($apiCategories as $cat): ?>
                <a href="<?php echo SITE_URL; ?>/products.php?category=<?php echo urlencode($cat['id']); ?>" class="category-card">
                    <div class="category-card-img">
                        <?php if (!empty($apiCategoryImages[$cat['id']])): ?>
                        <img src="<?php echo UPLOAD_URL . htmlspecialchars($apiCategoryImages[$cat['id']]); ?>" alt="<?php echo htmlspecialchars($cat['name']); ?>" loading="lazy">
                        <div class="cat-overlay"></div>
                        <?php else: ?>
                        <i class="fas fa-utensils"></i>
                        <?php endif; ?>
                    </div>
                    <h3><?php echo htmlspecialchars($cat['name']); ?></h3>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
