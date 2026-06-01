<?php
/**
 * Feed XML de estoque — Rei Motors
 * Compatível com: WebMotors, iCarros, Chaves na Mão, MobAutos, Na Pista
 * URL para cadastrar nos portais: https://reimotors.com.br/feed.php
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Token opcional de segurança (configurável em configuracoes)
$cfg_token = obterUmaLinha("SELECT valor FROM configuracoes WHERE chave = 'feed_token'");
if ($cfg_token && !empty($cfg_token['valor'])) {
    if (($_GET['token'] ?? '') !== $cfg_token['valor']) {
        http_response_code(403);
        die('Acesso negado.');
    }
}

$veiculos = obterTodas(
    "SELECT v.*,
            GROUP_CONCAT(DISTINCT vf.caminho ORDER BY vf.principal DESC, vf.ordem ASC SEPARATOR '|') AS fotos
     FROM veiculos v
     LEFT JOIN veiculos_fotos vf ON v.id = vf.veiculo_id
     WHERE v.status = 'disponivel'
     GROUP BY v.id
     ORDER BY v.criado_em DESC"
);

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<anuncios xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" data_geracao="<?= date('Y-m-d\TH:i:s') ?>">
<?php foreach ($veiculos as $v):
    $fotos = $v['fotos'] ? explode('|', $v['fotos']) : [];
    $preco = number_format((float)$v['preco_venda'], 2, '.', '');
    $fipe  = $v['preco_tabela_fipe'] ? number_format((float)$v['preco_tabela_fipe'], 2, '.', '') : '';
    $url   = BASE_URL . 'veiculo.php?slug=' . urlencode($v['slug']);
    $titulo = htmlspecialchars($v['marca'] . ' ' . $v['modelo'] . ' ' . $v['ano'], ENT_XML1);
    $desc   = htmlspecialchars(strip_tags($v['descricao'] ?? ''), ENT_XML1);
    $combustiveis_label = [
        'gasolina' => 'Gasolina', 'flex' => 'Flex', 'etanol' => 'Etanol',
        'diesel' => 'Diesel', 'eletrico' => 'Elétrico',
    ];
    $cambios_label = ['manual' => 'Manual', 'automatico' => 'Automático', 'cvt' => 'CVT'];
?>
    <anuncio>
        <id><?= $v['id'] ?></id>
        <url><?= htmlspecialchars($url, ENT_XML1) ?></url>
        <titulo><?= $titulo ?></titulo>
        <marca><?= htmlspecialchars($v['marca'], ENT_XML1) ?></marca>
        <modelo><?= htmlspecialchars($v['modelo'], ENT_XML1) ?></modelo>
        <ano_fabricacao><?= $v['ano'] ?></ano_fabricacao>
        <ano_modelo><?= $v['ano'] ?></ano_modelo>
        <preco><?= $preco ?></preco>
        <?php if ($fipe): ?><preco_fipe><?= $fipe ?></preco_fipe><?php endif; ?>
        <quilometragem><?= (int)$v['quilometragem'] ?></quilometragem>
        <combustivel><?= htmlspecialchars($combustiveis_label[$v['combustivel']] ?? $v['combustivel'], ENT_XML1) ?></combustivel>
        <cambio><?= htmlspecialchars($cambios_label[$v['cambio']] ?? $v['cambio'], ENT_XML1) ?></cambio>
        <cor><?= htmlspecialchars($v['cor'] ?? '', ENT_XML1) ?></cor>
        <?php if ($v['placa']): ?><placa><?= htmlspecialchars($v['placa'], ENT_XML1) ?></placa><?php endif; ?>
        <?php if ($v['numero_chassi']): ?><chassi><?= htmlspecialchars($v['numero_chassi'], ENT_XML1) ?></chassi><?php endif; ?>
        <descricao><?= $desc ?></descricao>
        <vendedor>
            <nome><?= htmlspecialchars(LOJA_NOME, ENT_XML1) ?></nome>
            <telefone><?= htmlspecialchars(LOJA_TELEFONE, ENT_XML1) ?></telefone>
            <cidade>Botucatu</cidade>
            <estado>SP</estado>
        </vendedor>
        <fotos>
            <?php foreach ($fotos as $f): ?>
            <foto><?= htmlspecialchars(BASE_URL . 'uploads/' . ltrim($f, '/'), ENT_XML1) ?></foto>
            <?php endforeach; ?>
        </fotos>
    </anuncio>
<?php endforeach; ?>
</anuncios>
