<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

unset($_SESSION['cliente_id'], $_SESSION['cliente_nome'], $_SESSION['cliente_login_time']);
header('Location: ' . CLIENTE_URL . 'login.php');
exit;
