<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/config/database.php';

$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $msg = 'Please enter your email address.';
        $msgType = 'danger';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT id FROM customers WHERE email = ?");
        $stmt->execute([$email]);
        $customer = $stmt->fetch();

        if ($customer) {
            $chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';
            $newPassword = '';
            for ($i = 0; $i < 10; $i++) {
                $newPassword .= $chars[random_int(0, strlen($chars) - 1)];
            }

            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $db->prepare("UPDATE customers SET password = ? WHERE id = ?")->execute([$hash, $customer['id']]);

            require_once __DIR__ . '/api/mailer.php';
            $sent = sendPasswordResetEmail($email, $newPassword);

            if ($sent) {
                $msg = 'A new password has been sent to your email.';
                $msgType = 'success';
            } else {
                $msg = 'Could not send email. Please try again later.';
                $msgType = 'danger';
            }
        } else {
            $msg = 'A new password has been sent to your email.';
            $msgType = 'success';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <h1>Forgot Password</h1>
        <div class="breadcrumb"><a href="<?php echo SITE_URL; ?>">Home</a> / Forgot Password</div>
    </div>
</div>

<section class="section">
    <div class="container">
        <div class="auth-box">
            <h2 style="font-family:'Playfair Display',serif;color:var(--primary);text-align:center;margin-bottom:5px;">Reset Password</h2>
            <p style="text-align:center;color:var(--text-muted);margin-bottom:30px;font-size:14px;">Enter your email and we'll send you a new password</p>

            <?php if ($msg): ?>
            <div style="background:<?php echo $msgType === 'success' ? '#d4edda' : '#f8d7da'; ?>;color:<?php echo $msgType === 'success' ? '#155724' : '#721c24'; ?>;padding:12px 16px;border-radius:8px;margin-bottom:20px;font-size:14px;">
                <i class="fas fa-<?php echo $msgType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i> <?php echo htmlspecialchars($msg); ?>
            </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label style="font-size:13px;font-weight:600;display:block;margin-bottom:5px;">Email Address</label>
                    <input type="email" name="email" class="checkout-input" required placeholder="your@email.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                <button type="submit" class="btn-add-cart" style="width:100%;justify-content:center;margin-top:10px;">
                    <i class="fas fa-paper-plane"></i> Send New Password
                </button>
            </form>

            <p style="text-align:center;margin-top:25px;font-size:14px;color:var(--text-light);">
                Remember your password? <a href="<?php echo SITE_URL; ?>/login.php" style="color:var(--primary);font-weight:600;">Login</a>
            </p>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
