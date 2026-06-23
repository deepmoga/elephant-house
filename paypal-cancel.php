<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/config/database.php';

unset($_SESSION['paypal_token']);
$_SESSION['checkout_error'] = 'Payment was cancelled. You can try again.';

header('Location: ' . SITE_URL . '/checkout.php');
exit;
