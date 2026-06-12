<?php
if (($_GET['token'] ?? '') !== 'reimotors2026fix') { http_response_code(403); die('Acesso negado'); }
ini_set('display_errors', 1); error_reporting(E_ALL);
header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';

echo "=== DIAGNÓSTICO DE PATHS ===\n\n";
echo "__FILE__: " . __FILE__ . "\n";
echo "__DIR__: " . __DIR__ . "\n";
echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'nao definido') . "\n";
echo "SCRIPT_FILENAME: " . ($_SERVER['SCRIPT_FILENAME'] ?? 'nao definido') . "\n\n";

echo "BASE_URL: " . BASE_URL . "\n";
echo "UPLOAD_PATH: " . UPLOAD_PATH . "\n";
echo "UPLOAD_PATH realpath: " . realpath(UPLOAD_PATH) . "\n\n";

// Descobrir o caminho real do web root
$doc_root = $_SERVER['DOCUMENT_ROOT'] ?? '';
echo "=== TESTAR UPLOAD_PATH CORRETO ===\n";

// Onde os uploads precisam estar para ser acessíveis via URL
$esperado_via_url = $doc_root . '/uploads/veiculos/';
echo "Uploads esperado pelo Apache: $esperado_via_url\n";
echo "Existe: " . (is_dir($esperado_via_url) ? "SIM" : "NAO") . "\n\n";

// Teste com o DOCUMENT_ROOT real
$test_nome = 'teste_' . time() . '.jpg';

// Teste 1: gravar no UPLOAD_PATH atual
$path1 = UPLOAD_PATH . 'veiculos/' . $test_nome;
$url1  = BASE_URL . 'uploads/veiculos/' . $test_nome;
$img = imagecreatetruecolor(10, 10);
imagejpeg($img, $path1, 80);
imagedestroy($img);
@chmod($path1, 0644);
$h1 = @get_headers($url1);
$status1 = $h1 ? $h1[0] : 'sem resposta';
echo "Teste UPLOAD_PATH atual:\n";
echo "  Arquivo: $path1\n";
echo "  URL: $url1\n";
echo "  HTTP: $status1\n";
if (file_exists($path1)) @unlink($path1);

// Teste 2: gravar no DOCUMENT_ROOT/uploads/veiculos/
echo "\nTeste DOCUMENT_ROOT/uploads/veiculos/:\n";
if ($doc_root && is_dir($doc_root . '/uploads/')) {
    $path2 = $doc_root . '/uploads/veiculos/' . $test_nome;
    if (!is_dir($doc_root . '/uploads/veiculos/')) mkdir($doc_root . '/uploads/veiculos/', 0755, true);
    $img = imagecreatetruecolor(10, 10);
    imagejpeg($img, $path2, 80);
    imagedestroy($img);
    @chmod($path2, 0644);
    $h2 = @get_headers($url1); // mesma URL
    $status2 = $h2 ? $h2[0] : 'sem resposta';
    echo "  Arquivo: $path2\n";
    echo "  HTTP: $status2\n";
    if (file_exists($path2)) @unlink($path2);
} else {
    echo "  DOCUMENT_ROOT/uploads/ nao existe\n";
}

// Permissões dos diretórios
echo "\n=== PERMISSÕES ===\n";
foreach ([UPLOAD_PATH, UPLOAD_PATH . 'veiculos/', $doc_root . '/uploads/'] as $dir) {
    if (is_dir($dir)) {
        $p = substr(sprintf('%o', fileperms($dir)), -4);
        echo "$dir → $p\n";
    } else {
        echo "$dir → NAO EXISTE\n";
    }
}

// Htaccess em uploads/
$hta = UPLOAD_PATH . '.htaccess';
echo "\nUPLOAD_PATH/.htaccess: " . (file_exists($hta) ? file_get_contents($hta) : "nao existe") . "\n";

echo "\n=== FIM ===\n";
