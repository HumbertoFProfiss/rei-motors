<?php
// Token de uso único — acesso sem login (apagar após usar)
if (($_GET['token'] ?? '') !== 'reimotors2026fix') {
    http_response_code(403);
    die('Acesso negado');
}

ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

echo "=== AUTOFIX Rei Motors ===\n\n";
echo "UPLOAD_PATH: " . UPLOAD_PATH . "\n";
echo "Pasta veiculos/: " . (is_dir(UPLOAD_PATH . 'veiculos/') ? "OK" : "NAO EXISTE") . "\n";
echo "Gravavel: " . (is_writable(UPLOAD_PATH . 'veiculos/') ? "SIM" : "NAO") . "\n\n";

// 1. Limpar entradas do banco onde arquivo não existe
echo "--- LIMPEZA DO BANCO ---\n";
$fotos = obterTodas("SELECT vf.id, vf.caminho, vf.veiculo_id FROM veiculos_fotos vf ORDER BY vf.id");
$deletadas = 0;
$ok = 0;
foreach ($fotos as $f) {
    $arquivo = UPLOAD_PATH . $f['caminho'];
    if (file_exists($arquivo)) {
        echo "OK   [{$f['id']}] {$f['caminho']}\n";
        $ok++;
    } else {
        executarQuery("DELETE FROM veiculos_fotos WHERE id = ?", [$f['id']]);
        echo "DEL  [{$f['id']}] {$f['caminho']} (arquivo inexistente)\n";
        $deletadas++;
    }
}
echo "\nTotal: $ok OK, $deletadas deletadas do banco\n\n";

// 2. Criar arquivo de teste para verificar se upload e acesso estão funcionando
echo "--- TESTE DE UPLOAD ---\n";
$test_nome = 'teste_autofix_' . time() . '.jpg';
$test_path = UPLOAD_PATH . 'veiculos/' . $test_nome;
$test_url  = BASE_URL . 'uploads/veiculos/' . $test_nome;

// Cria uma imagem JPEG 1x1 pixel
$img = imagecreatetruecolor(100, 100);
$cor = imagecolorallocate($img, 220, 50, 50);
imagefill($img, 0, 0, $cor);
$escrita = imagejpeg($img, $test_path, 85);
imagedestroy($img);

if ($escrita && file_exists($test_path)) {
    @chmod($test_path, 0644);
    $perms = substr(sprintf('%o', fileperms($test_path)), -4);
    echo "Arquivo criado: $test_path\n";
    echo "Permissoes: $perms\n";
    echo "URL de acesso: $test_url\n\n";

    // Testa acesso via HTTP
    $ctx = stream_context_create(['http' => ['timeout' => 10]]);
    $headers = @get_headers($test_url, true, $ctx);
    if ($headers && str_contains($headers[0], '200')) {
        echo "ACESSO HTTP: OK (200) - imagem acessivel!\n";
    } else {
        $status = $headers ? $headers[0] : 'sem resposta';
        echo "ACESSO HTTP: FALHOU ($status)\n";
        echo "URL testada: $test_url\n";
    }

    // Remove o arquivo de teste
    @unlink($test_path);
    echo "Arquivo de teste removido.\n";
} else {
    echo "ERRO: nao foi possivel criar o arquivo de teste em $test_path\n";
}

echo "\n=== FIM ===\n";
