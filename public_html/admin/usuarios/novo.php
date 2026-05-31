<?php
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/functions.php';

requerAdmin();

$titulo_pagina = 'Novo Usuário';
$pagina_ativa  = 'usuarios';
$admin_root    = '../';
$breadcrumb    = [
    ['label' => 'Usuários', 'url' => '../usuarios/'],
    ['label' => 'Novo', 'url' => ''],
];

$erros = [];
$d = [
    'nome' => '', 'email' => '', 'role' => 'vendedor',
    'comissao_percentual' => number_format(COMISSAO_PADRAO_VENDEDOR, 1, ',', '.'),
    'telefone' => '', 'ativo' => 1,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $d['nome']                = sanitizar($_POST['nome'] ?? '');
    $d['email']               = sanitizar($_POST['email'] ?? '');
    $d['role']                = ($_POST['role'] ?? '') === 'admin' ? 'admin' : 'vendedor';
    $d['comissao_percentual'] = (float)str_replace(',', '.', $_POST['comissao_percentual'] ?? '3.5');
    $d['telefone']            = sanitizar($_POST['telefone'] ?? '');
    $d['ativo']               = 1;
    $senha                    = $_POST['senha'] ?? '';
    $confirmar                = $_POST['confirmar_senha'] ?? '';

    if (empty($d['nome']))               $erros[] = 'Nome é obrigatório.';
    if (!validarEmail($d['email']))       $erros[] = 'E-mail inválido.';
    if (strlen($senha) < 8)              $erros[] = 'Senha deve ter pelo menos 8 caracteres.';
    if ($senha !== $confirmar)           $erros[] = 'As senhas não coincidem.';
    if (obterUmaLinha("SELECT id FROM usuarios WHERE email = ?", [$d['email']])) {
        $erros[] = 'E-mail já cadastrado.';
    }

    if (empty($erros)) {
        $d['senha'] = hashSenha($senha);
        inserir('usuarios', $d);
        header('Location: ' . ADMIN_URL . 'usuarios/?msg=salvo');
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
        <h1 class="page__titulo">Novo Usuário</h1>
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
                    <input type="text" name="telefone" value="<?= htmlspecialchars($d['telefone']) ?>">
                </div>
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
                           value="<?= htmlspecialchars($d['comissao_percentual']) ?>"
                           placeholder="3,5">
                </div>
            </div>
        </div>
    </div>

    <div class="form-section">
        <div class="form-section__titulo">🔒 Senha</div>
        <div class="form-section__body">
            <div class="form-grid--2 form-grid">
                <div class="form-grupo">
                    <label>Senha <span class="obrigatorio">*</span></label>
                    <input type="password" name="senha" required autocomplete="new-password"
                           minlength="8" placeholder="Mínimo 8 caracteres">
                </div>
                <div class="form-grupo">
                    <label>Confirmar Senha <span class="obrigatorio">*</span></label>
                    <input type="password" name="confirmar_senha" required autocomplete="new-password">
                </div>
            </div>
        </div>
    </div>

    <div class="form-acoes">
        <a href="." class="btn-admin btn-admin--secondary">Cancelar</a>
        <button type="submit" class="btn-admin btn-admin--primary">Criar Usuário</button>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
