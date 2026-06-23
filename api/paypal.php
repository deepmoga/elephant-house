<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/fetch.php';

function paypalNVP($method, $params = []) {
    $settings = getAllSettings();
    $username = $settings['paypal_username'] ?? '';
    $password = $settings['paypal_password'] ?? '';
    $signature = $settings['paypal_signature'] ?? '';
    $mode = $settings['paypal_mode'] ?? 'sandbox';

    $endpoint = $mode === 'live'
        ? 'https://api-3t.paypal.com/nvp'
        : 'https://api-3t.sandbox.paypal.com/nvp';

    $params['METHOD'] = $method;
    $params['VERSION'] = '124.0';
    $params['USER'] = $username;
    $params['PWD'] = $password;
    $params['SIGNATURE'] = $signature;

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $endpoint,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($params),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    parse_str($response, $result);
    return $result;
}

function getPayPalRedirectUrl($token) {
    $settings = getAllSettings();
    $mode = $settings['paypal_mode'] ?? 'sandbox';
    $base = $mode === 'live'
        ? 'https://www.paypal.com/cgi-bin/webscr'
        : 'https://www.sandbox.paypal.com/cgi-bin/webscr';
    return $base . '?cmd=_express-checkout&token=' . urlencode($token);
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'create') {
    header('Content-Type: application/json');

    $cart = $_SESSION['cart'] ?? [];
    if (empty($cart)) {
        echo json_encode(['success' => false, 'message' => 'Cart is empty']);
        exit;
    }

    $subtotal = 0;
    foreach ($cart as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }

    $discount = $_SESSION['coupon']['discount'] ?? 0;
    $shippingCost = floatval($_SESSION['checkout']['shipping_cost'] ?? 0);
    $total = max(0, $subtotal - $discount + $shippingCost);

    $params = [
        'PAYMENTREQUEST_0_PAYMENTACTION' => 'Sale',
        'PAYMENTREQUEST_0_AMT' => number_format($total, 2, '.', ''),
        'PAYMENTREQUEST_0_ITEMAMT' => number_format($subtotal - $discount, 2, '.', ''),
        'PAYMENTREQUEST_0_SHIPPINGAMT' => number_format($shippingCost, 2, '.', ''),
        'PAYMENTREQUEST_0_CURRENCYCODE' => 'AUD',
        'PAYMENTREQUEST_0_DESC' => 'Elephant House Order',
        'RETURNURL' => SITE_URL . '/paypal-return.php',
        'CANCELURL' => SITE_URL . '/paypal-cancel.php',
        'NOSHIPPING' => '1',
    ];

    $i = 0;
    foreach ($cart as $pid => $item) {
        $params["L_PAYMENTREQUEST_0_NAME{$i}"] = substr($item['name'], 0, 127);
        $params["L_PAYMENTREQUEST_0_AMT{$i}"] = number_format($item['price'], 2, '.', '');
        $params["L_PAYMENTREQUEST_0_QTY{$i}"] = $item['quantity'];
        $i++;
    }

    if ($discount > 0) {
        $params["L_PAYMENTREQUEST_0_NAME{$i}"] = 'Coupon Discount';
        $params["L_PAYMENTREQUEST_0_AMT{$i}"] = '-' . number_format($discount, 2, '.', '');
        $params["L_PAYMENTREQUEST_0_QTY{$i}"] = 1;
    }

    $result = paypalNVP('SetExpressCheckout', $params);

    if (isset($result['ACK']) && ($result['ACK'] === 'Success' || $result['ACK'] === 'SuccessWithWarning')) {
        $token = $result['TOKEN'];
        $_SESSION['paypal_token'] = $token;
        echo json_encode([
            'success' => true,
            'redirect' => getPayPalRedirectUrl($token)
        ]);
    } else {
        $errorMsg = $result['L_LONGMESSAGE0'] ?? $result['L_SHORTMESSAGE0'] ?? 'PayPal error occurred';
        echo json_encode(['success' => false, 'message' => $errorMsg]);
    }
    exit;
}
