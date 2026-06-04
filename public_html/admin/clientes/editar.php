<?php
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/functions.php';

requerAutenticacao();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: ' . ADMIN_URL . 'clientes/');
    exit;
}

$cliente = obterUmaLinha("SELECT * FROM clientes WHERE id = ?", [$id]);
if (!$cliente) {
    header('Location: ' . ADMIN_URL . 'clientes/');
    exit;
}

$titulo_pagina = 'Editar Cliente';
$pagina_ativa  = 'clientes';
$admin_root    = '../';
$breadcrumb    = [
    ['label' => 'Clientes', 'url' => '../clientes/'],
    ['label' => htmlspecialchars($cliente['nome']), 'url' => ''],
];

$erros = [];
$d = $cliente;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $campos = ['nome','cpf','rg','email','telefone','whatsapp',
               'endereco','numero','complemento','bairro','cidade','estado','cep',
               'profissao','renda_estimada'];
    foreach ($campos as $c) {
        $d[$c] = sanitizar($_POST[$c] ?? '');
    }
    $d['cpf']      = preg_replace('/\D/', '', $d['cpf']) ?: null;
    $d['telefone'] = preg_replace('/\D/', '', $d['telefone']) ?: null;
    $d['whatsapp'] = preg_replace('/\D/', '', $d['whatsapp']) ?: null;
    $d['renda_estimada'] = !empty($_POST['renda_estimada'])
        ? (float)str_replace(['.', ','], ['', '.'], $_POST['renda_estimada'])
        : null;

    if (empty($d['nome'])) $erros[] = 'Nome é obrigatório.';
    if (!empty($_POST['email']) && !validarEmail($_POST['email'])) $erros[] = 'E-mail inválido.';

    $senha_raw = $_POST['senha_acesso'] ?? '';
    if (!empty($senha_raw)) {
        if (strlen($senha_raw) < 6) $erros[] = 'Senha deve ter no mínimo 6 caracteres.';
    }

    if (empty($erros)) {
        $campos_upd = array_filter(
            array_intersect_key($d, array_flip($campos)),
            fn($v) => $v !== null
        );
        if (!empty($senha_raw)) $campos_upd['senha'] = hashSenha($senha_raw);
        atualizar('clientes', $campos_upd, 'id = ?', [$id]);
        header('Location: ' . ADMIN_URL . 'clientes/?msg=salvo');
        exit;
    }
}

$formas_pagamento = [
    'avista'        => 'À Vista',
    'financiamento' => 'Financiamento',
    'cartao'        => 'Cartão',
    'pix'           => 'PIX',
    'troca'         => 'Troca',
    'consorcio'     => 'Consórcio',
];

// Vendas do cliente
$vendas = obterTodas(
    "SELECT ve.*, v.marca, v.modelo, v.ano
     FROM vendas ve
     JOIN veiculos v ON ve.veiculo_id = v.id
     WHERE ve.cliente_id = ? ORDER BY ve.data_venda DESC",
    [$id]
);

require_once __DIR__ . '/../includes/header.php';
?>

<?php if ($erros): ?>
<div class="alert-admin alert-admin--error">
    <?php foreach ($erros as $e): ?><?= htmlspecialchars($e) ?><br><?php endforeach; ?>
</div>
<?php endif; ?>

<div class="page__header">
    <div>
        <h1 class="page__titulo"><?= htmlspecialchars($cliente['nome']) ?></h1>
        <p class="page__subtitulo">Cliente #<?= $id ?> · <?= count($vendas) ?> compra(s)</p>
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
                    <input type="text" name="profissao" value="<?= htmlspecialchars($d['profissao'] ?? '') ?>">
                </div>
                <div class="form-grupo">
                    <label>CPF</label>
                    <input type="text" name="cpf"
                           value="<?= $d['cpf'] ? htmlspecialchars(formatarCPF($d['cpf'])) : '' ?>"
                           placeholder="000.000.000-00" maxlength="14">
                </div>
                <div class="form-grupo">
                    <label>RG</label>
                    <input type="text" name="rg" value="<?= htmlspecialchars($d['rg'] ?? '') ?>">
                </div>
                <div class="form-grupo">
                    <label>Renda Estimada (R$)</label>
                    <input type="text" name="renda_estimada"
                           value="<?= $d['renda_estimada'] ? number_format((float)$d['renda_estimada'], 2, ',', '.') : '' ?>"
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
                    <input type="email" name="email" value="<?= htmlspecialchars($d['email'] ?? '') ?>">
                </div>
                <div class="form-grupo">
                    <label>Telefone</label>
                    <input type="text" name="telefone"
                           value="<?= $d['telefone'] ? htmlspecialchars(formatarTelefone($d['telefone'])) : '' ?>">
                </div>
                <div class="form-grupo">
                    <label>WhatsApp</label>
                    <input type="text" name="whatsapp"
                           value="<?= $d['whatsapp'] ? htmlspecialchars(formatarTelefone($d['whatsapp'])) : '' ?>">
                </div>
                <div class="form-grupo">
                    <label>Nova Senha (Área do Cliente)</label>
                    <input type="password" name="senha_acesso" placeholder="Deixe em branco para não alterar" autocomplete="new-password">
                    <span class="form-grupo__hint">
                        <?= !empty($d['senha']) ? '✓ Senha já definida — preencha só para redefinir.' : '⚠ Sem senha — cliente não consegue entrar.' ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="form-section">
        <div class="form-section__titulo">📍 Endereço</div>
        <div class="form-section__body">
            <div class="form-grid">
                <div class="form-grupo">
                    <label>CEP</label>
                    <input type="text" name="cep" id="campo_cep" value="<?= htmlspecialchars($d['cep'] ?? '') ?>" maxlength="9" placeholder="00000-000">
                    <span class="form-grupo__hint" id="cep_status"></span>
                </div>
                <div class="form-grupo form-grupo--2">
                    <label>Logradouro</label>
                    <input type="text" name="endereco" id="campo_endereco" value="<?= htmlspecialchars($d['endereco'] ?? '') ?>">
                </div>
                <div class="form-grupo">
                    <label>Número</label>
                    <input type="text" name="numero" value="<?= htmlspecialchars($d['numero'] ?? '') ?>">
                </div>
                <div class="form-grupo">
                    <label>Complemento</label>
                    <input type="text" name="complemento" value="<?= htmlspecialchars($d['complemento'] ?? '') ?>">
                </div>
                <div class="form-grupo">
                    <label>Bairro</label>
                    <input type="text" name="bairro" id="campo_bairro" value="<?= htmlspecialchars($d['bairro'] ?? '') ?>">
                </div>
                <div class="form-grupo">
                    <label>Cidade</label>
                    <input type="text" name="cidade" id="campo_cidade" value="<?= htmlspecialchars($d['cidade'] ?? '') ?>">
                </div>
                <div class="form-grupo">
                    <label>Estado</label>
                    <input type="text" name="estado" id="campo_estado" value="<?= htmlspecialchars($d['estado'] ?? '') ?>"
                           maxlength="2" style="text-transform:uppercase">
                </div>
            </div>
        </div>
    </div>

    <div class="form-acoes">
        <a href="." class="btn-admin btn-admin--secondary">Cancelar</a>
        <button type="submit" class="btn-admin btn-admin--primary">Salvar Alterações</button>
    </div>
</form>

<!-- HISTÓRICO DE COMPRAS -->
<?php if ($vendas): ?>
<div style="margin-top:14px">
    <div class="table-container">
        <div class="table-header">
            <h2 class="table-header__titulo">Histórico de Compras</h2>
        </div>
        <table class="table">
            <thead><tr>
                <th>Data</th><th>Veículo</th><th>Forma Pagamento</th>
                <th>Valor</th><th>Status</th>
            </tr></thead>
            <tbody>
            <?php foreach ($vendas as $v): ?>
            <tr>
                <td><?= formatarData($v['data_venda']) ?></td>
                <td class="td-titulo"><?= htmlspecialchars($v['marca'].' '.$v['modelo'].' '.$v['ano']) ?></td>
                <td><?= $formas_pagamento[$v['forma_pagamento']] ?? $v['forma_pagamento'] ?></td>
                <td><?= formatarMoeda($v['preco_venda']) ?></td>
                <td><span class="badge-admin badge-admin--<?= $v['status'] ?>"><?= ucfirst($v['status']) ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<script>
(function () {
    var cepInput = document.getElementById('campo_cep');
    if (!cepInput) return;
    cepInput.addEventListener('blur', function () {
        var cep = this.value.replace(/\D/g, '');
        if (cep.length !== 8) return;
        var status = document.getElementById('cep_status');
        status.textContent = 'Buscando...';
        fetch('https://viacep.com.br/ws/' + cep + '/json/')
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.erro) { status.textContent = 'CEP não encontrado.'; return; }
                status.textContent = '';
                if (!document.getElementById('campo_endereco').value) document.getElementById('campo_endereco').value = d.logradouro || '';
                if (!document.getElementById('campo_bairro').value)   document.getElementById('campo_bairro').value   = d.bairro    || '';
                if (!document.getElementById('campo_cidade').value)   document.getElementById('campo_cidade').value   = d.localidade || '';
                if (!document.getElementById('campo_estado').value)   document.getElementById('campo_estado').value   = d.uf         || '';
            })
            .catch(function () { status.textContent = ''; });
    });
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
