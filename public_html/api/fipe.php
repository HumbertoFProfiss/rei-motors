<?php
// Proxy para API FIPE (parallelum.com.br) — sem autenticação necessária
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$acao         = $_GET['acao']         ?? '';
$marca_codigo = (int)($_GET['marca_codigo'] ?? 0);
$modelo_codigo= (int)($_GET['modelo_codigo'] ?? 0);
$ano_codigo   = $_GET['ano_codigo']   ?? '';

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

$ctx = stream_context_create(['http' => [
    'timeout' => 8,
    'header'  => "User-Agent: ReiMotors/1.0\r\n",
]]);

$resp = @file_get_contents($url, false, $ctx);

if ($resp === false) {
    http_response_code(502);
    echo json_encode(['erro' => 'Não foi possível conectar à API FIPE. Tente novamente.']);
    exit;
}

echo $resp;
