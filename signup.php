<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/config/database.php';

if (!empty($_SESSION['customer_id'])) {
    header('Location: ' . SITE_URL . '/account.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Name, email and password are required.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $db = getDB();
        $check = $db->prepare("SELECT id FROM customers WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $error = 'An account with this email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO customers (name, email, phone, password) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $phone, $hash]);

            $_SESSION['customer_id'] = $db->lastInsertId();
            $_SESSION['customer_name'] = $name;
            $_SESSION['customer_email'] = $email;

            header('Location: ' . SITE_URL . '/account.php');
            exit;
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <h1>Create Account</h1>
        <div class="breadcrumb"><a href="<?php echo SITE_URL; ?>">Home</a> / Sign Up</div>
    </div>
</div>

<section class="section">
    <div class="container">
        <div class="auth-box">
            <h2 style="font-family:'Playfair Display',serif;color:var(--primary);text-align:center;margin-bottom:5px;">Create Account</h2>
            <p style="text-align:center;color:var(--text-muted);margin-bottom:30px;font-size:14px;">Join us for a better shopping experience</p>

            <?php if ($error): ?>
            <div style="background:#f8d7da;color:#721c24;padding:12px 16px;border-radius:8px;margin-bottom:20px;font-size:14px;">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label style="font-size:13px;font-weight:600;display:block;margin-bottom:5px;">Full Name *</label>
                    <input type="text" name="name" class="checkout-input" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label style="font-size:13px;font-weight:600;display:block;margin-bottom:5px;">Email Address *</label>
                    <input type="email" name="email" class="checkout-input" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label style="font-size:13px;font-weight:600;display:block;margin-bottom:5px;">Phone</label>
                    <input type="text" name="phone" class="checkout-input" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
                    <div class="form-group">
                        <label style="font-size:13px;font-weight:600;display:block;margin-bottom:5px;">Password *</label>
                        <input type="password" name="password" class="checkout-input" required placeholder="Min 6 characters">
                    </div>
                    <div class="form-group">
                        <label style="font-size:13px;font-weight:600;display:block;margin-bottom:5px;">Confirm Password *</label>
                        <input type="password" name="confirm_password" class="checkout-input" required>
                    </div>
                </div>
                <button type="submit" class="btn-add-cart" style="width:100%;justify-content:center;margin-top:10px;">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>

            <p style="text-align:center;margin-top:25px;font-size:14px;color:var(--text-light);">
                Already have an account? <a href="<?php echo SITE_URL; ?>/login.php" style="color:var(--primary);font-weight:600;">Login</a>
            </p>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
