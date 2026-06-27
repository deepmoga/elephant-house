<?php
$pageTitle = 'Category Management';
require_once __DIR__ . '/layout.php';

$db = getDB();
$action = $_GET['action'] ?? 'list';
$msg = '';
$msgType = '';

$apiCategories = getCategories();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['form_action'] ?? '';

    if ($postAction === 'add' || $postAction === 'edit') {
        $parentSource = $_POST['parent_source'] ?? 'api';
        $parentApiId = trim($_POST['parent_api_id'] ?? '');
        $customParentName = trim($_POST['custom_parent_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $sortOrder = intval($_POST['sort_order'] ?? 0);
        $showInMenu = isset($_POST['show_in_menu']) ? 1 : 0;
        $allowCart = isset($_POST['allow_cart']) ? 1 : 0;
        $priceMarkup = floatval($_POST['price_markup'] ?? 0);
        $priceMarkupType = $_POST['price_markup_type'] ?? 'fixed';
        $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $selectedSubs = $_POST['sub_categories'] ?? [];
        $catId = intval($_POST['cat_id'] ?? 0);

        $parentApiName = '';
        if ($parentSource === 'custom') {
            $parentApiName = $customParentName;
            if ($postAction === 'edit' && $catId > 0 && strpos($parentApiId, 'custom-') === 0) {
                $parentApiId = trim($_POST['parent_api_id'] ?? '');
            } else {
                $baseSlug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $customParentName));
                $parentApiId = 'custom-' . trim($baseSlug, '-') . '-' . time();
            }
        } else {
            foreach ($apiCategories as $ac) {
                if ($ac['id'] === $parentApiId) {
                    $parentApiName = $ac['name'];
                    break;
                }
            }
        }

        if (empty($parentApiId) || empty($parentApiName)) {
            $msg = 'Please select an API parent category or enter your own parent category name.';
            $msgType = 'danger';
        } else {
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $parentApiName));
            $slug = trim($slug, '-');
            $name = $parentApiName;

            $imageName = '';
            if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === 0) {
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (in_array($ext, $allowed)) {
                    $imageName = 'cat_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
                    move_uploaded_file($_FILES['image']['tmp_name'], UPLOAD_PATH . $imageName);
                }
            }

            if ($postAction === 'add') {
                $existCheck = $parentSource === 'api'
                    ? $db->prepare("SELECT id FROM parent_categories WHERE api_category_id = ?")
                    : $db->prepare("SELECT id FROM parent_categories WHERE slug = ?");
                $existCheck->execute([$parentSource === 'api' ? $parentApiId : $slug]);
                if ($existCheck->fetch()) {
                    $msg = 'This parent category is already added.';
                    $msgType = 'danger';
                } else {
                    if (!empty($imageName)) {
                        $stmt = $db->prepare("INSERT INTO parent_categories (api_category_id, api_category_name, name, slug, image, description, sort_order, show_in_menu, allow_cart, price_markup, price_markup_type, is_featured, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$parentApiId, $parentApiName, $name, $slug, $imageName, $description, $sortOrder, $showInMenu, $allowCart, $priceMarkup, $priceMarkupType, $isFeatured, $isActive]);
                    } else {
                        $stmt = $db->prepare("INSERT INTO parent_categories (api_category_id, api_category_name, name, slug, description, sort_order, show_in_menu, allow_cart, price_markup, price_markup_type, is_featured, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$parentApiId, $parentApiName, $name, $slug, $description, $sortOrder, $showInMenu, $allowCart, $priceMarkup, $priceMarkupType, $isFeatured, $isActive]);
                    }
                    $catId = $db->lastInsertId();

                    // Save subcategories with images
                    if (!empty($selectedSubs)) {
                        $stmtMap = $db->prepare("INSERT IGNORE INTO category_mapping (parent_category_id, api_category_id, api_category_name, image, sort_order) VALUES (?, ?, ?, ?, ?)");
                        $order = 0;
                        foreach ($selectedSubs as $subId) {
                            $subName = '';
                            foreach ($apiCategories as $ac) {
                                if ($ac['id'] === $subId) {
                                    $subName = $ac['name'];
                                    break;
                                }
                            }
                            $subImgName = '';
                            $fileKey = 'sub_image_' . str_replace('-', '_', $subId);
                            if (!empty($_FILES[$fileKey]['name']) && $_FILES[$fileKey]['error'] === 0) {
                                $ext = strtolower(pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION));
                                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                                    $subImgName = 'subcat_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
                                    move_uploaded_file($_FILES[$fileKey]['tmp_name'], UPLOAD_PATH . $subImgName);
                                }
                            }
                            if (!empty($subName)) {
                                $stmtMap->execute([$catId, $subId, $subName, $subImgName ?: null, $order++]);
                            }
                        }
                    }

                    $msg = 'Category created successfully!';
                    $msgType = 'success';
                    $action = 'list';
                }
            } elseif ($postAction === 'edit' && $catId > 0) {
                $dupCheck = $parentSource === 'api'
                    ? $db->prepare("SELECT id FROM parent_categories WHERE api_category_id = ? AND id != ?")
                    : $db->prepare("SELECT id FROM parent_categories WHERE slug = ? AND id != ?");
                $dupCheck->execute([$parentSource === 'api' ? $parentApiId : $slug, $catId]);
                if ($dupCheck->fetch()) {
                    $msg = 'This parent category is already used by another category.';
                    $msgType = 'danger';
                } else {
                    if (!empty($imageName)) {
                        $old = $db->prepare("SELECT image FROM parent_categories WHERE id = ?");
                        $old->execute([$catId]);
                        $oldImg = $old->fetchColumn();
                        if ($oldImg && file_exists(UPLOAD_PATH . $oldImg)) {
                            unlink(UPLOAD_PATH . $oldImg);
                        }
                        $stmt = $db->prepare("UPDATE parent_categories SET api_category_id=?, api_category_name=?, name=?, slug=?, image=?, description=?, sort_order=?, show_in_menu=?, allow_cart=?, price_markup=?, price_markup_type=?, is_featured=?, is_active=? WHERE id=?");
                        $stmt->execute([$parentApiId, $parentApiName, $name, $slug, $imageName, $description, $sortOrder, $showInMenu, $allowCart, $priceMarkup, $priceMarkupType, $isFeatured, $isActive, $catId]);
                    } else {
                        $stmt = $db->prepare("UPDATE parent_categories SET api_category_id=?, api_category_name=?, name=?, slug=?, description=?, sort_order=?, show_in_menu=?, allow_cart=?, price_markup=?, price_markup_type=?, is_featured=?, is_active=? WHERE id=?");
                        $stmt->execute([$parentApiId, $parentApiName, $name, $slug, $description, $sortOrder, $showInMenu, $allowCart, $priceMarkup, $priceMarkupType, $isFeatured, $isActive, $catId]);
                    }

                    // Delete old subcategory images
                    $oldSubs = $db->prepare("SELECT image FROM category_mapping WHERE parent_category_id = ?");
                    $oldSubs->execute([$catId]);
                    while ($os = $oldSubs->fetch()) {
                        if ($os['image'] && file_exists(UPLOAD_PATH . $os['image'])) {
                            unlink(UPLOAD_PATH . $os['image']);
                        }
                    }
                    $db->prepare("DELETE FROM category_mapping WHERE parent_category_id = ?")->execute([$catId]);

                    if (!empty($selectedSubs)) {
                        $stmtMap = $db->prepare("INSERT IGNORE INTO category_mapping (parent_category_id, api_category_id, api_category_name, image, sort_order) VALUES (?, ?, ?, ?, ?)");
                        $order = 0;
                        foreach ($selectedSubs as $subId) {
                            $subName = '';
                            foreach ($apiCategories as $ac) {
                                if ($ac['id'] === $subId) {
                                    $subName = $ac['name'];
                                    break;
                                }
                            }
                            $subImgName = '';
                            $fileKey = 'sub_image_' . str_replace('-', '_', $subId);
                            if (!empty($_FILES[$fileKey]['name']) && $_FILES[$fileKey]['error'] === 0) {
                                $ext = strtolower(pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION));
                                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                                    $subImgName = 'subcat_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
                                    move_uploaded_file($_FILES[$fileKey]['tmp_name'], UPLOAD_PATH . $subImgName);
                                }
                            }
                            if (!empty($subName)) {
                                $stmtMap->execute([$catId, $subId, $subName, $subImgName ?: null, $order++]);
                            }
                        }
                    }

                    $msg = 'Category updated successfully!';
                    $msgType = 'success';
                    $action = 'list';
                }
            }
        }
    }

    if ($postAction === 'delete') {
        $catId = intval($_POST['cat_id'] ?? 0);
        $old = $db->prepare("SELECT image FROM parent_categories WHERE id = ?");
        $old->execute([$catId]);
        $oldImg = $old->fetchColumn();
        if ($oldImg && file_exists(UPLOAD_PATH . $oldImg)) {
            unlink(UPLOAD_PATH . $oldImg);
        }
        $oldSubs = $db->prepare("SELECT image FROM category_mapping WHERE parent_category_id = ?");
        $oldSubs->execute([$catId]);
        while ($os = $oldSubs->fetch()) {
            if ($os['image'] && file_exists(UPLOAD_PATH . $os['image'])) {
                unlink(UPLOAD_PATH . $os['image']);
            }
        }
        $db->prepare("DELETE FROM parent_categories WHERE id = ?")->execute([$catId]);
        $msg = 'Category deleted.';
        $msgType = 'success';
        $action = 'list';
    }
}

$editData = null;
$editMappings = [];
if ($action === 'edit') {
    $editId = intval($_GET['id'] ?? 0);
    $stmt = $db->prepare("SELECT * FROM parent_categories WHERE id = ?");
    $stmt->execute([$editId]);
    $editData = $stmt->fetch();
    if ($editData) {
        $stmt = $db->prepare("SELECT api_category_id FROM category_mapping WHERE parent_category_id = ?");
        $stmt->execute([$editId]);
        $editMappings = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } else {
        $action = 'list';
    }
}

$usedParentIds = [];
$rows = $db->query("SELECT api_category_id FROM parent_categories")->fetchAll(PDO::FETCH_COLUMN);
$usedParentIds = $rows ?: [];
?>

<?php if ($msg): ?>
<div class="alert alert-<?php echo $msgType; ?>"><i class="fas fa-<?php echo $msgType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i> <?php echo htmlspecialchars($msg); ?></div>
<?php endif; ?>

<?php if ($action === 'list'): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i>
        <span><strong>How it works:</strong> Select an API category as <strong>Parent</strong>, assign <strong>Subcategories</strong> with optional images, and tick <strong>"Show in Menu"</strong> to display it in the main navigation.</span>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>Parent Categories</h2>
            <a href="?action=add" class="btn btn-primary"><i class="fas fa-plus"></i> Add Category</a>
        </div>
        <div class="card-body">
            <?php
            $cats = $db->query("SELECT pc.*, COUNT(cm.id) as sub_count FROM parent_categories pc LEFT JOIN category_mapping cm ON pc.id = cm.parent_category_id GROUP BY pc.id ORDER BY pc.sort_order ASC")->fetchAll();
            if (empty($cats)):
            ?>
            <p style="text-align:center;padding:30px;color:var(--admin-text-light);">No parent categories yet.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Parent Category</th>
                            <th>Subcategories</th>
                            <th>Menu</th>
                            <th>Cart</th>
                            <th>Markup</th>
                            <th>Featured</th>
                            <th>Order</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($cats as $c):
                        $subNames = $db->prepare("SELECT api_category_name FROM category_mapping WHERE parent_category_id = ? ORDER BY sort_order ASC");
                        $subNames->execute([$c['id']]);
                        $subList = $subNames->fetchAll(PDO::FETCH_COLUMN);
                    ?>
                        <tr>
                            <td>
                                <strong style="font-size:15px;"><?php echo htmlspecialchars($c['name'] ?: $c['api_category_name']); ?></strong>
                                <?php if (strpos($c['api_category_id'], 'custom-') === 0): ?>
                                <span class="badge badge-warning" style="margin-left:6px;">Custom</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($subList)): ?>
                                    <div class="mapping-list">
                                    <?php foreach ($subList as $sn): ?>
                                        <span class="mapping-tag"><?php echo htmlspecialchars($sn); ?></span>
                                    <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span style="color:var(--admin-text-light);font-size:13px;">None</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($c['show_in_menu'])): ?>
                                <span class="badge badge-success"><i class="fas fa-check"></i> Yes</span>
                                <?php else: ?>
                                <span class="badge badge-warning">No</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($c['allow_cart'])): ?>
                                <span class="badge badge-success">On</span>
                                <?php else: ?>
                                <span class="badge badge-danger">Off</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (($c['price_markup'] ?? 0) > 0): ?>
                                <span class="badge badge-warning"><?php echo $c['price_markup_type'] === 'percentage' ? $c['price_markup'] . '%' : '$' . number_format($c['price_markup'], 2); ?></span>
                                <?php else: ?>
                                <span style="color:var(--admin-text-light);">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($c['is_featured'])): ?>
                                <span class="badge badge-success"><i class="fas fa-star"></i></span>
                                <?php else: ?>
                                <span style="color:var(--admin-text-light);">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $c['sort_order']; ?></td>
                            <td><span class="badge badge-<?php echo $c['is_active'] ? 'success' : 'danger'; ?>"><?php echo $c['is_active'] ? 'Active' : 'Inactive'; ?></span></td>
                            <td>
                                <div class="actions">
                                    <a href="?action=edit&id=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline"><i class="fas fa-edit"></i></a>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="form_action" value="delete">
                                        <input type="hidden" name="cat_id" value="<?php echo $c['id']; ?>">
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
            <h2><?php echo $action === 'edit' ? 'Edit Category' : 'Add Parent Category'; ?></h2>
            <a href="?action=list" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="form_action" value="<?php echo $action; ?>">
                <?php if ($editData): ?>
                <input type="hidden" name="cat_id" value="<?php echo $editData['id']; ?>">
                <?php endif; ?>

                <!-- STEP 1: Select Parent Category -->
                <div style="background:#fef2f2;border:2px solid #fca5a5;border-radius:10px;padding:25px;margin-bottom:25px;">
                    <h3 style="color:var(--admin-primary);margin-bottom:5px;">
                        <i class="fas fa-folder" style="margin-right:8px;"></i>Step 1: Create Parent Category
                    </h3>
                    <p style="color:var(--admin-text-light);font-size:13px;margin-bottom:15px;">Choose an API category, or create your own parent category name.</p>

                    <?php $isCustomParent = $editData && strpos($editData['api_category_id'], 'custom-') === 0; ?>
                    <div style="display:flex;gap:20px;flex-wrap:wrap;margin-bottom:15px;">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                            <input type="radio" name="parent_source" value="api" <?php echo !$isCustomParent ? 'checked' : ''; ?> onchange="toggleParentSource()" style="accent-color:var(--admin-primary);width:18px;height:18px;">
                            Select from API categories
                        </label>
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                            <input type="radio" name="parent_source" value="custom" <?php echo $isCustomParent ? 'checked' : ''; ?> onchange="toggleParentSource()" style="accent-color:var(--admin-primary);width:18px;height:18px;">
                            Create my own parent category
                        </label>
                    </div>

                    <?php if ($isCustomParent): ?>
                    <input type="hidden" name="parent_api_id" value="<?php echo htmlspecialchars($editData['api_category_id']); ?>">
                    <?php endif; ?>

                    <div class="form-group" id="customParentBox" style="margin-bottom:18px;<?php echo $isCustomParent ? '' : 'display:none;'; ?>">
                        <label>Parent Category Name</label>
                        <input type="text" name="custom_parent_name" class="form-control" value="<?php echo $isCustomParent ? htmlspecialchars($editData['name']) : ''; ?>" placeholder="Example: Best sale of week" style="max-width:420px;">
                    </div>

                    <div class="form-group" id="apiParentBox" style="margin-bottom:0;<?php echo $isCustomParent ? 'display:none;' : ''; ?>">
                        <div style="margin-bottom:10px;">
                            <input type="text" id="searchParent" class="form-control" placeholder="Type to search..." style="max-width:400px;" oninput="filterParent(this.value)">
                        </div>
                        <div id="parentList" style="max-height:250px;overflow-y:auto;border:1px solid var(--admin-border);border-radius:8px;background:#fff;">
                            <?php foreach ($apiCategories as $ac):
                                $isCurrentEdit = ($editData && $editData['api_category_id'] === $ac['id']);
                                $isUsed = in_array($ac['id'], $usedParentIds) && !$isCurrentEdit;
                            ?>
                            <label class="parent-radio-item" style="display:flex;align-items:center;gap:10px;padding:10px 15px;border-bottom:1px solid #f0f0f0;cursor:pointer;transition:all 0.2s;<?php echo $isUsed ? 'opacity:0.4;' : ''; ?>" data-name="<?php echo strtolower($ac['name']); ?>">
                                <input type="radio" name="parent_api_id" value="<?php echo htmlspecialchars($ac['id']); ?>"
                                    <?php echo $isCurrentEdit ? 'checked' : ''; ?>
                                    <?php echo $isUsed ? 'disabled' : ''; ?>
                                    style="accent-color:var(--admin-primary);width:18px;height:18px;"
                                    onchange="onParentSelected(this)">
                                <span style="font-size:14px;font-weight:500;"><?php echo htmlspecialchars($ac['name']); ?></span>
                                <?php if ($isUsed): ?>
                                <small style="color:var(--admin-danger);margin-left:auto;">(Already a parent)</small>
                                <?php endif; ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- STEP 2: Select Subcategories with Image Upload -->
                <div style="background:#f0f4f7;border:2px solid #bee5eb;border-radius:10px;padding:25px;margin-bottom:25px;">
                    <h3 style="color:var(--admin-info);margin-bottom:5px;">
                        <i class="fas fa-sitemap" style="margin-right:8px;"></i>Step 2: Select Subcategories
                    </h3>
                    <p style="color:var(--admin-text-light);font-size:13px;margin-bottom:15px;">Check subcategories and optionally upload an image for each.</p>

                    <div style="margin-bottom:10px;">
                        <input type="text" id="searchSubs" class="form-control" placeholder="Search subcategories..." style="max-width:400px;" oninput="filterSubs(this.value)">
                    </div>

                    <div id="subCatGrid" style="max-height:400px;overflow-y:auto;border:1px solid var(--admin-border);border-radius:8px;background:#fff;">
                        <?php foreach ($apiCategories as $ac):
                            $isChecked = in_array($ac['id'], $editMappings);
                            $fileKey = 'sub_image_' . str_replace('-', '_', $ac['id']);
                        ?>
                        <div class="sub-cat-row" data-id="<?php echo htmlspecialchars($ac['id']); ?>" data-name="<?php echo strtolower($ac['name']); ?>" style="display:flex;align-items:center;gap:12px;padding:10px 15px;border-bottom:1px solid #f5f5f5;">
                            <input type="checkbox" name="sub_categories[]" value="<?php echo htmlspecialchars($ac['id']); ?>"
                                <?php echo $isChecked ? 'checked' : ''; ?>
                                style="accent-color:var(--admin-primary);width:18px;height:18px;flex-shrink:0;"
                                onchange="toggleSubImage(this)">
                            <span style="font-size:13px;font-weight:500;min-width:150px;"><?php echo htmlspecialchars($ac['name']); ?></span>
                            <input type="file" name="<?php echo $fileKey; ?>" accept="image/*" style="font-size:12px;max-width:220px;<?php echo $isChecked ? '' : 'display:none;'; ?>" class="sub-img-input">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="form-hint" style="margin-top:8px;"><?php echo count($apiCategories); ?> categories available</p>
                </div>

                <!-- Options -->
                <div class="card" style="margin-bottom:25px;">
                    <div class="card-header"><h2 style="font-size:16px;">Options</h2></div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($editData['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Category Image (Parent)</label>
                            <input type="file" name="image" class="form-control form-control-file" accept="image/*">
                            <div class="img-preview">
                                <?php if (!empty($editData['image'])): ?>
                                <img src="<?php echo UPLOAD_URL . htmlspecialchars($editData['image']); ?>" alt="">
                                <?php endif; ?>
                            </div>
                        </div>

                        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;">
                            <div class="form-group">
                                <label>Sort Order</label>
                                <input type="number" name="sort_order" class="form-control" value="<?php echo $editData['sort_order'] ?? 0; ?>">
                            </div>
                            <div class="form-group">
                                <label>Price Markup Amount</label>
                                <input type="number" name="price_markup" class="form-control" step="0.01" min="0" value="<?php echo $editData['price_markup'] ?? 0; ?>" placeholder="0.00">
                                <p class="form-hint">Extra amount added to all products in this category</p>
                            </div>
                            <div class="form-group">
                                <label>Markup Type</label>
                                <select name="price_markup_type" class="form-control">
                                    <option value="fixed" <?php echo ($editData['price_markup_type'] ?? 'fixed') === 'fixed' ? 'selected' : ''; ?>>Fixed ($)</option>
                                    <option value="percentage" <?php echo ($editData['price_markup_type'] ?? '') === 'percentage' ? 'selected' : ''; ?>>Percentage (%)</option>
                                </select>
                            </div>
                        </div>
                        <div style="display:flex;gap:30px;flex-wrap:wrap;margin-top:10px;">
                            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                                <input type="checkbox" name="show_in_menu" value="1" <?php echo ($editData['show_in_menu'] ?? 0) ? 'checked' : ''; ?> style="accent-color:var(--admin-primary);width:18px;height:18px;">
                                <strong>Show in Menu</strong>
                            </label>
                            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                                <input type="checkbox" name="allow_cart" value="1" <?php echo ($editData['allow_cart'] ?? 1) ? 'checked' : ''; ?> style="accent-color:#28a745;width:18px;height:18px;">
                                <strong>Allow Add to Cart</strong>
                            </label>
                            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                                <input type="checkbox" name="is_featured" value="1" <?php echo ($editData['is_featured'] ?? 0) ? 'checked' : ''; ?> style="accent-color:#D4A843;width:18px;height:18px;">
                                <strong><i class="fas fa-star" style="color:#D4A843;"></i> Featured on Homepage</strong>
                            </label>
                            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                                <input type="checkbox" name="is_active" value="1" <?php echo ($editData['is_active'] ?? 1) ? 'checked' : ''; ?> style="accent-color:var(--admin-primary);width:18px;height:18px;">
                                Active
                            </label>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save"></i> Save Category</button>
            </form>
        </div>
    </div>

    <script>
    function filterParent(query) {
        var items = document.querySelectorAll('.parent-radio-item');
        query = query.toLowerCase();
        items.forEach(function(item) {
            item.style.display = item.getAttribute('data-name').includes(query) ? '' : 'none';
        });
    }

    function filterSubs(query) {
        var rows = document.querySelectorAll('.sub-cat-row');
        query = query.toLowerCase();
        rows.forEach(function(row) {
            row.style.display = row.getAttribute('data-name').includes(query) ? '' : 'none';
        });
    }

    function onParentSelected(radio) {
        var parentId = radio.value;
        document.querySelectorAll('.sub-cat-row').forEach(function(row) {
            var subId = row.getAttribute('data-id');
            var checkbox = row.querySelector('input[type="checkbox"]');
            if (subId === parentId) {
                checkbox.checked = false;
                checkbox.disabled = true;
                row.style.opacity = '0.4';
            } else {
                checkbox.disabled = false;
                row.style.opacity = '1';
            }
        });
    }

    function toggleSubImage(checkbox) {
        var fileInput = checkbox.closest('.sub-cat-row').querySelector('.sub-img-input');
        fileInput.style.display = checkbox.checked ? '' : 'none';
    }

    function toggleParentSource() {
        var selected = document.querySelector('input[name="parent_source"]:checked');
        var isCustom = selected && selected.value === 'custom';
        var customBox = document.getElementById('customParentBox');
        var apiBox = document.getElementById('apiParentBox');
        if (customBox) customBox.style.display = isCustom ? '' : 'none';
        if (apiBox) apiBox.style.display = isCustom ? 'none' : '';
        document.querySelectorAll('#apiParentBox input[name="parent_api_id"]').forEach(function(input) {
            input.disabled = isCustom;
        });
    }

    var checkedRadio = document.querySelector('input[name="parent_api_id"]:checked');
    if (checkedRadio) onParentSelected(checkedRadio);
    toggleParentSource();
    </script>
<?php endif; ?>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
