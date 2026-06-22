<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/layout.php';

$db = getDB();

$bannerCount = $db->query("SELECT COUNT(*) FROM banners")->fetchColumn();
$offerCount = $db->query("SELECT COUNT(*) FROM offer_banners")->fetchColumn();
$parentCatCount = $db->query("SELECT COUNT(*) FROM parent_categories")->fetchColumn();
$pageCount = $db->query("SELECT COUNT(*) FROM pages")->fetchColumn();
$orderCount = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$customerCount = $db->query("SELECT COUNT(*) FROM customers")->fetchColumn();
$blogCount = $db->query("SELECT COUNT(*) FROM blogs")->fetchColumn();
$couponCount = $db->query("SELECT COUNT(*) FROM coupons WHERE is_active = 1")->fetchColumn();
$pendingOrders = $db->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
$totalRevenue = $db->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE status NOT IN ('cancelled')")->fetchColumn();
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon red"><i class="fas fa-shopping-bag"></i></div>
        <div class="stat-info">
            <h3><?php echo $orderCount; ?></h3>
            <p>Total Orders</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fas fa-clock"></i></div>
        <div class="stat-info">
            <h3><?php echo $pendingOrders; ?></h3>
            <p>Pending Orders</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-dollar-sign"></i></div>
        <div class="stat-info">
            <h3>$<?php echo number_format($totalRevenue, 2); ?></h3>
            <p>Total Revenue</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <h3><?php echo $customerCount; ?></h3>
            <p>Customers</p>
        </div>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-images"></i></div>
        <div class="stat-info"><h3><?php echo $bannerCount; ?></h3><p>Banners</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fas fa-sitemap"></i></div>
        <div class="stat-info"><h3><?php echo $parentCatCount; ?></h3><p>Categories</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-blog"></i></div>
        <div class="stat-info"><h3><?php echo $blogCount; ?></h3><p>Blog Posts</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fas fa-ticket-alt"></i></div>
        <div class="stat-info"><h3><?php echo $couponCount; ?></h3><p>Active Coupons</p></div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h2>Quick Actions</h2></div>
    <div class="card-body">
        <div class="quick-links">
            <a href="<?php echo SITE_URL; ?>/admin/orders.php" class="quick-link"><i class="fas fa-shopping-bag"></i><span>View Orders</span></a>
            <a href="<?php echo SITE_URL; ?>/admin/banners.php?action=add" class="quick-link"><i class="fas fa-plus-circle"></i><span>Add Banner</span></a>
            <a href="<?php echo SITE_URL; ?>/admin/categories.php?action=add" class="quick-link"><i class="fas fa-folder-plus"></i><span>Add Category</span></a>
            <a href="<?php echo SITE_URL; ?>/admin/coupons.php?action=add" class="quick-link"><i class="fas fa-ticket-alt"></i><span>Add Coupon</span></a>
            <a href="<?php echo SITE_URL; ?>/admin/blogs.php?action=add" class="quick-link"><i class="fas fa-pen"></i><span>Add Blog Post</span></a>
            <a href="<?php echo SITE_URL; ?>/admin/faqs.php?action=add" class="quick-link"><i class="fas fa-question-circle"></i><span>Add FAQ</span></a>
            <a href="<?php echo SITE_URL; ?>/admin/settings.php" class="quick-link"><i class="fas fa-cog"></i><span>Site Settings</span></a>
            <a href="<?php echo SITE_URL; ?>" target="_blank" class="quick-link"><i class="fas fa-globe"></i><span>View Website</span></a>
        </div>
    </div>
</div>

<?php
$recentOrders = $db->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5")->fetchAll();
if (!empty($recentOrders)):
?>
<div class="card">
    <div class="card-header"><h2>Recent Orders</h2><a href="<?php echo SITE_URL; ?>/admin/orders.php" class="btn btn-outline btn-sm">View All</a></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Order #</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach ($recentOrders as $o): ?>
                <tr>
                    <td><a href="<?php echo SITE_URL; ?>/admin/orders.php?action=view&id=<?php echo $o['id']; ?>" style="color:var(--admin-primary);font-weight:600;"><?php echo htmlspecialchars($o['order_number']); ?></a></td>
                    <td><?php echo htmlspecialchars($o['name']); ?></td>
                    <td>$<?php echo number_format($o['total'], 2); ?></td>
                    <td><span class="badge badge-<?php echo $o['status'] === 'delivered' ? 'success' : ($o['status'] === 'cancelled' ? 'danger' : 'warning'); ?>"><?php echo ucfirst($o['status']); ?></span></td>
                    <td style="font-size:12px;"><?php echo date('d M Y', strtotime($o['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
