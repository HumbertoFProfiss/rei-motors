<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

requerAdmin();

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><title>Limpeza de Fotos</title>
<style>body{font-family:monospace;background:#111;color:#eee;padding:2rem}
.ok{color:#4f4}
.err{color:#f44}
.warn{color:#fa0}
table{border-collapse:collapse;width:100%;margin-top:1rem}
td,th{border:1px solid #333;padding:6px 10px;text-align:left}
th{background:#222}
</style>
</head>
<body>
<h2>Limpeza de Fotos Órfãs</h2>
<p>UPLOAD_PATH: <b><?= UPLOAD_PATH ?></b></p>
<p>Pasta uploads/veiculos/ existe: <b><?= is_dir(UPLOAD_PATH . 'veiculos/') ? '<span class=ok>SIM</span>' : '<span class=err>NÃO</span>' ?></b></p>
<p>Gravável: <b><?= is_writable(UPLOAD_PATH . 'veiculos/') ? '<span class=ok>SIM</span>' : '<span class=err>NÃO</span>' ?></b></p>
<hr>

<?php
$fotos = obterTodas("SELECT vf.*, v.marca, v.modelo FROM veiculos_fotos vf JOIN veiculos v ON v.id = vf.veiculo_id ORDER BY vf.id");

$deletadas = 0;
$ok = 0;

echo '<table><tr><th>ID</th><th>Veículo</th><th>Caminho</th><th>Arquivo</th><th>Ação</th></tr>';
foreach ($fotos as $f) {
    $arquivo = UPLOAD_PATH . $f['caminho'];
    $existe  = file_exists($arquivo);

    if ($existe) {
        $size    = filesize($arquivo);
        $perms   = substr(sprintf('%o', fileperms($arquivo)), -4);
        $bytes   = file_get_contents($arquivo, false, null, 0, 2);
        $is_jpeg = ($bytes === "\xFF\xD8");
        echo '<tr>';
        echo '<td>' . $f['id'] . '</td>';
        echo '<td>' . htmlspecialchars($f['marca'] . ' ' . $f['modelo']) . '</td>';
        echo '<td>' . htmlspecialchars($f['caminho']) . '</td>';
        echo '<td class=ok>EXISTE (' . $size . 'b, ' . $perms . ', jpeg=' . ($is_jpeg ? 'SIM' : '<span class=warn>NÃO</span>') . ')</td>';
        echo '<td class=ok>OK</td>';
        echo '</tr>';
        $ok++;
    } else {
        executarQuery("DELETE FROM veiculos_fotos WHERE id = ?", [$f['id']]);
        echo '<tr>';
        echo '<td>' . $f['id'] . '</td>';
        echo '<td>' . htmlspecialchars($f['marca'] . ' ' . $f['modelo']) . '</td>';
        echo '<td>' . htmlspecialchars($f['caminho']) . '</td>';
        echo '<td class=err>NÃO EXISTE</td>';
        echo '<td class=warn>DELETADA DO BANCO</td>';
        echo '</tr>';
        $deletadas++;
    }
}
echo '</table>';
?>

<hr>
<p>Resultado: <b class=ok><?= $ok ?> foto(s) OK</b> | <b class=warn><?= $deletadas ?> entrada(s) removida(s) do banco</b></p>
<p>Agora <a href="<?= ADMIN_URL ?>veiculos/" style="color:#4af">volte ao admin</a> e faça o upload novamente.</p>

<hr>
<p style="color:#666;font-size:0.8rem">⚠️ Delete este arquivo do servidor após usar: <code>admin/cleanup_fotos.php</code></p>
</body>
</html>
