<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/api/paypal.php';

$token = $_GET['token'] ?? '';
$payerId = $_GET['PayerID'] ?? '';

if (empty($token) || empty($payerId) || empty($_SESSION['cart']) || empty($_SESSION['checkout'])) {
    header('Location: ' . SITE_URL . '/checkout.php');
    exit;
}

$cart = $_SESSION['cart'];
$checkout = $_SESSION['checkout'];

$subtotal = 0;
foreach ($cart as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$discount = $_SESSION['coupon']['discount'] ?? 0;
$shippingCost = floatval($checkout['shipping_cost'] ?? 0);
$total = max(0, $subtotal - $discount + $shippingCost);

$details = paypalNVP('GetExpressCheckoutDetails', ['TOKEN' => $token]);

if (!isset($details['ACK']) || ($details['ACK'] !== 'Success' && $details['ACK'] !== 'SuccessWithWarning')) {
    $_SESSION['checkout_error'] = 'Could not verify PayPal payment. Please try again.';
    header('Location: ' . SITE_URL . '/checkout.php');
    exit;
}

$captureParams = [
    'TOKEN' => $token,
    'PAYERID' => $payerId,
    'PAYMENTREQUEST_0_PAYMENTACTION' => 'Sale',
    'PAYMENTREQUEST_0_AMT' => number_format($total, 2, '.', ''),
    'PAYMENTREQUEST_0_ITEMAMT' => number_format($subtotal - $discount, 2, '.', ''),
    'PAYMENTREQUEST_0_SHIPPINGAMT' => number_format($shippingCost, 2, '.', ''),
    'PAYMENTREQUEST_0_CURRENCYCODE' => 'AUD',
];

$capture = paypalNVP('DoExpressCheckoutPayment', $captureParams);

if (!isset($capture['ACK']) || ($capture['ACK'] !== 'Success' && $capture['ACK'] !== 'SuccessWithWarning')) {
    $errorMsg = $capture['L_LONGMESSAGE0'] ?? 'Payment capture failed';
    $_SESSION['checkout_error'] = $errorMsg;
    header('Location: ' . SITE_URL . '/checkout.php');
    exit;
}

$transactionId = $capture['PAYMENTINFO_0_TRANSACTIONID'] ?? '';

$db = getDB();
$orderNumber = 'EH-' . date('Ymd') . '-' . mt_rand(10000, 99999);
$customerId = $_SESSION['customer_id'] ?? null;
$couponCode = $_SESSION['coupon']['code'] ?? null;

$stmt = $db->prepare("INSERT INTO orders (customer_id, order_number, name, email, phone, address, city, state, postcode, subtotal, discount, coupon_code, total, status, payment_method, payment_status, payment_transaction_id, shipping_method, shipping_cost, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed', 'paypal', 'paid', ?, ?, ?, ?)");
$stmt->execute([
    $customerId, $orderNumber,
    $checkout['name'], $checkout['email'], $checkout['phone'],
    $checkout['address'] ?? '', $checkout['city'] ?? '', $checkout['state'] ?? '', $checkout['postcode'] ?? '',
    $subtotal, $discount, $couponCode, $total,
    $transactionId,
    $checkout['shipping_method'], $shippingCost,
    $checkout['notes'] ?? ''
]);
$orderId = $db->lastInsertId();

$stmtItem = $db->prepare("INSERT INTO order_items (order_id, product_id, product_name, product_image, price, quantity) VALUES (?, ?, ?, ?, ?, ?)");
foreach ($cart as $pid => $item) {
    $stmtItem->execute([$orderId, $pid, $item['name'], $item['image'], $item['price'], $item['quantity']]);
}

if ($couponCode) {
    $db->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE code = ?")->execute([$couponCode]);
}

require_once __DIR__ . '/api/mailer.php';
sendOrderConfirmationEmail($orderId);

unset($_SESSION['cart'], $_SESSION['coupon'], $_SESSION['checkout'], $_SESSION['paypal_token']);

header('Location: ' . SITE_URL . '/checkout.php?success=' . urlencode($orderNumber));
exit;
