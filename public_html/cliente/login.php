<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

if (clienteAutenticado()) {
    header('Location: ' . CLIENTE_URL);
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (empty($email) || empty($senha)) {
        $erro = 'Preencha e-mail e senha.';
    } else {
        $cliente = obterUmaLinha(
            "SELECT id, nome, email, senha FROM clientes WHERE email = ? LIMIT 1",
            [$email]
        );

        if (!$cliente || empty($cliente['senha'])) {
            $erro = 'Acesso não encontrado. Entre em contato com a loja para ativar seu acesso.';
        } elseif (!verificarSenha($senha, $cliente['senha'])) {
            $erro = 'Senha incorreta.';
        } else {
            $_SESSION['cliente_id']         = $cliente['id'];
            $_SESSION['cliente_nome']       = $cliente['nome'];
            $_SESSION['cliente_login_time'] = time();
            header('Location: ' . CLIENTE_URL);
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Área do Cliente — Rei Motors</title>
    <meta name="robots" content="noindex,nofollow">
    <link rel="stylesheet" href="<?= CLIENTE_URL ?>assets/css/cliente.css">
</head>
<body>
<div class="login-page">
    <div class="login-box">
        <div class="login-logo">
            <div class="login-logo__nome">👑 Rei Motors</div>
            <div class="login-logo__sub">Área do Cliente</div>
        </div>

        <h1 class="login-titulo">Entrar</h1>

        <?php if ($erro): ?>
        <div class="alert alert--error"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
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

        <div class="login-info">
            Primeiro acesso? A loja define sua senha.<br>
            Entre em contato: <strong><?= LOJA_WHATSAPP ?></strong>
        </div>

        <p class="login-footer">
            <a href="<?= BASE_URL ?>">← Voltar ao site</a>
        </p>
    </div>
</div>
</body>
</html>
