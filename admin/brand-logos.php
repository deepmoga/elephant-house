<?php
$pageTitle = 'Brand Logos';
require_once __DIR__ . '/layout.php';

$db = getDB();
ensureBrandLogosTable();

$uploadDir = UPLOAD_PATH . 'brands/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

$action = $_GET['action'] ?? 'list';
$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['form_action'] ?? '';

    if ($postAction === 'add') {
        $sortOrder = intval($_POST['sort_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $uploaded = 0;
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

        if (!empty($_FILES['images']['name']) && is_array($_FILES['images']['name'])) {
            foreach ($_FILES['images']['name'] as $idx => $name) {
                if (empty($name) || ($_FILES['images']['error'][$idx] ?? 1) !== 0) continue;
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if (!in_array($ext, $allowed)) continue;

                $imageName = 'brand_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
                if (move_uploaded_file($_FILES['images']['tmp_name'][$idx], $uploadDir . $imageName)) {
                    $stmt = $db->prepare("INSERT INTO brand_logos (image, sort_order, is_active) VALUES (?, ?, ?)");
                    $stmt->execute([$imageName, $sortOrder + $uploaded, $isActive]);
                    $uploaded++;
                }
            }
        }

        if ($uploaded > 0) {
            $msg = $uploaded . ' brand logo(s) uploaded.';
            $msgType = 'success';
            $action = 'list';
        } else {
            $msg = 'Please upload at least one valid image.';
            $msgType = 'danger';
        }
    }

    if ($postAction === 'update') {
        $logoIds = $_POST['logo_id'] ?? [];
        foreach ($logoIds as $logoId) {
            $logoId = intval($logoId);
            $sortOrder = intval($_POST['sort_order'][$logoId] ?? 0);
            $isActive = isset($_POST['is_active'][$logoId]) ? 1 : 0;
            $stmt = $db->prepare("UPDATE brand_logos SET sort_order=?, is_active=? WHERE id=?");
            $stmt->execute([$sortOrder, $isActive, $logoId]);
        }
        $msg = 'Brand logos updated.';
        $msgType = 'success';
    }

    if ($postAction === 'delete') {
        $logoId = intval($_POST['logo_id'] ?? 0);
        $stmt = $db->prepare("SELECT image FROM brand_logos WHERE id = ?");
        $stmt->execute([$logoId]);
        $image = $stmt->fetchColumn();
        if ($image && file_exists($uploadDir . $image)) {
            unlink($uploadDir . $image);
        }
        $db->prepare("DELETE FROM brand_logos WHERE id = ?")->execute([$logoId]);
        $msg = 'Brand logo deleted.';
        $msgType = 'success';
    }
}
?>

<?php if ($msg): ?>
<div class="alert alert-<?php echo $msgType; ?>"><i class="fas fa-<?php echo $msgType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i> <?php echo htmlspecialchars($msg); ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h2>Upload Brand Logos</h2>
    </div>
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="form_action" value="add">
            <div class="form-group">
                <label>Logo Images <span class="required">*</span></label>
                <input type="file" name="images[]" class="form-control form-control-file" accept="image/*,.svg" multiple required>
                <p class="form-hint">You can select multiple images. Title is not required.</p>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                <div class="form-group">
                    <label>Starting Sort Order</label>
                    <input type="number" name="sort_order" class="form-control" value="0">
                </div>
                <div class="form-group">
                    <label>&nbsp;</label>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="checkbox" name="is_active" value="1" checked style="accent-color:var(--admin-primary);width:18px;height:18px;"> Active
                    </label>
                </div>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Upload Logos</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Brand Logo Carousel</h2>
    </div>
    <div class="card-body">
        <?php
        $logos = $db->query("SELECT * FROM brand_logos ORDER BY sort_order ASC, id ASC")->fetchAll();
        if (empty($logos)):
        ?>
        <p style="text-align:center;padding:30px;color:var(--admin-text-light);">No brand logos uploaded yet.</p>
        <?php else: ?>
        <form method="POST">
            <input type="hidden" name="form_action" value="update">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr><th>Logo</th><th>Sort Order</th><th>Status</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($logos as $logo): ?>
                        <tr>
                            <td>
                                <input type="hidden" name="logo_id[]" value="<?php echo $logo['id']; ?>">
                                <img src="<?php echo UPLOAD_URL . 'brands/' . htmlspecialchars($logo['image']); ?>" alt="" style="width:110px;height:60px;object-fit:contain;background:#fff;border:1px solid var(--admin-border);">
                            </td>
                            <td><input type="number" name="sort_order[<?php echo $logo['id']; ?>]" class="form-control" value="<?php echo intval($logo['sort_order']); ?>" style="max-width:110px;"></td>
                            <td>
                                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                                    <input type="checkbox" name="is_active[<?php echo $logo['id']; ?>]" value="1" <?php echo $logo['is_active'] ? 'checked' : ''; ?> style="accent-color:var(--admin-primary);width:18px;height:18px;"> Active
                                </label>
                            </td>
                            <td>
                                <button type="submit" class="btn btn-sm btn-outline"><i class="fas fa-save"></i></button>
                                <button type="submit" formaction="" formmethod="POST" name="delete_logo" value="<?php echo $logo['id']; ?>" class="btn btn-sm btn-danger" onclick="event.preventDefault(); if(confirm('Delete this logo?')) { var f=document.createElement('form'); f.method='POST'; f.innerHTML='<input type=&quot;hidden&quot; name=&quot;form_action&quot; value=&quot;delete&quot;><input type=&quot;hidden&quot; name=&quot;logo_id&quot; value=&quot;<?php echo $logo['id']; ?>&quot;>'; document.body.appendChild(f); f.submit(); }"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <button type="submit" class="btn btn-primary" style="margin-top:15px;"><i class="fas fa-save"></i> Save Changes</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
