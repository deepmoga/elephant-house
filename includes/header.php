<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../api/fetch.php';
$settings = getAllSettings();
$parentCategories = getParentCategories();
$menuCategories = getMenuCategories();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$cartCount = getCartCount();

$db = getDB();
$menuCatsWithSubs = [];
foreach ($menuCategories as $mc) {
    $subs = $db->prepare("SELECT api_category_id, api_category_name, image FROM category_mapping WHERE parent_category_id = ? ORDER BY sort_order ASC");
    $subs->execute([$mc['id']]);
    $mc['subcategories'] = $subs->fetchAll();
    $menuCatsWithSubs[] = $mc;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($settings['site_name'] ?? 'Elephant House'); ?> - <?php echo htmlspecialchars($settings['site_tagline'] ?? 'Premium Grocery Store'); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/css/style.css?v=<?php echo time(); ?>">
    <?php if (!empty($settings['favicon'])): ?>
    <link rel="icon" href="<?php echo UPLOAD_URL . htmlspecialchars($settings['favicon']); ?>">
    <?php endif; ?>
</head>
<body>

<!-- Top Bar -->
<div class="top-bar">
    <div class="container">
        <div class="top-bar-left">
            <?php if (!empty($settings['phone'])): ?>
            <span><i class="fas fa-phone"></i> <?php echo htmlspecialchars($settings['phone']); ?></span>
            <?php endif; ?>
            <?php if (!empty($settings['email'])): ?>
            <a href="mailto:<?php echo htmlspecialchars($settings['email']); ?>"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($settings['email']); ?></a>
            <?php endif; ?>
        </div>
        <div class="top-bar-right">
            <a href="<?php echo SITE_URL; ?>/page.php?slug=about-us">About Us</a>
            <a href="<?php echo SITE_URL; ?>/blogs.php">Blog</a>
            <a href="<?php echo SITE_URL; ?>/faq.php">FAQ</a>
            <a href="<?php echo SITE_URL; ?>/contact.php">Contact</a>
        </div>
    </div>
</div>

<!-- Main Header -->
<header class="main-header">
    <div class="container">
        <div class="header-inner">
            <div class="logo">
                <a href="<?php echo SITE_URL; ?>" style="display:flex;align-items:center;gap:10px;">
                    <img src="<?php echo UPLOAD_URL; ?>country-logo.png" alt="" class="country-flag">
                    <?php if (!empty($settings['logo'])): ?>
                    <img src="<?php echo UPLOAD_URL . htmlspecialchars($settings['logo']); ?>" alt="<?php echo htmlspecialchars($settings['site_name'] ?? ''); ?>" class="logo-img">
                    <?php endif; ?>
                </a>
            </div>

            <div class="search-bar">
                <form action="<?php echo SITE_URL; ?>/search.php" method="GET">
                    <input type="text" name="q" placeholder="Search for products, categories..." autocomplete="off">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>

            <div class="header-actions">
                <?php if (!empty($_SESSION['customer_id'])): ?>
                <a href="<?php echo SITE_URL; ?>/account.php">
                    <i class="fas fa-user-circle"></i>
                    <span>Account</span>
                </a>
                <?php else: ?>
                <a href="<?php echo SITE_URL; ?>/login.php">
                    <i class="fas fa-user"></i>
                    <span>Login</span>
                </a>
                <?php endif; ?>
                <a href="<?php echo SITE_URL; ?>/cart.php" class="cart-link">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Cart</span>
                    <?php if ($cartCount > 0): ?>
                    <span class="cart-badge" id="cartBadge"><?php echo $cartCount; ?></span>
                    <?php else: ?>
                    <span class="cart-badge" id="cartBadge" style="display:none;">0</span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </div>
</header>

<!-- Navigation -->
<nav class="main-nav">
    <div class="container">
        <button class="nav-toggle" aria-label="Toggle navigation">
            <i class="fas fa-bars"></i>
        </button>
        <div class="nav-links">
            <div class="nav-inner">
                <a href="<?php echo SITE_URL; ?>" class="<?php echo $currentPage === 'index' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i> Home
                </a>

                <?php foreach ($menuCatsWithSubs as $pCat): ?>
                <div class="nav-dropdown">
                    <a href="<?php echo SITE_URL; ?>/category.php?id=<?php echo $pCat['id']; ?>">
                        <?php echo htmlspecialchars($pCat['api_category_name']); ?>
                        <?php if (!empty($pCat['subcategories'])): ?>
                        <i class="fas fa-chevron-down" style="font-size:10px;margin-left:4px;"></i>
                        <?php endif; ?>
                    </a>
                    <?php if (!empty($pCat['subcategories'])): ?>
                    <div class="mega-menu">
                        <div class="mega-menu-grid">
                            <?php foreach ($pCat['subcategories'] as $sub): ?>
                            <a href="<?php echo SITE_URL; ?>/products.php?category=<?php echo urlencode($sub['api_category_id']); ?>" class="mega-menu-item">
                                <?php if (!empty($sub['image'])): ?>
                                <img src="<?php echo UPLOAD_URL . htmlspecialchars($sub['image']); ?>" alt="" class="mega-menu-img">
                                <?php else: ?>
                                <div class="mega-menu-img-placeholder"><i class="fas fa-utensils"></i></div>
                                <?php endif; ?>
                                <span><?php echo htmlspecialchars($sub['api_category_name']); ?></span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>

                <a href="<?php echo SITE_URL; ?>/categories.php" class="<?php echo $currentPage === 'categories' ? 'active' : ''; ?>">
                    <i class="fas fa-th-large"></i> All Categories
                </a>
            </div>
        </div>
    </div>
</nav>
