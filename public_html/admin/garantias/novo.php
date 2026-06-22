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
    'veiculo_id'    => (int)($_GET['veiculo_id'] ?? 0),
    'cliente_id'    => '',
    'venda_id'      => '',
    'tipo_problema' => '',
    'descricao'     => '',
    'status'         => 'aberto',
    'data_abertura'  => date('Y-m-d'),
    'data_resolucao' => '',
    'custo_reparo'   => '',
    'observacoes'    => '',
    'fornecedor'    => '',
    'peca_descricao'=> '',
    'custo_peca'    => 0,
    'custo_servico' => 0,
];

$veiculos  = obterTodas("SELECT id, marca, modelo, ano FROM veiculos WHERE status = 'vendido' ORDER BY marca, modelo");
$clientes  = obterTodas("SELECT id, nome, cpf FROM clientes ORDER BY nome");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $d['veiculo_id']    = (int)($_POST['veiculo_id']    ?? 0);
    $d['cliente_id']    = (int)($_POST['cliente_id']    ?? 0) ?: null;
    $d['venda_id']      = (int)($_POST['venda_id']      ?? 0) ?: null;
    $d['tipo_problema'] = sanitizar($_POST['tipo_problema'] ?? '');
    $d['descricao']     = sanitizar($_POST['descricao']     ?? '');
    $status_validos     = ['aberto', 'em_andamento', 'resolvido', 'recusado'];
    $d['status']        = in_array($_POST['status'] ?? '', $status_validos, true) ? $_POST['status'] : 'aberto';
    $d['data_abertura'] = sanitizar($_POST['data_abertura'] ?? date('Y-m-d'));
    $d['data_resolucao'] = ($d['status'] === 'resolvido' && !empty($_POST['data_resolucao']))
                          ? sanitizar($_POST['data_resolucao']) : null;
    $custo_raw          = trim($_POST['custo_reparo'] ?? '');
    $d['custo_reparo']  = $custo_raw !== '' ? (float)str_replace(['.', ','], ['', '.'], $custo_raw) : null;
    $d['observacoes']   = sanitizar($_POST['observacoes']   ?? '') ?: null;
    $d['fornecedor']    = sanitizar($_POST['fornecedor']    ?? '') ?: null;
    $d['peca_descricao']= sanitizar($_POST['peca_descricao'] ?? '') ?: null;
    $cp = trim($_POST['custo_peca']    ?? '');
    $cs = trim($_POST['custo_servico'] ?? '');
    $d['custo_peca']    = $cp !== '' ? (float)str_replace(['.', ','], ['', '.'], $cp) : 0;
    $d['custo_servico'] = $cs !== '' ? (float)str_replace(['.', ','], ['', '.'], $cs) : 0;
    // custo_reparo = soma dos dois se não informado manualmente
    if ($custo_raw === '') {
        $d['custo_reparo'] = $d['custo_peca'] + $d['custo_servico'];
    }

    if ($d['veiculo_id'] <= 0)    $erros[] = 'Selecione o veículo.';
    if (empty($d['tipo_problema'])) $erros[] = 'Informe o tipo do problema.';

    if (empty($erros)) {
        $novo_id = inserir('garantias_chamados', $d);

        // Se criado já como resolvido com custo, transfere para custos do veículo e contas a pagar
        if ($d['status'] === 'resolvido' && ($d['custo_reparo'] ?? 0) > 0 && $novo_id) {
            $vei   = obterUmaLinha("SELECT marca, modelo, ano FROM veiculos WHERE id = ?", [$d['veiculo_id']]);
            $descr = 'Garantia: ' . $d['tipo_problema']
                   . ($vei ? ' — ' . $vei['marca'] . ' ' . $vei['modelo'] . ' ' . $vei['ano'] : '');
            $data_ref = $d['data_resolucao'] ?? $d['data_abertura'];

            inserir('custos_veiculo', [
                'veiculo_id'   => $d['veiculo_id'],
                'categoria'    => 'garantia',
                'descricao'    => $descr,
                'valor'        => $d['custo_reparo'],
                'data_despesa' => $data_ref,
            ]);

            inserir('contas_pagar', [
                'descricao'       => $descr,
                'valor'           => $d['custo_reparo'],
                'data_vencimento' => $data_ref,
                'data_pagamento'  => $data_ref,
                'categoria'       => 'garantia',
                'status'          => 'paga',
                'observacoes'     => 'Gerado automaticamente de garantias_chamados #' . $novo_id,
            ]);
        }

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
                    <label>Status</label>
                    <select name="status" id="statusChamado" onchange="toggleDataResolucao()">
                        <option value="aberto"       <?= $d['status']==='aberto'       ?'selected':'' ?>>Aberto</option>
                        <option value="em_andamento" <?= $d['status']==='em_andamento' ?'selected':'' ?>>Em Andamento</option>
                        <option value="resolvido"    <?= $d['status']==='resolvido'    ?'selected':'' ?>>Resolvido</option>
                        <option value="recusado"     <?= $d['status']==='recusado'     ?'selected':'' ?>>Recusado</option>
                    </select>
                </div>
                <div class="form-grupo" id="campoDataResolucao" style="display:<?= $d['status']==='resolvido'?'block':'none' ?>">
                    <label>Data de Resolução</label>
                    <input type="date" name="data_resolucao" value="<?= htmlspecialchars($d['data_resolucao'] ?? '') ?>">
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
                           value="<?= $d['custo_peca'] ? number_format((float)$d['custo_peca'], 2, ',', '.') : '' ?>"
                           placeholder="0,00" oninput="calcTotal()">
                </div>
                <div class="form-grupo">
                    <label>Custo do Serviço / M.O. (R$)</label>
                    <input type="text" name="custo_servico" id="custoServico"
                           value="<?= $d['custo_servico'] ? number_format((float)$d['custo_servico'], 2, ',', '.') : '' ?>"
                           placeholder="0,00" oninput="calcTotal()">
                </div>
                <div class="form-grupo">
                    <label>Custo Total do Reparo (R$)</label>
                    <input type="text" name="custo_reparo" id="custoTotal"
                           value="<?= $d['custo_reparo'] ? number_format((float)$d['custo_reparo'], 2, ',', '.') : '' ?>"
                           placeholder="Calculado automaticamente">
                    <span class="form-grupo__hint">Preencha manualmente para sobrescrever. Abate da margem do veículo.</span>
                </div>
                <div class="form-grupo form-grupo--2">
                    <label>Observações Internas</label>
                    <textarea name="observacoes" rows="2"
                              placeholder="Solução aplicada, prazo dado ao cliente..."><?= htmlspecialchars($d['observacoes'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="form-acoes">
        <a href="." class="btn-admin btn-admin--secondary">Cancelar</a>
        <button type="submit" class="btn-admin btn-admin--primary">Abrir Chamado</button>
    </div>
</form>

<script>
function toggleDataResolucao() {
    var s = document.getElementById('statusChamado').value;
    document.getElementById('campoDataResolucao').style.display = s === 'resolvido' ? 'block' : 'none';
}
function calcTotal() {
    var toFloat = function(s) {
        return parseFloat((s || '0').replace(/\./g,'').replace(',','.')) || 0;
    };
    var peca    = toFloat(document.getElementById('custoPeca').value);
    var servico = toFloat(document.getElementById('custoServico').value);
    var total   = peca + servico;
    var campo   = document.getElementById('custoTotal');
    if (total > 0 && campo.value === '') {
        campo.placeholder = 'R$ ' + total.toFixed(2).replace('.',',');
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
