<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

$postcode = trim($_POST['postcode'] ?? '');

if (empty($postcode) || !is_numeric($postcode)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid postcode.']);
    exit;
}

$db = getDB();
$postcode = intval($postcode);

$stmt = $db->prepare("SELECT state, price FROM shipping_rates WHERE is_active = 1 AND CAST(postcode_from AS UNSIGNED) <= ? AND CAST(postcode_to AS UNSIGNED) >= ? ORDER BY price ASC LIMIT 1");
$stmt->execute([$postcode, $postcode]);
$rate = $stmt->fetch();

if ($rate) {
    echo json_encode([
        'success' => true,
        'shipping_cost' => number_format($rate['price'], 2),
        'shipping_raw' => floatval($rate['price']),
        'state' => $rate['state'],
        'message' => 'Shipping to ' . $rate['state'] . ': $' . number_format($rate['price'], 2)
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Sorry, we do not deliver to postcode ' . $postcode . '. Please try pickup from store.'
    ]);
}
