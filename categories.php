<?php
require_once __DIR__ . '/includes/header.php';

$parentCats = getParentCategories();
$apiCategories = getCategories();
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
                        <a href="<?php echo SITE_URL; ?>/products.php?category=<?php echo urlencode($pCat['api_category_id']); ?>" style="color:var(--primary);">
                            <?php echo htmlspecialchars($pCat['api_category_name']); ?>
                        </a>
                    </h2>
                    <?php if (!empty($pCat['description'])): ?>
                    <p style="margin:0;"><?php echo htmlspecialchars($pCat['description']); ?></p>
                    <?php endif; ?>
                    <div class="accent-line" style="margin:12px 0 0;"></div>
                </div>

                <?php if (!empty($pCat['sub_api_ids'])): ?>
                <div class="category-grid">
                    <?php
                    $subIds = explode(',', $pCat['sub_api_ids']);
                    $subNames = explode('||', $pCat['sub_api_names']);
                    foreach ($subIds as $idx => $subId):
                        $subName = trim($subNames[$idx] ?? '');
                    ?>
                    <a href="<?php echo SITE_URL; ?>/products.php?category=<?php echo urlencode(trim($subId)); ?>" class="category-card">
                        <div class="category-card-img">
                            <i class="fas fa-utensils"></i>
                        </div>
                        <h3><?php echo htmlspecialchars($subName); ?></h3>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <a href="<?php echo SITE_URL; ?>/products.php?category=<?php echo urlencode($pCat['api_category_id']); ?>" class="btn-view-all" style="display:inline-block;">
                    View All <?php echo htmlspecialchars($pCat['api_category_name']); ?> Products <i class="fas fa-arrow-right" style="margin-left:5px;"></i>
                </a>
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
                        <i class="fas fa-utensils"></i>
                    </div>
                    <h3><?php echo htmlspecialchars($cat['name']); ?></h3>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
