<?php
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/functions.php';

requerAutenticacao();

$titulo_pagina = 'Anúncios';
$pagina_ativa  = 'anuncios';
$admin_root    = '../';
$breadcrumb    = [['label' => 'Anúncios', 'url' => '']];

$portais = [
    'webmotors'    => ['nome' => 'WebMotors',            'tipo' => 'csv', 'cor' => '#e03a3a'],
    'icarros'      => ['nome' => 'iCarros',               'tipo' => 'csv', 'cor' => '#0066cc'],
    'chavesnamao'  => ['nome' => 'Chaves na Mão',         'tipo' => 'csv', 'cor' => '#ff6600'],
    'mobautos'     => ['nome' => 'MobAutos',              'tipo' => 'csv', 'cor' => '#00aa55'],
    'napista'      => ['nome' => 'Na Pista',              'tipo' => 'csv', 'cor' => '#333399'],
    'carrossp'     => ['nome' => 'Carros SP',             'tipo' => 'csv', 'cor' => '#cc0033'],
    'mercadolivre' => ['nome' => 'Mercado Livre',         'tipo' => 'csv', 'cor' => '#ffe600'],
    'facebook'     => ['nome' => 'Facebook Marketplace',  'tipo' => 'csv', 'cor' => '#1877f2'],
];

// Exportar CSV
if (isset($_GET['exportar_csv'])) {
    $vid = (int)($_GET['veiculo_id'] ?? 0);

    $where = $vid > 0
        ? "WHERE v.status = 'disponivel' AND v.id = $vid"
        : "WHERE v.status = 'disponivel'";

    $veiculos_csv = obterTodas(
        "SELECT v.*, GROUP_CONCAT(DISTINCT vf.caminho ORDER BY vf.principal DESC SEPARATOR '|') AS fotos
         FROM veiculos v
         LEFT JOIN veiculos_fotos vf ON v.id = vf.veiculo_id
         $where
         GROUP BY v.id ORDER BY v.criado_em DESC"
    );

    $portal   = $_GET['portal'] ?? 'geral';
    $filename = 'anuncio_reimotors_' . $portal . '_' . date('Y-m-d') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

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
    "SELECT v.id, v.marca, v.modelo, v.ano, v.preco_venda, v.quilometragem,
            (SELECT caminho FROM veiculos_fotos WHERE veiculo_id = v.id AND principal = 1 LIMIT 1) AS foto
     FROM veiculos v WHERE v.status = 'disponivel' ORDER BY v.marca, v.modelo"
);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page__header">
    <div>
        <h1 class="page__titulo">Anúncios de Veículos</h1>
        <p class="page__subtitulo">Exporte veículos para os portais parceiros</p>
    </div>
</div>

<!-- FORMULÁRIO PRINCIPAL -->
<div class="form-section" style="margin-bottom:24px">
    <div class="form-section__titulo">📢 Anunciar Veículo</div>
    <div class="form-section__body">
        <div class="form-grid">

            <!-- PASSO 1: Selecionar veículo -->
            <div class="form-grupo form-grupo--2">
                <label>1. Qual veículo deseja anunciar?</label>
                <select id="selectVeiculo" onchange="atualizarPainel()">
                    <option value="">— Todos os disponíveis —</option>
                    <?php foreach ($veiculos as $v): ?>
                    <option value="<?= $v['id'] ?>"
                            data-foto="<?= $v['foto'] ? UPLOAD_DIR . htmlspecialchars($v['foto']) : '' ?>"
                            data-nome="<?= htmlspecialchars($v['marca'] . ' ' . $v['modelo'] . ' ' . $v['ano']) ?>"
                            data-preco="<?= formatarMoeda($v['preco_venda']) ?>"
                            data-km="<?= formatarKM($v['quilometragem']) ?> km">
                        <?= htmlspecialchars($v['marca'] . ' ' . $v['modelo'] . ' ' . $v['ano']) ?>
                        — <?= formatarMoeda($v['preco_venda']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Preview do veículo selecionado -->
            <div class="form-grupo form-grupo--2" id="previewVeiculo" style="display:none">
                <label>&nbsp;</label>
                <div style="display:flex;align-items:center;gap:12px;padding:10px;background:var(--admin-card);border:1px solid var(--admin-border);border-radius:6px">
                    <img id="previewFoto" src="" style="width:72px;height:54px;object-fit:cover;border-radius:4px;display:none">
                    <div id="previewSemFoto" style="width:72px;height:54px;background:#222;border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:1.4rem">🚗</div>
                    <div>
                        <div id="previewNome" style="font-weight:700;color:#f0f0f0"></div>
                        <div id="previewPreco" style="color:#D4AF37;font-size:0.85rem"></div>
                        <div id="previewKm" style="color:#888;font-size:0.78rem"></div>
                    </div>
                </div>
            </div>

            <!-- PASSO 2: Selecionar site -->
            <div class="form-grupo" style="grid-column:1/-1">
                <label>2. Selecione o portal de destino</label>
                <div id="gridPortais" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px;margin-top:8px">
                    <?php foreach ($portais as $key => $p): ?>
                    <label class="portal-card" id="card-<?= $key ?>" onclick="selecionarPortal('<?= $key ?>')">
                        <input type="radio" name="portal" value="<?= $key ?>" style="display:none">
                        <div class="portal-card__dot" style="background:<?= $p['cor'] ?>"></div>
                        <div class="portal-card__nome"><?= $p['nome'] ?></div>
                        <div class="portal-card__tipo">📄 CSV</div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- PASSO 3: Info e botão exportar -->
            <div class="form-grupo" id="painelExportar" style="grid-column:1/-1;display:none">
                <div id="infoPortal" style="padding:14px;border-radius:8px;margin-bottom:16px;border:1px solid rgba(212,175,55,0.2);background:rgba(212,175,55,0.04)">
                </div>
                <div style="display:flex;gap:12px;flex-wrap:wrap">
                    <button id="btnExportar" onclick="exportar()" class="btn-admin btn-admin--primary" style="font-size:1rem;padding:12px 28px">
                        ⬇ Exportar Anúncio
                    </button>
                    <button onclick="limparSelecao()" class="btn-admin btn-admin--secondary">
                        Limpar
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- LISTA DE VEÍCULOS DISPONÍVEIS -->
<div class="table-container">
    <div class="table-header">
        <h2 class="table-header__titulo">Veículos disponíveis (<?= count($veiculos) ?>)</h2>
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
                <th>Anunciar</th>
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
                <button onclick="selecionarVeiculo(<?= $v['id'] ?>)"
                        class="btn-admin btn-admin--primary btn-admin--sm">
                    📢 Anunciar
                </button>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="empty-state">
        <div class="empty-state__icone">📢</div>
        <p class="empty-state__titulo">Nenhum veículo disponível</p>
        <p class="empty-state__texto">Cadastre veículos com status "Disponível" para anunciá-los.</p>
    </div>
    <?php endif; ?>
</div>

<style>
.portal-card {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 4px;
    padding: 14px;
    background: var(--admin-card);
    border: 2px solid var(--admin-border);
    border-radius: 8px;
    cursor: pointer;
    transition: border-color .2s, background .2s;
}
.portal-card:hover { border-color: #D4AF37; background: rgba(212,175,55,.06); }
.portal-card.selecionado { border-color: #D4AF37; background: rgba(212,175,55,.10); }
.portal-card__dot { width: 10px; height: 10px; border-radius: 50%; margin-bottom: 4px; }
.portal-card__nome { font-weight: 700; color: #f0f0f0; font-size: .9rem; }
.portal-card__tipo { font-size: .72rem; color: #888; }
</style>

<script>
var portais = <?= json_encode(array_map(function($k, $p){ return array_merge($p, ['key'=>$k]); }, array_keys($portais), $portais)) ?>;
var portalSelecionado = null;

function selecionarVeiculo(id) {
    var sel = document.getElementById('selectVeiculo');
    sel.value = id;
    atualizarPainel();
    sel.scrollIntoView({behavior:'smooth', block:'center'});
}

function atualizarPainel() {
    var sel = document.getElementById('selectVeiculo');
    var opt = sel.options[sel.selectedIndex];
    var preview = document.getElementById('previewVeiculo');

    if (opt.value) {
        document.getElementById('previewNome').textContent  = opt.dataset.nome  || '';
        document.getElementById('previewPreco').textContent = opt.dataset.preco || '';
        document.getElementById('previewKm').textContent   = opt.dataset.km    || '';
        var foto = opt.dataset.foto;
        if (foto) {
            document.getElementById('previewFoto').src = foto;
            document.getElementById('previewFoto').style.display = 'block';
            document.getElementById('previewSemFoto').style.display = 'none';
        } else {
            document.getElementById('previewFoto').style.display = 'none';
            document.getElementById('previewSemFoto').style.display = 'flex';
        }
        preview.style.display = 'block';
    } else {
        preview.style.display = 'none';
    }
}

function selecionarPortal(key) {
    document.querySelectorAll('.portal-card').forEach(function(c){ c.classList.remove('selecionado'); });
    document.getElementById('card-' + key).classList.add('selecionado');
    document.querySelector('input[name="portal"][value="' + key + '"]').checked = true;
    portalSelecionado = key;

    var portal = portais.find(function(p){ return p.key === key; });
    var info   = document.getElementById('infoPortal');
    var btn    = document.getElementById('btnExportar');

    info.innerHTML =
        '<strong style="color:#D4AF37">📄 ' + portal.nome + ' — Planilha CSV</strong><br>' +
        '<span style="color:#aaa;font-size:.85rem">Ao exportar, você baixa uma planilha CSV com os dados do veículo. Faça o upload no painel do ' + portal.nome + ' para publicar o anúncio.</span>';
    btn.textContent = '⬇ Baixar CSV';

    document.getElementById('painelExportar').style.display = 'block';
}

function exportar() {
    if (!portalSelecionado) { alert('Selecione um portal primeiro.'); return; }

    var vid = document.getElementById('selectVeiculo').value;
    var portal = portais.find(function(p){ return p.key === portalSelecionado; });
    var params = '?veiculo_id=' + (vid || 0) + '&portal=' + portalSelecionado;

    window.location = params + '&exportar_csv=1';
}

function limparSelecao() {
    document.getElementById('selectVeiculo').value = '';
    document.getElementById('previewVeiculo').style.display = 'none';
    document.querySelectorAll('.portal-card').forEach(function(c){ c.classList.remove('selecionado'); });
    portalSelecionado = null;
    document.getElementById('painelExportar').style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
