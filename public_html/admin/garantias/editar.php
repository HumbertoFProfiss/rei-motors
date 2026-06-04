<?php
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/functions.php';

requerAutenticacao();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: ' . ADMIN_URL . 'garantias/');
    exit;
}

$chamado = obterUmaLinha("SELECT * FROM garantias_chamados WHERE id = ?", [$id]);
if (!$chamado) {
    header('Location: ' . ADMIN_URL . 'garantias/');
    exit;
}

$titulo_pagina = 'Editar Chamado #' . $id;
$pagina_ativa  = 'garantias';
$admin_root    = '../';
$breadcrumb    = [
    ['label' => 'Garantias', 'url' => '../garantias/'],
    ['label' => 'Chamado #' . $id, 'url' => ''],
];

$status_garantia = [
    'aberto'       => 'Aberto',
    'em_andamento' => 'Em Andamento',
    'resolvido'    => 'Resolvido',
    'recusado'     => 'Recusado',
];

$erros = [];
$d = $chamado;

$veiculos = obterTodas("SELECT id, marca, modelo, ano FROM veiculos WHERE status = 'vendido' ORDER BY marca, modelo");
$clientes = obterTodas("SELECT id, nome, cpf FROM clientes ORDER BY nome");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $d['veiculo_id']    = (int)($_POST['veiculo_id']    ?? 0);
    $d['cliente_id']    = (int)($_POST['cliente_id']    ?? 0) ?: null;
    $d['tipo_problema'] = sanitizar($_POST['tipo_problema'] ?? '');
    $d['descricao']     = sanitizar($_POST['descricao']     ?? '');
    $d['status']        = array_key_exists($_POST['status'] ?? '', $status_garantia)
                          ? $_POST['status'] : $chamado['status'];
    $d['data_abertura'] = sanitizar($_POST['data_abertura'] ?? $chamado['data_abertura']);
    $data_res_raw       = trim($_POST['data_resolucao'] ?? '');
    $d['data_resolucao'] = $data_res_raw ?: null;
    $custo_raw           = trim($_POST['custo_reparo'] ?? '');
    $d['custo_reparo']   = $custo_raw !== '' ? (float)str_replace(['.', ','], ['', '.'], $custo_raw) : null;
    $d['observacoes']    = sanitizar($_POST['observacoes'] ?? '') ?: null;
    $d['fornecedor']     = sanitizar($_POST['fornecedor']    ?? '') ?: null;
    $d['peca_descricao'] = sanitizar($_POST['peca_descricao'] ?? '') ?: null;
    $cp = trim($_POST['custo_peca']    ?? '');
    $cs = trim($_POST['custo_servico'] ?? '');
    $d['custo_peca']    = $cp !== '' ? (float)str_replace(['.', ','], ['', '.'], $cp) : 0;
    $d['custo_servico'] = $cs !== '' ? (float)str_replace(['.', ','], ['', '.'], $cs) : 0;
    if ($custo_raw === '') {
        $d['custo_reparo'] = $d['custo_peca'] + $d['custo_servico'];
    }

    if ($d['veiculo_id'] <= 0)    $erros[] = 'Selecione o veículo.';
    if (empty($d['tipo_problema'])) $erros[] = 'Informe o tipo do problema.';

    if (empty($erros)) {
        $campos = ['veiculo_id', 'cliente_id', 'tipo_problema', 'descricao', 'status',
                   'data_abertura', 'data_resolucao', 'custo_reparo', 'observacoes',
                   'fornecedor', 'peca_descricao', 'custo_peca', 'custo_servico'];
        $update = array_intersect_key($d, array_flip($campos));
        atualizar('garantias_chamados', $update, 'id = ?', [$id]);

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
        <h1 class="page__titulo">Chamado de Garantia #<?= $id ?></h1>
        <p class="page__subtitulo">Aberto em <?= formatarData($chamado['data_abertura']) ?></p>
    </div>
</div>

<form method="POST" action="">
    <div class="form-section">
        <div class="form-section__titulo">🛡️ Dados do Chamado</div>
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
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-grupo">
                    <label>Status</label>
                    <select name="status">
                        <?php foreach ($status_garantia as $k => $v): ?>
                        <option value="<?= $k ?>" <?= $d['status'] === $k ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-grupo">
                    <label>Data de Abertura</label>
                    <input type="date" name="data_abertura" value="<?= htmlspecialchars($d['data_abertura']) ?>">
                </div>
                <div class="form-grupo">
                    <label>Data de Resolução</label>
                    <input type="date" name="data_resolucao" value="<?= htmlspecialchars($d['data_resolucao'] ?? '') ?>">
                </div>
                <div class="form-grupo form-grupo--2">
                    <label>Tipo / Título do Problema <span class="obrigatorio">*</span></label>
                    <input type="text" name="tipo_problema" required value="<?= htmlspecialchars($d['tipo_problema']) ?>">
                </div>
                <div class="form-grupo form-grupo--2">
                    <label>Descrição Detalhada</label>
                    <textarea name="descricao" rows="3"><?= htmlspecialchars($d['descricao']) ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="form-section">
        <div class="form-section__titulo">🔧 Fornecedor e Peças</div>
        <div class="form-section__body">
            <div class="form-grid">
                <div class="form-grupo form-grupo--2">
                    <label>Fornecedor do Serviço</label>
                    <input type="text" name="fornecedor"
                           value="<?= htmlspecialchars($d['fornecedor'] ?? '') ?>"
                           placeholder="Ex: Oficina Central Automecânica">
                </div>
                <div class="form-grupo form-grupo--2">
                    <label>Peça / Material Utilizado</label>
                    <input type="text" name="peca_descricao"
                           value="<?= htmlspecialchars($d['peca_descricao'] ?? '') ?>"
                           placeholder="Ex: Câmbio automático remanufaturado ZF 6HP">
                </div>
                <div class="form-grupo">
                    <label>Custo da Peça (R$)</label>
                    <input type="text" name="custo_peca" id="custoPeca"
                           value="<?= ($d['custo_peca'] ?? 0) > 0 ? number_format((float)$d['custo_peca'], 2, ',', '.') : '' ?>"
                           placeholder="0,00" oninput="calcTotal()">
                </div>
                <div class="form-grupo">
                    <label>Custo do Serviço / M.O. (R$)</label>
                    <input type="text" name="custo_servico" id="custoServico"
                           value="<?= ($d['custo_servico'] ?? 0) > 0 ? number_format((float)$d['custo_servico'], 2, ',', '.') : '' ?>"
                           placeholder="0,00" oninput="calcTotal()">
                </div>
                <div class="form-grupo">
                    <label>Custo Total do Reparo (R$)</label>
                    <input type="text" name="custo_reparo" id="custoTotal"
                           value="<?= $d['custo_reparo'] !== null ? number_format((float)$d['custo_reparo'], 2, ',', '.') : '' ?>"
                           placeholder="Calculado automaticamente">
                    <span class="form-grupo__hint">Preencha manualmente para sobrescrever. Abate da margem do veículo.</span>
                </div>
                <div class="form-grupo form-grupo--2">
                    <label>Observações / Solução Aplicada</label>
                    <textarea name="observacoes" rows="3"><?= htmlspecialchars($d['observacoes'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="form-acoes">
        <a href="." class="btn-admin btn-admin--secondary">Cancelar</a>
        <button type="submit" class="btn-admin btn-admin--primary">Salvar Chamado</button>
    </div>
</form>

<script>
function calcTotal() {
    var toFloat = function(s) {
        return parseFloat((s || '0').replace(/\./g,'').replace(',','.')) || 0;
    };
    var total = toFloat(document.getElementById('custoPeca').value)
              + toFloat(document.getElementById('custoServico').value);
    var campo = document.getElementById('custoTotal');
    if (total > 0 && campo.value === '') {
        campo.placeholder = 'R$ ' + total.toFixed(2).replace('.',',');
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
