<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/fetch.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

switch ($action) {
    case 'add':
        $id = trim($_POST['product_id'] ?? '');
        $qty = max(1, intval($_POST['quantity'] ?? 1));

        if (empty($id)) {
            echo json_encode(['success' => false, 'message' => 'Invalid product data']);
            exit;
        }

        $product = getProductById($id);
        if (!$product || !isProductActive($product)) {
            echo json_encode(['success' => false, 'message' => 'This product is no longer available.']);
            exit;
        }
        if (!isProductInStock($product)) {
            echo json_encode(['success' => false, 'message' => 'This product is out of stock.']);
            exit;
        }

        $categoryId = $product['product_type_id'] ?? '';
        if (!isCategoryCartAllowed($categoryId)) {
            echo json_encode(['success' => false, 'message' => 'This product is available in-store only.']);
            exit;
        }

        $name = trim($product['name'] ?? '');
        $image = trim($product['image_url'] ?? '');
        $price = applyPriceMarkup(floatval($product['price_including_tax'] ?? 0), $categoryId);
        if ($name === '' || $price <= 0) {
            echo json_encode(['success' => false, 'message' => 'This product cannot be added right now.']);
            exit;
        }

        if (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id]['quantity'] += $qty;
        } else {
            $_SESSION['cart'][$id] = [
                'name' => $name,
                'image' => $image,
                'price' => $price,
                'quantity' => $qty
            ];
        }

        echo json_encode([
            'success' => true,
            'message' => 'Added to cart!',
            'cart_count' => array_sum(array_column($_SESSION['cart'], 'quantity'))
        ]);
        break;

    case 'update':
        $id = trim($_POST['product_id'] ?? '');
        $qty = intval($_POST['quantity'] ?? 0);

        if (isset($_SESSION['cart'][$id])) {
            if ($qty <= 0) {
                unset($_SESSION['cart'][$id]);
            } else {
                $_SESSION['cart'][$id]['quantity'] = $qty;
            }
        }

        $cartTotal = 0;
        foreach ($_SESSION['cart'] as $item) {
            $cartTotal += $item['price'] * $item['quantity'];
        }

        echo json_encode([
            'success' => true,
            'cart_count' => array_sum(array_column($_SESSION['cart'], 'quantity')),
            'cart_total' => number_format($cartTotal, 2)
        ]);
        break;

    case 'remove':
        $id = trim($_POST['product_id'] ?? '');
        unset($_SESSION['cart'][$id]);

        $cartTotal = 0;
        foreach ($_SESSION['cart'] as $item) {
            $cartTotal += $item['price'] * $item['quantity'];
        }

        echo json_encode([
            'success' => true,
            'cart_count' => array_sum(array_column($_SESSION['cart'], 'quantity')),
            'cart_total' => number_format($cartTotal, 2)
        ]);
        break;

    case 'count':
        echo json_encode([
            'count' => array_sum(array_column($_SESSION['cart'], 'quantity'))
        ]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
