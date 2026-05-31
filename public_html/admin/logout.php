<?php
require_once __DIR__ . '/../../includes/config.php';

session_start();
session_destroy();
setcookie(session_name(), '', time() - 3600, '/');

header('Location: ' . ADMIN_URL . 'login.php');
exit;
