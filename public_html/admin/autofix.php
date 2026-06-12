<?php
if (($_GET['token'] ?? '') !== 'reimotors2026fix') { http_response_code(403); die('Acesso negado'); }
header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__ . '/../../includes/config.php';

$dr = $_SERVER['DOCUMENT_ROOT'] ?? 'UNDEF';
$sf = $_SERVER['SCRIPT_FILENAME'] ?? 'UNDEF';

echo "DOCROOT=$dr\n";
echo "SCRIPT=$sf\n";
echo "FILE=" . __FILE__ . "\n";
echo "UPLOAD_PATH=" . UPLOAD_PATH . "\n";
echo "UPLOAD_realpath=" . realpath(UPLOAD_PATH) . "\n";

// Permissões
$dirs = [
    'uploads/'         => realpath(UPLOAD_PATH),
    'uploads/veiculos' => realpath(UPLOAD_PATH . 'veiculos'),
    'docroot/uploads'  => $dr . '/uploads',
];
foreach ($dirs as $label => $d) {
    if ($d && is_dir($d)) {
        echo "PERM[$label]=" . substr(sprintf('%o', fileperms($d)), -4) . "\n";
    } else {
        echo "PERM[$label]=NOEXIST\n";
    }
}

// Htaccess em uploads
$hta = realpath(UPLOAD_PATH) . '/.htaccess';
echo "HTACCESS_UPLOADS=" . (file_exists($hta) ? trim(file_get_contents($hta)) : 'nao existe') . "\n";

// Teste rápido de acesso HTTP
$test = realpath(UPLOAD_PATH . 'veiculos') . '/t.jpg';
$img = imagecreatetruecolor(1,1);
imagejpeg($img, $test, 80);
imagedestroy($img);
@chmod($test, 0644);
$h = @get_headers(BASE_URL . 'uploads/veiculos/t.jpg');
echo "HTTP_TEST=" . ($h ? $h[0] : 'FAIL') . "\n";
@unlink($test);

// Teste com docroot
$test2 = $dr . '/uploads/veiculos/t2.jpg';
if (is_dir($dr . '/uploads/veiculos') || @mkdir($dr . '/uploads/veiculos', 0755, true)) {
    $img2 = imagecreatetruecolor(1,1);
    imagejpeg($img2, $test2, 80);
    imagedestroy($img2);
    @chmod($test2, 0644);
    $h2 = @get_headers(BASE_URL . 'uploads/veiculos/t2.jpg');
    echo "HTTP_TEST_DOCROOT=" . ($h2 ? $h2[0] : 'FAIL') . "\n";
    @unlink($test2);
} else {
    echo "HTTP_TEST_DOCROOT=MKDIR_FAIL\n";
}

echo "END\n";
