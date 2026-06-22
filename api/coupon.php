<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

$code = strtoupper(trim($_POST['code'] ?? ''));
$subtotal = floatval($_POST['subtotal'] ?? 0);

if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a coupon code.']);
    exit;
}

$db = getDB();
$stmt = $db->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1");
$stmt->execute([$code]);
$coupon = $stmt->fetch();

if (!$coupon) {
    echo json_encode(['success' => false, 'message' => 'Invalid coupon code.']);
    exit;
}

if ($coupon['expires_at'] && strtotime($coupon['expires_at']) < time()) {
    echo json_encode(['success' => false, 'message' => 'This coupon has expired.']);
    exit;
}

if ($coupon['max_uses'] > 0 && $coupon['used_count'] >= $coupon['max_uses']) {
    echo json_encode(['success' => false, 'message' => 'This coupon has reached its usage limit.']);
    exit;
}

if ($subtotal < $coupon['min_order']) {
    echo json_encode(['success' => false, 'message' => 'Minimum order of $' . number_format($coupon['min_order'], 2) . ' required.']);
    exit;
}

$discount = 0;
if ($coupon['type'] === 'percentage') {
    $discount = round($subtotal * ($coupon['value'] / 100), 2);
} else {
    $discount = min($coupon['value'], $subtotal);
}

$_SESSION['coupon'] = [
    'id' => $coupon['id'],
    'code' => $coupon['code'],
    'discount' => $discount,
    'type' => $coupon['type'],
    'value' => $coupon['value']
];

$label = $coupon['type'] === 'percentage' ? $coupon['value'] . '%' : '$' . number_format($coupon['value'], 2);

echo json_encode([
    'success' => true,
    'discount' => number_format($discount, 2),
    'total' => number_format($subtotal - $discount, 2),
    'message' => "Coupon applied! {$label} off."
]);
