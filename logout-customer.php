<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/config/database.php';
unset($_SESSION['customer_id'], $_SESSION['customer_name'], $_SESSION['customer_email']);
header('Location: ' . SITE_URL);
exit;
