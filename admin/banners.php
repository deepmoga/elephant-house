<?php
$pageTitle = 'Manage Banners';
require_once __DIR__ . '/layout.php';

$db = getDB();
$action = $_GET['action'] ?? 'list';
$msg = '';
$msgType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['form_action'] ?? '';

    if ($postAction === 'add' || $postAction === 'edit') {
        $title = trim($_POST['title'] ?? '');
        $subtitle = trim($_POST['subtitle'] ?? '');
        $link = trim($_POST['link'] ?? '');
        $sortOrder = intval($_POST['sort_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $bannerId = intval($_POST['banner_id'] ?? 0);

        $imageName = '';
        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === 0) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($ext, $allowed)) {
                $imageName = 'banner_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
                $dest = UPLOAD_PATH . 'banners/' . $imageName;
                move_uploaded_file($_FILES['image']['tmp_name'], $dest);
            }
        }

        if ($postAction === 'add') {
            if (empty($imageName)) {
                $msg = 'Please upload a banner image.';
                $msgType = 'danger';
            } else {
                $stmt = $db->prepare("INSERT INTO banners (title, subtitle, image, link, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $subtitle, $imageName, $link, $sortOrder, $isActive]);
                $msg = 'Banner added successfully!';
                $msgType = 'success';
                $action = 'list';
            }
        } elseif ($postAction === 'edit' && $bannerId > 0) {
            if (!empty($imageName)) {
                $old = $db->prepare("SELECT image FROM banners WHERE id = ?");
                $old->execute([$bannerId]);
                $oldImg = $old->fetchColumn();
                if ($oldImg && file_exists(UPLOAD_PATH . 'banners/' . $oldImg)) {
                    unlink(UPLOAD_PATH . 'banners/' . $oldImg);
                }
                $stmt = $db->prepare("UPDATE banners SET title=?, subtitle=?, image=?, link=?, sort_order=?, is_active=? WHERE id=?");
                $stmt->execute([$title, $subtitle, $imageName, $link, $sortOrder, $isActive, $bannerId]);
            } else {
                $stmt = $db->prepare("UPDATE banners SET title=?, subtitle=?, link=?, sort_order=?, is_active=? WHERE id=?");
                $stmt->execute([$title, $subtitle, $link, $sortOrder, $isActive, $bannerId]);
            }
            $msg = 'Banner updated successfully!';
            $msgType = 'success';
            $action = 'list';
        }
    }

    if ($postAction === 'delete') {
        $bannerId = intval($_POST['banner_id'] ?? 0);
        $old = $db->prepare("SELECT image FROM banners WHERE id = ?");
        $old->execute([$bannerId]);
        $oldImg = $old->fetchColumn();
        if ($oldImg && file_exists(UPLOAD_PATH . 'banners/' . $oldImg)) {
            unlink(UPLOAD_PATH . 'banners/' . $oldImg);
        }
        $db->prepare("DELETE FROM banners WHERE id = ?")->execute([$bannerId]);
        $msg = 'Banner deleted.';
        $msgType = 'success';
        $action = 'list';
    }
}

// Load edit data
$editData = null;
if ($action === 'edit') {
    $editId = intval($_GET['id'] ?? 0);
    $stmt = $db->prepare("SELECT * FROM banners WHERE id = ?");
    $stmt->execute([$editId]);
    $editData = $stmt->fetch();
    if (!$editData) {
        $action = 'list';
    }
}
?>

<?php if ($msg): ?>
<div class="alert alert-<?php echo $msgType; ?>">
    <i class="fas fa-<?php echo $msgType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
    <?php echo htmlspecialchars($msg); ?>
</div>
<?php endif; ?>

<?php if ($action === 'list'): ?>
    <div class="card">
        <div class="card-header">
            <h2>Homepage Banners</h2>
            <a href="?action=add" class="btn btn-primary"><i class="fas fa-plus"></i> Add Banner</a>
        </div>
        <div class="card-body">
            <?php
            $banners = $db->query("SELECT * FROM banners ORDER BY sort_order ASC")->fetchAll();
            if (empty($banners)):
            ?>
            <p style="text-align:center;padding:30px;color:var(--admin-text-light);">No banners yet. Click "Add Banner" to create your first one.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Title</th>
                            <th>Order</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($banners as $b): ?>
                        <tr>
                            <td><img src="<?php echo UPLOAD_URL . 'banners/' . htmlspecialchars($b['image']); ?>" alt=""></td>
                            <td><?php echo htmlspecialchars($b['title'] ?: '(No title)'); ?></td>
                            <td><?php echo $b['sort_order']; ?></td>
                            <td>
                                <?php if ($b['is_active']): ?>
                                <span class="badge badge-success">Active</span>
                                <?php else: ?>
                                <span class="badge badge-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="actions">
                                    <a href="?action=edit&id=<?php echo $b['id']; ?>" class="btn btn-sm btn-outline"><i class="fas fa-edit"></i></a>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="form_action" value="delete">
                                        <input type="hidden" name="banner_id" value="<?php echo $b['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger btn-delete"><i class="fas fa-trash"></i></button>
                                    </form>
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
        <div class="card-header">
            <h2><?php echo $action === 'edit' ? 'Edit Banner' : 'Add New Banner'; ?></h2>
            <a href="?action=list" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="form_action" value="<?php echo $action; ?>">
                <?php if ($editData): ?>
                <input type="hidden" name="banner_id" value="<?php echo $editData['id']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>Banner Title</label>
                    <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($editData['title'] ?? ''); ?>" placeholder="e.g. Welcome to Elephant House">
                </div>

                <div class="form-group">
                    <label>Subtitle</label>
                    <input type="text" name="subtitle" class="form-control" value="<?php echo htmlspecialchars($editData['subtitle'] ?? ''); ?>" placeholder="e.g. Your trusted grocery store">
                </div>

                <div class="form-group">
                    <label>Banner Image <span class="required">*</span></label>
                    <input type="file" name="image" class="form-control form-control-file" accept="image/*" <?php echo $action === 'add' ? 'required' : ''; ?>>
                    <p class="form-hint">Upload a wide banner image. It will show at 100% width with automatic height on desktop and mobile. Formats: JPG, PNG, WebP</p>
                    <div class="img-preview">
                        <?php if (!empty($editData['image'])): ?>
                        <img src="<?php echo UPLOAD_URL . 'banners/' . htmlspecialchars($editData['image']); ?>" alt="">
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label>Link URL</label>
                    <input type="url" name="link" class="form-control" value="<?php echo htmlspecialchars($editData['link'] ?? ''); ?>" placeholder="https://...">
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div class="form-group">
                        <label>Sort Order</label>
                        <input type="number" name="sort_order" class="form-control" value="<?php echo $editData['sort_order'] ?? 0; ?>">
                    </div>
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                            <input type="checkbox" name="is_active" value="1" <?php echo ($editData['is_active'] ?? 1) ? 'checked' : ''; ?> style="accent-color:var(--admin-primary);width:18px;height:18px;">
                            Active
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save"></i> Save Banner</button>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
