<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

$titulo_pagina = 'Meu Perfil';
$pagina_ativa  = 'perfil';

require_once __DIR__ . '/includes/header.php';

$id      = (int)$_SESSION['cliente_id'];
$cliente = obterUmaLinha("SELECT * FROM clientes WHERE id = ?", [$id]);

$msg_ok  = '';
$msg_err = '';

// Alterar senha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['alterar_senha'])) {
    $senha_atual = $_POST['senha_atual'] ?? '';
    $senha_nova  = $_POST['senha_nova'] ?? '';
    $senha_conf  = $_POST['senha_confirma'] ?? '';

    if (empty($senha_atual) || empty($senha_nova) || empty($senha_conf)) {
        $msg_err = 'Preencha todos os campos.';
    } elseif (empty($cliente['senha'])) {
        $msg_err = 'Sua senha ainda não foi configurada. Entre em contato com a loja.';
    } elseif (!verificarSenha($senha_atual, $cliente['senha'])) {
        $msg_err = 'Senha atual incorreta.';
    } elseif ($senha_nova !== $senha_conf) {
        $msg_err = 'A nova senha e a confirmação não são iguais.';
    } elseif (strlen($senha_nova) < 6) {
        $msg_err = 'A senha deve ter ao menos 6 caracteres.';
    } else {
        $hash = hashSenha($senha_nova);
        atualizar('clientes', ['senha' => $hash], 'id = ?', [$id]);
        // Atualiza o registro local
        $cliente['senha'] = $hash;
        $msg_ok = 'Senha alterada com sucesso!';
    }
}
?>

<div class="page__header">
    <div>
        <h1 class="page__titulo">Meu Perfil</h1>
        <p class="page__subtitulo">Seus dados cadastrados na loja</p>
    </div>
</div>

<?php if ($msg_ok): ?>
<div class="alert alert--success"><?= htmlspecialchars($msg_ok) ?></div>
<?php endif; ?>
<?php if ($msg_err): ?>
<div class="alert alert--error"><?= htmlspecialchars($msg_err) ?></div>
<?php endif; ?>

<!-- DADOS PESSOAIS -->
<div class="form-section">
    <div class="form-section__titulo">👤 Dados Pessoais</div>
    <div class="form-section__body">
        <div class="form-grid">
            <div class="form-grupo form-grupo--2">
                <label>Nome Completo</label>
                <input type="text" value="<?= htmlspecialchars($cliente['nome']) ?>" readonly>
            </div>
            <?php if ($cliente['cpf']): ?>
            <div class="form-grupo">
                <label>CPF</label>
                <input type="text" value="<?= htmlspecialchars(formatarCPF($cliente['cpf'])) ?>" readonly>
            </div>
            <?php endif; ?>
            <?php if ($cliente['rg']): ?>
            <div class="form-grupo">
                <label>RG</label>
                <input type="text" value="<?= htmlspecialchars($cliente['rg']) ?>" readonly>
            </div>
            <?php endif; ?>
            <?php if ($cliente['profissao']): ?>
            <div class="form-grupo">
                <label>Profissão</label>
                <input type="text" value="<?= htmlspecialchars($cliente['profissao']) ?>" readonly>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- CONTATO -->
<div class="form-section">
    <div class="form-section__titulo">📞 Contato</div>
    <div class="form-section__body">
        <div class="form-grid">
            <?php if ($cliente['email']): ?>
            <div class="form-grupo form-grupo--2">
                <label>E-mail</label>
                <input type="email" value="<?= htmlspecialchars($cliente['email']) ?>" readonly>
            </div>
            <?php endif; ?>
            <?php if ($cliente['telefone']): ?>
            <div class="form-grupo">
                <label>Telefone</label>
                <input type="text" value="<?= htmlspecialchars(formatarTelefone($cliente['telefone'])) ?>" readonly>
            </div>
            <?php endif; ?>
            <?php if ($cliente['whatsapp']): ?>
            <div class="form-grupo">
                <label>WhatsApp</label>
                <input type="text" value="<?= htmlspecialchars(formatarTelefone($cliente['whatsapp'])) ?>" readonly>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ENDEREÇO -->
<?php if ($cliente['endereco'] || $cliente['cidade']): ?>
<div class="form-section">
    <div class="form-section__titulo">📍 Endereço</div>
    <div class="form-section__body">
        <div class="form-grid">
            <?php if ($cliente['endereco']): ?>
            <div class="form-grupo form-grupo--2">
                <label>Logradouro</label>
                <input type="text" value="<?= htmlspecialchars($cliente['endereco'] . ($cliente['numero'] ? ', ' . $cliente['numero'] : '') . ($cliente['complemento'] ? ' - ' . $cliente['complemento'] : '')) ?>" readonly>
            </div>
            <?php endif; ?>
            <?php if ($cliente['bairro']): ?>
            <div class="form-grupo">
                <label>Bairro</label>
                <input type="text" value="<?= htmlspecialchars($cliente['bairro']) ?>" readonly>
            </div>
            <?php endif; ?>
            <?php if ($cliente['cidade']): ?>
            <div class="form-grupo">
                <label>Cidade</label>
                <input type="text" value="<?= htmlspecialchars($cliente['cidade'] . ($cliente['estado'] ? ' - ' . $cliente['estado'] : '')) ?>" readonly>
            </div>
            <?php endif; ?>
            <?php if ($cliente['cep']): ?>
            <div class="form-grupo">
                <label>CEP</label>
                <input type="text" value="<?= htmlspecialchars($cliente['cep']) ?>" readonly>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ALTERAR SENHA -->
<div class="form-section">
    <div class="form-section__titulo">🔐 Alterar Senha</div>
    <div class="form-section__body">
        <form method="POST" action="">
            <input type="hidden" name="alterar_senha" value="1">
            <div class="form-grid">
                <div class="form-grupo">
                    <label>Senha Atual</label>
                    <input type="password" name="senha_atual" required autocomplete="current-password">
                </div>
                <div class="form-grupo">
                    <label>Nova Senha</label>
                    <input type="password" name="senha_nova" required minlength="6" autocomplete="new-password">
                </div>
                <div class="form-grupo">
                    <label>Confirmar Nova Senha</label>
                    <input type="password" name="senha_confirma" required minlength="6" autocomplete="new-password">
                </div>
            </div>
            <div class="form-acoes" style="margin-top:14px">
                <button type="submit" class="btn-c btn-c--primary">Alterar Senha</button>
            </div>
        </form>
    </div>
</div>

<p style="font-size:0.75rem;color:#3A3A3A;margin-top:8px">
    Para atualizar outros dados cadastrais, entre em contato com a loja.
</p>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
