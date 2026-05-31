<?php
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/functions.php';

requerAdmin();

$titulo_pagina = 'Contas a Receber';
$pagina_ativa  = 'financeiro';
$admin_root    = '../';
$breadcrumb    = [
    ['label' => 'Financeiro',       'url' => './'],
    ['label' => 'Contas a Receber', 'url' => ''],
];

$status_conta = [
    'pendente'  => 'Pendente',
    'recebida'  => 'Recebida',
    'vencida'   => 'Vencida',
    'cancelada' => 'Cancelada',
];

// Marcar recebida / cancelada
if (isset($_GET['marcar'], $_GET['id'])) {
    $cid  = (int)$_GET['id'];
    $acao = sanitizar($_GET['marcar']);
    if ($cid > 0 && in_array($acao, ['recebida', 'cancelada'], true)) {
        $upd = ['status' => $acao];
        if ($acao === 'recebida') $upd['data_pagamento'] = date('Y-m-d');
        atualizar('contas_receber', $upd, 'id = ?', [$cid]);
    }
    header('Location: ' . ADMIN_URL . 'financeiro/receber.php?msg=atualizado');
    exit;
}

// DELETE
if (isset($_GET['deletar'])) {
    $cid = (int)$_GET['deletar'];
    if ($cid > 0) executarQuery("DELETE FROM contas_receber WHERE id = ?", [$cid]);
    header('Location: ' . ADMIN_URL . 'financeiro/receber.php?msg=deletado');
    exit;
}

$erros   = [];
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $desc    = sanitizar($_POST['descricao']      ?? '');
    $valor   = (float)str_replace(['.', ','], ['', '.'], $_POST['valor'] ?? '0');
    $venc    = sanitizar($_POST['data_vencimento'] ?? '');
    $obs     = sanitizar($_POST['observacoes']     ?? '');

    if (empty($desc))  $erros[] = 'Descrição é obrigatória.';
    if ($valor <= 0)   $erros[] = 'Valor inválido.';
    if (empty($venc))  $erros[] = 'Data de vencimento é obrigatória.';

    if (empty($erros)) {
        inserir('contas_receber', [
            'descricao'       => $desc,
            'valor'           => $valor,
            'data_vencimento' => $venc,
            'status'          => 'pendente',
            'observacoes'     => $obs ?: null,
        ]);
        $sucesso = 'Conta adicionada.';
    }
}

// Filtros
$status_f = trim($_GET['status'] ?? '');
$mes_f    = trim($_GET['mes']    ?? '');
$where    = ['1=1'];
$params   = [];

if ($status_f && array_key_exists($status_f, $status_conta)) {
    $where[] = "status = ?"; $params[] = $status_f;
}
if ($mes_f) {
    $where[] = "DATE_FORMAT(data_vencimento, '%Y-%m') = ?"; $params[] = $mes_f;
}

$contas = obterTodas(
    "SELECT cr.*, vd.numero_contrato, cl.nome as cliente_nome
     FROM contas_receber cr
     LEFT JOIN vendas vd ON vd.id = cr.venda_id
     LEFT JOIN clientes cl ON cl.id = vd.cliente_id
     WHERE " . implode(' AND ', array_map(fn($w) => "cr.$w", str_replace(['1=1'], ['1=1'], [$where[0]])) + $where) . "
     ORDER BY cr.data_vencimento ASC",
    $params
);

// Fallback query mais simples
$contas = obterTodas(
    "SELECT cr.*, vd.numero_contrato, cl.nome as cliente_nome
     FROM contas_receber cr
     LEFT JOIN vendas vd ON vd.id = cr.venda_id
     LEFT JOIN clientes cl ON cl.id = vd.cliente_id
     WHERE cr.status" . ($status_f ? " = '$status_f'" : " IS NOT NULL") .
     ($mes_f ? " AND DATE_FORMAT(cr.data_vencimento,'%Y-%m') = '$mes_f'" : "") .
     " ORDER BY cr.data_vencimento ASC"
);

// Totais
$total_a_receber = 0;
$total_recebido  = 0;
foreach ($contas as $c) {
    if (in_array($c['status'], ['pendente', 'vencida'])) $total_a_receber += (float)$c['valor'];
    if ($c['status'] === 'recebida') $total_recebido += (float)$c['valor'];
}

$msg = trim($_GET['msg'] ?? '');

require_once __DIR__ . '/../includes/header.php';
?>

<?php if ($msg === 'atualizado'): ?><div class="alert-admin alert-admin--success">✓ Status atualizado.</div><?php endif; ?>
<?php if ($msg === 'deletado'):   ?><div class="alert-admin alert-admin--success">✓ Registro removido.</div><?php endif; ?>
<?php if ($sucesso): ?><div class="alert-admin alert-admin--success">✓ <?= htmlspecialchars($sucesso) ?></div><?php endif; ?>
<?php if ($erros): ?>
<div class="alert-admin alert-admin--error">
    <?php foreach ($erros as $e): ?><?= htmlspecialchars($e) ?><br><?php endforeach; ?>
</div>
<?php endif; ?>

<!-- NAV FINANCEIRO -->
<div style="display:flex;gap:0.5rem;margin-bottom:1.5rem;flex-wrap:wrap">
    <a href="." class="btn-admin btn-admin--secondary btn-admin--sm">📤 Contas a Pagar</a>
    <a href="receber.php" class="btn-admin btn-admin--primary btn-admin--sm">📥 Contas a Receber</a>
    <a href="fluxo.php" class="btn-admin btn-admin--secondary btn-admin--sm">💹 Fluxo de Caixa</a>
    <a href="dre.php" class="btn-admin btn-admin--secondary btn-admin--sm">📊 DRE</a>
</div>

<div class="page__header">
    <div>
        <h1 class="page__titulo">Contas a Receber</h1>
    </div>
</div>

<div class="stats-grid" style="margin-bottom:1.5rem">
    <div class="stat-card stat-card--blue">
        <div class="stat-card__label">A Receber</div>
        <div class="stat-card__valor"><?= formatarMoeda($total_a_receber) ?></div>
    </div>
    <div class="stat-card stat-card--green">
        <div class="stat-card__label">Recebido (filtro)</div>
        <div class="stat-card__valor"><?= formatarMoeda($total_recebido) ?></div>
    </div>
</div>

<!-- ADICIONAR -->
<div class="table-container" style="margin-bottom:1.5rem">
    <div class="table-header"><h2 class="table-header__titulo">Nova Conta a Receber</h2></div>
    <div style="padding:1.5rem">
        <form method="POST" action="">
            <div class="form-grid">
                <div class="form-grupo form-grupo--2">
                    <label>Descrição <span class="obrigatorio">*</span></label>
                    <input type="text" name="descricao" required value="<?= htmlspecialchars($_POST['descricao'] ?? '') ?>"
                           placeholder="Ex: Parcela financiamento cliente João">
                </div>
                <div class="form-grupo">
                    <label>Valor (R$) <span class="obrigatorio">*</span></label>
                    <input type="text" name="valor" required value="<?= htmlspecialchars($_POST['valor'] ?? '') ?>" placeholder="0,00">
                </div>
                <div class="form-grupo">
                    <label>Vencimento <span class="obrigatorio">*</span></label>
                    <input type="date" name="data_vencimento" required value="<?= htmlspecialchars($_POST['data_vencimento'] ?? '') ?>">
                </div>
                <div class="form-grupo form-grupo--2">
                    <label>Observações</label>
                    <input type="text" name="observacoes" value="<?= htmlspecialchars($_POST['observacoes'] ?? '') ?>" placeholder="Ex: Contrato REI-2025-001">
                </div>
            </div>
            <div class="form-acoes" style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--admin-border)">
                <button type="submit" class="btn-admin btn-admin--primary">+ Adicionar</button>
            </div>
        </form>
    </div>
</div>

<!-- FILTROS + LISTA -->
<div class="table-container">
    <form method="GET" action="" style="padding:1rem;display:flex;gap:0.75rem;flex-wrap:wrap;align-items:flex-end;border-bottom:1px solid var(--admin-border)">
        <div class="form-grupo" style="min-width:160px;margin-bottom:0">
            <label>Status</label>
            <select name="status">
                <option value="">Todos</option>
                <?php foreach ($status_conta as $k => $v): ?>
                <option value="<?= $k ?>" <?= $status_f === $k ? 'selected' : '' ?>><?= $v ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-grupo" style="margin-bottom:0">
            <label>Mês</label>
            <input type="month" name="mes" value="<?= htmlspecialchars($mes_f) ?>">
        </div>
        <button type="submit" class="btn-admin btn-admin--primary">Filtrar</button>
        <a href="receber.php" class="btn-admin btn-admin--secondary">Limpar</a>
    </form>

    <div class="table-header"><h2 class="table-header__titulo">Contas (<?= count($contas) ?>)</h2></div>

    <?php if ($contas): ?>
    <table class="table">
        <thead>
            <tr><th>Vencimento</th><th>Descrição</th><th>Cliente</th><th>Valor</th><th>Status</th><th>Ações</th></tr>
        </thead>
        <tbody>
        <?php foreach ($contas as $c): ?>
        <?php
            $hoje    = date('Y-m-d');
            $vencida = $c['status'] === 'pendente' && $c['data_vencimento'] < $hoje;
            $st_real = $vencida ? 'vencida' : $c['status'];
            $badge   = ['pendente' => 'warning', 'recebida' => 'ativo', 'vencida' => 'danger', 'cancelada' => 'inativo'][$st_real] ?? 'info';
        ?>
        <tr>
            <td><?= formatarData($c['data_vencimento']) ?>
                <?php if ($c['data_pagamento']): ?><br><small style="color:#aaa">Rec: <?= formatarData($c['data_pagamento']) ?></small><?php endif; ?>
            </td>
            <td class="td-titulo"><?= htmlspecialchars($c['descricao']) ?>
                <?php if ($c['observacoes']): ?><br><small style="color:var(--admin-text-muted)"><?= htmlspecialchars($c['observacoes']) ?></small><?php endif; ?>
            </td>
            <td><?= $c['cliente_nome'] ? htmlspecialchars($c['cliente_nome']) : '—' ?></td>
            <td><?= formatarMoeda($c['valor']) ?></td>
            <td><span class="badge-admin badge-admin--<?= $badge ?>"><?= $status_conta[$st_real] ?? $st_real ?></span></td>
            <td class="td-acoes">
                <?php if ($c['status'] === 'pendente'): ?>
                <a href="?marcar=recebida&id=<?= $c['id'] ?>" class="btn-admin btn-admin--success btn-admin--sm">✓ Recebido</a>
                <?php endif; ?>
                <button onclick="confirmarAcao('?deletar=<?= $c['id'] ?>', 'Remover conta', 'Deseja remover este registro?')"
                        class="btn-admin btn-admin--danger btn-admin--sm">✕</button>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="empty-state">
        <div class="empty-state__icone">📥</div>
        <p class="empty-state__titulo">Nenhuma conta encontrada</p>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
