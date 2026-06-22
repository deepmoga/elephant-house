<?php
$pageTitle = 'Customers';
require_once __DIR__ . '/layout.php';

$db = getDB();
$customers = $db->query("SELECT c.*, COUNT(o.id) as order_count FROM customers c LEFT JOIN orders o ON c.id = o.customer_id GROUP BY c.id ORDER BY c.created_at DESC")->fetchAll();
?>

<div class="card">
    <div class="card-header"><h2>Registered Customers (<?php echo count($customers); ?>)</h2></div>
    <div class="card-body">
        <?php if (empty($customers)): ?>
        <p style="text-align:center;padding:30px;color:var(--admin-text-light);">No registered customers yet.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Location</th><th>Orders</th><th>Registered</th></tr></thead>
                <tbody>
                <?php foreach ($customers as $c): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($c['name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($c['email']); ?></td>
                    <td><?php echo htmlspecialchars($c['phone'] ?: '-'); ?></td>
                    <td><?php echo htmlspecialchars(($c['city'] ? $c['city'] . ', ' : '') . ($c['state'] ?: '-')); ?></td>
                    <td><span class="badge badge-<?php echo $c['order_count'] > 0 ? 'success' : 'warning'; ?>"><?php echo $c['order_count']; ?></span></td>
                    <td style="font-size:12px;"><?php echo date('d M Y', strtotime($c['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
