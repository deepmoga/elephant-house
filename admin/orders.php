<?php
$pageTitle = 'Orders';
require_once __DIR__ . '/layout.php';

$db = getDB();
$action = $_GET['action'] ?? 'list';
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['form_action'] === 'update_status') {
    $orderId = intval($_POST['order_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $allowed = ['pending','confirmed','processing','shipped','delivered','cancelled'];
    if ($orderId > 0 && in_array($status, $allowed)) {
        $db->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$status, $orderId]);
        $msg = 'Order status updated.';
    }
}

if ($action === 'view') {
    $orderId = intval($_GET['id'] ?? 0);
    $order = $db->prepare("SELECT * FROM orders WHERE id = ?");
    $order->execute([$orderId]);
    $order = $order->fetch();
    if (!$order) { $action = 'list'; }
    else {
        $items = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $items->execute([$orderId]);
        $items = $items->fetchAll();
    }
}
?>

<?php if ($msg): ?>
<div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($msg); ?></div>
<?php endif; ?>

<?php if ($action === 'list'): ?>
<div class="card">
    <div class="card-header"><h2>All Orders</h2></div>
    <div class="card-body">
        <?php
        $orders = $db->query("SELECT * FROM orders ORDER BY created_at DESC")->fetchAll();
        if (empty($orders)):
        ?>
        <p style="text-align:center;padding:30px;color:var(--admin-text-light);">No orders yet.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Order #</th><th>Customer</th><th>Email</th><th>Total</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($orders as $o): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($o['order_number']); ?></strong></td>
                    <td><?php echo htmlspecialchars($o['name']); ?></td>
                    <td><?php echo htmlspecialchars($o['email']); ?></td>
                    <td>$<?php echo number_format($o['total'], 2); ?></td>
                    <td><span class="badge badge-<?php echo $o['status'] === 'delivered' ? 'success' : ($o['status'] === 'cancelled' ? 'danger' : 'warning'); ?>"><?php echo ucfirst($o['status']); ?></span></td>
                    <td style="font-size:12px;"><?php echo date('d M Y H:i', strtotime($o['created_at'])); ?></td>
                    <td><a href="?action=view&id=<?php echo $o['id']; ?>" class="btn btn-sm btn-outline"><i class="fas fa-eye"></i> View</a></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php elseif ($action === 'view' && !empty($order)): ?>
<a href="?action=list" class="btn btn-outline" style="margin-bottom:20px;"><i class="fas fa-arrow-left"></i> Back to Orders</a>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
    <div class="card">
        <div class="card-header"><h2>Order #<?php echo htmlspecialchars($order['order_number']); ?></h2></div>
        <div class="card-body">
            <p><strong>Name:</strong> <?php echo htmlspecialchars($order['name']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone'] ?: 'N/A'); ?></p>
            <p><strong>Address:</strong> <?php echo htmlspecialchars($order['address'] . ', ' . $order['city'] . ', ' . $order['state'] . ' ' . $order['postcode']); ?></p>
            <?php if (!empty($order['notes'])): ?>
            <p><strong>Notes:</strong> <?php echo htmlspecialchars($order['notes']); ?></p>
            <?php endif; ?>
            <p><strong>Date:</strong> <?php echo date('d M Y H:i', strtotime($order['created_at'])); ?></p>
            <hr style="margin:15px 0;border:none;border-top:1px solid var(--admin-border);">
            <p><strong>Shipping:</strong>
                <?php if (($order['shipping_method'] ?? 'delivery') === 'pickup'): ?>
                <span class="badge badge-success"><i class="fas fa-store"></i> Pickup from Store</span>
                <?php else: ?>
                <span class="badge badge-warning"><i class="fas fa-truck"></i> Delivery ($<?php echo number_format($order['shipping_cost'] ?? 0, 2); ?>)</span>
                <?php endif; ?>
            </p>
            <p><strong>Payment:</strong>
                <?php if (($order['payment_method'] ?? 'cod') === 'paypal'): ?>
                <span class="badge badge-success"><i class="fab fa-paypal"></i> PayPal</span>
                <?php else: ?>
                <span class="badge badge-warning">Pay Later / COD</span>
                <?php endif; ?>
            </p>
            <?php if (!empty($order['payment_status'])): ?>
            <p><strong>Payment Status:</strong>
                <span class="badge badge-<?php echo ($order['payment_status'] ?? '') === 'paid' ? 'success' : 'warning'; ?>"><?php echo ucfirst($order['payment_status'] ?? 'pending'); ?></span>
            </p>
            <?php endif; ?>
            <?php if (!empty($order['payment_transaction_id'])): ?>
            <p><strong>Transaction ID:</strong> <code style="font-size:12px;"><?php echo htmlspecialchars($order['payment_transaction_id']); ?></code></p>
            <?php endif; ?>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h2>Update Status</h2></div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="form_action" value="update_status">
                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                <div class="form-group">
                    <select name="status" class="form-control">
                        <?php foreach (['pending','confirmed','processing','shipped','delivered','cancelled'] as $s): ?>
                        <option value="<?php echo $s; ?>" <?php echo $order['status'] === $s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update</button>
            </form>
            <hr style="margin:20px 0;">
            <p><strong>Subtotal:</strong> $<?php echo number_format($order['subtotal'], 2); ?></p>
            <?php if (($order['shipping_cost'] ?? 0) > 0): ?>
            <p><strong>Shipping:</strong> $<?php echo number_format($order['shipping_cost'], 2); ?></p>
            <?php endif; ?>
            <?php if ($order['discount'] > 0): ?>
            <p><strong>Discount:</strong> -$<?php echo number_format($order['discount'], 2); ?> (<?php echo htmlspecialchars($order['coupon_code']); ?>)</p>
            <?php endif; ?>
            <p style="font-size:20px;font-weight:700;color:var(--admin-primary);"><strong>Total:</strong> $<?php echo number_format($order['total'], 2); ?></p>
        </div>
    </div>
</div>

<div class="card" style="margin-top:20px;">
    <div class="card-header"><h2>Order Items</h2></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Product</th><th>Price</th><th>Qty</th><th>Total</th></tr></thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td style="display:flex;align-items:center;gap:10px;">
                        <?php if (!empty($item['product_image'])): ?>
                        <img src="<?php echo htmlspecialchars($item['product_image']); ?>" style="width:50px;height:50px;object-fit:contain;border-radius:6px;">
                        <?php endif; ?>
                        <?php echo htmlspecialchars($item['product_name']); ?>
                    </td>
                    <td>$<?php echo number_format($item['price'], 2); ?></td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td><strong>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></strong></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
