<?php
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/functions.php';

requerAdmin(); // Apenas admin

$titulo_pagina = 'Usuários';
$pagina_ativa  = 'usuarios';
$admin_root    = '../';
$breadcrumb    = [['label' => 'Usuários', 'url' => '']];

// Ativar / desativar usuário
if (isset($_GET['toggle'], $_GET['id'])) {
    $uid = (int)$_GET['id'];
    if ($uid !== (int)$_SESSION['usuario_id'] && $uid > 0) { // Não pode desativar a si mesmo
        $usuario = obterUmaLinha("SELECT ativo FROM usuarios WHERE id = ?", [$uid]);
        if ($usuario) {
            atualizar('usuarios', ['ativo' => $usuario['ativo'] ? 0 : 1], 'id = ?', [$uid]);
        }
    }
    header('Location: ' . ADMIN_URL . 'usuarios/?msg=atualizado');
    exit;
}

$usuarios = obterTodas(
    "SELECT u.*,
            (SELECT COUNT(*) FROM vendas v WHERE v.vendedor_id = u.id) as total_vendas
     FROM usuarios u ORDER BY u.role DESC, u.nome ASC"
);

$msg = trim($_GET['msg'] ?? '');

require_once __DIR__ . '/../includes/header.php';
?>

<?php if ($msg === 'salvo'):      ?><div class="alert-admin alert-admin--success">✓ Usuário salvo com sucesso.</div><?php endif; ?>
<?php if ($msg === 'atualizado'): ?><div class="alert-admin alert-admin--success">✓ Status atualizado.</div><?php endif; ?>

<div class="page__header">
    <div>
        <h1 class="page__titulo">Usuários</h1>
        <p class="page__subtitulo"><?= count($usuarios) ?> usuário(s) no sistema</p>
    </div>
    <div class="page__acoes">
        <a href="novo.php" class="btn-admin btn-admin--primary">+ Novo Usuário</a>
    </div>
</div>

<div class="table-container">
    <div class="table-header">
        <h2 class="table-header__titulo">Lista de Usuários</h2>
    </div>

    <?php if ($usuarios): ?>
    <table class="table">
        <thead>
            <tr>
                <th>Nome</th>
                <th>E-mail</th>
                <th>Perfil</th>
                <th>Comissão</th>
                <th>Vendas</th>
                <th>Status</th>
                <th>Criado em</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($usuarios as $u): ?>
        <tr>
            <td class="td-titulo">
                <div style="display:flex;align-items:center;gap:8px">
                    <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#D4AF37,#8B6914);display:flex;align-items:center;justify-content:center;font-size:0.7rem;font-weight:700;color:#0A0A0A;flex-shrink:0">
                        <?= mb_strtoupper(mb_substr($u['nome'], 0, 1, 'UTF-8'), 'UTF-8') ?>
                    </div>
                    <?= htmlspecialchars($u['nome']) ?>
                    <?= $u['id'] === (int)$_SESSION['usuario_id'] ? ' <small style="color:#888">(você)</small>' : '' ?>
                </div>
            </td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td><span class="badge-admin badge-admin--<?= $u['role'] ?>"><?= $u['role'] === 'admin' ? 'Admin' : 'Vendedor' ?></span></td>
            <td><?= number_format((float)$u['comissao_percentual'], 1, ',', '.') ?>%</td>
            <td><?= $u['total_vendas'] > 0 ? $u['total_vendas'] : '—' ?></td>
            <td>
                <span class="badge-admin badge-admin--<?= $u['ativo'] ? 'ativo' : 'inativo' ?>">
                    <?= $u['ativo'] ? 'Ativo' : 'Inativo' ?>
                </span>
            </td>
            <td><?= formatarData($u['criado_em']) ?></td>
            <td class="td-acoes">
                <a href="editar.php?id=<?= $u['id'] ?>" class="btn-admin btn-admin--secondary btn-admin--sm">Editar</a>
                <?php if ($u['id'] !== (int)$_SESSION['usuario_id']): ?>
                <a href="?toggle=1&id=<?= $u['id'] ?>"
                   class="btn-admin btn-admin--sm <?= $u['ativo'] ? 'btn-admin--danger' : 'btn-admin--success' ?>">
                    <?= $u['ativo'] ? 'Desativar' : 'Ativar' ?>
                </a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="empty-state">
        <div class="empty-state__icone">👤</div>
        <p class="empty-state__titulo">Nenhum usuário encontrado</p>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
