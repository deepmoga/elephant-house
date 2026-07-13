<?php
require_once __DIR__ . '/../config/database.php';

function apiGet($endpoint, $params = []) {
    $url = API_BASE . '/' . ltrim($endpoint, '/');
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Authorization: Bearer ' . API_BEARER_TOKEN,
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || $response === false) {
        return null;
    }

    return json_decode($response, true);
}

function inventoryApiPost($payload = []) {
    $url = 'https://elephanthouse.retail.lightspeed.app/api/2026-07/inventory';
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Bearer ' . API_BEARER_TOKEN,
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || $response === false) {
        return null;
    }

    $data = json_decode($response, true);
    return is_array($data) ? $data : null;
}

function getCategories() {
    $data = apiGet('product_categories');
    if ($data && isset($data['data']['data']['categories'])) {
        return $data['data']['data']['categories'];
    }
    return [];
}

function searchProducts($filters = [], $offset = 0, $pageSize = 1000) {
    $params = array_merge([
        'type' => 'products',
        'page_size' => min(max((int)$pageSize, 1), 1000),
        'offset' => max((int)$offset, 0),
    ], $filters);
    $data = apiGet('search', $params);
    if ($data && isset($data['data'])) {
        return $data;
    }
    return ['data' => []];
}

function getProductsByCategory($categoryId, $offset = 0, $pageSize = 1000) {
    return searchProducts(['product_type_id' => $categoryId], $offset, $pageSize);
}

function getProductsBySku($sku, $offset = 0, $pageSize = 1000) {
    return searchProducts(['sku' => strtolower(trim($sku))], $offset, $pageSize);
}

function getProducts($cursor = null, $pageSize = null) {
    $params = [];
    if ($pageSize !== null) {
        $params['page_size'] = min(max((int)$pageSize, 1), 1000);
    }
    if ($cursor) {
        $params['after'] = $cursor;
    }
    $data = apiGet('products', $params);
    if ($data && isset($data['data'])) {
        return $data;
    }
    return ['data' => [], 'version' => []];
}

function isProductActive($product) {
    return !empty($product['is_active'] ?? $product['active'] ?? false);
}

function getActiveProducts($products) {
    return array_values(array_filter(is_array($products) ? $products : [], 'isProductActive'));
}

function getInventoryCacheFile() {
    $cacheDir = __DIR__ . '/../cache';
    if (!is_dir($cacheDir) && !@mkdir($cacheDir, 0775, true)) {
        return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'elephant-inventory-levels.json';
    }
    if (!is_writable($cacheDir)) {
        return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'elephant-inventory-levels.json';
    }
    return $cacheDir . '/inventory-levels.json';
}

function getInventoryLevelsMap($ttlSeconds = 300) {
    static $requestCache = null;
    if ($requestCache !== null && $ttlSeconds > 0) {
        return $requestCache;
    }

    $cacheFile = getInventoryCacheFile();
    $staleLevels = [];
    if (is_file($cacheFile)) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        if (is_array($cached)) {
            $staleLevels = $cached;
            if ((time() - filemtime($cacheFile)) < $ttlSeconds) {
                $requestCache = $cached;
                return $requestCache;
            }
        }
    }

    $levels = [];
    $cursor = null;
    $seenCursors = [];
    $complete = true;

    for ($page = 0; $page < 20; $page++) {
        $payload = [
            'size' => 5000,
            'include_deleted' => false,
            'sort_direction' => 'asc',
        ];
        if ($cursor !== null) {
            $payload['after'] = $cursor;
        }

        $records = inventoryApiPost($payload);
        if ($records === null) {
            $complete = false;
            break;
        }
        if (empty($records)) {
            break;
        }

        $versions = [];
        foreach ($records as $record) {
            $productId = $record['product_id'] ?? '';
            if ($productId === '' || !isset($record['current_inventory_level'])) {
                continue;
            }
            $level = floatval($record['current_inventory_level']);
            $levels[$productId] = ($levels[$productId] ?? 0) + $level;
            if (isset($record['version']) && is_numeric($record['version'])) {
                $versions[] = $record['version'];
            }
        }

        $nextCursor = empty($versions) ? null : max($versions);
        if ($nextCursor === null || (string)$nextCursor === (string)$cursor || isset($seenCursors[(string)$nextCursor])) {
            break;
        }
        $seenCursors[(string)$nextCursor] = true;
        $cursor = $nextCursor;

        if (count($records) < 5000) {
            break;
        }
    }

    if (!$complete || empty($levels)) {
        $requestCache = $staleLevels;
        return $requestCache;
    }

    @file_put_contents($cacheFile, json_encode($levels), LOCK_EX);
    $requestCache = $levels;
    return $requestCache;
}

function getProductInventoryLevel($productId) {
    if (empty($productId)) {
        return null;
    }
    $levels = getInventoryLevelsMap();
    return array_key_exists($productId, $levels) ? floatval($levels[$productId]) : null;
}

function isProductInStock($product) {
    if (!isProductActive($product)) {
        return false;
    }
    $level = array_key_exists('current_inventory_level', $product)
        ? floatval($product['current_inventory_level'])
        : getProductInventoryLevel($product['id'] ?? '');
    return $level === null || $level > 0;
}

function productSearchText($product) {
    $parts = [
        $product['name'] ?? '',
        $product['variant_name'] ?? '',
        $product['sku'] ?? '',
        $product['brand']['name'] ?? '',
        $product['product_category']['name'] ?? '',
        $product['description'] ?? '',
    ];

    foreach (($product['categories'] ?? []) as $category) {
        $parts[] = $category['name'] ?? '';
    }
    foreach (($product['product_codes'] ?? []) as $code) {
        $parts[] = $code['code'] ?? '';
    }

    return strtolower(implode(' ', array_filter($parts)));
}

function getProductSearchCacheFile() {
    $cacheDir = __DIR__ . '/../cache';
    if (!is_dir($cacheDir) && !@mkdir($cacheDir, 0775, true)) {
        return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'elephant-products-search.json';
    }
    if (!is_writable($cacheDir)) {
        return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'elephant-products-search.json';
    }
    return $cacheDir . '/products-search.json';
}

function getAllProductsForSearch($ttlSeconds = 900) {
    $cacheFile = getProductSearchCacheFile();
    $staleProducts = [];
    if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < $ttlSeconds) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        if (is_array($cached)) {
            return getActiveProducts($cached);
        }
    }
    if (is_file($cacheFile)) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        if (is_array($cached)) {
            $staleProducts = getActiveProducts($cached);
        }
    }

    $products = [];
    $cursor = null;
    $pagesChecked = 0;
    $seenCursors = [];

    do {
        $result = getProducts($cursor, 1000);
        $batch = $result['data'] ?? [];
        if (empty($batch)) {
            break;
        }
        $products = array_merge($products, $batch);

        // Lightspeed collection pagination uses version.max as the next `after` cursor.
        $nextCursor = $result['version']['max'] ?? null;
        if ($nextCursor === null) {
            $versions = array_filter(array_column($batch, 'version'), 'is_numeric');
            $nextCursor = empty($versions) ? null : max($versions);
        }
        $hasNext = $nextCursor !== null
            && (string)$nextCursor !== (string)$cursor
            && empty($seenCursors[(string)$nextCursor]);
        if ($nextCursor !== null) {
            $seenCursors[(string)$nextCursor] = true;
        }
        $cursor = $nextCursor;
        $pagesChecked++;
    } while ($hasNext && $pagesChecked < 100);

    if (empty($products)) {
        return $staleProducts;
    }

    $activeProducts = [];
    $seenProducts = [];
    foreach (getActiveProducts($products) as $product) {
        $productId = $product['id'] ?? '';
        if ($productId !== '' && isset($seenProducts[$productId])) {
            continue;
        }
        $activeProducts[] = $product;
        if ($productId !== '') {
            $seenProducts[$productId] = true;
        }
    }

    @file_put_contents($cacheFile, json_encode($activeProducts), LOCK_EX);
    return $activeProducts;
}

function getSetting($key) {
    $db = getDB();
    $stmt = $db->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? $row['setting_value'] : '';
}

function getAllSettings() {
    $db = getDB();
    $stmt = $db->query("SELECT setting_key, setting_value FROM site_settings");
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    return $settings;
}

function getActiveBanners() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM banners WHERE is_active = 1 ORDER BY sort_order ASC");
    return $stmt->fetchAll();
}

function getActiveOffers() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM offer_banners WHERE is_active = 1 ORDER BY sort_order ASC");
    return $stmt->fetchAll();
}

function getHomeOffer() {
    $db = getDB();
    try {
        $db->exec("ALTER TABLE `offer_banners` ADD COLUMN `show_on_home` TINYINT(1) DEFAULT 0 AFTER `is_active`");
    } catch (PDOException $e) {}
    $stmt = $db->query("SELECT * FROM offer_banners WHERE is_active = 1 AND show_on_home = 1 ORDER BY sort_order ASC, id ASC LIMIT 1");
    return $stmt->fetch();
}

function getPage($slug) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM pages WHERE slug = ? AND is_active = 1");
    $stmt->execute([$slug]);
    return $stmt->fetch();
}

function getParentCategories() {
    $db = getDB();
    $stmt = $db->query("SELECT pc.*, GROUP_CONCAT(cm.api_category_id ORDER BY cm.sort_order ASC) as sub_api_ids, GROUP_CONCAT(cm.api_category_name ORDER BY cm.sort_order ASC SEPARATOR '||') as sub_api_names
        FROM parent_categories pc
        LEFT JOIN category_mapping cm ON pc.id = cm.parent_category_id
        WHERE pc.is_active = 1
        GROUP BY pc.id
        ORDER BY pc.sort_order ASC");
    return $stmt->fetchAll();
}

function ensureHomeSectionsTable() {
    $db = getDB();
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS `home_sections` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `title` VARCHAR(200) NOT NULL,
            `subtitle` VARCHAR(300) DEFAULT NULL,
            `section_source` ENUM('api','parent') NOT NULL DEFAULT 'api',
            `parent_category_id` INT DEFAULT NULL,
            `api_category_id` VARCHAR(100) NOT NULL,
            `api_category_name` VARCHAR(200) NOT NULL,
            `product_limit` INT NOT NULL DEFAULT 6,
            `sort_order` INT DEFAULT 0,
            `is_active` TINYINT(1) DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB");
    } catch (PDOException $e) {}
    $migrations = [
        "ALTER TABLE `home_sections` ADD COLUMN `section_source` ENUM('api','parent') NOT NULL DEFAULT 'api' AFTER `subtitle`",
        "ALTER TABLE `home_sections` ADD COLUMN `parent_category_id` INT DEFAULT NULL AFTER `section_source`",
    ];
    foreach ($migrations as $sql) {
        try { $db->exec($sql); } catch (PDOException $e) {}
    }
}

function getActiveHomeSections() {
    ensureHomeSectionsTable();
    $db = getDB();
    $stmt = $db->query("SELECT * FROM home_sections WHERE is_active = 1 ORDER BY sort_order ASC, id ASC");
    return $stmt->fetchAll();
}

function ensureBrandLogosTable() {
    $db = getDB();
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS `brand_logos` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `image` VARCHAR(500) NOT NULL,
            `sort_order` INT DEFAULT 0,
            `is_active` TINYINT(1) DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB");
    } catch (PDOException $e) {}
}

function getActiveBrandLogos() {
    ensureBrandLogosTable();
    $db = getDB();
    return $db->query("SELECT * FROM brand_logos WHERE is_active = 1 ORDER BY sort_order ASC, id ASC")->fetchAll();
}

function getMenuCategories() {
    $db = getDB();
    $stmt = $db->query("SELECT pc.*, GROUP_CONCAT(cm.api_category_id ORDER BY cm.sort_order ASC) as sub_api_ids, GROUP_CONCAT(cm.api_category_name ORDER BY cm.sort_order ASC SEPARATOR '||') as sub_api_names
        FROM parent_categories pc
        LEFT JOIN category_mapping cm ON pc.id = cm.parent_category_id
        WHERE pc.is_active = 1 AND pc.show_in_menu = 1
        GROUP BY pc.id
        ORDER BY pc.sort_order ASC");
    return $stmt->fetchAll();
}

function getSubcategoryImages($parentId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT api_category_id, api_category_name, image FROM category_mapping WHERE parent_category_id = ? ORDER BY sort_order ASC");
    $stmt->execute([$parentId]);
    return $stmt->fetchAll();
}

function getCategoryPriceMarkup($apiCategoryId) {
    if (empty($apiCategoryId)) return ['amount' => 0, 'type' => 'fixed'];
    $db = getDB();
    $stmt = $db->prepare("SELECT price_markup, price_markup_type FROM parent_categories WHERE api_category_id = ? AND is_active = 1");
    $stmt->execute([$apiCategoryId]);
    $row = $stmt->fetch();
    if ($row && $row['price_markup'] > 0) return ['amount' => floatval($row['price_markup']), 'type' => $row['price_markup_type']];
    $stmt2 = $db->prepare("SELECT pc.price_markup, pc.price_markup_type FROM category_mapping cm JOIN parent_categories pc ON cm.parent_category_id = pc.id WHERE cm.api_category_id = ? AND pc.is_active = 1");
    $stmt2->execute([$apiCategoryId]);
    $row2 = $stmt2->fetch();
    if ($row2 && $row2['price_markup'] > 0) return ['amount' => floatval($row2['price_markup']), 'type' => $row2['price_markup_type']];
    return ['amount' => 0, 'type' => 'fixed'];
}

function applyPriceMarkup($price, $apiCategoryId) {
    $markup = getCategoryPriceMarkup($apiCategoryId);
    if ($markup['amount'] <= 0) return $price;
    if ($markup['type'] === 'percentage') return $price + ($price * $markup['amount'] / 100);
    return $price + $markup['amount'];
}

function getFeaturedCategories() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM parent_categories WHERE is_active = 1 AND is_featured = 1 ORDER BY sort_order ASC");
    return $stmt->fetchAll();
}

function isCategoryCartAllowed($apiCategoryId) {
    if (empty($apiCategoryId)) return true;
    $db = getDB();
    $stmt = $db->prepare("SELECT allow_cart FROM parent_categories WHERE api_category_id = ? AND is_active = 1");
    $stmt->execute([$apiCategoryId]);
    $row = $stmt->fetch();
    if ($row) return (bool)$row['allow_cart'];
    $stmt2 = $db->prepare("SELECT pc.allow_cart FROM category_mapping cm JOIN parent_categories pc ON cm.parent_category_id = pc.id WHERE cm.api_category_id = ? AND pc.is_active = 1");
    $stmt2->execute([$apiCategoryId]);
    $row2 = $stmt2->fetch();
    if ($row2) return (bool)$row2['allow_cart'];
    return true;
}

function getProductById($id) {
    $data = apiGet('products/' . $id);
    if ($data && isset($data['data'])) {
        $d = $data['data'];
        return is_array($d) && isset($d[0]) ? $d[0] : $d;
    }
    return null;
}

function getActiveBlogs($limit = 12) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM blogs WHERE is_published = 1 ORDER BY created_at DESC LIMIT ?");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getBlogBySlug($slug) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM blogs WHERE slug = ? AND is_published = 1");
    $stmt->execute([$slug]);
    return $stmt->fetch();
}

function getActiveFaqs() {
    $db = getDB();
    return $db->query("SELECT * FROM faqs WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll();
}

function getCartCount() {
    $count = 0;
    if (!empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $count += $item['quantity'];
        }
    }
    return $count;
}

function getCartTotal() {
    $total = 0;
    if (!empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $total += $item['price'] * $item['quantity'];
        }
    }
    return $total;
}
