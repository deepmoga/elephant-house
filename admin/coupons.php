<?php
$pageTitle = 'Coupons';
require_once __DIR__ . '/layout.php';

$db = getDB();
$action = $_GET['action'] ?? 'list';
$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['form_action'] ?? '';

    if ($postAction === 'add' || $postAction === 'edit') {
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $type = $_POST['type'] ?? 'percentage';
        $value = floatval($_POST['value'] ?? 0);
        $minOrder = floatval($_POST['min_order'] ?? 0);
        $maxUses = intval($_POST['max_uses'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $expiresAt = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
        $couponId = intval($_POST['coupon_id'] ?? 0);

        if (empty($code) || $value <= 0) {
            $msg = 'Code and value are required.';
            $msgType = 'danger';
        } else {
            if ($postAction === 'add') {
                $stmt = $db->prepare("INSERT INTO coupons (code, type, value, min_order, max_uses, is_active, expires_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$code, $type, $value, $minOrder, $maxUses, $isActive, $expiresAt]);
                $msg = 'Coupon created!';
                $msgType = 'success';
                $action = 'list';
            } elseif ($couponId > 0) {
                $stmt = $db->prepare("UPDATE coupons SET code=?, type=?, value=?, min_order=?, max_uses=?, is_active=?, expires_at=? WHERE id=?");
                $stmt->execute([$code, $type, $value, $minOrder, $maxUses, $isActive, $expiresAt, $couponId]);
                $msg = 'Coupon updated!';
                $msgType = 'success';
                $action = 'list';
            }
        }
    }

    if ($postAction === 'delete') {
        $db->prepare("DELETE FROM coupons WHERE id = ?")->execute([intval($_POST['coupon_id'] ?? 0)]);
        $msg = 'Coupon deleted.';
        $msgType = 'success';
    }
}

$editData = null;
if ($action === 'edit') {
    $stmt = $db->prepare("SELECT * FROM coupons WHERE id = ?");
    $stmt->execute([intval($_GET['id'] ?? 0)]);
    $editData = $stmt->fetch();
    if (!$editData) $action = 'list';
}
?>

<?php if ($msg): ?>
<div class="alert alert-<?php echo $msgType; ?>"><i class="fas fa-<?php echo $msgType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i> <?php echo htmlspecialchars($msg); ?></div>
<?php endif; ?>

<?php if ($action === 'list'): ?>
<div class="card">
    <div class="card-header"><h2>Coupon Codes</h2><a href="?action=add" class="btn btn-primary"><i class="fas fa-plus"></i> Add Coupon</a></div>
    <div class="card-body">
        <?php $coupons = $db->query("SELECT * FROM coupons ORDER BY created_at DESC")->fetchAll(); ?>
        <?php if (empty($coupons)): ?>
        <p style="text-align:center;padding:30px;color:var(--admin-text-light);">No coupons yet.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Code</th><th>Type</th><th>Value</th><th>Min Order</th><th>Used / Max</th><th>Expires</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($coupons as $c): ?>
                <tr>
                    <td><strong><code><?php echo htmlspecialchars($c['code']); ?></code></strong></td>
                    <td><?php echo ucfirst($c['type']); ?></td>
                    <td><?php echo $c['type'] === 'percentage' ? $c['value'] . '%' : '$' . number_format($c['value'], 2); ?></td>
                    <td>$<?php echo number_format($c['min_order'], 2); ?></td>
                    <td><?php echo $c['used_count']; ?> / <?php echo $c['max_uses'] ?: '∞'; ?></td>
                    <td style="font-size:12px;"><?php echo $c['expires_at'] ? date('d M Y', strtotime($c['expires_at'])) : 'Never'; ?></td>
                    <td><span class="badge badge-<?php echo $c['is_active'] ? 'success' : 'danger'; ?>"><?php echo $c['is_active'] ? 'Active' : 'Inactive'; ?></span></td>
                    <td>
                        <div class="actions">
                            <a href="?action=edit&id=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline"><i class="fas fa-edit"></i></a>
                            <form method="POST" style="display:inline;"><input type="hidden" name="form_action" value="delete"><input type="hidden" name="coupon_id" value="<?php echo $c['id']; ?>"><button type="submit" class="btn btn-sm btn-danger btn-delete"><i class="fas fa-trash"></i></button></form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php elseif ($action === 'add' || $action === 'edit'): ?>
<div class="card">
    <div class="card-header"><h2><?php echo $action === 'edit' ? 'Edit Coupon' : 'Add Coupon'; ?></h2><a href="?action=list" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a></div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="form_action" value="<?php echo $action; ?>">
            <?php if ($editData): ?><input type="hidden" name="coupon_id" value="<?php echo $editData['id']; ?>"><?php endif; ?>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                <div class="form-group">
                    <label>Coupon Code *</label>
                    <input type="text" name="code" class="form-control" required value="<?php echo htmlspecialchars($editData['code'] ?? ''); ?>" placeholder="e.g. SAVE10" style="text-transform:uppercase;">
                </div>
                <div class="form-group">
                    <label>Discount Type</label>
                    <select name="type" class="form-control">
                        <option value="percentage" <?php echo ($editData['type'] ?? '') === 'percentage' ? 'selected' : ''; ?>>Percentage (%)</option>
                        <option value="fixed" <?php echo ($editData['type'] ?? '') === 'fixed' ? 'selected' : ''; ?>>Fixed Amount ($)</option>
                    </select>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;">
                <div class="form-group">
                    <label>Discount Value *</label>
                    <input type="number" name="value" class="form-control" step="0.01" min="0.01" required value="<?php echo $editData['value'] ?? ''; ?>" placeholder="e.g. 10">
                </div>
                <div class="form-group">
                    <label>Minimum Order ($)</label>
                    <input type="number" name="min_order" class="form-control" step="0.01" min="0" value="<?php echo $editData['min_order'] ?? 0; ?>">
                </div>
                <div class="form-group">
                    <label>Max Uses (0 = unlimited)</label>
                    <input type="number" name="max_uses" class="form-control" min="0" value="<?php echo $editData['max_uses'] ?? 0; ?>">
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                <div class="form-group">
                    <label>Expires At</label>
                    <input type="datetime-local" name="expires_at" class="form-control" value="<?php echo $editData['expires_at'] ? date('Y-m-d\TH:i', strtotime($editData['expires_at'])) : ''; ?>">
                </div>
                <div class="form-group">
                    <label>&nbsp;</label>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="checkbox" name="is_active" value="1" <?php echo ($editData['is_active'] ?? 1) ? 'checked' : ''; ?> style="accent-color:var(--admin-primary);width:18px;height:18px;"> Active
                    </label>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save"></i> Save Coupon</button>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
