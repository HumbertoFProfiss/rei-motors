<?php
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/functions.php';

$titulo_pagina = 'Clientes';
$pagina_ativa  = 'clientes';
$admin_root    = '../';
$breadcrumb    = [['label' => 'Clientes', 'url' => '']];

// Exclusão (admin only)
if (isset($_GET['deletar']) && ehAdmin()) {
    requerAutenticacao();
    $id_del = (int)$_GET['deletar'];
    if ($id_del > 0) {
        deletar('clientes', 'id = ?', [$id_del]);
        header('Location: ' . ADMIN_URL . 'clientes/?msg=deletado');
        exit;
    }
}

$busca   = trim($_GET['busca'] ?? '');
$pagina  = max(1, (int)($_GET['pagina'] ?? 1));
$por_pag = 25;

$where  = ['1=1'];
$params = [];

if ($busca !== '') {
    $where[]  = '(c.nome LIKE ? OR c.email LIKE ? OR c.cpf LIKE ? OR c.telefone LIKE ?)';
    $params[] = "%$busca%";
    $params[] = "%$busca%";
    $params[] = "%$busca%";
    $params[] = "%$busca%";
}

$where_sql = implode(' AND ', $where);
$total     = (int)obterUmaLinha("SELECT COUNT(*) c FROM clientes c WHERE $where_sql", $params)['c'];
$total_pag = max(1, (int)ceil($total / $por_pag));
$pagina    = min($pagina, $total_pag);
$offset    = ($pagina - 1) * $por_pag;

$clientes = obterTodas(
    "SELECT c.*,
            (SELECT COUNT(*) FROM vendas v WHERE v.cliente_id = c.id) as total_vendas
     FROM clientes c WHERE $where_sql ORDER BY c.criado_em DESC LIMIT ? OFFSET ?",
    array_merge($params, [$por_pag, $offset])
);

$msg = trim($_GET['msg'] ?? '');

require_once __DIR__ . '/../includes/header.php';
?>

<?php if ($msg === 'salvo'):    ?><div class="alert-admin alert-admin--success">✓ Cliente salvo com sucesso.</div><?php endif; ?>
<?php if ($msg === 'deletado'): ?><div class="alert-admin alert-admin--success">✓ Cliente excluído.</div><?php endif; ?>

<div class="page__header">
    <div>
        <h1 class="page__titulo">Clientes</h1>
        <p class="page__subtitulo"><?= $total ?> cliente<?= $total !== 1 ? 's' : '' ?> cadastrado<?= $total !== 1 ? 's' : '' ?></p>
    </div>
    <div class="page__acoes">
        <a href="novo.php" class="btn-admin btn-admin--primary">+ Novo Cliente</a>
    </div>
</div>

<!-- FILTROS -->
<form method="GET" class="filtros-admin">
    <div class="filtro-grupo">
        <label>Buscar</label>
        <input type="text" name="busca" value="<?= htmlspecialchars($busca) ?>"
               placeholder="Nome, e-mail, CPF, telefone..." style="min-width:260px">
    </div>
    <div class="filtro-grupo" style="padding-top:17px">
        <button type="submit" class="btn-admin btn-admin--secondary">Buscar</button>
    </div>
    <?php if ($busca !== ''): ?>
    <div class="filtro-grupo" style="padding-top:17px">
        <a href="?" class="btn-admin btn-admin--secondary">Limpar</a>
    </div>
    <?php endif; ?>
</form>

<div class="table-container">
    <div class="table-header">
        <h2 class="table-header__titulo">Lista de Clientes</h2>
    </div>

    <?php if ($clientes): ?>
    <table class="table">
        <thead>
            <tr>
                <th>Nome</th>
                <th>CPF</th>
                <th>Telefone / WhatsApp</th>
                <th>E-mail</th>
                <th>Cidade</th>
                <th>Compras</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($clientes as $c): ?>
        <tr>
            <td class="td-titulo"><?= htmlspecialchars($c['nome']) ?></td>
            <td><?= $c['cpf'] ? htmlspecialchars(formatarCPF($c['cpf'])) : '—' ?></td>
            <td>
                <?php $tel = $c['whatsapp'] ?: $c['telefone']; ?>
                <?php if ($tel): ?>
                <a href="https://wa.me/55<?= preg_replace('/\D/','',$tel) ?>" target="_blank"
                   style="color:#22c55e"><?= htmlspecialchars(formatarTelefone($tel)) ?></a>
                <?php else: ?>—<?php endif; ?>
            </td>
            <td><?= $c['email'] ? htmlspecialchars($c['email']) : '—' ?></td>
            <td><?= $c['cidade'] ? htmlspecialchars($c['cidade'].($c['estado'] ? '/'.$c['estado'] : '')) : '—' ?></td>
            <td><?= $c['total_vendas'] > 0
                ? '<span class="badge-admin badge-admin--disponivel">'.$c['total_vendas'].'</span>'
                : '—' ?></td>
            <td class="td-acoes">
                <a href="editar.php?id=<?= $c['id'] ?>" class="btn-admin btn-admin--secondary btn-admin--sm">Editar</a>
                <?php if (ehAdmin()): ?>
                <button onclick="confirmarAcao(
                    '?deletar=<?= $c['id'] ?>',
                    'Excluir cliente',
                    'Excluir <?= addslashes(htmlspecialchars($c['nome'])) ?>? Esta ação não pode ser desfeita.'
                )" class="btn-admin btn-admin--danger btn-admin--sm">✕</button>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($total_pag > 1):
        $qb  = http_build_query(array_filter(['busca' => $busca]));
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
        <div class="empty-state__icone">👥</div>
        <p class="empty-state__titulo">Nenhum cliente encontrado</p>
        <p class="empty-state__texto">Cadastre o primeiro cliente ou ajuste a busca.</p>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
