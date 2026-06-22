<?php
$pageTitle = 'Blog Posts';
require_once __DIR__ . '/layout.php';

$db = getDB();
$action = $_GET['action'] ?? 'list';
$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['form_action'] ?? '';

    if ($postAction === 'add' || $postAction === 'edit') {
        $title = trim($_POST['title'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $excerpt = trim($_POST['excerpt'] ?? '');
        $content = $_POST['content'] ?? '';
        $author = trim($_POST['author'] ?? '');
        $isPublished = isset($_POST['is_published']) ? 1 : 0;
        $blogId = intval($_POST['blog_id'] ?? 0);

        if (empty($title) || empty($slug)) {
            $msg = 'Title and slug are required.';
            $msgType = 'danger';
        } else {
            $slug = preg_replace('/[^a-z0-9-]/', '', strtolower(str_replace(' ', '-', $slug)));

            $imageName = '';
            if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === 0) {
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
                    $imageName = 'blog_' . time() . '_' . mt_rand(1000,9999) . '.' . $ext;
                    move_uploaded_file($_FILES['image']['tmp_name'], UPLOAD_PATH . 'blogs/' . $imageName);
                }
            }

            if ($postAction === 'add') {
                if (!empty($imageName)) {
                    $stmt = $db->prepare("INSERT INTO blogs (title, slug, excerpt, content, image, author, is_published) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$title, $slug, $excerpt, $content, $imageName, $author, $isPublished]);
                } else {
                    $stmt = $db->prepare("INSERT INTO blogs (title, slug, excerpt, content, author, is_published) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$title, $slug, $excerpt, $content, $author, $isPublished]);
                }
                $msg = 'Blog post created!';
                $msgType = 'success';
                $action = 'list';
            } elseif ($blogId > 0) {
                if (!empty($imageName)) {
                    $old = $db->prepare("SELECT image FROM blogs WHERE id = ?");
                    $old->execute([$blogId]);
                    $oldImg = $old->fetchColumn();
                    if ($oldImg && file_exists(UPLOAD_PATH . 'blogs/' . $oldImg)) unlink(UPLOAD_PATH . 'blogs/' . $oldImg);
                    $stmt = $db->prepare("UPDATE blogs SET title=?, slug=?, excerpt=?, content=?, image=?, author=?, is_published=? WHERE id=?");
                    $stmt->execute([$title, $slug, $excerpt, $content, $imageName, $author, $isPublished, $blogId]);
                } else {
                    $stmt = $db->prepare("UPDATE blogs SET title=?, slug=?, excerpt=?, content=?, author=?, is_published=? WHERE id=?");
                    $stmt->execute([$title, $slug, $excerpt, $content, $author, $isPublished, $blogId]);
                }
                $msg = 'Blog post updated!';
                $msgType = 'success';
                $action = 'list';
            }
        }
    }

    if ($postAction === 'delete') {
        $blogId = intval($_POST['blog_id'] ?? 0);
        $old = $db->prepare("SELECT image FROM blogs WHERE id = ?");
        $old->execute([$blogId]);
        $oldImg = $old->fetchColumn();
        if ($oldImg && file_exists(UPLOAD_PATH . 'blogs/' . $oldImg)) unlink(UPLOAD_PATH . 'blogs/' . $oldImg);
        $db->prepare("DELETE FROM blogs WHERE id = ?")->execute([$blogId]);
        $msg = 'Blog post deleted.';
        $msgType = 'success';
    }
}

$editData = null;
if ($action === 'edit') {
    $stmt = $db->prepare("SELECT * FROM blogs WHERE id = ?");
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
    <div class="card-header"><h2>Blog Posts</h2><a href="?action=add" class="btn btn-primary"><i class="fas fa-plus"></i> Add Post</a></div>
    <div class="card-body">
        <?php $blogs = $db->query("SELECT * FROM blogs ORDER BY created_at DESC")->fetchAll(); ?>
        <?php if (empty($blogs)): ?>
        <p style="text-align:center;padding:30px;color:var(--admin-text-light);">No blog posts yet.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Image</th><th>Title</th><th>Author</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($blogs as $b): ?>
                <tr>
                    <td><?php if (!empty($b['image'])): ?><img src="<?php echo UPLOAD_URL . 'blogs/' . htmlspecialchars($b['image']); ?>"><?php else: ?>-<?php endif; ?></td>
                    <td><strong><?php echo htmlspecialchars($b['title']); ?></strong></td>
                    <td><?php echo htmlspecialchars($b['author'] ?: '-'); ?></td>
                    <td><span class="badge badge-<?php echo $b['is_published'] ? 'success' : 'warning'; ?>"><?php echo $b['is_published'] ? 'Published' : 'Draft'; ?></span></td>
                    <td style="font-size:12px;"><?php echo date('d M Y', strtotime($b['created_at'])); ?></td>
                    <td>
                        <div class="actions">
                            <a href="?action=edit&id=<?php echo $b['id']; ?>" class="btn btn-sm btn-outline"><i class="fas fa-edit"></i></a>
                            <form method="POST" style="display:inline;"><input type="hidden" name="form_action" value="delete"><input type="hidden" name="blog_id" value="<?php echo $b['id']; ?>"><button type="submit" class="btn btn-sm btn-danger btn-delete"><i class="fas fa-trash"></i></button></form>
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
    <div class="card-header"><h2><?php echo $action === 'edit' ? 'Edit Post' : 'Add Blog Post'; ?></h2><a href="?action=list" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a></div>
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="form_action" value="<?php echo $action; ?>">
            <?php if ($editData): ?><input type="hidden" name="blog_id" value="<?php echo $editData['id']; ?>"><?php endif; ?>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                <div class="form-group">
                    <label>Title *</label>
                    <input type="text" name="title" id="cat_name" class="form-control" required value="<?php echo htmlspecialchars($editData['title'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Slug *</label>
                    <input type="text" name="slug" id="cat_slug" class="form-control" required value="<?php echo htmlspecialchars($editData['slug'] ?? ''); ?>">
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                <div class="form-group">
                    <label>Author</label>
                    <input type="text" name="author" class="form-control" value="<?php echo htmlspecialchars($editData['author'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Featured Image</label>
                    <input type="file" name="image" class="form-control form-control-file" accept="image/*">
                    <div class="img-preview"><?php if (!empty($editData['image'])): ?><img src="<?php echo UPLOAD_URL . 'blogs/' . htmlspecialchars($editData['image']); ?>"><?php endif; ?></div>
                </div>
            </div>
            <div class="form-group">
                <label>Excerpt</label>
                <textarea name="excerpt" class="form-control" rows="3" placeholder="Short summary for listing page"><?php echo htmlspecialchars($editData['excerpt'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label>Content</label>
                <textarea name="content" class="form-control" rows="15" style="font-family:monospace;font-size:13px;"><?php echo htmlspecialchars($editData['content'] ?? ''); ?></textarea>
                <p class="form-hint">HTML tags supported</p>
            </div>
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                    <input type="checkbox" name="is_published" value="1" <?php echo ($editData['is_published'] ?? 0) ? 'checked' : ''; ?> style="accent-color:var(--admin-primary);width:18px;height:18px;"> Published
                </label>
            </div>
            <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save"></i> Save Post</button>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
