<?php
session_start();
session_destroy();
require_once __DIR__ . '/../config/database.php';
header('Location: ' . SITE_URL . '/admin/login.php');
exit;
