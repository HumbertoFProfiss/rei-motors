<?php
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/functions.php';

$titulo_pagina = 'Veículos';
$pagina_ativa  = 'veiculos';
$admin_root    = '../';
$breadcrumb    = [['label' => 'Veículos', 'url' => '']];

// Ação de exclusão (admin only)
if (isset($_GET['deletar']) && ehAdmin()) {
    requerAutenticacao();
    $id_del = (int)$_GET['deletar'];
    if ($id_del > 0) {
        // Fotos são excluídas em cascade pelo FK
        deletar('veiculos', 'id = ?', [$id_del]);
        header('Location: ' . ADMIN_URL . 'veiculos/?msg=deletado');
        exit;
    }
}

// Filtros
$busca          = trim($_GET['busca'] ?? '');
$status_filtro  = trim($_GET['status'] ?? '');
$pagina         = max(1, (int)($_GET['pagina'] ?? 1));
$por_pagina     = 20;
$offset         = ($pagina - 1) * $por_pagina;

$where  = ['1=1'];
$params = [];

if ($busca !== '') {
    $where[]  = '(v.marca LIKE ? OR v.modelo LIKE ? OR v.placa LIKE ? OR v.numero_chassi LIKE ?)';
    $params[] = "%$busca%";
    $params[] = "%$busca%";
    $params[] = "%$busca%";
    $params[] = "%$busca%";
}
if ($status_filtro !== '' && array_key_exists($status_filtro, $status_veiculo)) {
    $where[]  = 'v.status = ?';
    $params[] = $status_filtro;
}

$where_sql = implode(' AND ', $where);

$total         = (int)obterUmaLinha("SELECT COUNT(*) c FROM veiculos v WHERE $where_sql", $params)['c'];
$total_paginas = max(1, (int)ceil($total / $por_pagina));
$pagina        = min($pagina, $total_paginas);
$offset        = ($pagina - 1) * $por_pagina;

$veiculos = obterTodas(
    "SELECT v.*,
            (SELECT vf.caminho FROM veiculos_fotos vf WHERE vf.veiculo_id=v.id AND vf.principal=1 LIMIT 1) as foto
     FROM veiculos v WHERE $where_sql ORDER BY v.criado_em DESC LIMIT ? OFFSET ?",
    array_merge($params, [$por_pagina, $offset])
);

$msg = trim($_GET['msg'] ?? '');

require_once __DIR__ . '/../includes/header.php';
?>

<?php if ($msg === 'salvo'):    ?><div class="alert-admin alert-admin--success">✓ Veículo salvo com sucesso.</div><?php endif; ?>
<?php if ($msg === 'deletado'): ?><div class="alert-admin alert-admin--success">✓ Veículo excluído.</div><?php endif; ?>

<div class="page__header">
    <div>
        <h1 class="page__titulo">Veículos</h1>
        <p class="page__subtitulo"><?= $total ?> veículo<?= $total !== 1 ? 's' : '' ?> no sistema</p>
    </div>
    <div class="page__acoes">
        <a href="novo.php" class="btn-admin btn-admin--primary">+ Novo Veículo</a>
    </div>
</div>

<!-- FILTROS -->
<form method="GET" class="filtros-admin">
    <div class="filtro-grupo">
        <label>Buscar</label>
        <input type="text" name="busca" value="<?= htmlspecialchars($busca) ?>"
               placeholder="Marca, modelo, placa..." style="min-width:200px">
    </div>
    <div class="filtro-grupo">
        <label>Status</label>
        <select name="status">
            <option value="">Todos</option>
            <?php foreach ($status_veiculo as $k => $v): ?>
            <option value="<?= $k ?>" <?= $status_filtro === $k ? 'selected' : '' ?>><?= $v ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filtro-grupo" style="padding-top:17px">
        <button type="submit" class="btn-admin btn-admin--secondary">Filtrar</button>
    </div>
    <?php if ($busca !== '' || $status_filtro !== ''): ?>
    <div class="filtro-grupo" style="padding-top:17px">
        <a href="?" class="btn-admin btn-admin--secondary">Limpar</a>
    </div>
    <?php endif; ?>
</form>

<!-- TABELA -->
<div class="table-container">
    <div class="table-header">
        <h2 class="table-header__titulo">Lista de Veículos</h2>
    </div>

    <?php if ($veiculos): ?>
    <table class="table">
        <thead>
            <tr>
                <th>Foto</th>
                <th>Veículo</th>
                <th>Ano</th>
                <th>Preço Venda</th>
                <th>KM</th>
                <th>Câmbio</th>
                <th>Status</th>
                <th>Dest.</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($veiculos as $v): ?>
        <tr>
            <td>
                <?php if ($v['foto']): ?>
                <img src="<?= BASE_URL ?>uploads/<?= htmlspecialchars($v['foto']) ?>"
                     class="thumb-veiculo" alt="" loading="lazy">
                <?php else: ?>
                <div class="thumb-veiculo-placeholder">🚗</div>
                <?php endif; ?>
            </td>
            <td class="td-titulo">
                <?= htmlspecialchars($v['marca'].' '.$v['modelo']) ?>
                <?php if ($v['cor']): ?>
                <br><small style="color:#454545;font-weight:400"><?= htmlspecialchars($v['cor']) ?></small>
                <?php endif; ?>
            </td>
            <td><?= $v['ano'] ?></td>
            <td><?= formatarMoeda($v['preco_venda']) ?></td>
            <td><?= formatarKM($v['quilometragem']) ?> km</td>
            <td><?= $cambios[$v['cambio']] ?? $v['cambio'] ?></td>
            <td><span class="badge-admin badge-admin--<?= $v['status'] ?>">
                <?= $status_veiculo[$v['status']] ?? $v['status'] ?></span></td>
            <td><?= $v['destaque'] ? '<span class="badge-admin badge-admin--destaque">★</span>' : '—' ?></td>
            <td class="td-acoes">
                <a href="editar.php?id=<?= $v['id'] ?>" class="btn-admin btn-admin--secondary btn-admin--sm">Editar</a>
                <a href="fotos.php?id=<?= $v['id'] ?>"  class="btn-admin btn-admin--secondary btn-admin--sm">Fotos</a>
                <a href="custos.php?id=<?= $v['id'] ?>" class="btn-admin btn-admin--secondary btn-admin--sm">💰</a>
                <?php if (ehAdmin()): ?>
                <button onclick="confirmarAcao(
                    '?deletar=<?= $v['id'] ?>',
                    'Excluir veículo',
                    'Excluir <?= addslashes(htmlspecialchars($v['marca'].' '.$v['modelo'])) ?>? Todas as fotos serão removidas. Não é possível desfazer.'
                )" class="btn-admin btn-admin--danger btn-admin--sm">✕</button>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($total_paginas > 1):
        $qb  = http_build_query(array_filter(['busca' => $busca, 'status' => $status_filtro]));
        $sep = $qb ? '&' : '';
    ?>
    <div class="paginacao-admin">
        <?php if ($pagina > 1): ?>
        <a href="?<?= $qb.$sep ?>pagina=<?= $pagina-1 ?>">‹</a>
        <?php else: ?><span class="disabled">‹</span><?php endif; ?>

        <?php for ($i = 1; $i <= $total_paginas; $i++):
            if ($i === $pagina): ?><span class="ativo"><?= $i ?></span>
            <?php elseif ($i <= 2 || $i >= $total_paginas-1 || abs($i-$pagina) <= 1): ?>
            <a href="?<?= $qb.$sep ?>pagina=<?= $i ?>"><?= $i ?></a>
            <?php elseif ($i === 3 || $i === $total_paginas-2): ?><span>…</span>
            <?php endif;
        endfor; ?>

        <?php if ($pagina < $total_paginas): ?>
        <a href="?<?= $qb.$sep ?>pagina=<?= $pagina+1 ?>">›</a>
        <?php else: ?><span class="disabled">›</span><?php endif; ?>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <div class="empty-state">
        <div class="empty-state__icone">🚗</div>
        <p class="empty-state__titulo">Nenhum veículo encontrado</p>
        <p class="empty-state__texto">Cadastre o primeiro veículo ou ajuste os filtros.</p>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
