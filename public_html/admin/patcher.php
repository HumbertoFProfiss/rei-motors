<?php
if (($_GET['token'] ?? '') !== 'reimotors2026fix') { http_response_code(403); die('403'); }
header('Content-Type: text/plain; charset=utf-8');

$dir = __DIR__; // /home2/reidosco/worldcred.com.br/admin

// ===== 1. CORRIGIR UPLOAD_PATH NO config.php =====
echo "=== CORRIGINDO config.php ===\n";
$config_path = $dir . '/../../includes/config.php'; // /home2/reidosco/includes/config.php
$config = file_get_contents($config_path);

// Detectar UPLOAD_PATH atual
preg_match("/define\('UPLOAD_PATH',\s*(.+?)\);/", $config, $m);
echo "UPLOAD_PATH atual no config: " . ($m[1] ?? 'NAO ENCONTRADO') . "\n";

$old  = "define('UPLOAD_PATH', __DIR__ . '/../uploads/');";
$novo = "define('UPLOAD_PATH', __DIR__ . '/../worldcred.com.br/uploads/');";

if (str_contains($config, $old)) {
    $config_novo = str_replace($old, $novo, $config);
    file_put_contents($config_path, $config_novo);
    echo "config.php CORRIGIDO: uploads/ → worldcred.com.br/uploads/\n";
} elseif (str_contains($config, $novo)) {
    echo "config.php ja esta correto.\n";
} else {
    echo "AVISO: padrao nao encontrado, mostrando linha atual:\n";
    foreach (explode("\n", $config) as $i => $line) {
        if (str_contains($line, 'UPLOAD_PATH')) echo ($i+1) . ": $line\n";
    }
}

// Recarregar config para verificar
require_once $config_path;
echo "UPLOAD_PATH agora: " . UPLOAD_PATH . "\n";
echo "UPLOAD_PATH realpath: " . realpath(UPLOAD_PATH) . "\n\n";

// Garantir que a pasta veiculos existe
$pasta = UPLOAD_PATH . 'veiculos/';
if (!is_dir($pasta)) {
    mkdir($pasta, 0755, true);
    echo "Criou pasta: $pasta\n";
} else {
    echo "Pasta OK: $pasta\n";
}
@chmod(UPLOAD_PATH, 0755);
@chmod($pasta, 0755);

// ===== 2. LIMPAR BANCO =====
echo "\n=== LIMPANDO BANCO ===\n";
require_once $dir . '/../../includes/db.php';
require_once $dir . '/../../includes/functions.php';

$fotos = obterTodas("SELECT vf.id, vf.caminho, vf.veiculo_id FROM veiculos_fotos vf ORDER BY vf.id");
$del = 0; $ok = 0;
foreach ($fotos as $f) {
    $arquivo = UPLOAD_PATH . $f['caminho'];
    if (file_exists($arquivo)) {
        echo "OK   [{$f['id']}] {$f['caminho']}\n";
        $ok++;
    } else {
        executarQuery("DELETE FROM veiculos_fotos WHERE id = ?", [$f['id']]);
        echo "DEL  [{$f['id']}] {$f['caminho']} (arquivo nao existe)\n";
        $del++;
    }
}
echo "Total: $ok OK, $del deletadas\n\n";

// ===== 3. TESTE FINAL =====
echo "=== TESTE FINAL ===\n";
$test = UPLOAD_PATH . 'veiculos/testfinal.jpg';
$img = imagecreatetruecolor(10, 10);
$c   = imagecolorallocate($img, 100, 150, 200);
imagefill($img, 0, 0, $c);
imagejpeg($img, $test, 80);
imagedestroy($img);
@chmod($test, 0644);

$url  = BASE_URL . 'uploads/veiculos/testfinal.jpg';
$h    = @get_headers($url);
$stat = $h ? substr($h[0], 9, 3) : '???';
echo "Arquivo: $test\n";
echo "URL: $url\n";
echo "HTTP: $stat\n";
@unlink($test);

echo "\n" . ($stat === '200' ? "SUCESSO! Upload funcionando." : "FALHOU. HTTP=$stat") . "\n";
echo "END\n";
