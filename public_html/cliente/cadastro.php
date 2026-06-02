<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

if (clienteAutenticado()) {
    header('Location: ' . CLIENTE_URL);
    exit;
}

$erros = [];
$d     = ['nome' => '', 'email' => '', 'telefone' => '', 'cpf' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $d['nome']     = sanitizar($_POST['nome'] ?? '');
    $d['email']    = trim($_POST['email'] ?? '');
    $d['telefone'] = preg_replace('/\D/', '', $_POST['telefone'] ?? '');
    $d['cpf']      = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
    $senha         = $_POST['senha'] ?? '';
    $senha_conf    = $_POST['senha_confirma'] ?? '';

    if (empty($d['nome']))                          $erros[] = 'Nome é obrigatório.';
    if (empty($d['email']))                         $erros[] = 'E-mail é obrigatório.';
    elseif (!validarEmail($d['email']))             $erros[] = 'E-mail inválido.';
    if (empty($senha))                              $erros[] = 'Senha é obrigatória.';
    elseif (strlen($senha) < 6)                     $erros[] = 'A senha deve ter ao menos 6 caracteres.';
    elseif ($senha !== $senha_conf)                 $erros[] = 'As senhas não são iguais.';
    if (!empty($d['cpf']) && !validarCPF($d['cpf'])) $erros[] = 'CPF inválido.';

    if (empty($erros)) {
        // Verificar e-mail duplicado
        $existe = obterUmaLinha("SELECT id FROM clientes WHERE email = ? LIMIT 1", [$d['email']]);
        if ($existe) {
            $erros[] = 'Este e-mail já está cadastrado. Tente fazer login.';
        }

        // Verificar CPF duplicado (se informado)
        if (empty($erros) && !empty($d['cpf'])) {
            $cpf_existe = obterUmaLinha("SELECT id FROM clientes WHERE cpf = ? LIMIT 1", [$d['cpf']]);
            if ($cpf_existe) $erros[] = 'Este CPF já está cadastrado.';
        }
    }

    if (empty($erros)) {
        $novo = [
            'nome'     => $d['nome'],
            'email'    => $d['email'],
            'senha'    => hashSenha($senha),
            'telefone' => $d['telefone'] ?: null,
            'whatsapp' => $d['telefone'] ?: null,
            'cpf'      => $d['cpf'] ?: null,
        ];
        $novo_id = inserir('clientes', $novo);

        // Login automático após cadastro
        $_SESSION['cliente_id']         = $novo_id;
        $_SESSION['cliente_nome']       = $d['nome'];
        $_SESSION['cliente_login_time'] = time();

        header('Location: ' . CLIENTE_URL . '?cadastro=ok');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro — Rei Motors</title>
    <meta name="robots" content="noindex,nofollow">
    <link rel="stylesheet" href="<?= CLIENTE_URL ?>assets/css/cliente.css">
</head>
<body>
<div class="login-page">
    <div class="login-box" style="max-width:420px">
        <div class="login-logo">
            <div class="login-logo__nome">👑 Rei Motors</div>
            <div class="login-logo__sub">Criar conta</div>
        </div>

        <h1 class="login-titulo">Cadastro</h1>

        <?php if ($erros): ?>
        <div class="alert alert--error">
            <?php foreach ($erros as $e): ?>
                <?= htmlspecialchars($e) ?><br>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="login-grupo">
                <label for="nome">Nome Completo <span style="color:#ef4444">*</span></label>
                <input type="text" id="nome" name="nome" required
                       value="<?= htmlspecialchars($d['nome']) ?>"
                       placeholder="Seu nome completo">
            </div>
            <div class="login-grupo">
                <label for="email">E-mail <span style="color:#ef4444">*</span></label>
                <input type="email" id="email" name="email" required
                       value="<?= htmlspecialchars($d['email']) ?>"
                       placeholder="seu@email.com" autocomplete="email">
            </div>
            <div class="login-grupo">
                <label for="telefone">WhatsApp / Telefone</label>
                <input type="text" id="telefone" name="telefone"
                       value="<?= htmlspecialchars($d['telefone'] ? '(' . substr($d['telefone'],0,2) . ') ' . substr($d['telefone'],2) : '') ?>"
                       placeholder="(00) 00000-0000">
            </div>
            <div class="login-grupo">
                <label for="cpf">CPF <span style="font-size:0.65rem;color:#3A3A3A">(opcional)</span></label>
                <input type="text" id="cpf" name="cpf"
                       value="<?= htmlspecialchars($d['cpf'] ? substr($d['cpf'],0,3).'.'.substr($d['cpf'],3,3).'.'.substr($d['cpf'],6,3).'-'.substr($d['cpf'],9,2) : '') ?>"
                       placeholder="000.000.000-00" maxlength="14">
            </div>
            <div class="login-grupo">
                <label for="senha">Senha <span style="color:#ef4444">*</span></label>
                <input type="password" id="senha" name="senha" required
                       minlength="6" placeholder="Mín. 6 caracteres"
                       autocomplete="new-password">
            </div>
            <div class="login-grupo">
                <label for="senha_confirma">Confirmar Senha <span style="color:#ef4444">*</span></label>
                <input type="password" id="senha_confirma" name="senha_confirma" required
                       minlength="6" placeholder="Repita a senha"
                       autocomplete="new-password">
            </div>
            <button type="submit" class="login-btn">Criar Conta</button>
        </form>

        <p class="login-footer" style="margin-top:16px">
            Já tem conta? <a href="<?= CLIENTE_URL ?>login.php">Entrar</a>
            &nbsp;·&nbsp;
            <a href="<?= BASE_URL ?>">← Voltar ao site</a>
        </p>
    </div>
</div>

<script>
// Máscara CPF
document.getElementById('cpf').addEventListener('input', function () {
    let v = this.value.replace(/\D/g, '').slice(0, 11);
    if (v.length > 9)      v = v.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, '$1.$2.$3-$4');
    else if (v.length > 6) v = v.replace(/(\d{3})(\d{3})(\d{1,3})/, '$1.$2.$3');
    else if (v.length > 3) v = v.replace(/(\d{3})(\d{1,3})/, '$1.$2');
    this.value = v;
});

// Máscara telefone
document.getElementById('telefone').addEventListener('input', function () {
    let v = this.value.replace(/\D/g, '').slice(0, 11);
    if (v.length > 10)     v = v.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
    else if (v.length > 6) v = v.replace(/(\d{2})(\d{4,5})(\d{0,4})/, '($1) $2-$3');
    else if (v.length > 2) v = v.replace(/(\d{2})(\d+)/, '($1) $2');
    this.value = v;
});
</script>
</body>
</html>
