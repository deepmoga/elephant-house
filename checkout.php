<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/api/fetch.php';

$cart = $_SESSION['cart'] ?? [];

if (empty($cart) && empty($_GET['success'])) {
    header('Location: ' . SITE_URL . '/cart.php');
    exit;
}

if (empty($_SESSION['customer_id']) && empty($_GET['success'])) {
    header('Location: ' . SITE_URL . '/login.php?redirect=checkout');
    exit;
}

$msg = '';
$msgType = '';

if (!empty($_SESSION['checkout_error'])) {
    $msg = $_SESSION['checkout_error'];
    $msgType = 'danger';
    unset($_SESSION['checkout_error']);
}

$allSettings = getAllSettings();
$paypalEnabled = !empty($allSettings['paypal_enabled']) && !empty($allSettings['paypal_username']);

// Handle form submission → save to session → redirect to PayPal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proceed_payment']) && !empty($cart)) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $shippingMethod = $_POST['shipping_method'] ?? 'delivery';
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $postcode = trim($_POST['postcode'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $shippingCost = floatval($_POST['shipping_cost_val'] ?? 0);

    if (empty($name) || empty($email)) {
        $msg = 'Name and email are required.';
        $msgType = 'danger';
    } elseif ($shippingMethod === 'delivery' && (empty($address) || empty($city) || empty($state) || empty($postcode))) {
        $msg = 'Please fill in all shipping address fields.';
        $msgType = 'danger';
    } else {
        if ($shippingMethod === 'pickup') {
            $shippingCost = 0;
            $address = 'Pickup from Store';
            $city = $allSettings['address'] ?? 'Store';
            $state = '';
            $postcode = '';
        }

        $_SESSION['checkout'] = [
            'name' => $name, 'email' => $email, 'phone' => $phone,
            'shipping_method' => $shippingMethod, 'shipping_cost' => $shippingCost,
            'address' => $address, 'city' => $city, 'state' => $state, 'postcode' => $postcode,
            'notes' => $notes,
        ];

        if ($paypalEnabled) {
            header('Content-Type: application/json');
            require_once __DIR__ . '/api/paypal.php';
            $subtotal = 0;
            foreach ($cart as $item) { $subtotal += $item['price'] * $item['quantity']; }
            $discount = $_SESSION['coupon']['discount'] ?? 0;
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
                header('Location: ' . getPayPalRedirectUrl($result['TOKEN']));
                exit;
            } else {
                $msg = $result['L_LONGMESSAGE0'] ?? 'PayPal error. Please try again.';
                $msgType = 'danger';
            }
        } else {
            // No PayPal - create order directly (COD)
            $db = getDB();
            $subtotal = 0;
            foreach ($cart as $item) { $subtotal += $item['price'] * $item['quantity']; }
            $discount = $_SESSION['coupon']['discount'] ?? 0;
            $couponCode = $_SESSION['coupon']['code'] ?? null;
            $total = max(0, $subtotal - $discount + $shippingCost);
            $orderNumber = 'EH-' . date('Ymd') . '-' . mt_rand(10000, 99999);
            $customerId = $_SESSION['customer_id'] ?? null;

            $stmt = $db->prepare("INSERT INTO orders (customer_id, order_number, name, email, phone, address, city, state, postcode, subtotal, discount, coupon_code, total, status, payment_method, payment_status, shipping_method, shipping_cost, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'cod', 'pending', ?, ?, ?)");
            $stmt->execute([$customerId, $orderNumber, $name, $email, $phone, $address, $city, $state, $postcode, $subtotal, $discount, $couponCode, $total, $shippingMethod, $shippingCost, $notes]);
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

            unset($_SESSION['cart'], $_SESSION['coupon'], $_SESSION['checkout']);

            header('Location: ' . SITE_URL . '/checkout.php?success=' . urlencode($orderNumber));
            exit;
        }
    }
}

require_once __DIR__ . '/includes/header.php';

// Success page
if (!empty($_GET['success'])) {
    $orderNum = htmlspecialchars($_GET['success']);
?>
<div class="page-header"><div class="container"><h1>Order Confirmed!</h1></div></div>
<section class="section">
    <div class="container" style="text-align:center;max-width:600px;margin:0 auto;">
        <div class="card">
            <div class="card-body" style="padding:50px;">
                <i class="fas fa-check-circle" style="font-size:70px;color:#28a745;margin-bottom:20px;display:block;"></i>
                <h2 style="font-family:'Playfair Display',serif;color:var(--primary);margin-bottom:10px;">Thank You!</h2>
                <p style="color:var(--text-light);margin-bottom:5px;">Your order has been placed successfully.</p>
                <p style="font-size:18px;font-weight:700;color:var(--primary);margin:15px 0;">Order #<?php echo $orderNum; ?></p>
                <p style="color:var(--text-muted);font-size:14px;margin-bottom:30px;">We'll process your order shortly.</p>
                <a href="<?php echo SITE_URL; ?>" class="btn-view-all" style="display:inline-block;">Continue Shopping</a>
                <?php if (!empty($_SESSION['customer_id'])): ?>
                <a href="<?php echo SITE_URL; ?>/account.php" style="display:inline-block;margin-left:15px;color:var(--primary);font-weight:600;">View My Orders</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Calculate totals
$subtotal = 0;
foreach ($cart as $item) { $subtotal += $item['price'] * $item['quantity']; }
$discount = $_SESSION['coupon']['discount'] ?? 0;
$savedCheckout = $_SESSION['checkout'] ?? [];
$shippingCost = floatval($savedCheckout['shipping_cost'] ?? 0);
$total = max(0, $subtotal - $discount + $shippingCost);

$custData = [];
if (!empty($_SESSION['customer_id'])) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$_SESSION['customer_id']]);
    $custData = $stmt->fetch() ?: [];
}
$storeAddress = $allSettings['address'] ?? '';
$storePhone = $allSettings['phone'] ?? '';
?>

<div class="page-header">
    <div class="container">
        <h1>Checkout</h1>
        <div class="breadcrumb">
            <a href="<?php echo SITE_URL; ?>">Home</a> / <a href="<?php echo SITE_URL; ?>/cart.php">Cart</a> / Checkout
        </div>
    </div>
</div>

<section class="section">
    <div class="container">
        <?php if ($msg): ?>
        <div style="background:<?php echo $msgType === 'danger' ? '#f8d7da' : '#d4edda'; ?>;color:<?php echo $msgType === 'danger' ? '#721c24' : '#155724'; ?>;padding:14px 20px;border-radius:8px;margin-bottom:20px;">
            <i class="fas fa-<?php echo $msgType === 'danger' ? 'exclamation-circle' : 'check-circle'; ?>"></i> <?php echo htmlspecialchars($msg); ?>
        </div>
        <?php endif; ?>

        <form method="POST" id="checkoutForm" class="checkout-layout">
            <div class="checkout-form">

                <!-- Shipping Method -->
                <div class="card" style="margin-bottom:20px;">
                    <div class="card-body">
                        <h3 style="font-family:'Playfair Display',serif;font-size:20px;color:var(--primary);margin-bottom:20px;"><i class="fas fa-shipping-fast"></i> Shipping Method</h3>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
                            <label class="shipping-option" id="optDelivery" style="display:flex;align-items:center;gap:12px;padding:18px;border:2px solid var(--primary);border-radius:10px;cursor:pointer;background:var(--cream);">
                                <input type="radio" name="shipping_method" value="delivery" checked style="accent-color:var(--primary);width:20px;height:20px;" onchange="toggleShipping()">
                                <div>
                                    <strong style="font-size:15px;display:block;"><i class="fas fa-truck" style="margin-right:6px;"></i> Delivery</strong>
                                    <small style="color:var(--text-muted);">Delivered to your address</small>
                                </div>
                            </label>
                            <label class="shipping-option" id="optPickup" style="display:flex;align-items:center;gap:12px;padding:18px;border:2px solid var(--border);border-radius:10px;cursor:pointer;">
                                <input type="radio" name="shipping_method" value="pickup" style="accent-color:var(--primary);width:20px;height:20px;" onchange="toggleShipping()">
                                <div>
                                    <strong style="font-size:15px;display:block;"><i class="fas fa-store" style="margin-right:6px;"></i> Pickup from Store</strong>
                                    <small style="color:var(--text-muted);">Free - collect from our store</small>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Pickup Info (hidden by default) -->
                <div class="card" id="pickupInfo" style="margin-bottom:20px;display:none;">
                    <div class="card-body" style="background:var(--cream);border-radius:var(--radius);">
                        <h4 style="color:var(--primary);margin-bottom:10px;"><i class="fas fa-map-marker-alt"></i> Pickup Location</h4>
                        <p style="font-size:14px;color:var(--text-light);margin-bottom:5px;"><?php echo htmlspecialchars($storeAddress); ?></p>
                        <?php if ($storePhone): ?>
                        <p style="font-size:14px;color:var(--text-light);"><i class="fas fa-phone" style="margin-right:5px;"></i> <?php echo htmlspecialchars($storePhone); ?></p>
                        <?php endif; ?>
                        <p style="font-size:13px;color:var(--text-muted);margin-top:8px;">We'll notify you when your order is ready for pickup.</p>
                    </div>
                </div>

                <!-- Customer Details -->
                <div class="card" style="margin-bottom:20px;">
                    <div class="card-body">
                        <h3 style="font-family:'Playfair Display',serif;font-size:20px;color:var(--primary);margin-bottom:20px;"><i class="fas fa-user"></i> Your Details</h3>

                        <?php if (empty($_SESSION['customer_id'])): ?>
                        <p style="font-size:13px;color:var(--text-muted);margin-bottom:20px;">Have an account? <a href="<?php echo SITE_URL; ?>/login.php?redirect=checkout" style="color:var(--primary);font-weight:600;">Login here</a></p>
                        <?php endif; ?>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
                            <div class="form-group">
                                <label style="font-size:13px;font-weight:600;display:block;margin-bottom:5px;">Full Name *</label>
                                <input type="text" name="name" required class="checkout-input" value="<?php echo htmlspecialchars($custData['name'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label style="font-size:13px;font-weight:600;display:block;margin-bottom:5px;">Email *</label>
                                <input type="email" name="email" required class="checkout-input" value="<?php echo htmlspecialchars($custData['email'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label style="font-size:13px;font-weight:600;display:block;margin-bottom:5px;">Phone</label>
                            <input type="text" name="phone" class="checkout-input" value="<?php echo htmlspecialchars($custData['phone'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <!-- Delivery Address (shown for delivery only) -->
                <div class="card" id="deliveryFields" style="margin-bottom:20px;">
                    <div class="card-body">
                        <h3 style="font-family:'Playfair Display',serif;font-size:20px;color:var(--primary);margin-bottom:20px;"><i class="fas fa-map-marker-alt"></i> Delivery Address</h3>
                        <div class="form-group">
                            <label style="font-size:13px;font-weight:600;display:block;margin-bottom:5px;">Street Address *</label>
                            <input type="text" name="address" class="checkout-input" id="shippingAddress" value="<?php echo htmlspecialchars($custData['address'] ?? ''); ?>">
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:15px;">
                            <div class="form-group">
                                <label style="font-size:13px;font-weight:600;display:block;margin-bottom:5px;">City *</label>
                                <input type="text" name="city" class="checkout-input" value="<?php echo htmlspecialchars($custData['city'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label style="font-size:13px;font-weight:600;display:block;margin-bottom:5px;">State *</label>
                                <select name="state" class="checkout-input" style="padding:11px 15px;">
                                    <option value="">Select State</option>
                                    <?php foreach (['Victoria','New South Wales','Queensland','South Australia','Western Australia','Tasmania','Northern Territory','Australian Capital Territory'] as $st): ?>
                                    <option value="<?php echo $st; ?>" <?php echo ($custData['state'] ?? '') === $st ? 'selected' : ''; ?>><?php echo $st; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label style="font-size:13px;font-weight:600;display:block;margin-bottom:5px;">Postcode *</label>
                                <input type="text" name="postcode" class="checkout-input" id="shippingPostcode" maxlength="4" value="<?php echo htmlspecialchars($custData['postcode'] ?? ''); ?>">
                                <p id="shippingMsg" style="font-size:12px;margin-top:4px;display:none;"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Notes -->
                <div class="card">
                    <div class="card-body">
                        <div class="form-group" style="margin-bottom:0;">
                            <label style="font-size:13px;font-weight:600;display:block;margin-bottom:5px;">Order Notes (optional)</label>
                            <textarea name="notes" class="checkout-input" rows="3" placeholder="Special delivery instructions..." style="margin-bottom:0;"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Summary Sidebar -->
            <div class="order-summary">
                <div class="card" style="position:sticky;top:90px;">
                    <div class="card-body">
                        <h3 style="font-family:'Playfair Display',serif;font-size:20px;color:var(--primary);margin-bottom:20px;">Order Summary</h3>

                        <?php foreach ($cart as $id => $item): ?>
                        <div style="display:flex;gap:12px;align-items:center;margin-bottom:12px;padding-bottom:12px;border-bottom:1px solid #f0f0f0;">
                            <?php if (!empty($item['image'])): ?>
                            <img src="<?php echo htmlspecialchars($item['image']); ?>" style="width:50px;height:50px;object-fit:contain;border-radius:6px;background:var(--cream-dark);">
                            <?php endif; ?>
                            <div style="flex:1;min-width:0;">
                                <p style="font-size:13px;font-weight:600;margin-bottom:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo htmlspecialchars($item['name']); ?></p>
                                <p style="font-size:12px;color:var(--text-muted);">x<?php echo $item['quantity']; ?></p>
                            </div>
                            <span style="font-weight:600;font-size:14px;">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                        </div>
                        <?php endforeach; ?>

                        <div style="margin-top:15px;">
                            <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:14px;">
                                <span>Subtotal</span><span>$<?php echo number_format($subtotal, 2); ?></span>
                            </div>

                            <!-- Shipping Cost -->
                            <div id="shippingLine" style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:14px;">
                                <span>Shipping</span>
                                <span id="shippingDisplay">$0.00</span>
                            </div>
                            <input type="hidden" name="shipping_cost_val" id="shippingCostVal" value="0">

                            <!-- Coupon -->
                            <div style="margin:12px 0;padding:12px;background:var(--cream);border-radius:8px;">
                                <?php if (!empty($_SESSION['coupon'])): ?>
                                <div style="display:flex;justify-content:space-between;align-items:center;font-size:14px;color:#28a745;">
                                    <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($_SESSION['coupon']['code']); ?></span>
                                    <span>-$<?php echo number_format($discount, 2); ?></span>
                                </div>
                                <?php else: ?>
                                <div style="display:flex;gap:8px;">
                                    <input type="text" id="couponInput" placeholder="Coupon code" style="flex:1;padding:8px 12px;border:1px solid var(--border);border-radius:6px;font-size:13px;outline:none;">
                                    <button type="button" id="applyCoupon" style="padding:8px 15px;background:var(--primary);color:#fff;border:none;border-radius:6px;font-size:13px;cursor:pointer;font-weight:600;">Apply</button>
                                </div>
                                <p id="couponMsg" style="font-size:12px;margin-top:6px;display:none;"></p>
                                <?php endif; ?>
                            </div>

                            <?php if ($discount > 0): ?>
                            <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:14px;color:#28a745;">
                                <span>Discount</span><span>-$<?php echo number_format($discount, 2); ?></span>
                            </div>
                            <?php endif; ?>

                            <hr style="border:none;border-top:2px solid var(--border);margin:15px 0;">
                            <div style="display:flex;justify-content:space-between;font-size:20px;font-weight:700;color:var(--primary);">
                                <span>Total</span><span id="checkoutTotal">$<?php echo number_format($total, 2); ?></span>
                            </div>
                        </div>

                        <?php if ($paypalEnabled): ?>
                        <button type="submit" name="proceed_payment" value="1" class="btn-add-cart" style="width:100%;justify-content:center;margin-top:20px;background:#0070ba;">
                            <i class="fab fa-paypal"></i> Pay with PayPal
                        </button>
                        <p style="text-align:center;font-size:11px;color:var(--text-muted);margin-top:8px;">You'll be redirected to PayPal to complete payment</p>
                        <?php else: ?>
                        <button type="submit" name="proceed_payment" value="1" class="btn-add-cart" style="width:100%;justify-content:center;margin-top:20px;">
                            <i class="fas fa-lock"></i> Place Order
                        </button>
                        <?php endif; ?>

                        <div style="margin-top:15px;text-align:center;">
                            <img src="https://www.paypalobjects.com/webstatic/mktg/logo/AM_mc_vs_dc_ae.jpg" alt="Payment methods" style="max-width:200px;opacity:0.7;">
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var siteUrl = '';
    var metaBase = document.querySelector('link[rel="stylesheet"][href*="/css/style.css"]');
    if (metaBase) siteUrl = metaBase.getAttribute('href').split('/css/style.css')[0];

    var subtotal = <?php echo $subtotal; ?>;
    var discount = <?php echo $discount; ?>;
    var currentShipping = 0;

    function updateTotal() {
        var total = Math.max(0, subtotal - discount + currentShipping);
        document.getElementById('checkoutTotal').textContent = '$' + total.toFixed(2);
        document.getElementById('shippingDisplay').textContent = currentShipping > 0 ? '$' + currentShipping.toFixed(2) : 'Free';
        document.getElementById('shippingCostVal').value = currentShipping;
    }

    // Shipping method toggle
    window.toggleShipping = function() {
        var delivery = document.querySelector('input[name="shipping_method"][value="delivery"]').checked;
        document.getElementById('deliveryFields').style.display = delivery ? '' : 'none';
        document.getElementById('pickupInfo').style.display = delivery ? 'none' : '';
        document.getElementById('optDelivery').style.borderColor = delivery ? 'var(--primary)' : 'var(--border)';
        document.getElementById('optDelivery').style.background = delivery ? 'var(--cream)' : '';
        document.getElementById('optPickup').style.borderColor = delivery ? 'var(--border)' : 'var(--primary)';
        document.getElementById('optPickup').style.background = delivery ? '' : 'var(--cream)';

        if (!delivery) {
            currentShipping = 0;
            updateTotal();
            document.getElementById('shippingMsg').style.display = 'none';
        }
    };

    // Postcode shipping lookup
    var postcodeInput = document.getElementById('shippingPostcode');
    if (postcodeInput) {
        postcodeInput.addEventListener('blur', function() {
            var postcode = this.value.trim();
            if (postcode.length < 3) return;
            var msgEl = document.getElementById('shippingMsg');
            msgEl.style.display = 'block';
            msgEl.style.color = 'var(--text-muted)';
            msgEl.textContent = 'Calculating shipping...';

            var fd = new FormData();
            fd.append('postcode', postcode);
            fetch(siteUrl + '/api/shipping.php', { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    msgEl.style.display = 'block';
                    if (data.success) {
                        currentShipping = data.shipping_raw;
                        msgEl.style.color = '#28a745';
                        msgEl.textContent = data.message;
                    } else {
                        currentShipping = 0;
                        msgEl.style.color = '#dc3545';
                        msgEl.textContent = data.message;
                    }
                    updateTotal();
                });
        });
    }

    // Coupon
    var applyBtn = document.getElementById('applyCoupon');
    if (applyBtn) {
        applyBtn.addEventListener('click', function() {
            var code = document.getElementById('couponInput').value.trim();
            if (!code) return;
            var fd = new FormData();
            fd.append('code', code);
            fd.append('subtotal', subtotal);
            fetch(siteUrl + '/api/coupon.php', { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    var msgEl = document.getElementById('couponMsg');
                    msgEl.style.display = 'block';
                    msgEl.style.color = data.success ? '#28a745' : '#dc3545';
                    msgEl.textContent = data.message;
                    if (data.success) setTimeout(function() { location.reload(); }, 1000);
                });
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
