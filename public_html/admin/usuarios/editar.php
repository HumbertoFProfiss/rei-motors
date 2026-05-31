<?php
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/functions.php';

requerAutenticacao();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: ' . ADMIN_URL . 'usuarios/');
    exit;
}

// Vendedores podem editar apenas a si mesmos; admin pode editar qualquer um
if (!ehAdmin() && $id !== (int)$_SESSION['usuario_id']) {
    header('Location: ' . ADMIN_URL);
    exit;
}

$usuario = obterUmaLinha("SELECT * FROM usuarios WHERE id = ?", [$id]);
if (!$usuario) {
    header('Location: ' . ADMIN_URL . 'usuarios/');
    exit;
}

$titulo_pagina = 'Editar Usuário';
$pagina_ativa  = 'usuarios';
$admin_root    = '../';
$breadcrumb    = [
    ['label' => 'Usuários', 'url' => '../usuarios/'],
    ['label' => htmlspecialchars($usuario['nome']), 'url' => ''],
];

$erros = [];
$d = $usuario;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $d['nome']    = sanitizar($_POST['nome'] ?? '');
    $d['email']   = sanitizar($_POST['email'] ?? '');
    $d['telefone'] = sanitizar($_POST['telefone'] ?? '');

    if (ehAdmin()) {
        $d['role']               = ($_POST['role'] ?? '') === 'admin' ? 'admin' : 'vendedor';
        $d['comissao_percentual'] = (float)str_replace(',', '.', $_POST['comissao_percentual'] ?? '3.5');
    }

    $senha     = $_POST['senha'] ?? '';
    $confirmar = $_POST['confirmar_senha'] ?? '';

    if (empty($d['nome']))     $erros[] = 'Nome é obrigatório.';
    if (!validarEmail($d['email'])) $erros[] = 'E-mail inválido.';

    // Verifica e-mail duplicado (exceto o próprio)
    $outro = obterUmaLinha("SELECT id FROM usuarios WHERE email = ? AND id != ?", [$d['email'], $id]);
    if ($outro) $erros[] = 'E-mail já em uso por outro usuário.';

    if (!empty($senha)) {
        if (strlen($senha) < 8)   $erros[] = 'Senha deve ter pelo menos 8 caracteres.';
        if ($senha !== $confirmar) $erros[] = 'As senhas não coincidem.';
    }

    if (empty($erros)) {
        $campos = ['nome', 'email', 'telefone'];
        if (ehAdmin()) $campos = array_merge($campos, ['role', 'comissao_percentual']);

        $update = array_intersect_key($d, array_flip($campos));
        if (!empty($senha)) $update['senha'] = hashSenha($senha);

        atualizar('usuarios', $update, 'id = ?', [$id]);

        $redirect = ehAdmin() ? ADMIN_URL . 'usuarios/?msg=salvo' : ADMIN_URL . '?msg=perfil_salvo';
        header('Location: ' . $redirect);
        exit;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<?php if ($erros): ?>
<div class="alert-admin alert-admin--error">
    <?php foreach ($erros as $e): ?><?= htmlspecialchars($e) ?><br><?php endforeach; ?>
</div>
<?php endif; ?>

<div class="page__header">
    <div>
        <h1 class="page__titulo">Editar Usuário</h1>
        <p class="page__subtitulo"><?= htmlspecialchars($usuario['nome']) ?></p>
    </div>
</div>

<form method="POST" action="">
    <div class="form-section">
        <div class="form-section__titulo">👤 Dados do Usuário</div>
        <div class="form-section__body">
            <div class="form-grid">
                <div class="form-grupo">
                    <label>Nome Completo <span class="obrigatorio">*</span></label>
                    <input type="text" name="nome" value="<?= htmlspecialchars($d['nome']) ?>" required>
                </div>
                <div class="form-grupo">
                    <label>E-mail <span class="obrigatorio">*</span></label>
                    <input type="email" name="email" value="<?= htmlspecialchars($d['email']) ?>" required>
                </div>
                <div class="form-grupo">
                    <label>Telefone</label>
                    <input type="text" name="telefone" value="<?= htmlspecialchars($d['telefone'] ?? '') ?>">
                </div>
                <?php if (ehAdmin()): ?>
                <div class="form-grupo">
                    <label>Perfil</label>
                    <select name="role">
                        <option value="vendedor" <?= $d['role'] === 'vendedor' ? 'selected' : '' ?>>Vendedor</option>
                        <option value="admin"    <?= $d['role'] === 'admin' ? 'selected' : '' ?>>Administrador</option>
                    </select>
                </div>
                <div class="form-grupo">
                    <label>Comissão (%)</label>
                    <input type="text" name="comissao_percentual"
                           value="<?= number_format((float)$d['comissao_percentual'], 1, ',', '.') ?>">
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="form-section">
        <div class="form-section__titulo">🔒 Alterar Senha</div>
        <div class="form-section__body">
            <p class="form-grupo__hint" style="margin-bottom:12px">Deixe em branco para manter a senha atual.</p>
            <div class="form-grid--2 form-grid">
                <div class="form-grupo">
                    <label>Nova Senha</label>
                    <input type="password" name="senha" autocomplete="new-password"
                           minlength="8" placeholder="Mínimo 8 caracteres">
                </div>
                <div class="form-grupo">
                    <label>Confirmar Nova Senha</label>
                    <input type="password" name="confirmar_senha" autocomplete="new-password">
                </div>
            </div>
        </div>
    </div>

    <div class="form-acoes">
        <?php if (ehAdmin()): ?>
        <a href="." class="btn-admin btn-admin--secondary">Cancelar</a>
        <?php endif; ?>
        <button type="submit" class="btn-admin btn-admin--primary">Salvar Alterações</button>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
