<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/config/database.php';

if (!empty($_SESSION['customer_id'])) {
    header('Location: ' . SITE_URL . '/account.php');
    exit;
}

$error = '';
$redirect = $_GET['redirect'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $redirect = $_POST['redirect'] ?? $redirect;

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM customers WHERE email = ?");
        $stmt->execute([$email]);
        $customer = $stmt->fetch();

        if ($customer && password_verify($password, $customer['password'])) {
            $_SESSION['customer_id'] = $customer['id'];
            $_SESSION['customer_name'] = $customer['name'];
            $_SESSION['customer_email'] = $customer['email'];
            $redir = !empty($redirect) ? SITE_URL . '/' . $redirect . '.php' : SITE_URL . '/account.php';
            header('Location: ' . $redir);
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <h1>Login</h1>
        <div class="breadcrumb"><a href="<?php echo SITE_URL; ?>">Home</a> / Login</div>
    </div>
</div>

<section class="section">
    <div class="container">
        <div class="auth-box">
            <h2 style="font-family:'Playfair Display',serif;color:var(--primary);text-align:center;margin-bottom:5px;">Welcome Back</h2>
            <p style="text-align:center;color:var(--text-muted);margin-bottom:30px;font-size:14px;">Login to your account</p>

            <?php if ($error): ?>
            <div style="background:#f8d7da;color:#721c24;padding:12px 16px;border-radius:8px;margin-bottom:20px;font-size:14px;">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">
                <div class="form-group">
                    <label style="font-size:13px;font-weight:600;display:block;margin-bottom:5px;">Email Address</label>
                    <input type="email" name="email" class="checkout-input" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" placeholder="your@email.com">
                </div>
                <div class="form-group">
                    <label style="font-size:13px;font-weight:600;display:block;margin-bottom:5px;">Password</label>
                    <input type="password" name="password" class="checkout-input" required placeholder="Enter your password">
                </div>
                <button type="submit" class="btn-add-cart" style="width:100%;justify-content:center;margin-top:10px;">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>

            <p style="text-align:center;margin-top:25px;font-size:14px;color:var(--text-light);">
                Don't have an account? <a href="<?php echo SITE_URL; ?>/signup.php" style="color:var(--primary);font-weight:600;">Create one</a>
            </p>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
