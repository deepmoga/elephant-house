<?php
$pageTitle = 'FAQs';
require_once __DIR__ . '/layout.php';

$db = getDB();
$action = $_GET['action'] ?? 'list';
$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['form_action'] ?? '';

    if ($postAction === 'add' || $postAction === 'edit') {
        $question = trim($_POST['question'] ?? '');
        $answer = trim($_POST['answer'] ?? '');
        $sortOrder = intval($_POST['sort_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $faqId = intval($_POST['faq_id'] ?? 0);

        if (empty($question) || empty($answer)) {
            $msg = 'Question and answer are required.';
            $msgType = 'danger';
        } else {
            if ($postAction === 'add') {
                $stmt = $db->prepare("INSERT INTO faqs (question, answer, sort_order, is_active) VALUES (?, ?, ?, ?)");
                $stmt->execute([$question, $answer, $sortOrder, $isActive]);
                $msg = 'FAQ added!';
            } else {
                $stmt = $db->prepare("UPDATE faqs SET question=?, answer=?, sort_order=?, is_active=? WHERE id=?");
                $stmt->execute([$question, $answer, $sortOrder, $isActive, $faqId]);
                $msg = 'FAQ updated!';
            }
            $msgType = 'success';
            $action = 'list';
        }
    }

    if ($postAction === 'delete') {
        $db->prepare("DELETE FROM faqs WHERE id = ?")->execute([intval($_POST['faq_id'] ?? 0)]);
        $msg = 'FAQ deleted.';
        $msgType = 'success';
    }
}

$editData = null;
if ($action === 'edit') {
    $stmt = $db->prepare("SELECT * FROM faqs WHERE id = ?");
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
    <div class="card-header"><h2>FAQs</h2><a href="?action=add" class="btn btn-primary"><i class="fas fa-plus"></i> Add FAQ</a></div>
    <div class="card-body">
        <?php $faqs = $db->query("SELECT * FROM faqs ORDER BY sort_order ASC")->fetchAll(); ?>
        <?php if (empty($faqs)): ?>
        <p style="text-align:center;padding:30px;color:var(--admin-text-light);">No FAQs yet.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Question</th><th>Order</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($faqs as $f): ?>
                <tr>
                    <td style="max-width:400px;"><?php echo htmlspecialchars($f['question']); ?></td>
                    <td><?php echo $f['sort_order']; ?></td>
                    <td><span class="badge badge-<?php echo $f['is_active'] ? 'success' : 'danger'; ?>"><?php echo $f['is_active'] ? 'Active' : 'Inactive'; ?></span></td>
                    <td>
                        <div class="actions">
                            <a href="?action=edit&id=<?php echo $f['id']; ?>" class="btn btn-sm btn-outline"><i class="fas fa-edit"></i></a>
                            <form method="POST" style="display:inline;"><input type="hidden" name="form_action" value="delete"><input type="hidden" name="faq_id" value="<?php echo $f['id']; ?>"><button type="submit" class="btn btn-sm btn-danger btn-delete"><i class="fas fa-trash"></i></button></form>
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
    <div class="card-header"><h2><?php echo $action === 'edit' ? 'Edit FAQ' : 'Add FAQ'; ?></h2><a href="?action=list" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a></div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="form_action" value="<?php echo $action; ?>">
            <?php if ($editData): ?><input type="hidden" name="faq_id" value="<?php echo $editData['id']; ?>"><?php endif; ?>
            <div class="form-group">
                <label>Question *</label>
                <input type="text" name="question" class="form-control" required value="<?php echo htmlspecialchars($editData['question'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Answer *</label>
                <textarea name="answer" class="form-control" rows="6" required><?php echo htmlspecialchars($editData['answer'] ?? ''); ?></textarea>
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
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save"></i> Save FAQ</button>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
