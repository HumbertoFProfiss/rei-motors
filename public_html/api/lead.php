<?php
/**
 * API endpoint para captura de leads via AJAX
 * POST: nome, telefone, email (opcional), origem, observacoes (opcional)
 */

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'erro' => 'Método não permitido']);
    exit;
}

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

// Rate limiting: máx 5 submissões por IP a cada 10 minutos
$ip  = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$key = 'lead_rate_' . md5($ip);
if (!isset($_SESSION[$key])) {
    $_SESSION[$key] = ['count' => 0, 'reset_at' => time() + 600];
}
if (time() > $_SESSION[$key]['reset_at']) {
    $_SESSION[$key] = ['count' => 0, 'reset_at' => time() + 600];
}
$_SESSION[$key]['count']++;
if ($_SESSION[$key]['count'] > 5) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'erro' => 'Muitas tentativas. Aguarde alguns minutos.']);
    exit;
}

$origens_permitidas = ['simulador', 'estoque', 'site'];

$nome     = sanitizar($_POST['nome']     ?? '');
$telefone = sanitizar($_POST['telefone'] ?? '');
$email    = sanitizar($_POST['email']    ?? '');
$origem   = sanitizar($_POST['origem']   ?? 'site');
$obs      = sanitizar($_POST['observacoes'] ?? '');

if (!in_array($origem, $origens_permitidas, true)) {
    $origem = 'site';
}

if (!$nome || !$telefone) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'erro' => 'Nome e telefone são obrigatórios']);
    exit;
}

if ($email && !validarEmail($email)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'erro' => 'E-mail inválido']);
    exit;
}

$lead_data = [
    'nome'        => $nome,
    'email'       => $email ?: null,
    'telefone'    => $telefone,
    'origem'      => $origem,
    'tipo_lead'   => 'novo',
    'observacoes' => $obs ?: null,
    'ip_origem'   => $_SERVER['REMOTE_ADDR'] ?? '',
    'user_agent'  => $_SERVER['HTTP_USER_AGENT'] ?? '',
];
inserir('leads', $lead_data);
notificarNovoLead($lead_data);

echo json_encode(['ok' => true]);
