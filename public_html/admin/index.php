<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

$status_veiculo = [
    'disponivel' => 'Disponível',
    'reservado'  => 'Reservado',
    'vendido'    => 'Vendido',
    'inativo'    => 'Inativo',
];

$titulo_pagina = 'Dashboard';
$pagina_ativa  = 'dashboard';
$breadcrumb    = [];
$admin_root    = '';

require_once 'includes/header.php';

// ===== STATS =====
$total_disponiveis  = obterUmaLinha("SELECT COUNT(*) c FROM veiculos WHERE status = 'disponivel'")['c'];
$total_reservados   = obterUmaLinha("SELECT COUNT(*) c FROM veiculos WHERE status = 'reservado'")['c'];
$leads_novos        = obterUmaLinha("SELECT COUNT(*) c FROM leads WHERE tipo_lead = 'novo'")['c'];
$total_clientes     = obterUmaLinha("SELECT COUNT(*) c FROM clientes")['c'];

$row_vendas = obterUmaLinha(
    "SELECT COUNT(*) c, COALESCE(SUM(preco_venda - desconto_aplicado),0) v
     FROM vendas
     WHERE MONTH(data_venda)=MONTH(CURDATE()) AND YEAR(data_venda)=YEAR(CURDATE())
       AND status IN ('confirmada','entregue')"
);

// ===== LUCRO DO MÊS =====
$custo_mes = (float)(obterUmaLinha(
    "SELECT COALESCE(SUM(v.preco_custo),0) c
     FROM vendas vd JOIN veiculos v ON v.id=vd.veiculo_id
     WHERE MONTH(vd.data_venda)=MONTH(CURDATE()) AND YEAR(vd.data_venda)=YEAR(CURDATE())
       AND vd.status IN ('confirmada','entregue')"
)['c'] ?? 0);

// Despesas adicionais (custos_veiculo) dos carros vendidos no mês
$custos_adicionais_mes = (float)(obterUmaLinha(
    "SELECT COALESCE(SUM(cv.valor),0) c
     FROM custos_veiculo cv
     JOIN vendas vd ON vd.veiculo_id = cv.veiculo_id
     WHERE MONTH(vd.data_venda)=MONTH(CURDATE()) AND YEAR(vd.data_venda)=YEAR(CURDATE())
       AND vd.status IN ('confirmada','entregue')"
)['c'] ?? 0);

$lucro_mes   = (float)$row_vendas['v'] - $custo_mes;
$lucro_liq   = $lucro_mes - $custos_adicionais_mes;

// ===== FATURAMENTO 12 MESES =====
$fat_12m = obterTodas(
    "SELECT DATE_FORMAT(data_venda,'%Y-%m') as mes,
            COALESCE(SUM(preco_venda-desconto_aplicado),0) as total
     FROM vendas
     WHERE data_venda >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)
       AND status IN ('confirmada','entregue')
     GROUP BY DATE_FORMAT(data_venda,'%Y-%m')
     ORDER BY mes ASC"
);

// ===== CUSTOS PÓS-VENDA 12 MESES =====
$custos_12m = obterTodas(
    "SELECT DATE_FORMAT(vd.data_venda,'%Y-%m') as mes,
            COALESCE(SUM(cv.valor),0) as total
     FROM custos_veiculo cv
     JOIN vendas vd ON vd.veiculo_id = cv.veiculo_id
     WHERE vd.data_venda >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)
       AND vd.status IN ('confirmada','entregue')
     GROUP BY DATE_FORMAT(vd.data_venda,'%Y-%m')
     ORDER BY mes ASC"
);

// ===== RANKING VENDEDORES DO MÊS =====
$ranking_vendedores = obterTodas(
    "SELECT u.nome, COUNT(*) as qtd,
            COALESCE(SUM(vd.preco_venda-vd.desconto_aplicado),0) as faturamento
     FROM vendas vd JOIN usuarios u ON u.id=vd.vendedor_id
     WHERE MONTH(vd.data_venda)=MONTH(CURDATE()) AND YEAR(vd.data_venda)=YEAR(CURDATE())
       AND vd.status IN ('confirmada','entregue')
     GROUP BY vd.vendedor_id ORDER BY faturamento DESC LIMIT 5"
);

// ===== ÚLTIMOS LEADS =====
$ultimos_leads = obterTodas(
    "SELECT l.id, l.nome, l.telefone, l.email, l.tipo_lead, l.criado_em,
            v.marca, v.modelo
     FROM leads l
     LEFT JOIN veiculos v ON l.veiculo_id = v.id
     ORDER BY l.criado_em DESC LIMIT 6"
);

// ===== ÚLTIMOS VEÍCULOS =====
$ultimos_veiculos = obterTodas(
    "SELECT v.id, v.marca, v.modelo, v.ano, v.preco_venda, v.status,
            (SELECT vf.caminho FROM veiculos_fotos vf WHERE vf.veiculo_id=v.id AND vf.principal=1 LIMIT 1) as foto
     FROM veiculos v ORDER BY v.criado_em DESC LIMIT 6"
);
?>

<div class="page__header">
    <div>
        <h1 class="page__titulo">Dashboard</h1>
        <p class="page__subtitulo">Bem-vindo, <?= htmlspecialchars($_SESSION['usuario_nome']) ?>!</p>
    </div>
    <div class="page__acoes">
        <a href="veiculos/novo.php" class="btn-admin btn-admin--primary">+ Novo Veículo</a>
    </div>
</div>

<!-- STATS -->
<div class="stats-grid">
    <div class="stat-card stat-card--gold">
        <span class="stat-card__icone">🚗</span>
        <p class="stat-card__label">Disponíveis</p>
        <p class="stat-card__valor"><?= $total_disponiveis ?></p>
        <p class="stat-card__sub"><?= $total_reservados ?> reservados</p>
    </div>
    <div class="stat-card stat-card--blue">
        <span class="stat-card__icone">📬</span>
        <p class="stat-card__label">Leads Novos</p>
        <p class="stat-card__valor"><?= $leads_novos ?></p>
        <p class="stat-card__sub">aguardando contato</p>
    </div>
    <div class="stat-card stat-card--green">
        <span class="stat-card__icone">🏷️</span>
        <p class="stat-card__label">Vendas no Mês</p>
        <p class="stat-card__valor"><?= $row_vendas['c'] ?></p>
        <p class="stat-card__sub">negócios fechados</p>
    </div>
    <div class="stat-card stat-card--gold">
        <span class="stat-card__icone">💰</span>
        <p class="stat-card__label">Faturamento Bruto</p>
        <p class="stat-card__valor" style="font-size:1.35rem"><?= formatarMoeda($row_vendas['v']) ?></p>
        <p class="stat-card__sub">total de vendas do mês</p>
    </div>
    <div class="stat-card <?= $lucro_liq >= 0 ? 'stat-card--green' : 'stat-card--red' ?>">
        <span class="stat-card__icone">📈</span>
        <p class="stat-card__label">Lucro Líquido</p>
        <p class="stat-card__valor" style="font-size:1.35rem"><?= formatarMoeda($lucro_liq) ?></p>
        <p class="stat-card__sub">após custos e despesas</p>
    </div>
    <div class="stat-card stat-card--orange">
        <span class="stat-card__icone">🔧</span>
        <p class="stat-card__label">Custos Pós-Venda</p>
        <p class="stat-card__valor" style="font-size:1.35rem"><?= formatarMoeda($custos_adicionais_mes) ?></p>
        <p class="stat-card__sub">despesas em carros vendidos</p>
    </div>
    <div class="stat-card stat-card--purple">
        <span class="stat-card__icone">👥</span>
        <p class="stat-card__label">Clientes</p>
        <p class="stat-card__valor"><?= $total_clientes ?></p>
        <p class="stat-card__sub">cadastrados</p>
    </div>
</div>

<!-- GRÁFICO 12 MESES -->
<div class="widget" style="margin-bottom:1.5rem;padding:1.5rem">
    <div class="widget__header">
        <h2 class="widget__titulo">Faturamento — Últimos 12 Meses</h2>
        <a href="financeiro/dre.php" class="widget__link">DRE completo →</a>
    </div>
    <canvas id="chartFat12m" height="80"></canvas>
</div>

<!-- GRID PRINCIPAL -->
<div class="dashboard-grid">

    <!-- ÚLTIMOS LEADS -->
    <div class="widget">
        <div class="widget__header">
            <h2 class="widget__titulo">Últimos Leads</h2>
            <a href="leads/" class="widget__link">Ver todos →</a>
        </div>
        <?php if ($ultimos_leads): ?>
        <table class="table">
            <thead><tr>
                <th>Nome</th><th>Veículo</th><th>Contato</th><th>Status</th><th></th>
            </tr></thead>
            <tbody>
            <?php foreach ($ultimos_leads as $l): ?>
            <tr>
                <td class="td-titulo"><?= htmlspecialchars($l['nome']) ?></td>
                <td><?= $l['marca'] ? htmlspecialchars($l['marca'].' '.$l['modelo']) : '—' ?></td>
                <td><?= htmlspecialchars($l['telefone'] ?: ($l['email'] ?: '—')) ?></td>
                <td><span class="badge-admin badge-admin--<?= htmlspecialchars($l['tipo_lead']) ?>">
                    <?= ucfirst($l['tipo_lead']) ?></span></td>
                <td><a href="leads/?id=<?= $l['id'] ?>" class="btn-admin btn-admin--secondary btn-admin--sm">Ver</a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <div class="empty-state__icone">📬</div>
            <p class="empty-state__titulo">Nenhum lead ainda</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- RANKING DE VENDEDORES -->
    <div class="widget">
        <div class="widget__header">
            <h2 class="widget__titulo">Ranking Vendedores (mês)</h2>
            <a href="usuarios/" class="widget__link">Ver usuários →</a>
        </div>
        <?php if ($ranking_vendedores): ?>
        <table class="table">
            <thead><tr><th>#</th><th>Vendedor</th><th>Vendas</th><th>Faturamento</th></tr></thead>
            <tbody>
            <?php foreach ($ranking_vendedores as $i => $rv): ?>
            <tr>
                <td style="font-weight:700;color:<?= ['#D4AF37','#C0C0C0','#CD7F32'][$i] ?? 'inherit' ?>">
                    <?= $i + 1 ?>º
                </td>
                <td class="td-titulo"><?= htmlspecialchars($rv['nome']) ?></td>
                <td><?= $rv['qtd'] ?></td>
                <td><?= formatarMoeda($rv['faturamento']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <div class="empty-state__icone">🏆</div>
            <p class="empty-state__titulo">Sem vendas este mês</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- VEÍCULOS RECENTES -->
    <div class="widget">
        <div class="widget__header">
            <h2 class="widget__titulo">Recém Cadastrados</h2>
            <a href="veiculos/" class="widget__link">Ver todos →</a>
        </div>
        <?php if ($ultimos_veiculos): ?>
        <table class="table">
            <thead><tr><th>Veículo</th><th>Preço</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($ultimos_veiculos as $v): ?>
            <tr>
                <td class="td-titulo">
                    <?= htmlspecialchars($v['marca'].' '.$v['modelo']) ?>
                    <br><small style="color:#888"><?= $v['ano'] ?></small>
                </td>
                <td><?= formatarMoeda($v['preco_venda']) ?></td>
                <td><span class="badge-admin badge-admin--<?= $v['status'] ?>"><?= $status_veiculo[$v['status']] ?? $v['status'] ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <div class="empty-state__icone">🚗</div>
            <p class="empty-state__titulo">Nenhum veículo</p>
        </div>
        <?php endif; ?>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    var raw = <?php
        $labels   = [];
        $valores  = [];
        $custos_v = [];
        $map_fat  = [];
        $map_cus  = [];
        foreach ($fat_12m  as $r) { $map_fat[$r['mes']]  = (float)$r['total']; }
        foreach ($custos_12m as $r) { $map_cus[$r['mes']] = (float)$r['total']; }
        for ($i = 11; $i >= 0; $i--) {
            $d = date('Y-m', strtotime("-$i months"));
            $labels[]   = date('M/y', strtotime($d . '-01'));
            $valores[]  = $map_fat[$d]  ?? 0;
            $custos_v[] = $map_cus[$d]  ?? 0;
        }
        echo json_encode(['labels' => $labels, 'valores' => $valores, 'custos' => $custos_v]);
    ?>;

    var ctx = document.getElementById('chartFat12m');
    if (!ctx) return;

    var fatChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: raw.labels,
            datasets: [
                {
                    label: 'Faturamento Bruto',
                    data: raw.valores,
                    backgroundColor: 'rgba(212,175,55,0.65)',
                    borderColor: '#D4AF37',
                    borderWidth: 1,
                    borderRadius: 4,
                    order: 2
                },
                {
                    label: 'Custos Pós-Venda',
                    data: raw.custos,
                    type: 'line',
                    borderColor: '#f97316',
                    backgroundColor: 'rgba(249,115,22,0.15)',
                    borderWidth: 2,
                    pointBackgroundColor: '#f97316',
                    pointRadius: 4,
                    tension: 0.3,
                    fill: true,
                    order: 1
                }
            ]
        },
        options: {
            responsive: true,
            devicePixelRatio: 1.75,
            plugins: {
                legend: { display: true, labels: { color: '#ccc', boxWidth: 16, font: { size: 14, weight: 'bold', family: 'Segoe UI, system-ui, sans-serif' } } },
                tooltip: {
                    callbacks: {
                        label: function (c) {
                            return c.dataset.label + ': R$ ' + c.parsed.y.toLocaleString('pt-BR', {minimumFractionDigits:2});
                        }
                    }
                }
            },
            scales: {
                x: { ticks: { color: '#aaa', font: { size: 13 } }, grid: { color: 'rgba(255,255,255,0.04)' } },
                y: {
                    ticks: {
                        color: '#aaa',
                        font: { size: 13 },
                        callback: function (v) { return 'R$ ' + v.toLocaleString('pt-BR'); }
                    },
                    grid: { color: 'rgba(255,255,255,0.04)' }
                }
            }
        }
    });

    window.onThemeChange = function(isLight) {
        var tickColor   = isLight ? '#555' : '#aaa';
        var gridColor   = isLight ? 'rgba(0,0,0,0.06)' : 'rgba(255,255,255,0.04)';
        var legendColor = isLight ? '#333' : '#ccc';
        fatChart.options.plugins.legend.labels.color = legendColor;
        fatChart.options.scales.x.ticks.color = tickColor;
        fatChart.options.scales.x.grid.color  = gridColor;
        fatChart.options.scales.y.ticks.color = tickColor;
        fatChart.options.scales.y.grid.color  = gridColor;
        fatChart.update();
    };
    if (document.body.classList.contains('light')) window.onThemeChange(true);
})();
</script>

<?php require_once 'includes/footer.php'; ?>
