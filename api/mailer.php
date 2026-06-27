<?php
require_once __DIR__ . '/../phpmailer/autoload.php';
require_once __DIR__ . '/../config/database.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function getMailer() {
    $db = getDB();
    $stmt = $db->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('smtp_host','smtp_port','smtp_username','smtp_password','smtp_from_email','smtp_from_name','admin_email')");
    $s = [];
    while ($row = $stmt->fetch()) { $s[$row['setting_key']] = $row['setting_value']; }

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $s['smtp_host'] ?? 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = $s['smtp_username'] ?? '';
    $mail->Password = $s['smtp_password'] ?? '';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = intval($s['smtp_port'] ?? 587);
    $mail->setFrom($s['smtp_from_email'] ?? $s['smtp_username'] ?? '', $s['smtp_from_name'] ?? 'Elephant House');
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';

    return ['mailer' => $mail, 'settings' => $s];
}

function emailTemplate($title, $bodyContent) {
    return '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,Helvetica,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:30px 0;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,0.08);">
<!-- Header -->
<tr><td style="background:#b52d31;padding:25px 30px;text-align:center;">
<h1 style="color:#ffffff;margin:0;font-size:24px;font-weight:700;">Elephant House</h1>
<p style="color:rgba(255,255,255,0.8);margin:5px 0 0;font-size:13px;">Premium Sri Lankan & Asian Grocery Store</p>
</td></tr>
<!-- Title -->
<tr><td style="padding:30px 30px 15px;text-align:center;">
<h2 style="color:#b52d31;margin:0;font-size:22px;">' . $title . '</h2>
</td></tr>
<!-- Body -->
<tr><td style="padding:10px 30px 30px;font-size:14px;line-height:1.8;color:#444444;">
' . $bodyContent . '
</td></tr>
<!-- Footer -->
<tr><td style="background:#f8f8f8;padding:20px 30px;text-align:center;font-size:12px;color:#999;">
<p style="margin:0;">Thank you for shopping with Elephant House!</p>
<p style="margin:5px 0 0;"><a href="' . SITE_URL . '" style="color:#b52d31;text-decoration:none;">Visit our store</a></p>
</td></tr>
</table>
</td></tr>
</table>
</body></html>';
}

function sendOrderConfirmationEmail($orderId) {
    try {
        $db = getDB();
        $order = $db->prepare("SELECT * FROM orders WHERE id = ?");
        $order->execute([$orderId]);
        $order = $order->fetch();
        if (!$order) return;

        $items = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $items->execute([$orderId]);
        $items = $items->fetchAll();

        $itemsHtml = '<table width="100%" cellpadding="8" cellspacing="0" style="border-collapse:collapse;margin:15px 0;">
        <tr style="background:#f8f8f8;"><th style="text-align:left;font-size:13px;border-bottom:2px solid #eee;">Product</th><th style="text-align:center;font-size:13px;border-bottom:2px solid #eee;">Qty</th><th style="text-align:right;font-size:13px;border-bottom:2px solid #eee;">Price</th></tr>';
        foreach ($items as $item) {
            $itemsHtml .= '<tr><td style="border-bottom:1px solid #f0f0f0;font-size:13px;">' . htmlspecialchars($item['product_name']) . '</td>';
            $itemsHtml .= '<td style="text-align:center;border-bottom:1px solid #f0f0f0;font-size:13px;">' . $item['quantity'] . '</td>';
            $itemsHtml .= '<td style="text-align:right;border-bottom:1px solid #f0f0f0;font-size:13px;">$' . number_format($item['price'] * $item['quantity'], 2) . '</td></tr>';
        }
        $itemsHtml .= '</table>';

        $shippingLabel = ($order['shipping_method'] ?? 'delivery') === 'pickup' ? 'Pickup from Store' : 'Delivery';
        $shippingCost = floatval($order['shipping_cost'] ?? 0);
        $paymentLabel = ($order['payment_method'] ?? 'cod') === 'paypal' ? 'PayPal' : 'Pay on Delivery';

        $body = '<p>Hi <strong>' . htmlspecialchars($order['name']) . '</strong>,</p>
        <p>Your order has been placed successfully! Here are the details:</p>
        <div style="background:#fef2f2;border-left:4px solid #b52d31;padding:15px 20px;border-radius:6px;margin:15px 0;">
            <p style="margin:0;font-size:16px;font-weight:700;color:#b52d31;">Order #' . htmlspecialchars($order['order_number']) . '</p>
            <p style="margin:5px 0 0;font-size:13px;color:#666;">Placed on ' . date('d M Y, h:i A', strtotime($order['created_at'])) . '</p>
        </div>
        ' . $itemsHtml . '
        <table width="100%" style="margin:10px 0;">
            <tr><td style="font-size:14px;padding:4px 0;">Subtotal</td><td style="text-align:right;font-size:14px;">$' . number_format($order['subtotal'], 2) . '</td></tr>';
        if ($shippingCost > 0) {
            $body .= '<tr><td style="font-size:14px;padding:4px 0;">Shipping (' . $shippingLabel . ')</td><td style="text-align:right;font-size:14px;">$' . number_format($shippingCost, 2) . '</td></tr>';
        }
        if ($order['discount'] > 0) {
            $body .= '<tr><td style="font-size:14px;padding:4px 0;color:#28a745;">Discount</td><td style="text-align:right;font-size:14px;color:#28a745;">-$' . number_format($order['discount'], 2) . '</td></tr>';
        }
        $body .= '<tr><td style="font-size:18px;font-weight:700;padding:10px 0;border-top:2px solid #eee;color:#b52d31;">Total</td><td style="text-align:right;font-size:18px;font-weight:700;padding:10px 0;border-top:2px solid #eee;color:#b52d31;">$' . number_format($order['total'], 2) . '</td></tr>
        </table>
        <div style="background:#f8f8f8;padding:15px 20px;border-radius:8px;margin:15px 0;">
            <p style="margin:0 0 5px;font-size:13px;"><strong>Shipping:</strong> ' . $shippingLabel . '</p>
            <p style="margin:0 0 5px;font-size:13px;"><strong>Payment:</strong> ' . $paymentLabel . '</p>
            <p style="margin:0;font-size:13px;"><strong>Address:</strong> ' . htmlspecialchars($order['address'] . ', ' . $order['city'] . ', ' . $order['state'] . ' ' . $order['postcode']) . '</p>
        </div>';

        $m = getMailer();
        $mail = $m['mailer'];
        $mail->addAddress($order['email'], $order['name']);
        $mail->Subject = 'Order Confirmation - #' . $order['order_number'];
        $mail->Body = emailTemplate('Order Confirmed!', $body);
        $mail->send();

        // Send to admin
        $adminEmail = $m['settings']['admin_email'] ?? '';
        if (!empty($adminEmail)) {
            $mail2 = getMailer()['mailer'];
            $mail2->addAddress($adminEmail);
            $mail2->Subject = 'New Order - #' . $order['order_number'] . ' - $' . number_format($order['total'], 2);
            $adminBody = '<p>A new order has been placed.</p>' . $body . '<p style="text-align:center;margin-top:20px;"><a href="' . SITE_URL . '/admin/orders.php?action=view&id=' . $orderId . '" style="background:#b52d31;color:#fff;padding:12px 30px;border-radius:8px;text-decoration:none;font-weight:600;display:inline-block;">View Order</a></p>';
            $mail2->Body = emailTemplate('New Order Received', $adminBody);
            $mail2->send();
        }
    } catch (Exception $e) {
        error_log('Order email failed: ' . $e->getMessage());
    }
}

function sendOrderStatusEmail($orderId) {
    try {
        $db = getDB();
        $order = $db->prepare("SELECT * FROM orders WHERE id = ?");
        $order->execute([$orderId]);
        $order = $order->fetch();
        if (!$order) return;

        $statusColors = [
            'pending' => '#ffc107', 'confirmed' => '#17a2b8', 'processing' => '#007bff',
            'shipped' => '#28a745', 'delivered' => '#28a745', 'cancelled' => '#dc3545'
        ];
        $statusMessages = [
            'pending' => 'Your order is pending confirmation.',
            'confirmed' => 'Your order has been confirmed and will be processed shortly.',
            'processing' => 'Your order is being prepared.',
            'shipped' => 'Your order has been shipped! It\'s on its way to you.',
            'delivered' => 'Your order has been delivered. Enjoy your products!',
            'cancelled' => 'Your order has been cancelled. If you have questions, please contact us.'
        ];

        $status = $order['status'];
        $color = $statusColors[$status] ?? '#666';
        $statusMsg = $statusMessages[$status] ?? '';

        $body = '<p>Hi <strong>' . htmlspecialchars($order['name']) . '</strong>,</p>
        <p>Your order status has been updated:</p>
        <div style="text-align:center;margin:25px 0;">
            <div style="display:inline-block;background:' . $color . ';color:#fff;padding:12px 30px;border-radius:50px;font-size:16px;font-weight:700;text-transform:uppercase;letter-spacing:1px;">' . ucfirst($status) . '</div>
        </div>
        <div style="background:#f8f8f8;padding:20px;border-radius:8px;text-align:center;">
            <p style="margin:0;font-size:15px;color:#555;">' . $statusMsg . '</p>
        </div>
        <div style="margin:20px 0;padding:15px 20px;border-left:4px solid #b52d31;background:#fef2f2;border-radius:6px;">
            <p style="margin:0;font-size:14px;"><strong>Order:</strong> #' . htmlspecialchars($order['order_number']) . '</p>
            <p style="margin:5px 0 0;font-size:14px;"><strong>Total:</strong> $' . number_format($order['total'], 2) . '</p>
        </div>';

        $m = getMailer();
        $mail = $m['mailer'];
        $mail->addAddress($order['email'], $order['name']);
        $mail->Subject = 'Order Update - #' . $order['order_number'] . ' - ' . ucfirst($status);
        $mail->Body = emailTemplate('Order Status Updated', $body);
        $mail->send();
    } catch (Exception $e) {
        error_log('Status email failed: ' . $e->getMessage());
    }
}

function sendPasswordResetEmail($email, $newPassword) {
    try {
        $db = getDB();
        $customer = $db->prepare("SELECT name FROM customers WHERE email = ?");
        $customer->execute([$email]);
        $customer = $customer->fetch();
        if (!$customer) return false;

        $body = '<p>Hi <strong>' . htmlspecialchars($customer['name']) . '</strong>,</p>
        <p>You requested a password reset. Here is your new temporary password:</p>
        <div style="text-align:center;margin:25px 0;">
            <div style="display:inline-block;background:#f8f8f8;border:2px dashed #b52d31;padding:18px 40px;border-radius:10px;">
                <span style="font-size:24px;font-weight:700;color:#b52d31;letter-spacing:3px;">' . htmlspecialchars($newPassword) . '</span>
            </div>
        </div>
        <p style="text-align:center;font-size:14px;color:#666;">Please login with this password and change it from your account settings.</p>
        <p style="text-align:center;margin-top:20px;">
            <a href="' . SITE_URL . '/login.php" style="background:#b52d31;color:#fff;padding:12px 35px;border-radius:50px;text-decoration:none;font-weight:600;display:inline-block;">Login Now</a>
        </p>
        <p style="font-size:12px;color:#999;margin-top:20px;">If you did not request this, please ignore this email or contact support.</p>';

        $m = getMailer();
        $mail = $m['mailer'];
        $mail->addAddress($email, $customer['name']);
        $mail->Subject = 'Password Reset - Elephant House';
        $mail->Body = emailTemplate('Password Reset', $body);
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Password reset email failed: ' . $e->getMessage());
        return false;
    }
}
