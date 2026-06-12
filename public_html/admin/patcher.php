<?php
if (($_GET['token'] ?? '') !== 'reimotors2026fix') { http_response_code(403); die('403'); }
header('Content-Type: text/plain; charset=utf-8');

// Informações do servidor
$dr   = $_SERVER['DOCUMENT_ROOT'] ?? '';
$file = __FILE__;
$dir  = __DIR__;

echo "FILE=$file\n";
echo "DOCROOT=$dr\n\n";

// Descobrir onde os uploads devem estar para ser web-acessíveis
// BASE_URL/uploads/ deve mapear para o diretório de uploads correto
// A raiz web é DOCUMENT_ROOT
$uploads_web = $dr . '/uploads/veiculos/';
if (!is_dir($uploads_web)) @mkdir($uploads_web, 0755, true);

// Teste: gravar arquivo no DOCROOT/uploads/veiculos/ e checar via HTTP
$url_base = 'https://worldcred.com.br';
$test_file = 'ptest_' . time() . '.txt';
$test_path = $uploads_web . $test_file;
file_put_contents($test_path, 'ok');
@chmod($test_path, 0644);
$h = @get_headers("$url_base/uploads/veiculos/$test_file");
$status_docroot = $h ? substr($h[0], 9, 3) : '???';
@unlink($test_path);
echo "TEST_DOCROOT_UPLOADS=$status_docroot (esperado: 200)\n";

// Onde o PHP está salvando os uploads atualmente
require_once $dir . '/../../includes/config.php';
echo "UPLOAD_PATH=" . UPLOAD_PATH . "\n";
echo "UPLOAD_realpath=" . realpath(UPLOAD_PATH) . "\n";
echo "DOCROOT_uploads_realpath=" . realpath($dr . '/uploads') . "\n";

// Verificar se são o mesmo diretório
$same = (realpath(UPLOAD_PATH) === realpath($dr . '/uploads/'));
echo "UPLOAD_PATH_CORRETO=" . ($same ? "SIM" : "NAO") . "\n\n";

// Corrigir functions.php puxando do GitHub
echo "=== ATUALIZANDO functions.php ===\n";
$fn_url = 'https://raw.githubusercontent.com/HumbertoFProfiss/rei-motors/main/includes/functions.php';
$fn_content = @file_get_contents($fn_url);
if ($fn_content && strlen($fn_content) > 10000) {
    $fn_path = $dir . '/../../includes/functions.php';
    $wrote = file_put_contents($fn_path, $fn_content);
    @chmod($fn_path, 0644);
    echo "functions.php atualizado: $wrote bytes escritos\n";
    echo "Tem fix .jpg: " . (str_contains($fn_content, "gerarHashArquivo() . '.jpg'") ? "SIM" : "NAO") . "\n";
} else {
    echo "ERRO: nao conseguiu baixar functions.php do GitHub (len=" . strlen($fn_content) . ")\n";
}

echo "\nEND\n";
