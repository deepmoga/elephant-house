<?php
$pageTitle = 'Offer Banners';
require_once __DIR__ . '/layout.php';

$db = getDB();
try { $db->exec("ALTER TABLE `offer_banners` ADD COLUMN `show_on_home` TINYINT(1) DEFAULT 0 AFTER `is_active`"); } catch (PDOException $e) {}
$action = $_GET['action'] ?? 'list';
$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['form_action'] ?? '';

    if ($postAction === 'add' || $postAction === 'edit') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $link = trim($_POST['link'] ?? '');
        $sortOrder = intval($_POST['sort_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $showOnHome = isset($_POST['show_on_home']) ? 1 : 0;
        $offerId = intval($_POST['offer_id'] ?? 0);

        $imageName = '';
        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === 0) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($ext, $allowed)) {
                $imageName = 'offer_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
                move_uploaded_file($_FILES['image']['tmp_name'], UPLOAD_PATH . 'offers/' . $imageName);
            }
        }

        if ($postAction === 'add') {
            if (empty($imageName)) {
                $msg = 'Please upload an image.';
                $msgType = 'danger';
            } else {
                if ($showOnHome) $db->exec("UPDATE offer_banners SET show_on_home = 0");
                $stmt = $db->prepare("INSERT INTO offer_banners (title, description, image, link, sort_order, is_active, show_on_home) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $description, $imageName, $link, $sortOrder, $isActive, $showOnHome]);
                $msg = 'Offer banner added!';
                $msgType = 'success';
                $action = 'list';
            }
        } elseif ($postAction === 'edit' && $offerId > 0) {
            if ($showOnHome) $db->prepare("UPDATE offer_banners SET show_on_home = 0 WHERE id != ?")->execute([$offerId]);
            if (!empty($imageName)) {
                $old = $db->prepare("SELECT image FROM offer_banners WHERE id = ?");
                $old->execute([$offerId]);
                $oldImg = $old->fetchColumn();
                if ($oldImg && file_exists(UPLOAD_PATH . 'offers/' . $oldImg)) {
                    unlink(UPLOAD_PATH . 'offers/' . $oldImg);
                }
                $stmt = $db->prepare("UPDATE offer_banners SET title=?, description=?, image=?, link=?, sort_order=?, is_active=?, show_on_home=? WHERE id=?");
                $stmt->execute([$title, $description, $imageName, $link, $sortOrder, $isActive, $showOnHome, $offerId]);
            } else {
                $stmt = $db->prepare("UPDATE offer_banners SET title=?, description=?, link=?, sort_order=?, is_active=?, show_on_home=? WHERE id=?");
                $stmt->execute([$title, $description, $link, $sortOrder, $isActive, $showOnHome, $offerId]);
            }
            $msg = 'Offer banner updated!';
            $msgType = 'success';
            $action = 'list';
        }
    }

    if ($postAction === 'delete') {
        $offerId = intval($_POST['offer_id'] ?? 0);
        $old = $db->prepare("SELECT image FROM offer_banners WHERE id = ?");
        $old->execute([$offerId]);
        $oldImg = $old->fetchColumn();
        if ($oldImg && file_exists(UPLOAD_PATH . 'offers/' . $oldImg)) {
            unlink(UPLOAD_PATH . 'offers/' . $oldImg);
        }
        $db->prepare("DELETE FROM offer_banners WHERE id = ?")->execute([$offerId]);
        $msg = 'Offer banner deleted.';
        $msgType = 'success';
        $action = 'list';
    }
}

$editData = null;
if ($action === 'edit') {
    $editId = intval($_GET['id'] ?? 0);
    $stmt = $db->prepare("SELECT * FROM offer_banners WHERE id = ?");
    $stmt->execute([$editId]);
    $editData = $stmt->fetch();
    if (!$editData) $action = 'list';
}
?>

<?php if ($msg): ?>
<div class="alert alert-<?php echo $msgType; ?>"><i class="fas fa-<?php echo $msgType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i> <?php echo htmlspecialchars($msg); ?></div>
<?php endif; ?>

<?php if ($action === 'list'): ?>
    <div class="card">
        <div class="card-header">
            <h2>Offer Banners</h2>
            <a href="?action=add" class="btn btn-primary"><i class="fas fa-plus"></i> Add Offer</a>
        </div>
        <div class="card-body">
            <?php
            $offers = $db->query("SELECT * FROM offer_banners ORDER BY sort_order ASC")->fetchAll();
            if (empty($offers)):
            ?>
            <p style="text-align:center;padding:30px;color:var(--admin-text-light);">No offer banners yet.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr><th>Image</th><th>Title</th><th>Order</th><th>Home Offer</th><th>Status</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($offers as $o): ?>
                        <tr>
                            <td><img src="<?php echo UPLOAD_URL . 'offers/' . htmlspecialchars($o['image']); ?>" alt=""></td>
                            <td><?php echo htmlspecialchars($o['title'] ?: '(No title)'); ?></td>
                            <td><?php echo $o['sort_order']; ?></td>
                            <td>
                                <?php if (!empty($o['show_on_home'])): ?>
                                <span class="badge badge-success"><i class="fas fa-home"></i> Yes</span>
                                <?php else: ?>
                                <span style="color:var(--admin-text-light);">-</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge badge-<?php echo $o['is_active'] ? 'success' : 'danger'; ?>"><?php echo $o['is_active'] ? 'Active' : 'Inactive'; ?></span></td>
                            <td>
                                <div class="actions">
                                    <a href="?action=edit&id=<?php echo $o['id']; ?>" class="btn btn-sm btn-outline"><i class="fas fa-edit"></i></a>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="form_action" value="delete">
                                        <input type="hidden" name="offer_id" value="<?php echo $o['id']; ?>">
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
            <h2><?php echo $action === 'edit' ? 'Edit Offer Banner' : 'Add Offer Banner'; ?></h2>
            <a href="?action=list" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="form_action" value="<?php echo $action; ?>">
                <?php if ($editData): ?>
                <input type="hidden" name="offer_id" value="<?php echo $editData['id']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($editData['title'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($editData['description'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Image <span class="required">*</span></label>
                    <input type="file" name="image" class="form-control form-control-file" accept="image/*" <?php echo $action === 'add' ? 'required' : ''; ?>>
                    <p class="form-hint">Recommended: 800x400px</p>
                    <div class="img-preview">
                        <?php if (!empty($editData['image'])): ?>
                        <img src="<?php echo UPLOAD_URL . 'offers/' . htmlspecialchars($editData['image']); ?>" alt="">
                        <?php endif; ?>
                    </div>
                </div>
                <div class="form-group">
                    <label>Link URL</label>
                    <input type="url" name="link" class="form-control" value="<?php echo htmlspecialchars($editData['link'] ?? ''); ?>">
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div class="form-group">
                        <label>Sort Order</label>
                        <input type="number" name="sort_order" class="form-control" value="<?php echo $editData['sort_order'] ?? 0; ?>">
                    </div>
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                            <input type="checkbox" name="is_active" value="1" <?php echo ($editData['is_active'] ?? 1) ? 'checked' : ''; ?> style="accent-color:var(--admin-primary);width:18px;height:18px;"> Active
                        </label>
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-top:10px;">
                            <input type="checkbox" name="show_on_home" value="1" <?php echo ($editData['show_on_home'] ?? 0) ? 'checked' : ''; ?> style="accent-color:var(--admin-accent);width:18px;height:18px;"> Show as single home offer
                        </label>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save"></i> Save</button>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
