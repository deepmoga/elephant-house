<?php
$pageTitle = 'Home Sections';
require_once __DIR__ . '/layout.php';

$db = getDB();
ensureHomeSectionsTable();

$action = $_GET['action'] ?? 'list';
$msg = '';
$msgType = '';
$apiCategories = getCategories();
$parentCategories = getParentCategories();

function findApiCategoryName($apiCategories, $categoryId) {
    foreach ($apiCategories as $cat) {
        if ($cat['id'] === $categoryId) return $cat['name'];
    }
    return '';
}

function findParentCategory($parentCategories, $parentId) {
    foreach ($parentCategories as $cat) {
        if (intval($cat['id']) === intval($parentId)) return $cat;
    }
    return null;
}

function getFirstCategoryForParent($db, $parent) {
    if (!$parent) return ['', ''];
    $stmt = $db->prepare("SELECT api_category_id, api_category_name FROM category_mapping WHERE parent_category_id = ? ORDER BY sort_order ASC LIMIT 1");
    $stmt->execute([$parent['id']]);
    $firstSub = $stmt->fetch();
    if ($firstSub) return [$firstSub['api_category_id'], $firstSub['api_category_name']];
    if (strpos($parent['api_category_id'], 'custom-') !== 0) return [$parent['api_category_id'], $parent['name'] ?: $parent['api_category_name']];
    return ['', ''];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['form_action'] ?? '';

    if ($postAction === 'add' || $postAction === 'edit') {
        $title = trim($_POST['title'] ?? '');
        $subtitle = trim($_POST['subtitle'] ?? '');
        $sectionSource = $_POST['section_source'] ?? 'api';
        $parentCategoryId = intval($_POST['parent_category_id'] ?? 0);
        $categoryId = trim($_POST['api_category_id'] ?? '');
        $categoryName = findApiCategoryName($apiCategories, $categoryId);
        if ($sectionSource === 'parent') {
            $parent = findParentCategory($parentCategories, $parentCategoryId);
            [$categoryId, $categoryName] = getFirstCategoryForParent($db, $parent);
            if ($parent) $categoryName = $parent['name'] ?: $parent['api_category_name'];
        } else {
            $parentCategoryId = 0;
        }
        $productLimit = max(1, min(20, intval($_POST['product_limit'] ?? 6)));
        $sortOrder = intval($_POST['sort_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $sectionId = intval($_POST['section_id'] ?? 0);

        if ($title === '' || $categoryId === '' || $categoryName === '') {
            $msg = 'Please enter a title and choose a category with products.';
            $msgType = 'danger';
        } elseif ($postAction === 'add') {
            $stmt = $db->prepare("INSERT INTO home_sections (title, subtitle, section_source, parent_category_id, api_category_id, api_category_name, product_limit, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $subtitle, $sectionSource, $parentCategoryId ?: null, $categoryId, $categoryName, $productLimit, $sortOrder, $isActive]);
            $msg = 'Home section created.';
            $msgType = 'success';
            $action = 'list';
        } elseif ($sectionId > 0) {
            $stmt = $db->prepare("UPDATE home_sections SET title=?, subtitle=?, section_source=?, parent_category_id=?, api_category_id=?, api_category_name=?, product_limit=?, sort_order=?, is_active=? WHERE id=?");
            $stmt->execute([$title, $subtitle, $sectionSource, $parentCategoryId ?: null, $categoryId, $categoryName, $productLimit, $sortOrder, $isActive, $sectionId]);
            $msg = 'Home section updated.';
            $msgType = 'success';
            $action = 'list';
        }
    }

    if ($postAction === 'delete') {
        $sectionId = intval($_POST['section_id'] ?? 0);
        $db->prepare("DELETE FROM home_sections WHERE id = ?")->execute([$sectionId]);
        $msg = 'Home section deleted.';
        $msgType = 'success';
        $action = 'list';
    }
}

$editData = null;
if ($action === 'edit') {
    $editId = intval($_GET['id'] ?? 0);
    $stmt = $db->prepare("SELECT * FROM home_sections WHERE id = ?");
    $stmt->execute([$editId]);
    $editData = $stmt->fetch();
    if (!$editData) $action = 'list';
}
?>

<?php if ($msg): ?>
<div class="alert alert-<?php echo $msgType; ?>"><i class="fas fa-<?php echo $msgType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i> <?php echo htmlspecialchars($msg); ?></div>
<?php endif; ?>

<?php if ($action === 'list'): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i>
        <span>Create homepage product sections such as <strong>Best sale of week</strong>. Each section shows products from the category you choose.</span>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>Home Sections</h2>
            <a href="?action=add" class="btn btn-primary"><i class="fas fa-plus"></i> Add Section</a>
        </div>
        <div class="card-body">
            <?php
            $sections = $db->query("SELECT * FROM home_sections ORDER BY sort_order ASC, id ASC")->fetchAll();
            if (empty($sections)):
            ?>
            <p style="text-align:center;padding:30px;color:var(--admin-text-light);">No home sections yet.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr><th>Title</th><th>Source</th><th>Category</th><th>Products</th><th>Order</th><th>Status</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($sections as $section): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($section['title']); ?></strong></td>
                            <td><span class="badge badge-<?php echo ($section['section_source'] ?? 'api') === 'parent' ? 'warning' : 'success'; ?>"><?php echo ($section['section_source'] ?? 'api') === 'parent' ? 'Parent' : 'API'; ?></span></td>
                            <td><?php echo htmlspecialchars($section['api_category_name']); ?></td>
                            <td><?php echo intval($section['product_limit']); ?></td>
                            <td><?php echo intval($section['sort_order']); ?></td>
                            <td><span class="badge badge-<?php echo $section['is_active'] ? 'success' : 'danger'; ?>"><?php echo $section['is_active'] ? 'Active' : 'Inactive'; ?></span></td>
                            <td>
                                <div class="actions">
                                    <a href="?action=edit&id=<?php echo $section['id']; ?>" class="btn btn-sm btn-outline"><i class="fas fa-edit"></i></a>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="form_action" value="delete">
                                        <input type="hidden" name="section_id" value="<?php echo $section['id']; ?>">
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
            <h2><?php echo $action === 'edit' ? 'Edit Section' : 'Add Home Section'; ?></h2>
            <a href="?action=list" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="form_action" value="<?php echo $action; ?>">
                <?php if ($editData): ?>
                <input type="hidden" name="section_id" value="<?php echo $editData['id']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>Section Title <span class="required">*</span></label>
                    <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($editData['title'] ?? ''); ?>" placeholder="Best sale of week" required>
                </div>

                <div class="form-group">
                    <label>Subtitle</label>
                    <input type="text" name="subtitle" class="form-control" value="<?php echo htmlspecialchars($editData['subtitle'] ?? ''); ?>" placeholder="Optional short text under the title">
                </div>

                <?php $currentSource = $editData['section_source'] ?? 'api'; ?>
                <div class="form-group">
                    <label>Section Source</label>
                    <div style="display:flex;gap:20px;flex-wrap:wrap;">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                            <input type="radio" name="section_source" value="parent" <?php echo $currentSource === 'parent' ? 'checked' : ''; ?> onchange="toggleSectionSource()" style="accent-color:var(--admin-primary);width:18px;height:18px;">
                            Portal parent category
                        </label>
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                            <input type="radio" name="section_source" value="api" <?php echo $currentSource !== 'parent' ? 'checked' : ''; ?> onchange="toggleSectionSource()" style="accent-color:var(--admin-primary);width:18px;height:18px;">
                            Single API product category
                        </label>
                    </div>
                </div>

                <div class="form-group" id="parentCategoryBox">
                    <label>Portal Parent Category</label>
                    <select name="parent_category_id" class="form-control" style="max-width:520px;">
                        <option value="">Select parent category</option>
                        <?php foreach ($parentCategories as $parent): ?>
                        <option value="<?php echo $parent['id']; ?>" <?php echo intval($editData['parent_category_id'] ?? 0) === intval($parent['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($parent['name'] ?: $parent['api_category_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="form-hint">The home section will show this parent category with its subcategories on the left.</p>
                </div>

                <div class="form-group" id="apiCategoryBox">
                    <label>Product Category <span class="required">*</span></label>
                    <input type="text" id="searchSectionCategory" class="form-control" placeholder="Search categories..." style="margin-bottom:10px;max-width:420px;" oninput="filterSectionCategories(this.value)">
                    <div id="sectionCategoryList" style="max-height:300px;overflow-y:auto;border:1px solid var(--admin-border);border-radius:8px;background:#fff;">
                        <?php foreach ($apiCategories as $cat): ?>
                        <label class="section-category-row" data-name="<?php echo strtolower($cat['name']); ?>" style="display:flex;align-items:center;gap:10px;padding:10px 15px;border-bottom:1px solid #f0f0f0;cursor:pointer;">
                            <input type="radio" name="api_category_id" value="<?php echo htmlspecialchars($cat['id']); ?>" <?php echo (($editData['api_category_id'] ?? '') === $cat['id']) ? 'checked' : ''; ?> required style="accent-color:var(--admin-primary);width:18px;height:18px;">
                            <span><?php echo htmlspecialchars($cat['name']); ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;">
                    <div class="form-group">
                        <label>Products to Show</label>
                        <input type="number" name="product_limit" class="form-control" min="1" max="20" value="<?php echo $editData['product_limit'] ?? 6; ?>">
                    </div>
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

                <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save"></i> Save Section</button>
            </form>
        </div>
    </div>

    <script>
    function filterSectionCategories(query) {
        query = query.toLowerCase();
        document.querySelectorAll('.section-category-row').forEach(function(row) {
            row.style.display = row.getAttribute('data-name').includes(query) ? '' : 'none';
        });
    }
    function toggleSectionSource() {
        var selected = document.querySelector('input[name="section_source"]:checked');
        var source = selected ? selected.value : 'api';
        var parentBox = document.getElementById('parentCategoryBox');
        var apiBox = document.getElementById('apiCategoryBox');
        if (parentBox) parentBox.style.display = source === 'parent' ? '' : 'none';
        if (apiBox) apiBox.style.display = source === 'api' ? '' : 'none';
        document.querySelectorAll('#apiCategoryBox input[name="api_category_id"]').forEach(function(input) {
            input.disabled = source !== 'api';
        });
    }
    toggleSectionSource();
    </script>
<?php endif; ?>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
