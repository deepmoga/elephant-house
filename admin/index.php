<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/layout.php';

$db = getDB();

$bannerCount = $db->query("SELECT COUNT(*) FROM banners")->fetchColumn();
$offerCount = $db->query("SELECT COUNT(*) FROM offer_banners")->fetchColumn();
$parentCatCount = $db->query("SELECT COUNT(*) FROM parent_categories")->fetchColumn();
$pageCount = $db->query("SELECT COUNT(*) FROM pages")->fetchColumn();
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-images"></i></div>
        <div class="stat-info">
            <h3><?php echo $bannerCount; ?></h3>
            <p>Homepage Banners</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fas fa-tags"></i></div>
        <div class="stat-info">
            <h3><?php echo $offerCount; ?></h3>
            <p>Offer Banners</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-sitemap"></i></div>
        <div class="stat-info">
            <h3><?php echo $parentCatCount; ?></h3>
            <p>Parent Categories</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fas fa-file-alt"></i></div>
        <div class="stat-info">
            <h3><?php echo $pageCount; ?></h3>
            <p>Content Pages</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Quick Actions</h2>
    </div>
    <div class="card-body">
        <div class="quick-links">
            <a href="<?php echo SITE_URL; ?>/admin/banners.php?action=add" class="quick-link">
                <i class="fas fa-plus-circle"></i>
                <span>Add Banner</span>
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/offers.php?action=add" class="quick-link">
                <i class="fas fa-percentage"></i>
                <span>Add Offer</span>
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/categories.php?action=add" class="quick-link">
                <i class="fas fa-folder-plus"></i>
                <span>Add Category</span>
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/pages.php" class="quick-link">
                <i class="fas fa-edit"></i>
                <span>Edit Pages</span>
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/settings.php" class="quick-link">
                <i class="fas fa-cog"></i>
                <span>Site Settings</span>
            </a>
            <a href="<?php echo SITE_URL; ?>" target="_blank" class="quick-link">
                <i class="fas fa-globe"></i>
                <span>View Website</span>
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Getting Started</h2>
    </div>
    <div class="card-body">
        <div style="line-height:2;color:var(--admin-text-light);">
            <p><strong>1.</strong> Go to <a href="<?php echo SITE_URL; ?>/admin/settings.php" style="color:var(--admin-primary-light);">Site Settings</a> and update your store contact details, logo, and social media links.</p>
            <p><strong>2.</strong> Go to <a href="<?php echo SITE_URL; ?>/admin/categories.php" style="color:var(--admin-primary-light);">Categories</a> to create parent categories and map API subcategories under them.</p>
            <p><strong>3.</strong> Go to <a href="<?php echo SITE_URL; ?>/admin/banners.php" style="color:var(--admin-primary-light);">Banners</a> to add homepage slider images.</p>
            <p><strong>4.</strong> Go to <a href="<?php echo SITE_URL; ?>/admin/offers.php" style="color:var(--admin-primary-light);">Offer Banners</a> to add promotional banners on the homepage.</p>
            <p><strong>5.</strong> Go to <a href="<?php echo SITE_URL; ?>/admin/pages.php" style="color:var(--admin-primary-light);">Pages</a> to edit About Us, Privacy Policy, and Terms & Conditions.</p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
