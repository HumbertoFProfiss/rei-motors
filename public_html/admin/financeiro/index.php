<?php
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/functions.php';

requerAdmin();

$titulo_pagina = 'Contas a Pagar';
$pagina_ativa  = 'financeiro';
$admin_root    = '../';
$breadcrumb    = [
    ['label' => 'Financeiro',      'url' => ''],
    ['label' => 'Contas a Pagar',  'url' => ''],
];

$categorias_despesas = [
    'revisao'      => 'Revisão / Mecânica',
    'funilaria'    => 'Funilaria / Pintura',
    'limpeza'      => 'Limpeza / Estética',
    'documentacao' => 'Documentação / Taxas',
    'pneus'        => 'Pneus / Suspensão',
    'eletrica'     => 'Elétrica / Eletrônica',
    'garantia'     => 'Garantia / Pós-Venda',
    'outros'       => 'Outros',
];

$status_conta = [
    'pendente'  => 'Pendente',
    'paga'      => 'Paga',
    'vencida'   => 'Vencida',
    'cancelada' => 'Cancelada',
];

// Marcar paga / cancelada
if (isset($_GET['marcar'], $_GET['id'])) {
    $cid    = (int)$_GET['id'];
    $acao   = sanitizar($_GET['marcar']);
    if ($cid > 0 && in_array($acao, ['paga', 'cancelada'], true)) {
        $upd = ['status' => $acao];
        if ($acao === 'paga') $upd['data_pagamento'] = date('Y-m-d');
        atualizar('contas_pagar', $upd, 'id = ?', [$cid]);

        // Gerar próxima recorrência se aplicável
        if ($acao === 'paga') {
            $conta = obterUmaLinha("SELECT * FROM contas_pagar WHERE id = ?", [$cid]);
            if ($conta && ($conta['recorrencia'] ?? 'nenhuma') !== 'nenhuma') {
                $intervalos = ['mensal' => '+1 month', 'bimestral' => '+2 months', 'trimestral' => '+3 months', 'anual' => '+1 year'];
                $intervalo  = $intervalos[$conta['recorrencia']] ?? null;
                if ($intervalo) {
                    $prox_venc = date('Y-m-d', strtotime($conta['data_vencimento'] . ' ' . $intervalo));
                    $ja_existe = obterUmaLinha(
                        "SELECT id FROM contas_pagar WHERE descricao = ? AND data_vencimento = ? AND status = 'pendente'",
                        [$conta['descricao'], $prox_venc]
                    );
                    if (!$ja_existe) {
                        inserir('contas_pagar', [
                            'descricao'       => $conta['descricao'],
                            'valor'           => $conta['valor'],
                            'data_vencimento' => $prox_venc,
                            'categoria'       => $conta['categoria'],
                            'status'          => 'pendente',
                            'recorrencia'     => $conta['recorrencia'],
                            'observacoes'     => $conta['observacoes'],
                        ]);
                    }
                }
            }
        }
    }
    header('Location: ' . ADMIN_URL . 'financeiro/?msg=atualizado');
    exit;
}

// DELETE
if (isset($_GET['deletar'])) {
    $cid = (int)$_GET['deletar'];
    if ($cid > 0) executarQuery("DELETE FROM contas_pagar WHERE id = ?", [$cid]);
    header('Location: ' . ADMIN_URL . 'financeiro/?msg=deletado');
    exit;
}

// ADD
$recorrentes = [
    'aluguel'   => ['label' => 'Aluguel',          'categoria' => 'outros'],
    'agua'      => ['label' => 'Água',              'categoria' => 'outros'],
    'luz'       => ['label' => 'Luz',               'categoria' => 'outros'],
    'internet'  => ['label' => 'Internet/Telefone', 'categoria' => 'outros'],
    'limpeza'   => ['label' => 'Limpeza',           'categoria' => 'limpeza'],
    'marketing' => ['label' => 'Marketing',         'categoria' => 'outros'],
    'trafego'   => ['label' => 'Tráfego Pago',      'categoria' => 'outros'],
    'outros'    => ['label' => 'Outros',            'categoria' => 'outros'],
];

$erros   = [];
$sucesso = '';

$recorrencias = [
    'nenhuma'     => 'Sem recorrência',
    'mensal'      => 'Mensal',
    'bimestral'   => 'Bimestral',
    'trimestral'  => 'Trimestral',
    'anual'       => 'Anual',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $desc       = sanitizar($_POST['descricao']      ?? '');
    $valor      = (float)str_replace(['.', ','], ['', '.'], $_POST['valor'] ?? '0');
    $venc       = sanitizar($_POST['data_vencimento'] ?? '');
    $categoria  = sanitizar($_POST['categoria']       ?? '');
    $nr_nf      = sanitizar($_POST['numero_nf']       ?? '');
    $obs        = sanitizar($_POST['observacoes']     ?? '');
    $recorr     = array_key_exists($_POST['recorrencia'] ?? '', $recorrencias) ? $_POST['recorrencia'] : 'nenhuma';

    if (empty($desc))    $erros[] = 'Descrição é obrigatória.';
    if ($valor <= 0)     $erros[] = 'Valor inválido.';
    if (empty($venc))    $erros[] = 'Data de vencimento é obrigatória.';

    if (empty($erros)) {
        inserir('contas_pagar', [
            'descricao'       => $desc,
            'valor'           => $valor,
            'data_vencimento' => $venc,
            'categoria'       => $categoria ?: null,
            'status'          => 'pendente',
            'numero_nf'       => $nr_nf ?: null,
            'observacoes'     => $obs ?: null,
            'recorrencia'     => $recorr,
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
    "SELECT * FROM contas_pagar WHERE " . implode(' AND ', $where) . " ORDER BY data_vencimento ASC",
    $params
);

// Totais
$total_pendente = 0;
$total_pago     = 0;
foreach ($contas as $c) {
    if ($c['status'] === 'pendente' || $c['status'] === 'vencida') $total_pendente += (float)$c['valor'];
    if ($c['status'] === 'paga') $total_pago += (float)$c['valor'];
}

$msg = trim($_GET['msg'] ?? '');

require_once __DIR__ . '/../includes/header.php';
?>

<?php if ($msg === 'atualizado'): ?><div class="alert-admin alert-admin--success">✓ Status atualizado.</div><?php endif; ?>
<?php if ($msg === 'deletado'):   ?><div class="alert-admin alert-admin--success">✓ Conta removida.</div><?php endif; ?>
<?php if ($msg === 'recorrente'): ?><div class="alert-admin alert-admin--success">✓ Despesa do mês adicionada — atualize o valor.</div><?php endif; ?>
<?php if ($sucesso): ?><div class="alert-admin alert-admin--success">✓ <?= htmlspecialchars($sucesso) ?></div><?php endif; ?>
<?php if ($erros): ?>
<div class="alert-admin alert-admin--error">
    <?php foreach ($erros as $e): ?><?= htmlspecialchars($e) ?><br><?php endforeach; ?>
</div>
<?php endif; ?>

<!-- NAV FINANCEIRO -->
<div style="display:flex;gap:0.5rem;margin-bottom:1.5rem;flex-wrap:wrap">
    <a href="." class="btn-admin btn-admin--primary btn-admin--sm">📤 Contas a Pagar</a>
    <a href="receber.php" class="btn-admin btn-admin--secondary btn-admin--sm">📥 Contas a Receber</a>
    <a href="fluxo.php" class="btn-admin btn-admin--secondary btn-admin--sm">💹 Fluxo de Caixa</a>
    <a href="dre.php" class="btn-admin btn-admin--secondary btn-admin--sm">📊 DRE</a>
</div>

<div class="page__header">
    <div>
        <h1 class="page__titulo">Contas a Pagar</h1>
    </div>
</div>

<div class="stats-grid" style="margin-bottom:1.5rem">
    <div class="stat-card stat-card--red">
        <div class="stat-card__label">A Pagar / Vencido</div>
        <div class="stat-card__valor"><?= formatarMoeda($total_pendente) ?></div>
    </div>
    <div class="stat-card stat-card--green">
        <div class="stat-card__label">Pago (filtro atual)</div>
        <div class="stat-card__valor"><?= formatarMoeda($total_pago) ?></div>
    </div>
</div>

<!-- ATALHOS DESPESAS FIXAS -->
<div class="table-container" style="margin-bottom:1rem">
    <div class="table-header"><h2 class="table-header__titulo">⚡ Atalhos — Despesas Fixas</h2></div>
    <div style="padding:1rem;display:flex;gap:8px;flex-wrap:wrap;align-items:center">
        <span style="color:var(--admin-text-muted);font-size:0.78rem;margin-right:4px">Pré-preencher formulário:</span>
        <?php foreach ($recorrentes as $chave => $info): ?>
        <button type="button"
                onclick="atalhoRecorrente('<?= htmlspecialchars($info['label'], ENT_QUOTES) ?>', '<?= $info['categoria'] ?>')"
                class="btn-admin btn-admin--secondary btn-admin--sm" style="font-size:0.75rem">
            <?= htmlspecialchars($info['label']) ?>
        </button>
        <?php endforeach; ?>
    </div>
</div>

<!-- ADICIONAR -->
<div class="table-container" style="margin-bottom:1.5rem">
    <div class="table-header"><h2 class="table-header__titulo">Nova Conta a Pagar</h2></div>
    <div style="padding:1.5rem">
        <form method="POST" action="">
            <div class="form-grid">
                <div class="form-grupo form-grupo--2">
                    <label>Descrição <span class="obrigatorio">*</span></label>
                    <input type="text" name="descricao" required value="<?= htmlspecialchars($_POST['descricao'] ?? '') ?>" placeholder="Ex: Aluguel do espaço, IPVA veículo XYZ...">
                </div>
                <div class="form-grupo">
                    <label>Valor (R$) <span class="obrigatorio">*</span></label>
                    <input type="text" name="valor" required value="<?= htmlspecialchars($_POST['valor'] ?? '') ?>" placeholder="0,00">
                </div>
                <div class="form-grupo">
                    <label>Vencimento <span class="obrigatorio">*</span></label>
                    <input type="date" name="data_vencimento" required value="<?= htmlspecialchars($_POST['data_vencimento'] ?? '') ?>">
                </div>
                <div class="form-grupo">
                    <label>Categoria</label>
                    <select name="categoria">
                        <option value="">Selecione...</option>
                        <?php foreach ($categorias_despesas as $k => $v): ?>
                        <option value="<?= $k ?>" <?= ($_POST['categoria'] ?? '') === $k ? 'selected' : '' ?>><?= htmlspecialchars($v) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-grupo">
                    <label>Recorrência</label>
                    <select name="recorrencia">
                        <?php foreach ($recorrencias as $k => $v): ?>
                        <option value="<?= $k ?>" <?= ($_POST['recorrencia'] ?? 'nenhuma') === $k ? 'selected' : '' ?>><?= htmlspecialchars($v) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-grupo">
                    <label>Número da NF</label>
                    <input type="text" name="numero_nf" value="<?= htmlspecialchars($_POST['numero_nf'] ?? '') ?>">
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
        <a href="." class="btn-admin btn-admin--secondary">Limpar</a>
    </form>

    <div class="table-header"><h2 class="table-header__titulo">Contas (<?= count($contas) ?>)</h2></div>

    <?php if ($contas): ?>
    <table class="table">
        <thead>
            <tr><th>Vencimento</th><th>Descrição</th><th>Categoria</th><th>Recorrência</th><th>Valor</th><th>Status</th><th>Ações</th></tr>
        </thead>
        <tbody>
        <?php foreach ($contas as $c): ?>
        <?php
            $hoje     = date('Y-m-d');
            $vencida  = $c['status'] === 'pendente' && $c['data_vencimento'] < $hoje;
            $st_real  = $vencida ? 'vencida' : $c['status'];
            $badge    = ['pendente' => 'warning', 'paga' => 'ativo', 'vencida' => 'danger', 'cancelada' => 'inativo'][$st_real] ?? 'info';
        ?>
        <tr>
            <td><?= formatarData($c['data_vencimento']) ?>
                <?php if ($c['data_pagamento']): ?><br><small style="color:#aaa">Pago: <?= formatarData($c['data_pagamento']) ?></small><?php endif; ?>
            </td>
            <td class="td-titulo"><?= htmlspecialchars($c['descricao']) ?></td>
            <td><?= $c['categoria'] ? htmlspecialchars($categorias_despesas[$c['categoria']] ?? $c['categoria']) : '—' ?></td>
            <td><?= ($c['recorrencia'] ?? 'nenhuma') !== 'nenhuma' ? '<span style="color:#D4AF37;font-size:0.75rem">🔄 ' . htmlspecialchars($recorrencias[$c['recorrencia']] ?? '') . '</span>' : '<span style="color:#444">—</span>' ?></td>
            <td><?= formatarMoeda($c['valor']) ?></td>
            <td><span class="badge-admin badge-admin--<?= $badge ?>"><?= $status_conta[$st_real] ?? $st_real ?></span></td>
            <td class="td-acoes">
                <?php if ($c['status'] === 'pendente'): ?>
                <a href="?marcar=paga&id=<?= $c['id'] ?>" class="btn-admin btn-admin--success btn-admin--sm">✓ Pagar</a>
                <?php endif; ?>
                <button onclick="confirmarAcao('?deletar=<?= $c['id'] ?>', 'Remover conta', 'Deseja remover esta conta?')"
                        class="btn-admin btn-admin--danger btn-admin--sm">✕</button>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="empty-state">
        <div class="empty-state__icone">📤</div>
        <p class="empty-state__titulo">Nenhuma conta encontrada</p>
    </div>
    <?php endif; ?>
</div>

<script>
function atalhoRecorrente(descricao, categoria) {
    var form = document.querySelector('form[method="POST"]');
    form.querySelector('[name="descricao"]').value    = descricao;
    form.querySelector('[name="categoria"]').value    = categoria;
    form.querySelector('[name="recorrencia"]').value  = 'mensal';
    var campoValor = form.querySelector('[name="valor"]');
    campoValor.value = '';
    form.scrollIntoView({ behavior: 'smooth', block: 'start' });
    setTimeout(function() { campoValor.focus(); }, 350);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
