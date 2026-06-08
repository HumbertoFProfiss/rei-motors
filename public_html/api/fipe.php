<?php
// Proxy para API FIPE — somente para admin autenticado
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!estaAutenticado()) {
    http_response_code(403);
    echo json_encode(['erro' => 'Acesso negado']);
    exit;
}

$acao         = $_GET['acao']         ?? '';
$marca_codigo = (int)($_GET['marca_codigo'] ?? 0);
$modelo_codigo= (int)($_GET['modelo_codigo'] ?? 0);
$ano_codigo   = $_GET['ano_codigo']   ?? '';

// Busca por placa via wdapi2.com.br
if ($acao === 'placa') {
    $placa = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $_GET['placa'] ?? ''));
    if (strlen($placa) < 7) {
        echo json_encode(['erro' => 'Placa inválida (mínimo 7 caracteres)']);
        exit;
    }
    $url = 'https://wdapi2.com.br/consulta/' . $placa . '/' . WDAPI2_TOKEN;
    // Continua com o fetch abaixo
} else {
    $base = 'https://parallelum.com.br/fipe/api/v1/carros';
    $url = match($acao) {
        'marcas'  => "$base/marcas",
        'modelos' => $marca_codigo > 0 ? "$base/marcas/$marca_codigo/modelos" : null,
        'anos'    => ($marca_codigo > 0 && $modelo_codigo > 0) ? "$base/marcas/$marca_codigo/modelos/$modelo_codigo/anos" : null,
        'preco'   => ($marca_codigo > 0 && $modelo_codigo > 0 && $ano_codigo !== '') ? "$base/marcas/$marca_codigo/modelos/$modelo_codigo/anos/$ano_codigo" : null,
        default   => null,
    };

    if (!$url) {
        echo json_encode(['erro' => 'Parâmetros inválidos']);
        exit;
    }
}

$resp      = false;
$http_code = 0;

if (function_exists('curl_init')) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT      => 'ReiMotors/1.0',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
    ]);
    $resp      = curl_exec($ch);
    $http_code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if (curl_errno($ch)) { $resp = false; }
    curl_close($ch);
} else {
    $ctx  = stream_context_create(['http' => ['timeout' => 8, 'header' => "User-Agent: ReiMotors/1.0\r\nAccept: application/json\r\n"]]);
    $resp = @file_get_contents($url, false, $ctx);
}

if ($resp === false) {
    http_response_code(502);
    echo json_encode(['erro' => 'Não foi possível conectar à API. Verifique sua conexão.']);
    exit;
}

// Garante que a resposta é JSON válido (APIs externas às vezes retornam HTML em erros)
$decoded = json_decode($resp, true);
if ($decoded === null) {
    if ($acao === 'placa') {
        echo json_encode(['erro' => 'Placa não encontrada ou serviço indisponível no momento.']);
    } else {
        http_response_code(502);
        echo json_encode(['erro' => 'Resposta inválida da API FIPE.']);
    }
    exit;
}

if ($acao === 'placa') {
    if ($http_code !== 200) {
        echo json_encode(['erro' => 'Placa não encontrada na base de dados (código ' . $http_code . ').']);
        exit;
    }

    // wdapi2: campos principais em MAIÚSCULO, detalhes em extra{}
    $extra = $decoded['extra'] ?? [];

    $normalizado = [
        'marca'       => $decoded['MARCA']      ?? '',
        'modelo'      => $decoded['MODELO']     ?? '',   // ex: "HB20 1.0M COMFOR"
        'anoModelo'   => $decoded['anoModelo']  ?? ($decoded['ano'] ?? ''),
        'combustivel' => $extra['combustivel']  ?? '',   // ex: "Alcool / Gasolina"
        'cor'         => $decoded['cor']        ?? '',   // ex: "Branca"
        'chassi'      => $extra['chassi']       ?? ($decoded['chassi'] ?? ''),
        'municipio'   => $extra['municipio']    ?? '',
        'situacao'    => $decoded['codigoSituacao'] ?? '',
    ];

    echo json_encode($normalizado);
    exit;
}

echo $resp;
