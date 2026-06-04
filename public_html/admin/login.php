<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

if (estaAutenticado()) {
    header('Location: ' . ADMIN_URL);
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificarTokenCSRF($_POST['csrf_token'] ?? '')) {
        $erro = 'Token de segurança inválido. Recarregue a página.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';

        if (empty($email) || empty($senha)) {
            $erro = 'Preencha e-mail e senha.';
        } else {
            $usuario = obterUmaLinha(
                "SELECT id, nome, email, senha, role, ativo FROM usuarios WHERE email = ? LIMIT 1",
                [$email]
            );

            if (!$usuario || !$usuario['ativo']) {
                $erro = 'Usuário não encontrado ou inativo.';
            } elseif (!verificarSenha($senha, $usuario['senha'])) {
                $erro = 'Senha incorreta.';
            } else {
                $_SESSION['usuario_id']   = $usuario['id'];
                $_SESSION['usuario_nome'] = $usuario['nome'];
                $_SESSION['usuario_role'] = $usuario['role'];
                $_SESSION['login_time']   = time();

                header('Location: ' . ADMIN_URL);
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Rei Motors Admin</title>
    <meta name="robots" content="noindex,nofollow">
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
<div class="login-page">
    <div class="login-box">
        <div class="login-logo">
            <img src="<?= BASE_URL ?>uploads/logo.png" alt="Rei Motors"
                 style="height:56px;width:auto;object-fit:contain;display:block;margin:0 auto 6px">
            <div class="login-logo__sub">Painel Administrativo</div>
        </div>

        <h1 class="login-titulo">Entrar</h1>

        <?php if ($erro): ?>
        <div class="alert-admin alert-admin--error"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gerarTokenCSRF()) ?>">
            <div class="login-grupo">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" required
                       autocomplete="email"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="login-grupo">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" required
                       autocomplete="current-password">
            </div>
            <button type="submit" class="login-btn">Entrar</button>
        </form>

        <p class="login-footer">
            <a href="<?= BASE_URL ?>">← Voltar ao site</a>
        </p>
    </div>
</div>
</body>
</html>
