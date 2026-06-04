<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

$titulo_pagina = 'Início';
$pagina_ativa  = 'dashboard';

require_once __DIR__ . '/includes/header.php';

$id = (int)$_SESSION['cliente_id'];

// Dados do cliente
$cliente = obterUmaLinha("SELECT * FROM clientes WHERE id = ?", [$id]);

// Compras do cliente
$compras = obterTodas(
    "SELECT ve.*, v.marca, v.modelo, v.ano,
            (SELECT caminho FROM veiculos_fotos WHERE veiculo_id = ve.veiculo_id AND principal = 1 LIMIT 1) AS foto
     FROM vendas ve
     JOIN veiculos v ON ve.veiculo_id = v.id
     WHERE ve.cliente_id = ?
     ORDER BY ve.data_venda DESC",
    [$id]
);

$total_compras = count($compras);
$ultima_compra = $compras[0] ?? null;

// Garantias ativas (dentro do prazo)
$garantias_ativas = 0;
foreach ($compras as $c) {
    if (!empty($c['data_entrega']) && (int)($c['prazo_garantia_meses'] ?? 0) > 0) {
        $fim = strtotime($c['data_entrega'] . ' +' . (int)$c['prazo_garantia_meses'] . ' months');
        if ($fim > time()) $garantias_ativas++;
    }
}

// Chamados de garantia abertos
$chamados_abertos = obterUmaLinha(
    "SELECT COUNT(*) AS total FROM garantias_chamados WHERE cliente_id = ? AND status IN ('aberto','em_andamento')",
    [$id]
);
$qtd_chamados = (int)($chamados_abertos['total'] ?? 0);
?>

<?php if (isset($_GET['cadastro'])): ?>
<div class="alert alert--success">Cadastro realizado com sucesso! Bem-vindo à Rei Motors.</div>
<?php endif; ?>

<div class="page__header">
    <div>
        <h1 class="page__titulo">Olá, <?= htmlspecialchars(explode(' ', $cliente['nome'])[0]) ?>!</h1>
        <p class="page__subtitulo">Bem-vindo à sua área exclusiva</p>
    </div>
</div>

<!-- STAT CARDS -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card__label">Veículos Comprados</div>
        <div class="stat-card__valor"><?= $total_compras ?></div>
        <div class="stat-card__sub"><?= $total_compras === 1 ? 'compra realizada' : 'compras realizadas' ?></div>
    </div>
    <div class="stat-card stat-card--gold">
        <div class="stat-card__label">Garantias Ativas</div>
        <div class="stat-card__valor"><?= $garantias_ativas ?></div>
        <div class="stat-card__sub"><?= $garantias_ativas === 1 ? 'veículo em garantia' : 'veículos em garantia' ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card__label">Chamados Abertos</div>
        <div class="stat-card__valor"><?= $qtd_chamados ?></div>
        <div class="stat-card__sub">em atendimento</div>
    </div>
</div>

<?php if ($ultima_compra): ?>
<!-- ÚLTIMA COMPRA -->
<div class="table-container" style="margin-bottom:20px">
    <div class="table-header">
        <h2 class="table-header__titulo">Última Compra</h2>
        <a href="<?= CLIENTE_URL ?>compras.php" class="btn-c btn-c--secondary" style="font-size:0.75rem">Ver todas</a>
    </div>
    <div style="padding:16px 18px">
        <div class="veiculo-card" style="margin-bottom:0">
            <?php if (!empty($ultima_compra['foto'])): ?>
            <div class="veiculo-card__foto">
                <img src="<?= UPLOAD_DIR . htmlspecialchars($ultima_compra['foto']) ?>"
                     alt="<?= htmlspecialchars($ultima_compra['marca'] . ' ' . $ultima_compra['modelo']) ?>">
            </div>
            <?php else: ?>
            <div class="veiculo-card__foto-placeholder">🚗</div>
            <?php endif; ?>
            <div class="veiculo-card__corpo">
                <div class="veiculo-card__titulo">
                    <?= htmlspecialchars($ultima_compra['marca'] . ' ' . $ultima_compra['modelo'] . ' ' . $ultima_compra['ano']) ?>
                </div>
                <div class="veiculo-card__meta">
                    <span>Comprado em <?= formatarData($ultima_compra['data_venda']) ?></span>
                    <?php if ($ultima_compra['data_entrega']): ?>
                    <span>Entregue em <?= formatarData($ultima_compra['data_entrega']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="veiculo-card__info">
                    <?php
                    $status_labels = ['pendente'=>'Pendente','confirmada'=>'Confirmada','entregue'=>'Entregue','cancelada'=>'Cancelada'];
                    $status_badge  = ['pendente'=>'gold','confirmada'=>'blue','entregue'=>'green','cancelada'=>'red'];
                    $s = $ultima_compra['status'];
                    ?>
                    <span class="badge badge--<?= $status_badge[$s] ?? 'gray' ?>">
                        <?= $status_labels[$s] ?? ucfirst($s) ?>
                    </span>
                    <?php if ((int)($ultima_compra['prazo_garantia_meses'] ?? 0) > 0 && !empty($ultima_compra['data_entrega'])): ?>
                    <?php
                    $fim_g  = strtotime($ultima_compra['data_entrega'] . ' +' . (int)$ultima_compra['prazo_garantia_meses'] . ' months');
                    $dias_g = (int)ceil(($fim_g - time()) / 86400);
                    if ($dias_g > 30):
                    ?>
                    <span class="badge badge--green">🛡️ Garantia: <?= $dias_g ?>d restantes</span>
                    <?php elseif ($dias_g > 0): ?>
                    <span class="badge badge--gold">🛡️ Garantia: <?= $dias_g ?>d restantes</span>
                    <?php else: ?>
                    <span class="badge badge--gray">🛡️ Garantia expirada</span>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="empty-state">
    <div class="empty-state__icone">🚗</div>
    <p class="empty-state__titulo">Nenhuma compra registrada</p>
    <p class="empty-state__texto">Suas compras aparecerão aqui após serem registradas pela loja.</p>
</div>
<?php endif; ?>

<!-- CONTATO RÁPIDO -->
<div class="form-section">
    <div class="form-section__titulo">📞 Precisa de ajuda?</div>
    <div class="form-section__body" style="display:flex;gap:12px;flex-wrap:wrap">
        <a href="https://wa.me/55<?= preg_replace('/\D/','',(string)LOJA_WHATSAPP) ?>"
           target="_blank" class="btn-c btn-c--primary">
            💬 WhatsApp
        </a>
        <a href="tel:<?= preg_replace('/\D/','',(string)LOJA_TELEFONE) ?>"
           class="btn-c btn-c--secondary">
            📞 Ligar para a Loja
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
