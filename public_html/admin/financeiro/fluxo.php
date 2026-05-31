<?php
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/functions.php';

requerAdmin();

$titulo_pagina = 'Fluxo de Caixa';
$pagina_ativa  = 'financeiro';
$admin_root    = '../';
$breadcrumb    = [
    ['label' => 'Financeiro',   'url' => './'],
    ['label' => 'Fluxo de Caixa', 'url' => ''],
];

// Mês selecionado (padrão: mês atual)
$mes_sel = trim($_GET['mes'] ?? date('Y-m'));

// Receitas do período: vendas confirmadas/entregues
$receitas_vendas = obterTodas(
    "SELECT data_venda as data, CONCAT('Venda — ', v.marca, ' ', v.modelo) as descricao,
            (preco_venda - desconto_aplicado) as valor, 'receita' as tipo
     FROM vendas vd
     JOIN veiculos v ON v.id = vd.veiculo_id
     WHERE DATE_FORMAT(data_venda, '%Y-%m') = ?
       AND vd.status IN ('confirmada', 'entregue')
     ORDER BY data_venda ASC",
    [$mes_sel]
);

// Receitas: contas a receber pagas no período
$receitas_cr = obterTodas(
    "SELECT data_pagamento as data, descricao, valor, 'receita' as tipo
     FROM contas_receber
     WHERE DATE_FORMAT(data_pagamento, '%Y-%m') = ? AND status = 'recebida'
     ORDER BY data_pagamento ASC",
    [$mes_sel]
);

// Despesas: contas a pagar pagas no período
$despesas_cp = obterTodas(
    "SELECT data_pagamento as data, descricao, valor, 'despesa' as tipo
     FROM contas_pagar
     WHERE DATE_FORMAT(data_pagamento, '%Y-%m') = ? AND status = 'paga'
     ORDER BY data_pagamento ASC",
    [$mes_sel]
);

// Despesas: custos de veículos no período
$despesas_cv = obterTodas(
    "SELECT cv.data_despesa as data,
            CONCAT(cv.descricao, ' (', v.marca, ' ', v.modelo, ')') as descricao,
            cv.valor, 'despesa' as tipo
     FROM custos_veiculo cv
     JOIN veiculos v ON v.id = cv.veiculo_id
     WHERE DATE_FORMAT(cv.data_despesa, '%Y-%m') = ?
     ORDER BY cv.data_despesa ASC",
    [$mes_sel]
);

// Unir e ordenar por data
$lancamentos = array_merge($receitas_vendas, $receitas_cr, $despesas_cp, $despesas_cv);
usort($lancamentos, fn($a, $b) => strcmp($a['data'], $b['data']));

$total_receitas = 0;
$total_despesas = 0;
foreach ($lancamentos as $l) {
    if ($l['tipo'] === 'receita') $total_receitas += (float)$l['valor'];
    else                          $total_despesas += (float)$l['valor'];
}
$saldo = $total_receitas - $total_despesas;

require_once __DIR__ . '/../includes/header.php';
?>

<!-- NAV FINANCEIRO -->
<div style="display:flex;gap:0.5rem;margin-bottom:1.5rem;flex-wrap:wrap">
    <a href="." class="btn-admin btn-admin--secondary btn-admin--sm">📤 Contas a Pagar</a>
    <a href="receber.php" class="btn-admin btn-admin--secondary btn-admin--sm">📥 Contas a Receber</a>
    <a href="fluxo.php" class="btn-admin btn-admin--primary btn-admin--sm">💹 Fluxo de Caixa</a>
    <a href="dre.php" class="btn-admin btn-admin--secondary btn-admin--sm">📊 DRE</a>
</div>

<div class="page__header">
    <div>
        <h1 class="page__titulo">Fluxo de Caixa</h1>
        <p class="page__subtitulo">Mês: <?= date('m/Y', strtotime($mes_sel . '-01')) ?></p>
    </div>
    <div class="page__acoes">
        <form method="GET" action="" style="display:flex;gap:0.5rem;align-items:center">
            <input type="month" name="mes" value="<?= htmlspecialchars($mes_sel) ?>"
                   style="padding:0.5rem;border-radius:8px;border:1px solid var(--admin-border);background:var(--admin-card);color:var(--admin-text)">
            <button type="submit" class="btn-admin btn-admin--primary btn-admin--sm">Filtrar</button>
        </form>
    </div>
</div>

<div class="stats-grid" style="margin-bottom:1.5rem">
    <div class="stat-card stat-card--green">
        <div class="stat-card__label">Total Receitas</div>
        <div class="stat-card__valor"><?= formatarMoeda($total_receitas) ?></div>
    </div>
    <div class="stat-card stat-card--red">
        <div class="stat-card__label">Total Despesas</div>
        <div class="stat-card__valor"><?= formatarMoeda($total_despesas) ?></div>
    </div>
    <div class="stat-card <?= $saldo >= 0 ? 'stat-card--green' : 'stat-card--red' ?>">
        <div class="stat-card__label">Saldo do Mês</div>
        <div class="stat-card__valor"><?= formatarMoeda($saldo) ?></div>
    </div>
</div>

<div class="table-container">
    <div class="table-header"><h2 class="table-header__titulo">Lançamentos de <?= date('M/Y', strtotime($mes_sel . '-01')) ?></h2></div>

    <?php if ($lancamentos): ?>
    <table class="table">
        <thead>
            <tr><th>Data</th><th>Descrição</th><th>Tipo</th><th>Valor</th></tr>
        </thead>
        <tbody>
        <?php
            $saldo_acum = 0;
            foreach ($lancamentos as $l):
                if ($l['tipo'] === 'receita') $saldo_acum += (float)$l['valor'];
                else                          $saldo_acum -= (float)$l['valor'];
        ?>
        <tr>
            <td><?= formatarData($l['data']) ?></td>
            <td class="td-titulo"><?= htmlspecialchars($l['descricao']) ?></td>
            <td>
                <span class="badge-admin badge-admin--<?= $l['tipo'] === 'receita' ? 'ativo' : 'danger' ?>">
                    <?= $l['tipo'] === 'receita' ? '▲ Receita' : '▼ Despesa' ?>
                </span>
            </td>
            <td style="color:<?= $l['tipo'] === 'receita' ? '#4CAF50' : '#f44336' ?>">
                <?= $l['tipo'] === 'receita' ? '+' : '-' ?><?= formatarMoeda($l['valor']) ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" style="text-align:right;font-weight:700;padding:0.75rem 1rem">Saldo Final:</td>
                <td style="font-weight:700;color:<?= $saldo >= 0 ? '#4CAF50' : '#f44336' ?>;padding:0.75rem 0.5rem">
                    <?= formatarMoeda($saldo) ?>
                </td>
            </tr>
        </tfoot>
    </table>
    <?php else: ?>
    <div class="empty-state">
        <div class="empty-state__icone">💹</div>
        <p class="empty-state__titulo">Nenhum lançamento neste período</p>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
