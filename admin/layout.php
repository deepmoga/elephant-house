<?php
require_once __DIR__ . '/auth.php';
requireLogin();
require_once __DIR__ . '/../api/fetch.php';

$currentAdminPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Dashboard'; ?> - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/css/admin.css">
</head>
<body>
<div class="admin-wrapper">

    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-store"></i> Elephant House</h2>
            <small>Admin Panel</small>
        </div>
        <nav class="sidebar-nav">
            <a href="<?php echo SITE_URL; ?>/admin/" class="<?php echo $currentAdminPage === 'index' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>

            <div class="nav-section">Content</div>
            <a href="<?php echo SITE_URL; ?>/admin/banners.php" class="<?php echo $currentAdminPage === 'banners' ? 'active' : ''; ?>">
                <i class="fas fa-images"></i> Banners
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/offers.php" class="<?php echo $currentAdminPage === 'offers' ? 'active' : ''; ?>">
                <i class="fas fa-tags"></i> Offer Banners
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/home-sections.php" class="<?php echo $currentAdminPage === 'home-sections' ? 'active' : ''; ?>">
                <i class="fas fa-layer-group"></i> Home Sections
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/categories.php" class="<?php echo $currentAdminPage === 'categories' ? 'active' : ''; ?>">
                <i class="fas fa-sitemap"></i> Categories
            </a>

            <div class="nav-section">Shop</div>
            <a href="<?php echo SITE_URL; ?>/admin/orders.php" class="<?php echo $currentAdminPage === 'orders' ? 'active' : ''; ?>">
                <i class="fas fa-shopping-bag"></i> Orders
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/customers.php" class="<?php echo $currentAdminPage === 'customers' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> Customers
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/coupons.php" class="<?php echo $currentAdminPage === 'coupons' ? 'active' : ''; ?>">
                <i class="fas fa-ticket-alt"></i> Coupons
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/shipping.php" class="<?php echo $currentAdminPage === 'shipping' ? 'active' : ''; ?>">
                <i class="fas fa-truck"></i> Shipping Rates
            </a>

            <div class="nav-section">Content</div>
            <a href="<?php echo SITE_URL; ?>/admin/pages.php" class="<?php echo $currentAdminPage === 'pages' ? 'active' : ''; ?>">
                <i class="fas fa-file-alt"></i> Pages
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/blogs.php" class="<?php echo $currentAdminPage === 'blogs' ? 'active' : ''; ?>">
                <i class="fas fa-blog"></i> Blog Posts
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/faqs.php" class="<?php echo $currentAdminPage === 'faqs' ? 'active' : ''; ?>">
                <i class="fas fa-question-circle"></i> FAQs
            </a>

            <div class="nav-section">Settings</div>
            <a href="<?php echo SITE_URL; ?>/admin/settings.php" class="<?php echo $currentAdminPage === 'settings' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i> Site Settings
            </a>

            <div class="divider"></div>
            <a href="<?php echo SITE_URL; ?>" target="_blank">
                <i class="fas fa-external-link-alt"></i> View Website
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="admin-main">
        <div class="admin-topbar">
            <div style="display:flex;align-items:center;gap:15px;">
                <button class="sidebar-toggle"><i class="fas fa-bars"></i></button>
                <h1><?php echo $pageTitle ?? 'Dashboard'; ?></h1>
            </div>
            <div class="user-info">
                <span style="font-size:14px;color:var(--admin-text-light);">
                    <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars(getAdminName()); ?>
                </span>
                <a href="<?php echo SITE_URL; ?>/admin/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <div class="admin-content">
