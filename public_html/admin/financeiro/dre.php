<?php
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/functions.php';

requerAdmin();

$titulo_pagina = 'DRE — Resultado';
$pagina_ativa  = 'financeiro';
$admin_root    = '../';
$breadcrumb    = [
    ['label' => 'Financeiro', 'url' => './'],
    ['label' => 'DRE',        'url' => ''],
];

$ano = (int)($_GET['ano'] ?? date('Y'));

// 1. RECEITA BRUTA — somatório das vendas confirmadas/entregues no ano
$rec_bruta = (float)(obterUmaLinha(
    "SELECT COALESCE(SUM(preco_venda - desconto_aplicado), 0) as t
     FROM vendas WHERE YEAR(data_venda) = ? AND status IN ('confirmada','entregue')",
    [$ano]
)['t'] ?? 0);

// 2. CUSTO DOS VEÍCULOS VENDIDOS (CVV) = preco_custo + custos_adicionais + garantias
$cvv_custo = (float)(obterUmaLinha(
    "SELECT COALESCE(SUM(v.preco_custo), 0) as t
     FROM vendas vd JOIN veiculos v ON v.id = vd.veiculo_id
     WHERE YEAR(vd.data_venda) = ? AND vd.status IN ('confirmada','entregue')",
    [$ano]
)['t'] ?? 0);

$cvv_desp_veic = (float)(obterUmaLinha(
    "SELECT COALESCE(SUM(cv.valor), 0) as t
     FROM custos_veiculo cv
     JOIN vendas vd ON vd.veiculo_id = cv.veiculo_id
     WHERE YEAR(vd.data_venda) = ? AND vd.status IN ('confirmada','entregue')",
    [$ano]
)['t'] ?? 0);

$cvv_garantias = (float)(obterUmaLinha(
    "SELECT COALESCE(SUM(g.custo_reparo), 0) as t
     FROM garantias_chamados g
     JOIN vendas vd ON vd.veiculo_id = g.veiculo_id
     WHERE YEAR(vd.data_venda) = ? AND vd.status IN ('confirmada','entregue')",
    [$ano]
)['t'] ?? 0);

$cvv_comissoes = (float)(obterUmaLinha(
    "SELECT COALESCE(SUM(comissao_vendedor), 0) as t
     FROM vendas WHERE YEAR(data_venda) = ? AND status IN ('confirmada','entregue')",
    [$ano]
)['t'] ?? 0);

$cvv_total = $cvv_custo + $cvv_desp_veic + $cvv_garantias + $cvv_comissoes;

// 3. LUCRO BRUTO
$lucro_bruto = $rec_bruta - $cvv_total;

// 4. DESPESAS OPERACIONAIS (contas_pagar pagas no ano)
$desp_operacionais = (float)(obterUmaLinha(
    "SELECT COALESCE(SUM(valor), 0) as t FROM contas_pagar
     WHERE YEAR(data_pagamento) = ? AND status = 'paga'",
    [$ano]
)['t'] ?? 0);

// 5. RESULTADO LÍQUIDO
$resultado_liquido = $lucro_bruto - $desp_operacionais;

// Dados mensais para gráfico
$mensal = obterTodas(
    "SELECT MONTH(data_venda) as mes,
            COALESCE(SUM(preco_venda - desconto_aplicado), 0) as receita,
            COALESCE(SUM(v.preco_custo), 0) as custo
     FROM vendas vd
     JOIN veiculos v ON v.id = vd.veiculo_id
     WHERE YEAR(data_venda) = ? AND status IN ('confirmada','entregue')
     GROUP BY MONTH(data_venda) ORDER BY mes ASC",
    [$ano]
);

$meses_receita = array_fill(1, 12, 0);
$meses_custo   = array_fill(1, 12, 0);
foreach ($mensal as $m) {
    $meses_receita[(int)$m['mes']] = (float)$m['receita'];
    $meses_custo[(int)$m['mes']]   = (float)$m['custo'];
}
$labels_meses = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];

require_once __DIR__ . '/../includes/header.php';
?>

<!-- NAV FINANCEIRO -->
<div style="display:flex;gap:0.5rem;margin-bottom:1.5rem;flex-wrap:wrap">
    <a href="." class="btn-admin btn-admin--secondary btn-admin--sm">📤 Contas a Pagar</a>
    <a href="receber.php" class="btn-admin btn-admin--secondary btn-admin--sm">📥 Contas a Receber</a>
    <a href="fluxo.php" class="btn-admin btn-admin--secondary btn-admin--sm">💹 Fluxo de Caixa</a>
    <a href="dre.php" class="btn-admin btn-admin--primary btn-admin--sm">📊 DRE</a>
</div>

<div class="page__header">
    <div>
        <h1 class="page__titulo">DRE — Demonstrativo de Resultado</h1>
        <p class="page__subtitulo">Exercício <?= $ano ?></p>
    </div>
    <div class="page__acoes">
        <form method="GET" style="display:flex;gap:0.5rem;align-items:center">
            <select name="ano" style="padding:0.5rem;border-radius:8px;border:1px solid var(--admin-border);background:var(--admin-card);color:var(--admin-text)">
                <?php for ($y = date('Y'); $y >= date('Y') - 4; $y--): ?>
                <option value="<?= $y ?>" <?= $ano === $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
            <button type="submit" class="btn-admin btn-admin--primary btn-admin--sm">Ver</button>
        </form>
    </div>
</div>

<!-- GRÁFICO MENSAL -->
<div class="table-container" style="margin-bottom:1.5rem;padding:1.5rem">
    <h2 class="table-header__titulo" style="margin-bottom:1rem">Receita vs Custo — <?= $ano ?></h2>
    <canvas id="chartDRE" height="100"></canvas>
</div>

<!-- TABELA DRE -->
<div class="table-container">
    <div class="table-header"><h2 class="table-header__titulo">Demonstrativo <?= $ano ?></h2></div>
    <table class="table">
        <colgroup><col style="width:60%"><col></colgroup>
        <tbody>
            <tr style="background:rgba(212,175,55,0.08)">
                <td style="font-weight:700;font-size:1rem">RECEITA BRUTA</td>
                <td style="font-weight:700;color:#D4AF37;font-size:1rem"><?= formatarMoeda($rec_bruta) ?></td>
            </tr>
            <tr>
                <td style="padding-left:2rem">(-) Custo de Aquisição dos Veículos</td>
                <td style="color:#f44336">(<?= formatarMoeda($cvv_custo) ?>)</td>
            </tr>
            <tr>
                <td style="padding-left:2rem">(-) Despesas nos Veículos (manutenção, etc.)</td>
                <td style="color:#f44336">(<?= formatarMoeda($cvv_desp_veic) ?>)</td>
            </tr>
            <tr>
                <td style="padding-left:2rem">(-) Custos de Garantia</td>
                <td style="color:#f44336">(<?= formatarMoeda($cvv_garantias) ?>)</td>
            </tr>
            <tr>
                <td style="padding-left:2rem">(-) Comissões de Vendedores</td>
                <td style="color:#f44336">(<?= formatarMoeda($cvv_comissoes) ?>)</td>
            </tr>
            <tr style="background:rgba(76,175,80,0.08)">
                <td style="font-weight:700">= LUCRO BRUTO</td>
                <td style="font-weight:700;color:<?= $lucro_bruto >= 0 ? '#4CAF50' : '#f44336' ?>"><?= formatarMoeda($lucro_bruto) ?></td>
            </tr>
            <tr>
                <td style="padding-left:2rem">(-) Despesas Operacionais (contas pagas)</td>
                <td style="color:#f44336">(<?= formatarMoeda($desp_operacionais) ?>)</td>
            </tr>
            <tr style="background:rgba(212,175,55,0.12);border-top:2px solid var(--admin-border)">
                <td style="font-weight:700;font-size:1.05rem">= RESULTADO LÍQUIDO DO EXERCÍCIO</td>
                <td style="font-weight:700;font-size:1.05rem;color:<?= $resultado_liquido >= 0 ? '#4CAF50' : '#f44336' ?>"><?= formatarMoeda($resultado_liquido) ?></td>
            </tr>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    var ctx = document.getElementById('chartDRE');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($labels_meses) ?>,
            datasets: [
                {
                    label: 'Receita',
                    data: <?= json_encode(array_values($meses_receita)) ?>,
                    backgroundColor: 'rgba(212,175,55,0.7)',
                    borderColor: '#D4AF37',
                    borderWidth: 1
                },
                {
                    label: 'Custo Aquisição',
                    data: <?= json_encode(array_values($meses_custo)) ?>,
                    backgroundColor: 'rgba(244,67,54,0.4)',
                    borderColor: '#f44336',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { labels: { color: '#C0C0C0' } },
                tooltip: {
                    callbacks: {
                        label: function (ctx) {
                            return ctx.dataset.label + ': R$ ' +
                                ctx.parsed.y.toLocaleString('pt-BR', {minimumFractionDigits: 2});
                        }
                    }
                }
            },
            scales: {
                x: { ticks: { color: '#C0C0C0' }, grid: { color: 'rgba(255,255,255,0.05)' } },
                y: { ticks: { color: '#C0C0C0', callback: function(v){ return 'R$ '+v.toLocaleString('pt-BR'); } },
                     grid: { color: 'rgba(255,255,255,0.05)' } }
            }
        }
    });
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
