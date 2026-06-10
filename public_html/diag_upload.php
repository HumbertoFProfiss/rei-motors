<?php
require_once __DIR__ . '/../../includes/config.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== DIAGNÓSTICO UPLOAD ===\n\n";
echo "UPLOAD_PATH: " . UPLOAD_PATH . "\n";
echo "BASE_URL: " . BASE_URL . "\n\n";

$pasta = UPLOAD_PATH . 'veiculos/';
echo "Pasta veiculos: $pasta\n";
echo "Existe: " . (is_dir($pasta) ? "SIM" : "NÃO") . "\n";
echo "Gravável: " . (is_writable($pasta) ? "SIM" : "NÃO") . "\n\n";

$pasta_base = UPLOAD_PATH;
echo "Pasta base uploads: $pasta_base\n";
echo "Existe: " . (is_dir($pasta_base) ? "SIM" : "NÃO") . "\n";
echo "Gravável: " . (is_writable($pasta_base) ? "SIM" : "NÃO") . "\n\n";

// Testa criar um arquivo
$teste = $pasta_base . 'teste_diag.txt';
$ok = @file_put_contents($teste, 'ok');
echo "Criar arquivo teste: " . ($ok !== false ? "OK ($ok bytes)" : "FALHOU") . "\n";
if ($ok !== false) @unlink($teste);

// Lista arquivos existentes em uploads/veiculos/
echo "\nArquivos em uploads/veiculos/:\n";
if (is_dir($pasta)) {
    $arquivos = glob($pasta . '*');
    if ($arquivos) {
        foreach ($arquivos as $a) {
            echo "  " . basename($a) . " (" . filesize($a) . " bytes, " . decoct(fileperms($a) & 0777) . ")\n";
        }
    } else {
        echo "  (vazio)\n";
    }
} else {
    echo "  (pasta não existe)\n";
}

echo "\nextension gd: " . (extension_loaded('gd') ? "SIM" : "NÃO") . "\n";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
