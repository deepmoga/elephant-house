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
