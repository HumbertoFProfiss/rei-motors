<?php
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/functions.php';

requerAutenticacao();

$titulo_pagina = 'Novo Chamado de Garantia';
$pagina_ativa  = 'garantias';
$admin_root    = '../';
$breadcrumb    = [
    ['label' => 'Garantias', 'url' => '../garantias/'],
    ['label' => 'Novo',      'url' => ''],
];

$erros = [];
$d = [
    'veiculo_id'   => (int)($_GET['veiculo_id'] ?? 0),
    'cliente_id'   => '',
    'venda_id'     => '',
    'tipo_problema' => '',
    'descricao'    => '',
    'status'       => 'aberto',
    'data_abertura' => date('Y-m-d'),
    'custo_reparo' => '',
    'observacoes'  => '',
];

$veiculos  = obterTodas("SELECT id, marca, modelo, ano FROM veiculos ORDER BY marca, modelo");
$clientes  = obterTodas("SELECT id, nome, cpf FROM clientes ORDER BY nome");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $d['veiculo_id']    = (int)($_POST['veiculo_id']    ?? 0);
    $d['cliente_id']    = (int)($_POST['cliente_id']    ?? 0) ?: null;
    $d['venda_id']      = (int)($_POST['venda_id']      ?? 0) ?: null;
    $d['tipo_problema'] = sanitizar($_POST['tipo_problema'] ?? '');
    $d['descricao']     = sanitizar($_POST['descricao']     ?? '');
    $d['status']        = 'aberto';
    $d['data_abertura'] = sanitizar($_POST['data_abertura'] ?? date('Y-m-d'));
    $custo_raw          = trim($_POST['custo_reparo'] ?? '');
    $d['custo_reparo']  = $custo_raw !== '' ? (float)str_replace(['.', ','], ['', '.'], $custo_raw) : null;
    $d['observacoes']   = sanitizar($_POST['observacoes']   ?? '') ?: null;

    if ($d['veiculo_id'] <= 0)    $erros[] = 'Selecione o veículo.';
    if (empty($d['tipo_problema'])) $erros[] = 'Informe o tipo do problema.';

    if (empty($erros)) {
        inserir('garantias_chamados', $d);
        header('Location: ' . ADMIN_URL . 'garantias/?msg=salvo');
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
        <h1 class="page__titulo">Novo Chamado de Garantia</h1>
    </div>
</div>

<form method="POST" action="">
    <div class="form-section">
        <div class="form-section__titulo">🛡️ Informações do Chamado</div>
        <div class="form-section__body">
            <div class="form-grid">
                <div class="form-grupo">
                    <label>Veículo <span class="obrigatorio">*</span></label>
                    <select name="veiculo_id" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($veiculos as $v): ?>
                        <option value="<?= $v['id'] ?>" <?= (int)$d['veiculo_id'] === $v['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($v['marca'].' '.$v['modelo'].' '.$v['ano']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-grupo">
                    <label>Cliente</label>
                    <select name="cliente_id">
                        <option value="">Selecione...</option>
                        <?php foreach ($clientes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= (int)$d['cliente_id'] === $c['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['nome']) ?>
                            <?= $c['cpf'] ? '('.htmlspecialchars(formatarCPF($c['cpf'])).')' : '' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-grupo">
                    <label>Data de Abertura</label>
                    <input type="date" name="data_abertura" value="<?= $d['data_abertura'] ?>">
                </div>
                <div class="form-grupo">
                    <label>Custo do Reparo (R$)</label>
                    <input type="text" name="custo_reparo"
                           value="<?= htmlspecialchars($d['custo_reparo'] ?? '') ?>"
                           placeholder="0,00">
                    <span class="form-grupo__hint">Abate da margem do veículo automaticamente</span>
                </div>
                <div class="form-grupo form-grupo--2">
                    <label>Tipo / Título do Problema <span class="obrigatorio">*</span></label>
                    <input type="text" name="tipo_problema" required
                           value="<?= htmlspecialchars($d['tipo_problema']) ?>"
                           placeholder="Ex: Falha no câmbio automático">
                </div>
                <div class="form-grupo form-grupo--2">
                    <label>Descrição Detalhada</label>
                    <textarea name="descricao" rows="3"
                              placeholder="Descreva o problema relatado pelo cliente..."><?= htmlspecialchars($d['descricao']) ?></textarea>
                </div>
                <div class="form-grupo form-grupo--2">
                    <label>Observações Internas</label>
                    <textarea name="observacoes" rows="2"
                              placeholder="Solução aplicada, peças trocadas..."><?= htmlspecialchars($d['observacoes'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="form-acoes">
        <a href="." class="btn-admin btn-admin--secondary">Cancelar</a>
        <button type="submit" class="btn-admin btn-admin--primary">Abrir Chamado</button>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
