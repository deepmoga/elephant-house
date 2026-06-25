<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/fetch.php';
header('Content-Type: application/json');

$categoryId = trim($_GET['category_id'] ?? '');
$limit = min(intval($_GET['limit'] ?? 8), 20);

if (empty($categoryId)) {
    echo json_encode(['success' => false, 'products' => []]);
    exit;
}

$result = getProductsByCategory($categoryId);
$products = $result['data'] ?? [];

$activeProducts = array_filter($products, function($p) { return !empty($p['is_active']); });
$displayProducts = array_slice(array_values($activeProducts), 0, $limit);

$output = [];
foreach ($displayProducts as $p) {
    $catId = $p['product_type_id'] ?? '';
    $rawPrice = $p['price_including_tax'] ?? 0;
    $price = applyPriceMarkup($rawPrice, $catId);

    $output[] = [
        'id' => $p['id'],
        'name' => $p['name'],
        'price' => number_format($price, 2),
        'image' => $p['image_url'] ?? '',
        'brand' => $p['brand']['name'] ?? '',
        'category' => $p['product_category']['name'] ?? '',
    ];
}

echo json_encode(['success' => true, 'products' => $output]);
