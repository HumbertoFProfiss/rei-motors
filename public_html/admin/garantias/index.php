<?php
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/functions.php';

requerAutenticacao();

$titulo_pagina = 'Garantias';
$pagina_ativa  = 'garantias';
$admin_root    = '../';
$breadcrumb    = [['label' => 'Garantias', 'url' => '']];

$status_garantia = [
    'aberto'       => 'Aberto',
    'em_andamento' => 'Em Andamento',
    'resolvido'    => 'Resolvido',
    'recusado'     => 'Recusado',
];

// Atualização rápida de status
if (isset($_GET['status_g'], $_GET['id'])) {
    $gid      = (int)$_GET['id'];
    $novo_st  = sanitizar($_GET['status_g']);
    if ($gid > 0 && array_key_exists($novo_st, $status_garantia)) {
        $upd = ['status' => $novo_st];
        if ($novo_st === 'resolvido') {
            $upd['data_resolucao'] = date('Y-m-d');
        }
        atualizar('garantias_chamados', $upd, 'id = ?', [$gid]);
    }
    header('Location: ' . ADMIN_URL . 'garantias/?msg=atualizado');
    exit;
}

// Filtros
$busca        = trim($_GET['busca'] ?? '');
$status_f     = trim($_GET['status'] ?? '');
$pagina       = max(1, (int)($_GET['pagina'] ?? 1));
$por_pagina   = 20;
$offset       = ($pagina - 1) * $por_pagina;

$where  = ['1=1'];
$params = [];

if ($busca) {
    $where[]  = "(c.nome LIKE ? OR v.marca LIKE ? OR v.modelo LIKE ? OR g.tipo_problema LIKE ?)";
    $like = "%$busca%";
    $params = array_merge($params, [$like, $like, $like, $like]);
}
if ($status_f && array_key_exists($status_f, $status_garantia)) {
    $where[]  = "g.status = ?";
    $params[] = $status_f;
}

$sql_base = "FROM garantias_chamados g
             LEFT JOIN veiculos v  ON v.id  = g.veiculo_id
             LEFT JOIN clientes c  ON c.id  = g.cliente_id
             WHERE " . implode(' AND ', $where);

$total    = (int)(obterUmaLinha("SELECT COUNT(*) as n $sql_base", $params)['n'] ?? 0);
$paginas  = max(1, (int)ceil($total / $por_pagina));
$chamados = obterTodas(
    "SELECT g.*, v.marca, v.modelo, v.ano, c.nome as cliente_nome, c.telefone as cliente_tel
     $sql_base ORDER BY g.data_abertura DESC LIMIT $por_pagina OFFSET $offset",
    $params
);

$msg = trim($_GET['msg'] ?? '');

// Totais de custo dos chamados resolvidos
$total_custos = (float)(obterUmaLinha(
    "SELECT COALESCE(SUM(custo_reparo),0) as t FROM garantias_chamados WHERE status NOT IN ('recusado')"
)['t'] ?? 0);

require_once __DIR__ . '/../includes/header.php';
?>

<?php if ($msg === 'salvo'):      ?><div class="alert-admin alert-admin--success">✓ Chamado salvo.</div><?php endif; ?>
<?php if ($msg === 'atualizado'): ?><div class="alert-admin alert-admin--success">✓ Status atualizado.</div><?php endif; ?>
<?php if ($msg === 'deletado'):   ?><div class="alert-admin alert-admin--success">✓ Chamado removido.</div><?php endif; ?>

<div class="page__header">
    <div>
        <h1 class="page__titulo">Garantias</h1>
        <p class="page__subtitulo"><?= $total ?> chamado(s) · Custo acumulado: <?= formatarMoeda($total_custos) ?></p>
    </div>
    <div class="page__acoes">
        <a href="novo.php" class="btn-admin btn-admin--primary">+ Novo Chamado</a>
    </div>
</div>

<!-- FILTROS -->
<div class="table-container" style="margin-bottom:1rem">
    <form method="GET" action="" style="padding:1rem;display:flex;gap:0.75rem;flex-wrap:wrap;align-items:flex-end">
        <div class="form-grupo" style="flex:1;min-width:200px;margin-bottom:0">
            <label>Busca</label>
            <input type="text" name="busca" value="<?= htmlspecialchars($busca) ?>" placeholder="Cliente, veículo, problema...">
        </div>
        <div class="form-grupo" style="min-width:160px;margin-bottom:0">
            <label>Status</label>
            <select name="status">
                <option value="">Todos</option>
                <?php foreach ($status_garantia as $k => $v): ?>
                <option value="<?= $k ?>" <?= $status_f === $k ? 'selected' : '' ?>><?= $v ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn-admin btn-admin--primary">Filtrar</button>
        <a href="." class="btn-admin btn-admin--secondary">Limpar</a>
    </form>
</div>

<div class="table-container">
    <div class="table-header">
        <h2 class="table-header__titulo">Chamados de Garantia</h2>
    </div>

    <?php if ($chamados): ?>
    <table class="table">
        <thead>
            <tr>
                <th>Data</th>
                <th>Veículo</th>
                <th>Cliente</th>
                <th>Problema</th>
                <th>Custo</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($chamados as $g): ?>
        <tr>
            <td><?= formatarData($g['data_abertura']) ?></td>
            <td class="td-titulo">
                <?php if ($g['marca']): ?>
                    <a href="../veiculos/editar.php?id=<?= $g['veiculo_id'] ?>" style="color:inherit">
                        <?= htmlspecialchars($g['marca'].' '.$g['modelo'].' '.$g['ano']) ?>
                    </a>
                <?php else: ?>—<?php endif; ?>
            </td>
            <td>
                <?= htmlspecialchars($g['cliente_nome'] ?? '—') ?>
                <?php if ($g['cliente_tel']): ?>
                <br><a href="https://wa.me/55<?= preg_replace('/\D/', '', $g['cliente_tel']) ?>" target="_blank" style="color:#25D366;font-size:0.8rem">
                    💬 <?= htmlspecialchars(formatarTelefone($g['cliente_tel'])) ?>
                </a>
                <?php endif; ?>
            </td>
            <td>
                <strong><?= htmlspecialchars($g['tipo_problema']) ?></strong>
                <?php if ($g['descricao']): ?>
                <br><small style="color:var(--admin-text-muted)"><?= htmlspecialchars(mb_substr($g['descricao'], 0, 80)) ?><?= mb_strlen($g['descricao']) > 80 ? '…' : '' ?></small>
                <?php endif; ?>
            </td>
            <td><?= $g['custo_reparo'] > 0 ? formatarMoeda($g['custo_reparo']) : '—' ?></td>
            <td>
                <select onchange="window.location='?id=<?= $g['id'] ?>&status_g='+this.value"
                        class="badge-select">
                    <?php foreach ($status_garantia as $k => $v): ?>
                    <option value="<?= $k ?>" <?= $g['status'] === $k ? 'selected' : '' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td class="td-acoes">
                <a href="editar.php?id=<?= $g['id'] ?>" class="btn-admin btn-admin--secondary btn-admin--sm">Editar</a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($paginas > 1): ?>
    <div class="paginacao-admin">
        <?php for ($p = 1; $p <= $paginas; $p++): ?>
            <?php $url_p = '?' . http_build_query(array_merge($_GET, ['pagina' => $p])); ?>
            <a href="<?= $url_p ?>" class="paginacao-admin__item <?= $p === $pagina ? 'paginacao-admin__item--ativo' : '' ?>"><?= $p ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <div class="empty-state">
        <div class="empty-state__icone">🛡️</div>
        <p class="empty-state__titulo">Nenhum chamado encontrado</p>
        <a href="novo.php" class="btn-admin btn-admin--primary" style="margin-top:1rem">+ Abrir primeiro chamado</a>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
