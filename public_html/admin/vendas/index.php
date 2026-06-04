<?php
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/functions.php';

$titulo_pagina = 'Vendas';
$pagina_ativa  = 'vendas';
$admin_root    = '../';
$breadcrumb    = [['label' => 'Vendas', 'url' => '']];

// Atualizar status da venda
if (isset($_GET['status_venda'], $_GET['id']) && ehAdmin()) {
    requerAutenticacao();
    $venda_id    = (int)$_GET['id'];
    $novo_status = trim($_GET['status_venda']);
    $validos     = ['pendente', 'confirmada', 'entregue', 'cancelada'];
    if (in_array($novo_status, $validos, true) && $venda_id > 0) {
        atualizar('vendas', ['status' => $novo_status], 'id = ?', [$venda_id]);
        // Se confirmada/entregue, marca veículo como vendido
        if (in_array($novo_status, ['confirmada', 'entregue'], true)) {
            $venda = obterUmaLinha("SELECT veiculo_id FROM vendas WHERE id = ?", [$venda_id]);
            if ($venda) {
                atualizar('veiculos', ['status' => 'vendido'], 'id = ?', [$venda['veiculo_id']]);
            }
        }
    }
    header('Location: ' . ADMIN_URL . 'vendas/?msg=atualizado');
    exit;
}

// Filtros
$status_f  = trim($_GET['status'] ?? '');
$mes_f     = trim($_GET['mes'] ?? '');
$pagina    = max(1, (int)($_GET['pagina'] ?? 1));
$por_pag   = 20;

$where  = ['1=1'];
$params = [];

$status_validos = ['pendente', 'confirmada', 'entregue', 'cancelada'];
if ($status_f !== '' && in_array($status_f, $status_validos, true)) {
    $where[]  = 've.status = ?';
    $params[] = $status_f;
}
if ($mes_f !== '' && preg_match('/^\d{4}-\d{2}$/', $mes_f)) {
    $where[]  = "DATE_FORMAT(ve.data_venda, '%Y-%m') = ?";
    $params[] = $mes_f;
}

$where_sql = implode(' AND ', $where);
$total     = (int)obterUmaLinha("SELECT COUNT(*) c FROM vendas ve WHERE $where_sql", $params)['c'];
$total_pag = max(1, (int)ceil($total / $por_pag));
$pagina    = min($pagina, $total_pag);
$offset    = ($pagina - 1) * $por_pag;

$vendas = obterTodas(
    "SELECT ve.*,
            v.marca, v.modelo, v.ano,
            c.nome AS cliente_nome,
            u.nome AS vendedor_nome
     FROM vendas ve
     JOIN veiculos v ON ve.veiculo_id = v.id
     JOIN clientes c ON ve.cliente_id = c.id
     JOIN usuarios u ON ve.vendedor_id = u.id
     WHERE $where_sql
     ORDER BY ve.data_venda DESC, ve.criado_em DESC LIMIT ? OFFSET ?",
    array_merge($params, [$por_pag, $offset])
);

// Totais do período filtrado
$totais = obterUmaLinha(
    "SELECT COUNT(*) c, COALESCE(SUM(preco_venda),0) faturamento, COALESCE(SUM(comissao_vendedor),0) comissao
     FROM vendas ve WHERE $where_sql AND ve.status IN ('confirmada','entregue')",
    $params
);

$msg = trim($_GET['msg'] ?? '');

require_once __DIR__ . '/../includes/header.php';
?>

<?php if ($msg === 'salvo'):      ?><div class="alert-admin alert-admin--success">✓ Venda registrada com sucesso.</div><?php endif; ?>
<?php if ($msg === 'atualizado'): ?><div class="alert-admin alert-admin--success">✓ Status atualizado.</div><?php endif; ?>

<div class="page__header">
    <div>
        <h1 class="page__titulo">Vendas</h1>
        <p class="page__subtitulo"><?= $total ?> venda<?= $total !== 1 ? 's' : '' ?> encontrada<?= $total !== 1 ? 's' : '' ?></p>
    </div>
    <div class="page__acoes">
        <a href="nova.php" class="btn-admin btn-admin--primary">+ Nova Venda</a>
    </div>
</div>

<!-- TOTAIS -->
<?php if ($totais['c'] > 0): ?>
<div class="stats-grid" style="margin-bottom:16px">
    <div class="stat-card stat-card--green">
        <p class="stat-card__label">Vendas Confirmadas</p>
        <p class="stat-card__valor"><?= $totais['c'] ?></p>
        <p class="stat-card__sub">no período</p>
    </div>
    <div class="stat-card stat-card--gold">
        <p class="stat-card__label">Faturamento</p>
        <p class="stat-card__valor" style="font-size:1.3rem"><?= formatarMoeda($totais['faturamento']) ?></p>
        <p class="stat-card__sub">vendas confirmadas + entregues</p>
    </div>
    <div class="stat-card stat-card--purple">
        <p class="stat-card__label">Total Comissões</p>
        <p class="stat-card__valor" style="font-size:1.3rem"><?= formatarMoeda($totais['comissao']) ?></p>
        <p class="stat-card__sub">a pagar vendedores</p>
    </div>
</div>
<?php endif; ?>

<!-- FILTROS -->
<form method="GET" class="filtros-admin">
    <div class="filtro-grupo">
        <label>Status</label>
        <select name="status">
            <option value="">Todos</option>
            <option value="pendente"   <?= $status_f === 'pendente' ? 'selected' : '' ?>>Pendente</option>
            <option value="confirmada" <?= $status_f === 'confirmada' ? 'selected' : '' ?>>Confirmada</option>
            <option value="entregue"   <?= $status_f === 'entregue' ? 'selected' : '' ?>>Entregue</option>
            <option value="cancelada"  <?= $status_f === 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
        </select>
    </div>
    <div class="filtro-grupo">
        <label>Mês</label>
        <input type="month" name="mes" value="<?= htmlspecialchars($mes_f) ?>">
    </div>
    <div class="filtro-grupo" style="padding-top:17px">
        <button type="submit" class="btn-admin btn-admin--secondary">Filtrar</button>
    </div>
    <?php if ($status_f !== '' || $mes_f !== ''): ?>
    <div class="filtro-grupo" style="padding-top:17px">
        <a href="?" class="btn-admin btn-admin--secondary">Limpar</a>
    </div>
    <?php endif; ?>
</form>

<div class="table-container">
    <div class="table-header">
        <h2 class="table-header__titulo">Lista de Vendas</h2>
    </div>

    <?php if ($vendas): ?>
    <table class="table">
        <thead>
            <tr>
                <th>Data</th>
                <th>Veículo</th>
                <th>Cliente</th>
                <th>Vendedor</th>
                <th>Forma Pag.</th>
                <th>Valor</th>
                <th>Comissão</th>
                <th>Garantia</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($vendas as $v): ?>
        <tr>
            <td style="white-space:nowrap"><?= formatarData($v['data_venda']) ?></td>
            <td class="td-titulo">
                <?= htmlspecialchars($v['marca'].' '.$v['modelo']) ?>
                <br><small style="color:#454545"><?= $v['ano'] ?></small>
            </td>
            <td><?= htmlspecialchars($v['cliente_nome']) ?></td>
            <td><?= htmlspecialchars($v['vendedor_nome']) ?></td>
            <td><?= $formas_pagamento[$v['forma_pagamento']] ?? $v['forma_pagamento'] ?></td>
            <td><?= formatarMoeda($v['preco_venda']) ?>
                <?php if ($v['desconto_aplicado'] > 0): ?>
                <br><small style="color:#f97316">-<?= formatarMoeda($v['desconto_aplicado']) ?></small>
                <?php endif; ?>
            </td>
            <td><?= formatarMoeda($v['comissao_vendedor']) ?></td>
            <td>
                <?php
                $prazo_d = (int)($v['prazo_garantia_dias'] ?? 0);
                if ($prazo_d > 0 && !empty($v['data_entrega'])) {
                    $fim  = strtotime($v['data_entrega']) + $prazo_d * 86400;
                    $rest = (int)ceil(($fim - time()) / 86400);
                    if ($rest > 30) {
                        echo '<span class="badge-admin badge-admin--green">✓ ' . $rest . 'd</span>';
                    } elseif ($rest > 0) {
                        echo '<span class="badge-admin badge-admin--gold">⚠ ' . $rest . 'd</span>';
                    } else {
                        echo '<span class="badge-admin badge-admin--cancelada">Expirada</span>';
                    }
                } elseif ($prazo_d > 0) {
                    echo '<small style="color:#555">' . $prazo_d . 'd</small>';
                } else {
                    echo '<small style="color:#333">—</small>';
                }
                ?>
            </td>
            <td><span class="badge-admin badge-admin--<?= $v['status'] ?>"><?= ucfirst($v['status']) ?></span></td>
            <td class="td-acoes">
                <?php if (ehAdmin()): ?>
                <select onchange="window.location='?id=<?= $v['id'] ?>&status_venda='+this.value"
                        style="background:#0A0A0A;border:1px solid #252525;color:#C0C0C0;border-radius:5px;padding:3px 6px;font-size:0.72rem;cursor:pointer">
                    <option value="">Status...</option>
                    <option value="pendente">Pendente</option>
                    <option value="confirmada">Confirmada</option>
                    <option value="entregue">Entregue</option>
                    <option value="cancelada">Cancelada</option>
                </select>
                <?php endif; ?>
                <div style="margin-top:4px;display:flex;flex-direction:column;gap:2px">
                    <a href="../contratos/gerar.php?tipo=compra_venda&venda_id=<?= $v['id'] ?>" target="_blank"
                       class="btn-admin btn-admin--secondary btn-admin--sm" style="font-size:0.65rem">📄 C&V</a>
                    <a href="../contratos/gerar.php?tipo=procuracao&venda_id=<?= $v['id'] ?>" target="_blank"
                       class="btn-admin btn-admin--secondary btn-admin--sm" style="font-size:0.65rem">📋 Proc.</a>
                </div>
            </td>
        </tr>
        <?php if (!empty($v['observacoes'])): ?>
        <tr>
            <td></td>
            <td colspan="8" style="color:#454545;font-size:0.72rem;padding-top:0;padding-bottom:10px">
                💬 <?= nl2br(htmlspecialchars($v['observacoes'])) ?>
            </td>
        </tr>
        <?php endif; ?>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($total_pag > 1):
        $qb = http_build_query(array_filter(['status' => $status_f, 'mes' => $mes_f]));
        $sep = $qb ? '&' : '';
    ?>
    <div class="paginacao-admin">
        <?php if ($pagina > 1): ?><a href="?<?= $qb.$sep ?>pagina=<?= $pagina-1 ?>">‹</a>
        <?php else: ?><span class="disabled">‹</span><?php endif; ?>
        <?php for ($i = 1; $i <= $total_pag; $i++):
            if ($i === $pagina): ?><span class="ativo"><?= $i ?></span>
            <?php elseif ($i <= 2 || $i >= $total_pag-1 || abs($i-$pagina) <= 1): ?>
            <a href="?<?= $qb.$sep ?>pagina=<?= $i ?>"><?= $i ?></a>
            <?php elseif ($i === 3 || $i === $total_pag-2): ?><span>…</span>
            <?php endif;
        endfor; ?>
        <?php if ($pagina < $total_pag): ?><a href="?<?= $qb.$sep ?>pagina=<?= $pagina+1 ?>">›</a>
        <?php else: ?><span class="disabled">›</span><?php endif; ?>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <div class="empty-state">
        <div class="empty-state__icone">💰</div>
        <p class="empty-state__titulo">Nenhuma venda encontrada</p>
        <p class="empty-state__texto">Registre a primeira venda ou ajuste os filtros.</p>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
