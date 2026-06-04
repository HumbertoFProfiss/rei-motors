<?php
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false]);
    exit;
}

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

if (!clienteAutenticado()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'erro' => 'Login necessário']);
    exit;
}

$cliente_id = (int)$_SESSION['cliente_id'];
$veiculo_id = (int)($_POST['veiculo_id'] ?? 0);

if (!$veiculo_id) {
    http_response_code(422);
    echo json_encode(['ok' => false]);
    exit;
}

$existe = obterUmaLinha(
    "SELECT id FROM cliente_favoritos WHERE cliente_id = ? AND veiculo_id = ?",
    [$cliente_id, $veiculo_id]
);

if ($existe) {
    executar("DELETE FROM cliente_favoritos WHERE cliente_id = ? AND veiculo_id = ?", [$cliente_id, $veiculo_id]);
    echo json_encode(['ok' => true, 'acao' => 'removido']);
} else {
    inserir('cliente_favoritos', ['cliente_id' => $cliente_id, 'veiculo_id' => $veiculo_id]);
    echo json_encode(['ok' => true, 'acao' => 'adicionado']);
}
