<?php
$pageTitle = 'Manage Pages';
require_once __DIR__ . '/layout.php';

$db = getDB();
$action = $_GET['action'] ?? 'list';
$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['form_action'] ?? '';

    if ($postAction === 'edit') {
        $pageId = intval($_POST['page_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $content = $_POST['content'] ?? '';
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($pageId > 0 && !empty($title)) {
            $stmt = $db->prepare("UPDATE pages SET title=?, content=?, is_active=? WHERE id=?");
            $stmt->execute([$title, $content, $isActive, $pageId]);
            $msg = 'Page updated successfully!';
            $msgType = 'success';
            $action = 'list';
        }
    }

    if ($postAction === 'add') {
        $title = trim($_POST['title'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $content = $_POST['content'] ?? '';
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if (!empty($title) && !empty($slug)) {
            $slug = preg_replace('/[^a-z0-9-]/', '', strtolower(str_replace(' ', '-', $slug)));
            $stmt = $db->prepare("INSERT INTO pages (slug, title, content, is_active) VALUES (?, ?, ?, ?)");
            $stmt->execute([$slug, $title, $content, $isActive]);
            $msg = 'Page created!';
            $msgType = 'success';
            $action = 'list';
        }
    }

    if ($postAction === 'delete') {
        $pageId = intval($_POST['page_id'] ?? 0);
        $db->prepare("DELETE FROM pages WHERE id = ?")->execute([$pageId]);
        $msg = 'Page deleted.';
        $msgType = 'success';
        $action = 'list';
    }
}

$editData = null;
if ($action === 'edit') {
    $editId = intval($_GET['id'] ?? 0);
    $stmt = $db->prepare("SELECT * FROM pages WHERE id = ?");
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
            <h2>Content Pages</h2>
            <a href="?action=add" class="btn btn-primary"><i class="fas fa-plus"></i> Add Page</a>
        </div>
        <div class="card-body">
            <?php $pages = $db->query("SELECT * FROM pages ORDER BY id ASC")->fetchAll(); ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr><th>Title</th><th>Slug</th><th>Status</th><th>Last Updated</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($pages as $p): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($p['title']); ?></strong></td>
                            <td><code><?php echo htmlspecialchars($p['slug']); ?></code></td>
                            <td><span class="badge badge-<?php echo $p['is_active'] ? 'success' : 'danger'; ?>"><?php echo $p['is_active'] ? 'Active' : 'Inactive'; ?></span></td>
                            <td style="font-size:12px;color:var(--admin-text-light);"><?php echo $p['updated_at']; ?></td>
                            <td>
                                <div class="actions">
                                    <a href="?action=edit&id=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline"><i class="fas fa-edit"></i> Edit</a>
                                    <a href="<?php echo SITE_URL; ?>/page.php?slug=<?php echo urlencode($p['slug']); ?>" target="_blank" class="btn btn-sm btn-outline"><i class="fas fa-eye"></i></a>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="form_action" value="delete">
                                        <input type="hidden" name="page_id" value="<?php echo $p['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger btn-delete"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php elseif ($action === 'edit' || $action === 'add'): ?>
    <div class="card">
        <div class="card-header">
            <h2><?php echo $action === 'edit' ? 'Edit Page: ' . htmlspecialchars($editData['title'] ?? '') : 'Add New Page'; ?></h2>
            <a href="?action=list" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="form_action" value="<?php echo $action; ?>">
                <?php if ($editData): ?>
                <input type="hidden" name="page_id" value="<?php echo $editData['id']; ?>">
                <?php endif; ?>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div class="form-group">
                        <label>Page Title <span class="required">*</span></label>
                        <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($editData['title'] ?? ''); ?>" required>
                    </div>
                    <?php if ($action === 'add'): ?>
                    <div class="form-group">
                        <label>Slug <span class="required">*</span></label>
                        <input type="text" name="slug" class="form-control" placeholder="e.g. about-us" required>
                    </div>
                    <?php else: ?>
                    <div class="form-group">
                        <label>Slug</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($editData['slug'] ?? ''); ?>" disabled>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label>Page Content</label>
                    <textarea name="content" class="form-control" rows="20" style="font-family:monospace;font-size:13px;"><?php echo htmlspecialchars($editData['content'] ?? ''); ?></textarea>
                    <p class="form-hint">You can use HTML tags for formatting (h2, h3, p, ul, li, strong, em, a, img, table, etc.)</p>
                </div>

                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="checkbox" name="is_active" value="1" <?php echo ($editData['is_active'] ?? 1) ? 'checked' : ''; ?> style="accent-color:var(--admin-primary);width:18px;height:18px;"> Active
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save"></i> Save Page</button>

                <?php if ($editData): ?>
                <a href="<?php echo SITE_URL; ?>/page.php?slug=<?php echo urlencode($editData['slug']); ?>" target="_blank" class="btn btn-outline btn-lg" style="margin-left:10px;">
                    <i class="fas fa-eye"></i> Preview
                </a>
                <?php endif; ?>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
