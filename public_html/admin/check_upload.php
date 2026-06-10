<?php
require_once __DIR__ . '/../../includes/config.php';
requerAutenticacao();

header('Content-Type: text/plain; charset=utf-8');

echo "=== CHECK UPLOAD (admin only) ===\n\n";

echo "UPLOAD_PATH: " . UPLOAD_PATH . "\n";
echo "BASE_URL: " . BASE_URL . "\n\n";

$pasta = UPLOAD_PATH . 'veiculos/';
echo "Pasta uploads/veiculos/: " . (is_dir($pasta) ? "EXISTE" : "NAO EXISTE") . "\n";
echo "Gravavel: " . (is_writable($pasta) ? "SIM" : "NAO") . "\n\n";

echo "GD Library: " . (extension_loaded('gd') ? "SIM" : "NAO") . "\n";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n\n";

// Verifica versão do fix — a função uploadImagem deve ter lógica de .jpg forçado
$src = file_get_contents(__DIR__ . '/../../includes/functions.php');
$tem_fix = str_contains($src, "gerarHashArquivo() . '.jpg'");
echo "Fix .jpg no functions.php: " . ($tem_fix ? "PRESENTE (fix deployado)" : "AUSENTE (fix nao deployado ainda)") . "\n\n";

// Lista fotos existentes
echo "Arquivos em uploads/veiculos/:\n";
if (is_dir($pasta)) {
    $arquivos = glob($pasta . '*');
    if ($arquivos) {
        foreach ($arquivos as $a) {
            $ext = pathinfo($a, PATHINFO_EXTENSION);
            $size = filesize($a);
            // Verifica se é realmente JPEG lendo os primeiros bytes
            $bytes = file_get_contents($a, false, null, 0, 3);
            $is_jpeg = (substr($bytes, 0, 2) === "\xFF\xD8");
            echo "  " . basename($a) . " ({$size}b, ext={$ext}, jpeg_real=" . ($is_jpeg ? "SIM" : "NAO") . ")\n";
        }
    } else {
        echo "  (vazio)\n";
    }
} else {
    echo "  (pasta nao existe)\n";
}
