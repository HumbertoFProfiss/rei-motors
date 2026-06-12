<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/plain; charset=utf-8');
echo "PHP: " . PHP_VERSION . "\n\n";

echo "=== config.php ===\n";
require_once __DIR__ . '/../../includes/config.php';
echo "BASE_URL: " . BASE_URL . "\n";
echo "UPLOAD_PATH: " . UPLOAD_PATH . "\n";
echo "Admin autenticado: " . (estaAutenticado() ? "SIM" : "NÃO") . "\n\n";

echo "=== uploads/veiculos/ ===\n";
$pasta = UPLOAD_PATH . 'veiculos/';
echo "Existe: " . (is_dir($pasta) ? "SIM" : "NAO") . "\n";
echo "Gravavel: " . (is_writable($pasta) ? "SIM" : "NAO") . "\n\n";

echo "=== db.php ===\n";
require_once __DIR__ . '/../../includes/db.php';
echo "Conexao: OK\n\n";

echo "=== functions.php ===\n";
$fn_path = __DIR__ . '/../../includes/functions.php';
$fn_content = file_get_contents($fn_path);
echo "Fix .jpg: " . (strpos($fn_content, "gerarHashArquivo() . '.jpg'") !== false ? "PRESENTE" : "AUSENTE") . "\n";
echo "chmod fix: " . (strpos($fn_content, '@chmod($caminho_completo') !== false ? "PRESENTE" : "AUSENTE") . "\n";
echo "Tamanho functions.php: " . strlen($fn_content) . " bytes\n";
echo "Linha 145-170 do functions.php:\n";
$lines = explode("\n", $fn_content);
for ($i = 144; $i < min(170, count($lines)); $i++) {
    echo ($i+1) . ": " . $lines[$i] . "\n";
}
require_once $fn_path;

echo "\n=== FIM — sem erros ===\n";
