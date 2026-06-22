<?php
require_once __DIR__ . '/../api/fetch.php';
$settings = getAllSettings();
$parentCategories = getParentCategories();
$menuCategories = getMenuCategories();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($settings['site_name'] ?? 'Elephant House'); ?> - <?php echo htmlspecialchars($settings['site_tagline'] ?? 'Premium Grocery Store'); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/css/style.css">
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
            <?php if (!empty($settings['opening_hours'])): ?>
            <span><i class="fas fa-clock"></i> <?php echo htmlspecialchars($settings['opening_hours']); ?></span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Main Header -->
<header class="main-header">
    <div class="container">
        <div class="header-inner">
            <div class="logo">
                <a href="<?php echo SITE_URL; ?>">
                    <?php if (!empty($settings['logo'])): ?>
                    <img src="<?php echo UPLOAD_URL . htmlspecialchars($settings['logo']); ?>" alt="<?php echo htmlspecialchars($settings['site_name'] ?? ''); ?>">
                    <?php endif; ?>
                    <div class="logo-text">
                        <?php echo htmlspecialchars($settings['site_name'] ?? 'Elephant House'); ?>
                        <small><?php echo htmlspecialchars($settings['site_tagline'] ?? 'Premium Grocery Store'); ?></small>
                    </div>
                </a>
            </div>

            <div class="search-bar">
                <form action="<?php echo SITE_URL; ?>/search.php" method="GET">
                    <input type="text" name="q" placeholder="Search for products, categories..." autocomplete="off">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>

            <div class="header-actions">
                <a href="<?php echo SITE_URL; ?>/page.php?slug=about-us">
                    <i class="fas fa-info-circle"></i>
                    <span>About</span>
                </a>
                <a href="<?php echo SITE_URL; ?>/contact.php">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>Find Us</span>
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

                <?php if (!empty($menuCategories)): ?>
                    <?php foreach ($menuCategories as $pCat): ?>
                    <div class="nav-dropdown">
                        <a href="<?php echo SITE_URL; ?>/category.php?id=<?php echo $pCat['id']; ?>">
                            <?php echo htmlspecialchars($pCat['api_category_name']); ?>
                            <?php if (!empty($pCat['sub_api_ids'])): ?>
                            <i class="fas fa-chevron-down" style="font-size:10px;margin-left:4px;"></i>
                            <?php endif; ?>
                        </a>
                        <?php if (!empty($pCat['sub_api_names'])): ?>
                        <div class="dropdown-menu">
                            <a href="<?php echo SITE_URL; ?>/products.php?category=<?php echo urlencode($pCat['api_category_id']); ?>">
                                All <?php echo htmlspecialchars($pCat['api_category_name']); ?>
                            </a>
                            <?php
                            $subIds = explode(',', $pCat['sub_api_ids']);
                            $subNames = explode('||', $pCat['sub_api_names']);
                            foreach ($subIds as $idx => $subId):
                                $subName = $subNames[$idx] ?? '';
                            ?>
                            <a href="<?php echo SITE_URL; ?>/products.php?category=<?php echo urlencode(trim($subId)); ?>">
                                <?php echo htmlspecialchars(trim($subName)); ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <a href="<?php echo SITE_URL; ?>/categories.php" class="<?php echo $currentPage === 'categories' ? 'active' : ''; ?>">
                    <i class="fas fa-th-large"></i> All Categories
                </a>
                <a href="<?php echo SITE_URL; ?>/page.php?slug=about-us">About Us</a>
                <a href="<?php echo SITE_URL; ?>/contact.php" class="<?php echo $currentPage === 'contact' ? 'active' : ''; ?>">Contact</a>
            </div>
        </div>
    </div>
</nav>
