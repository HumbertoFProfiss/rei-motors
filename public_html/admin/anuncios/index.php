<?php
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/functions.php';

requerAutenticacao();

$titulo_pagina = 'Anúncios';
$pagina_ativa  = 'anuncios';
$admin_root    = '../';
$breadcrumb    = [['label' => 'Anúncios', 'url' => '']];

// Exportar CSV para Mercado Livre / Facebook
if (isset($_GET['exportar_csv'])) {
    $veiculos_csv = obterTodas(
        "SELECT v.*, GROUP_CONCAT(DISTINCT vf.caminho ORDER BY vf.principal DESC SEPARATOR '|') AS fotos
         FROM veiculos v
         LEFT JOIN veiculos_fotos vf ON v.id = vf.veiculo_id
         WHERE v.status = 'disponivel'
         GROUP BY v.id ORDER BY v.criado_em DESC"
    );
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="estoque_reimotors_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8
    fputcsv($out, ['ID','Título','Marca','Modelo','Ano','Preço','KM','Combustível','Câmbio','Cor','Descrição','URL Anúncio','Foto Principal']);
    $combustiveis_l = ['gasolina'=>'Gasolina','flex'=>'Flex','etanol'=>'Etanol','diesel'=>'Diesel','eletrico'=>'Elétrico'];
    $cambios_l = ['manual'=>'Manual','automatico'=>'Automático','cvt'=>'CVT'];
    foreach ($veiculos_csv as $v) {
        $fotos = $v['fotos'] ? explode('|', $v['fotos']) : [];
        $foto_principal = !empty($fotos) ? BASE_URL . 'uploads/' . ltrim($fotos[0], '/') : '';
        fputcsv($out, [
            $v['id'],
            $v['marca'] . ' ' . $v['modelo'] . ' ' . $v['ano'],
            $v['marca'], $v['modelo'], $v['ano'],
            number_format((float)$v['preco_venda'], 2, ',', '.'),
            (int)$v['quilometragem'],
            $combustiveis_l[$v['combustivel']] ?? $v['combustivel'],
            $cambios_l[$v['cambio']] ?? $v['cambio'],
            $v['cor'] ?? '',
            strip_tags($v['descricao'] ?? ''),
            BASE_URL . 'veiculo.php?slug=' . $v['slug'],
            $foto_principal,
        ]);
    }
    fclose($out);
    exit;
}

$veiculos = obterTodas(
    "SELECT v.id, v.slug, v.marca, v.modelo, v.ano, v.preco_venda, v.quilometragem, v.status,
            (SELECT caminho FROM veiculos_fotos WHERE veiculo_id = v.id AND principal = 1 LIMIT 1) AS foto
     FROM veiculos v WHERE v.status = 'disponivel' ORDER BY v.criado_em DESC"
);

$feed_url = BASE_URL . 'feed.php';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page__header">
    <div>
        <h1 class="page__titulo">Anúncios Automáticos</h1>
        <p class="page__subtitulo"><?= count($veiculos) ?> veículo<?= count($veiculos) !== 1 ? 's' : '' ?> disponíveis no feed</p>
    </div>
    <div class="page__acoes">
        <a href="?exportar_csv=1" class="btn-admin btn-admin--secondary">⬇ Exportar CSV</a>
        <a href="<?= htmlspecialchars($feed_url) ?>" target="_blank" class="btn-admin btn-admin--primary">📡 Ver Feed XML</a>
    </div>
</div>

<!-- INSTRUÇÕES -->
<div class="form-section" style="margin-bottom:24px">
    <div class="form-section__titulo">📋 Como cadastrar nos portais</div>
    <div class="form-section__body">
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px">
            <?php
            $portais = [
                ['WebMotors',       'webmotors.com.br/anunciante', 'Painel do anunciante → Integração → Cole a URL do feed XML'],
                ['iCarros',         'icarros.com.br/anunciante',   'Painel → Importar estoque → URL do feed XML'],
                ['Chaves na Mão',   'chavesnamao.com.br',          'Painel do revendedor → Feed de estoque → URL XML'],
                ['MobAutos',        'mobautos.com.br',             'Painel → Importação automática → URL do feed'],
                ['Na Pista',        'napista.com.br',              'Painel → Integração de estoque → XML'],
                ['Carros SP',       'carrossp.com.br',             'Contate o suporte informando a URL do feed'],
                ['Mercado Livre',   'mercadolivre.com.br',         'Use o botão "Exportar CSV" e faça upload no Gerenciador de Catálogos'],
                ['Facebook Marketplace','facebook.com/marketplace','Gerenciador de Comércio → Catálogo → Importar planilha CSV'],
            ];
            foreach ($portais as [$nome, $site, $instrucao]): ?>
            <div style="background:var(--admin-card);border:1px solid var(--admin-border);border-radius:8px;padding:14px">
                <div style="font-weight:700;color:#D4AF37;margin-bottom:6px"><?= $nome ?></div>
                <div style="font-size:0.75rem;color:#888;margin-bottom:8px"><?= $site ?></div>
                <div style="font-size:0.78rem;color:#888;line-height:1.5"><?= $instrucao ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <div style="margin-top:16px;padding:12px;background:rgba(212,175,55,0.06);border:1px solid rgba(212,175,55,0.2);border-radius:6px">
            <strong style="color:#D4AF37">URL do Feed XML:</strong>
            <code style="display:block;margin-top:6px;color:#aaa;font-size:0.82rem;word-break:break-all"><?= htmlspecialchars($feed_url) ?></code>
        </div>
    </div>
</div>

<!-- LISTA DE VEÍCULOS NO FEED -->
<div class="table-container">
    <div class="table-header">
        <h2 class="table-header__titulo">Veículos incluídos no feed</h2>
    </div>
    <?php if ($veiculos): ?>
    <table class="table">
        <thead>
            <tr>
                <th style="width:50px">Foto</th>
                <th>Veículo</th>
                <th>Ano</th>
                <th>Preço</th>
                <th>KM</th>
                <th>Link</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($veiculos as $v): ?>
        <tr>
            <td>
                <?php if ($v['foto']): ?>
                <img src="<?= UPLOAD_DIR . htmlspecialchars($v['foto']) ?>"
                     style="width:48px;height:36px;object-fit:cover;border-radius:4px">
                <?php else: ?>
                <span>🚗</span>
                <?php endif; ?>
            </td>
            <td class="td-titulo"><?= htmlspecialchars($v['marca'] . ' ' . $v['modelo']) ?></td>
            <td><?= $v['ano'] ?></td>
            <td><?= formatarMoeda($v['preco_venda']) ?></td>
            <td><?= formatarKM($v['quilometragem']) ?> km</td>
            <td>
                <a href="<?= BASE_URL ?>veiculo.php?slug=<?= htmlspecialchars($v['slug']) ?>"
                   style="color:#D4AF37;font-size:0.75rem">↗ Ver</a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="empty-state">
        <div class="empty-state__icone">📢</div>
        <p class="empty-state__titulo">Nenhum veículo disponível</p>
        <p class="empty-state__texto">Cadastre veículos com status "Disponível" para que apareçam no feed.</p>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
