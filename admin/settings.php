<?php
$pageTitle = 'Site Settings';
require_once __DIR__ . '/layout.php';

$db = getDB();
$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'site_name', 'site_tagline', 'phone', 'email', 'address',
        'facebook', 'instagram', 'opening_hours', 'footer_text', 'google_maps'
    ];

    $stmt = $db->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");

    foreach ($fields as $field) {
        $value = trim($_POST[$field] ?? '');
        $stmt->execute([$field, $value]);
    }

    // Handle logo upload
    if (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] === 0) {
        $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])) {
            $oldLogo = getSetting('logo');
            if ($oldLogo && file_exists(UPLOAD_PATH . $oldLogo)) {
                unlink(UPLOAD_PATH . $oldLogo);
            }
            $logoName = 'logo_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['logo']['tmp_name'], UPLOAD_PATH . $logoName);
            $stmt->execute(['logo', $logoName]);
        }
    }

    // Handle favicon upload
    if (!empty($_FILES['favicon']['name']) && $_FILES['favicon']['error'] === 0) {
        $ext = strtolower(pathinfo($_FILES['favicon']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['ico', 'png', 'jpg', 'svg'])) {
            $oldFav = getSetting('favicon');
            if ($oldFav && file_exists(UPLOAD_PATH . $oldFav)) {
                unlink(UPLOAD_PATH . $oldFav);
            }
            $favName = 'favicon_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['favicon']['tmp_name'], UPLOAD_PATH . $favName);
            $stmt->execute(['favicon', $favName]);
        }
    }

    $msg = 'Settings saved successfully!';
    $msgType = 'success';
}

$s = getAllSettings();
?>

<?php if ($msg): ?>
<div class="alert alert-<?php echo $msgType; ?>"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($msg); ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">

    <div class="card">
        <div class="card-header"><h2><i class="fas fa-store"></i> General</h2></div>
        <div class="card-body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                <div class="form-group">
                    <label>Site Name</label>
                    <input type="text" name="site_name" class="form-control" value="<?php echo htmlspecialchars($s['site_name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Tagline</label>
                    <input type="text" name="site_tagline" class="form-control" value="<?php echo htmlspecialchars($s['site_tagline'] ?? ''); ?>">
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                <div class="form-group">
                    <label>Logo</label>
                    <input type="file" name="logo" class="form-control form-control-file" accept="image/*">
                    <div class="img-preview">
                        <?php if (!empty($s['logo'])): ?>
                        <img src="<?php echo UPLOAD_URL . htmlspecialchars($s['logo']); ?>" alt="Logo">
                        <?php endif; ?>
                    </div>
                </div>
                <div class="form-group">
                    <label>Favicon</label>
                    <input type="file" name="favicon" class="form-control form-control-file" accept="image/*,.ico">
                    <div class="img-preview">
                        <?php if (!empty($s['favicon'])): ?>
                        <img src="<?php echo UPLOAD_URL . htmlspecialchars($s['favicon']); ?>" alt="Favicon" style="max-width:64px;">
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h2><i class="fas fa-phone"></i> Contact Details</h2></div>
        <div class="card-body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($s['phone'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($s['email'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Address</label>
                <input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars($s['address'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Opening Hours</label>
                <input type="text" name="opening_hours" class="form-control" value="<?php echo htmlspecialchars($s['opening_hours'] ?? ''); ?>" placeholder="e.g. Mon-Sat: 9AM - 8PM | Sun: 10AM - 6PM">
            </div>
            <div class="form-group">
                <label>Google Maps Embed URL</label>
                <input type="url" name="google_maps" class="form-control" value="<?php echo htmlspecialchars($s['google_maps'] ?? ''); ?>" placeholder="https://www.google.com/maps/embed?...">
                <p class="form-hint">Go to Google Maps → Share → Embed → Copy the src URL from the iframe</p>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h2><i class="fas fa-share-alt"></i> Social Media</h2></div>
        <div class="card-body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                <div class="form-group">
                    <label><i class="fab fa-facebook" style="color:#1877f2;"></i> Facebook URL</label>
                    <input type="url" name="facebook" class="form-control" value="<?php echo htmlspecialchars($s['facebook'] ?? ''); ?>" placeholder="https://facebook.com/...">
                </div>
                <div class="form-group">
                    <label><i class="fab fa-instagram" style="color:#e4405f;"></i> Instagram URL</label>
                    <input type="url" name="instagram" class="form-control" value="<?php echo htmlspecialchars($s['instagram'] ?? ''); ?>" placeholder="https://instagram.com/...">
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h2><i class="fas fa-cog"></i> Footer</h2></div>
        <div class="card-body">
            <div class="form-group">
                <label>Footer Copyright Text</label>
                <input type="text" name="footer_text" class="form-control" value="<?php echo htmlspecialchars($s['footer_text'] ?? ''); ?>">
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save"></i> Save All Settings</button>
</form>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
