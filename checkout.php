<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/config/database.php';

$cart = $_SESSION['cart'] ?? [];

// Redirect to cart if empty and not a success page
if (empty($cart) && empty($_GET['success'])) {
    header('Location: ' . SITE_URL . '/cart.php');
    exit;
}

$msg = '';
$msgType = '';

// Handle order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order']) && !empty($cart)) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $postcode = trim($_POST['postcode'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if (empty($name) || empty($email) || empty($address) || empty($city) || empty($state) || empty($postcode)) {
        $msg = 'Please fill in all required fields.';
        $msgType = 'danger';
    } else {
        $db = getDB();
        $subtotal = 0;
        foreach ($cart as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }

        $discount = 0;
        $couponCode = null;
        if (!empty($_SESSION['coupon'])) {
            $discount = $_SESSION['coupon']['discount'];
            $couponCode = $_SESSION['coupon']['code'];
        }
        $total = max(0, $subtotal - $discount);

        $orderNumber = 'EH-' . date('Ymd') . '-' . mt_rand(10000, 99999);
        $customerId = $_SESSION['customer_id'] ?? null;

        $stmt = $db->prepare("INSERT INTO orders (customer_id, order_number, name, email, phone, address, city, state, postcode, subtotal, discount, coupon_code, total, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)");
        $stmt->execute([$customerId, $orderNumber, $name, $email, $phone, $address, $city, $state, $postcode, $subtotal, $discount, $couponCode, $total, $notes]);
        $orderId = $db->lastInsertId();

        $stmtItem = $db->prepare("INSERT INTO order_items (order_id, product_id, product_name, product_image, price, quantity) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($cart as $pid => $item) {
            $stmtItem->execute([$orderId, $pid, $item['name'], $item['image'], $item['price'], $item['quantity']]);
        }

        if ($couponCode) {
            $db->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE code = ?")->execute([$couponCode]);
        }

        unset($_SESSION['cart'], $_SESSION['coupon']);

        header('Location: ' . SITE_URL . '/checkout.php?success=' . urlencode($orderNumber));
        exit;
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
foreach ($cart as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$discount = $_SESSION['coupon']['discount'] ?? 0;
$total = max(0, $subtotal - $discount);

// Pre-fill from customer profile
$custData = [];
if (!empty($_SESSION['customer_id'])) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$_SESSION['customer_id']]);
    $custData = $stmt->fetch() ?: [];
}
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
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($msg); ?>
        </div>
        <?php endif; ?>

        <form method="POST" class="checkout-layout">
            <div class="checkout-form">
                <div class="card">
                    <div class="card-body">
                        <h3 style="font-family:'Playfair Display',serif;font-size:20px;color:var(--primary);margin-bottom:20px;"><i class="fas fa-truck"></i> Shipping Details</h3>

                        <?php if (empty($_SESSION['customer_id'])): ?>
                        <p style="font-size:13px;color:var(--text-muted);margin-bottom:20px;">Already have an account? <a href="<?php echo SITE_URL; ?>/login.php?redirect=checkout" style="color:var(--primary);font-weight:600;">Login here</a></p>
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
                        <div class="form-group">
                            <label style="font-size:13px;font-weight:600;display:block;margin-bottom:5px;">Address *</label>
                            <input type="text" name="address" required class="checkout-input" value="<?php echo htmlspecialchars($custData['address'] ?? ''); ?>">
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:15px;">
                            <div class="form-group">
                                <label style="font-size:13px;font-weight:600;display:block;margin-bottom:5px;">City *</label>
                                <input type="text" name="city" required class="checkout-input" value="<?php echo htmlspecialchars($custData['city'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label style="font-size:13px;font-weight:600;display:block;margin-bottom:5px;">State *</label>
                                <input type="text" name="state" required class="checkout-input" value="<?php echo htmlspecialchars($custData['state'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label style="font-size:13px;font-weight:600;display:block;margin-bottom:5px;">Postcode *</label>
                                <input type="text" name="postcode" required class="checkout-input" value="<?php echo htmlspecialchars($custData['postcode'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label style="font-size:13px;font-weight:600;display:block;margin-bottom:5px;">Order Notes (optional)</label>
                            <textarea name="notes" class="checkout-input" rows="3" placeholder="Special delivery instructions..."></textarea>
                        </div>
                    </div>
                </div>
            </div>

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

                            <div id="couponSection" style="margin:15px 0;padding:12px;background:var(--cream);border-radius:8px;">
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

                        <button type="submit" name="place_order" value="1" class="btn-add-cart" style="width:100%;justify-content:center;margin-top:20px;">
                            <i class="fas fa-lock"></i> Place Order - $<?php echo number_format($total, 2); ?>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var applyBtn = document.getElementById('applyCoupon');
    if (applyBtn) {
        applyBtn.addEventListener('click', function() {
            var code = document.getElementById('couponInput').value.trim();
            if (!code) return;
            var formData = new FormData();
            formData.append('code', code);
            formData.append('subtotal', '<?php echo $subtotal; ?>');
            fetch('<?php echo SITE_URL; ?>/api/coupon.php', { method: 'POST', body: formData })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    var msgEl = document.getElementById('couponMsg');
                    msgEl.style.display = 'block';
                    msgEl.style.color = data.success ? '#28a745' : '#dc3545';
                    msgEl.textContent = data.message;
                    if (data.success) {
                        setTimeout(function() { location.reload(); }, 1000);
                    }
                });
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
