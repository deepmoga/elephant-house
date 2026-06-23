<?php
$pageTitle = 'Shipping Rates';
require_once __DIR__ . '/layout.php';

$db = getDB();
$action = $_GET['action'] ?? 'list';
$msg = '';
$msgType = '';
$states = ['Victoria','New South Wales','Queensland','South Australia','Western Australia','Tasmania','Northern Territory','Australian Capital Territory'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['form_action'] ?? '';

    if ($postAction === 'add' || $postAction === 'edit') {
        $state = trim($_POST['state'] ?? '');
        $postcodeFrom = trim($_POST['postcode_from'] ?? '');
        $postcodeTo = trim($_POST['postcode_to'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $rateId = intval($_POST['rate_id'] ?? 0);

        if (empty($state) || empty($postcodeFrom) || empty($postcodeTo) || $price <= 0) {
            $msg = 'All fields are required.';
            $msgType = 'danger';
        } else {
            if ($postAction === 'add') {
                $stmt = $db->prepare("INSERT INTO shipping_rates (state, postcode_from, postcode_to, price, is_active) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$state, $postcodeFrom, $postcodeTo, $price, $isActive]);
                $msg = 'Shipping rate added!';
            } else {
                $stmt = $db->prepare("UPDATE shipping_rates SET state=?, postcode_from=?, postcode_to=?, price=?, is_active=? WHERE id=?");
                $stmt->execute([$state, $postcodeFrom, $postcodeTo, $price, $isActive, $rateId]);
                $msg = 'Shipping rate updated!';
            }
            $msgType = 'success';
            $action = 'list';
        }
    }

    if ($postAction === 'delete') {
        $db->prepare("DELETE FROM shipping_rates WHERE id = ?")->execute([intval($_POST['rate_id'] ?? 0)]);
        $msg = 'Shipping rate deleted.';
        $msgType = 'success';
    }
}

$editData = null;
if ($action === 'edit') {
    $stmt = $db->prepare("SELECT * FROM shipping_rates WHERE id = ?");
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
    <div class="card-header"><h2>Shipping Rates</h2><a href="?action=add" class="btn btn-primary"><i class="fas fa-plus"></i> Add Rate</a></div>
    <div class="card-body">
        <?php $rates = $db->query("SELECT * FROM shipping_rates ORDER BY state ASC, postcode_from ASC")->fetchAll(); ?>
        <?php if (empty($rates)): ?>
        <p style="text-align:center;padding:30px;color:var(--admin-text-light);">No shipping rates configured.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>State</th><th>Postcode Range</th><th>Price</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($rates as $r): ?>
                <tr>
                    <td><?php echo htmlspecialchars($r['state']); ?></td>
                    <td><code><?php echo htmlspecialchars($r['postcode_from']); ?> - <?php echo htmlspecialchars($r['postcode_to']); ?></code></td>
                    <td><strong>$<?php echo number_format($r['price'], 2); ?></strong></td>
                    <td><span class="badge badge-<?php echo $r['is_active'] ? 'success' : 'danger'; ?>"><?php echo $r['is_active'] ? 'Active' : 'Inactive'; ?></span></td>
                    <td>
                        <div class="actions">
                            <a href="?action=edit&id=<?php echo $r['id']; ?>" class="btn btn-sm btn-outline"><i class="fas fa-edit"></i></a>
                            <form method="POST" style="display:inline;"><input type="hidden" name="form_action" value="delete"><input type="hidden" name="rate_id" value="<?php echo $r['id']; ?>"><button type="submit" class="btn btn-sm btn-danger btn-delete"><i class="fas fa-trash"></i></button></form>
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
    <div class="card-header"><h2><?php echo $action === 'edit' ? 'Edit Rate' : 'Add Shipping Rate'; ?></h2><a href="?action=list" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a></div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="form_action" value="<?php echo $action; ?>">
            <?php if ($editData): ?><input type="hidden" name="rate_id" value="<?php echo $editData['id']; ?>"><?php endif; ?>

            <div class="form-group">
                <label>State *</label>
                <select name="state" class="form-control" required>
                    <option value="">Select State</option>
                    <?php foreach ($states as $st): ?>
                    <option value="<?php echo $st; ?>" <?php echo ($editData['state'] ?? '') === $st ? 'selected' : ''; ?>><?php echo $st; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;">
                <div class="form-group">
                    <label>Postcode From *</label>
                    <input type="text" name="postcode_from" class="form-control" required value="<?php echo htmlspecialchars($editData['postcode_from'] ?? ''); ?>" placeholder="e.g. 3000">
                </div>
                <div class="form-group">
                    <label>Postcode To *</label>
                    <input type="text" name="postcode_to" class="form-control" required value="<?php echo htmlspecialchars($editData['postcode_to'] ?? ''); ?>" placeholder="e.g. 3549">
                </div>
                <div class="form-group">
                    <label>Price ($) *</label>
                    <input type="number" name="price" class="form-control" step="0.01" min="0.01" required value="<?php echo $editData['price'] ?? ''; ?>" placeholder="e.g. 25.00">
                </div>
            </div>
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                    <input type="checkbox" name="is_active" value="1" <?php echo ($editData['is_active'] ?? 1) ? 'checked' : ''; ?> style="accent-color:var(--admin-primary);width:18px;height:18px;"> Active
                </label>
            </div>
            <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save"></i> Save Rate</button>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
