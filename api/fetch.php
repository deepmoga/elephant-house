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

function getCategories() {
    $data = apiGet('product_categories');
    if ($data && isset($data['data']['data']['categories'])) {
        return $data['data']['data']['categories'];
    }
    return [];
}

function getProductsByCategory($categoryId, $cursor = null) {
    $params = [
        'type' => 'products',
        'product_type_id' => $categoryId,
    ];
    if ($cursor) {
        $params['after'] = $cursor;
    }
    $data = apiGet('search', $params);
    if ($data && isset($data['data'])) {
        return $data;
    }
    return ['data' => [], 'page_info' => ['has_next_page' => false]];
}

function getProducts($cursor = null) {
    $params = [];
    if ($cursor) {
        $params['after'] = $cursor;
    }
    $data = apiGet('products', $params);
    if ($data && isset($data['data'])) {
        return $data;
    }
    return ['data' => [], 'page_info' => ['has_next_page' => false]];
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
