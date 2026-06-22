<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/config/database.php';

if (empty($_SESSION['customer_id'])) {
    header('Location: ' . SITE_URL . '/login.php');
    exit;
}

require_once __DIR__ . '/includes/header.php';

$db = getDB();
$customerId = $_SESSION['customer_id'];
$msg = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $postcode = trim($_POST['postcode'] ?? '');

    if (!empty($name)) {
        $stmt = $db->prepare("UPDATE customers SET name=?, phone=?, address=?, city=?, state=?, postcode=? WHERE id=?");
        $stmt->execute([$name, $phone, $address, $city, $state, $postcode, $customerId]);
        $_SESSION['customer_name'] = $name;
        $msg = 'Profile updated successfully!';
    }
}

$stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$customerId]);
$customer = $stmt->fetch();

$tab = $_GET['tab'] ?? 'orders';

$orders = $db->prepare("SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC");
$orders->execute([$customerId]);
$orderList = $orders->fetchAll();
?>

<div class="page-header">
    <div class="container">
        <h1>My Account</h1>
        <div class="breadcrumb"><a href="<?php echo SITE_URL; ?>">Home</a> / My Account</div>
    </div>
</div>

<section class="section">
    <div class="container">
        <div class="account-layout">
            <div class="account-sidebar">
                <div style="text-align:center;padding:20px;border-bottom:1px solid var(--border);">
                    <i class="fas fa-user-circle" style="font-size:50px;color:var(--primary);margin-bottom:10px;display:block;"></i>
                    <h3 style="font-size:16px;color:var(--text);"><?php echo htmlspecialchars($customer['name']); ?></h3>
                    <p style="font-size:12px;color:var(--text-muted);"><?php echo htmlspecialchars($customer['email']); ?></p>
                </div>
                <nav style="padding:10px 0;">
                    <a href="?tab=orders" class="account-nav-link <?php echo $tab === 'orders' ? 'active' : ''; ?>"><i class="fas fa-shopping-bag"></i> My Orders</a>
                    <a href="?tab=profile" class="account-nav-link <?php echo $tab === 'profile' ? 'active' : ''; ?>"><i class="fas fa-user-edit"></i> Profile</a>
                    <a href="<?php echo SITE_URL; ?>/logout-customer.php" class="account-nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </nav>
            </div>

            <div class="account-main">
                <?php if ($msg): ?>
                <div style="background:#d4edda;color:#155724;padding:12px 16px;border-radius:8px;margin-bottom:20px;font-size:14px;">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($msg); ?>
                </div>
                <?php endif; ?>

                <?php if ($tab === 'orders'): ?>
                <div class="card">
                    <div class="card-body">
                        <h3 style="font-family:'Playfair Display',serif;font-size:20px;color:var(--primary);margin-bottom:20px;">My Orders</h3>
                        <?php if (empty($orderList)): ?>
                        <p style="text-align:center;padding:30px;color:var(--text-muted);">No orders yet. <a href="<?php echo SITE_URL; ?>/categories.php" style="color:var(--primary);">Start shopping</a></p>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="cart-table" style="font-size:14px;">
                                <thead><tr><th>Order #</th><th>Date</th><th>Total</th><th>Status</th></tr></thead>
                                <tbody>
                                <?php foreach ($orderList as $o): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($o['order_number']); ?></strong></td>
                                    <td><?php echo date('d M Y', strtotime($o['created_at'])); ?></td>
                                    <td>$<?php echo number_format($o['total'], 2); ?></td>
                                    <td><span class="status-badge status-<?php echo $o['status']; ?>"><?php echo ucfirst($o['status']); ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php elseif ($tab === 'profile'): ?>
                <div class="card">
                    <div class="card-body">
                        <h3 style="font-family:'Playfair Display',serif;font-size:20px;color:var(--primary);margin-bottom:20px;">Edit Profile</h3>
                        <form method="POST">
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
                                <div class="form-group">
                                    <label style="font-size:13px;font-weight:600;display:block;margin-bottom:5px;">Full Name</label>
                                    <input type="text" name="name" class="checkout-input" value="<?php echo htmlspecialchars($customer['name']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label style="font-size:13px;font-weight:600;display:block;margin-bottom:5px;">Email (cannot change)</label>
                                    <input type="email" class="checkout-input" value="<?php echo htmlspecialchars($customer['email']); ?>" disabled style="background:#f5f5f5;">
                                </div>
                            </div>
                            <div class="form-group">
                                <label style="font-size:13px;font-weight:600;display:block;margin-bottom:5px;">Phone</label>
                                <input type="text" name="phone" class="checkout-input" value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label style="font-size:13px;font-weight:600;display:block;margin-bottom:5px;">Address</label>
                                <input type="text" name="address" class="checkout-input" value="<?php echo htmlspecialchars($customer['address'] ?? ''); ?>">
                            </div>
                            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:15px;">
                                <div class="form-group">
                                    <label style="font-size:13px;font-weight:600;display:block;margin-bottom:5px;">City</label>
                                    <input type="text" name="city" class="checkout-input" value="<?php echo htmlspecialchars($customer['city'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label style="font-size:13px;font-weight:600;display:block;margin-bottom:5px;">State</label>
                                    <input type="text" name="state" class="checkout-input" value="<?php echo htmlspecialchars($customer['state'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label style="font-size:13px;font-weight:600;display:block;margin-bottom:5px;">Postcode</label>
                                    <input type="text" name="postcode" class="checkout-input" value="<?php echo htmlspecialchars($customer['postcode'] ?? ''); ?>">
                                </div>
                            </div>
                            <button type="submit" name="update_profile" value="1" class="btn-add-cart" style="margin-top:10px;">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
