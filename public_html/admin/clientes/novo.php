<?php
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/functions.php';

$titulo_pagina = 'Novo Cliente';
$pagina_ativa  = 'clientes';
$admin_root    = '../';
$breadcrumb    = [
    ['label' => 'Clientes', 'url' => '../clientes/'],
    ['label' => 'Novo', 'url' => ''],
];

$erros = [];
$d = [
    'nome' => '', 'cpf' => '', 'rg' => '', 'email' => '',
    'telefone' => '', 'whatsapp' => '',
    'endereco' => '', 'numero' => '', 'complemento' => '',
    'bairro' => '', 'cidade' => '', 'estado' => '', 'cep' => '',
    'profissao' => '', 'renda_estimada' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requerAutenticacao();

    foreach ($d as $k => $v) {
        $d[$k] = sanitizar($_POST[$k] ?? '');
    }
    $d['cpf']            = preg_replace('/\D/', '', $d['cpf']) ?: null;
    $d['telefone']       = preg_replace('/\D/', '', $d['telefone']) ?: null;
    $d['whatsapp']       = preg_replace('/\D/', '', $d['whatsapp']) ?: null;
    $d['renda_estimada'] = !empty($_POST['renda_estimada'])
        ? (float)str_replace(['.', ','], ['', '.'], $_POST['renda_estimada'])
        : null;

    if (empty($d['nome'])) $erros[] = 'Nome é obrigatório.';
    if (!empty($_POST['email']) && !validarEmail($_POST['email'])) $erros[] = 'E-mail inválido.';
    if (!empty($_POST['cpf']) && !validarCPF($_POST['cpf'])) $erros[] = 'CPF inválido.';

    if (empty($erros)) {
        $novo_id = inserir('clientes', array_filter($d, fn($v) => $v !== null && $v !== ''));
        header('Location: ' . ADMIN_URL . 'clientes/?msg=salvo');
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
        <h1 class="page__titulo">Novo Cliente</h1>
    </div>
</div>

<form method="POST" action="">
    <div class="form-section">
        <div class="form-section__titulo">👤 Dados Pessoais</div>
        <div class="form-section__body">
            <div class="form-grid">
                <div class="form-grupo form-grupo--2">
                    <label>Nome Completo <span class="obrigatorio">*</span></label>
                    <input type="text" name="nome" value="<?= htmlspecialchars($d['nome']) ?>" required>
                </div>
                <div class="form-grupo">
                    <label>Profissão</label>
                    <input type="text" name="profissao" value="<?= htmlspecialchars($d['profissao']) ?>">
                </div>
                <div class="form-grupo">
                    <label>CPF</label>
                    <input type="text" name="cpf" value="<?= htmlspecialchars($_POST['cpf'] ?? '') ?>"
                           placeholder="000.000.000-00" maxlength="14">
                </div>
                <div class="form-grupo">
                    <label>RG</label>
                    <input type="text" name="rg" value="<?= htmlspecialchars($d['rg']) ?>">
                </div>
                <div class="form-grupo">
                    <label>Renda Estimada (R$)</label>
                    <input type="text" name="renda_estimada"
                           value="<?= htmlspecialchars($_POST['renda_estimada'] ?? '') ?>"
                           placeholder="0,00">
                </div>
            </div>
        </div>
    </div>

    <div class="form-section">
        <div class="form-section__titulo">📞 Contato</div>
        <div class="form-section__body">
            <div class="form-grid">
                <div class="form-grupo">
                    <label>E-mail</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($d['email']) ?>">
                </div>
                <div class="form-grupo">
                    <label>Telefone</label>
                    <input type="text" name="telefone" value="<?= htmlspecialchars($_POST['telefone'] ?? '') ?>"
                           placeholder="(11) 99999-9999">
                </div>
                <div class="form-grupo">
                    <label>WhatsApp</label>
                    <input type="text" name="whatsapp" value="<?= htmlspecialchars($_POST['whatsapp'] ?? '') ?>"
                           placeholder="(11) 99999-9999">
                </div>
            </div>
        </div>
    </div>

    <div class="form-section">
        <div class="form-section__titulo">📍 Endereço</div>
        <div class="form-section__body">
            <div class="form-grid">
                <div class="form-grupo form-grupo--2">
                    <label>Logradouro</label>
                    <input type="text" name="endereco" value="<?= htmlspecialchars($d['endereco']) ?>"
                           placeholder="Rua, Av., etc.">
                </div>
                <div class="form-grupo">
                    <label>Número</label>
                    <input type="text" name="numero" value="<?= htmlspecialchars($d['numero']) ?>">
                </div>
                <div class="form-grupo">
                    <label>Complemento</label>
                    <input type="text" name="complemento" value="<?= htmlspecialchars($d['complemento']) ?>">
                </div>
                <div class="form-grupo">
                    <label>Bairro</label>
                    <input type="text" name="bairro" value="<?= htmlspecialchars($d['bairro']) ?>">
                </div>
                <div class="form-grupo">
                    <label>Cidade</label>
                    <input type="text" name="cidade" value="<?= htmlspecialchars($d['cidade']) ?>">
                </div>
                <div class="form-grupo">
                    <label>Estado</label>
                    <input type="text" name="estado" value="<?= htmlspecialchars($d['estado']) ?>"
                           maxlength="2" placeholder="SP" style="text-transform:uppercase">
                </div>
                <div class="form-grupo">
                    <label>CEP</label>
                    <input type="text" name="cep" value="<?= htmlspecialchars($d['cep']) ?>"
                           placeholder="00000-000" maxlength="9">
                </div>
            </div>
        </div>
    </div>

    <div class="form-acoes">
        <a href="." class="btn-admin btn-admin--secondary">Cancelar</a>
        <button type="submit" class="btn-admin btn-admin--primary">Salvar Cliente</button>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
