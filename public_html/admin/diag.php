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
require_once __DIR__ . '/../../includes/functions.php';
echo "Fix .jpg: " . (strpos(file_get_contents(__DIR__ . '/../../includes/functions.php'), "gerarHashArquivo() . '.jpg'") !== false ? "PRESENTE" : "AUSENTE") . "\n";
echo "chmod fix: " . (strpos(file_get_contents(__DIR__ . '/../../includes/functions.php'), '@chmod($caminho_completo') !== false ? "PRESENTE" : "AUSENTE") . "\n";

echo "\n=== FIM — sem erros ===\n";
