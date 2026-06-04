<?php
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/functions.php';

requerAutenticacao();

$titulo_pagina = 'Nova Venda';
$pagina_ativa  = 'vendas';
$admin_root    = '../';
$breadcrumb    = [
    ['label' => 'Vendas', 'url' => '../vendas/'],
    ['label' => 'Nova', 'url' => ''],
];

$erros = [];
$d = [
    'veiculo_id' => '', 'cliente_id' => '', 'vendedor_id' => $_SESSION['usuario_id'],
    'forma_pagamento' => 'avista', 'preco_venda' => '', 'desconto_aplicado' => '0',
    'valor_troca' => '0', 'data_venda' => date('Y-m-d'), 'data_entrega' => '',
    'status' => 'pendente', 'numero_contrato' => '', 'observacoes' => '',
];

// Listas para selects
$veiculos_disponiveis = obterTodas(
    "SELECT id, marca, modelo, ano, preco_venda, tipo_propriedade, consignado_valor_minimo, consignado_proprietario_nome FROM veiculos WHERE status IN ('disponivel','reservado') ORDER BY marca, modelo"
);
$clientes_lista = obterTodas("SELECT id, nome, cpf FROM clientes ORDER BY nome");
$vendedores     = obterTodas("SELECT id, nome FROM usuarios WHERE ativo = 1 ORDER BY nome");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $d['veiculo_id']        = (int)($_POST['veiculo_id'] ?? 0);
    $d['cliente_id']        = (int)($_POST['cliente_id'] ?? 0);
    $d['vendedor_id']       = (int)($_POST['vendedor_id'] ?? $_SESSION['usuario_id']);
    $d['forma_pagamento']   = array_key_exists($_POST['forma_pagamento'] ?? '', $formas_pagamento) ? $_POST['forma_pagamento'] : 'avista';
    $d['preco_venda']       = (float)str_replace(['.', ','], ['', '.'], $_POST['preco_venda'] ?? '0');
    $d['desconto_aplicado'] = (float)str_replace(['.', ','], ['', '.'], $_POST['desconto_aplicado'] ?? '0');
    $d['valor_troca']       = (float)str_replace(['.', ','], ['', '.'], $_POST['valor_troca'] ?? '0');
    $d['data_venda']             = sanitizar($_POST['data_venda'] ?? date('Y-m-d'));
    $d['data_entrega']           = !empty($_POST['data_entrega']) ? sanitizar($_POST['data_entrega']) : null;
    $d['prazo_garantia_dias']    = (int)($_POST['prazo_garantia_dias'] ?? 90);
    $d['status']                 = 'pendente';
    $d['numero_contrato']   = sanitizar($_POST['numero_contrato'] ?? '');
    $d['observacoes']       = sanitizar($_POST['observacoes'] ?? '');

    // Dados do carro de troca
    $troca = [
        'marca'          => sanitizar($_POST['troca_marca']   ?? ''),
        'modelo'         => sanitizar($_POST['troca_modelo']  ?? ''),
        'ano'            => (int)($_POST['troca_ano']         ?? 0),
        'cor'            => sanitizar($_POST['troca_cor']     ?? ''),
        'quilometragem'  => (int)str_replace(['.', ',', ' '], '', $_POST['troca_km'] ?? '0'),
        'valor_estimado' => (float)str_replace(['.', ','], ['', '.'], $_POST['troca_valor'] ?? '0'),
        'condicao_veiculo' => sanitizar($_POST['troca_condicao'] ?? ''),
    ];

    if ($d['veiculo_id'] <= 0)  $erros[] = 'Selecione um veículo.';
    if ($d['cliente_id'] <= 0)  $erros[] = 'Selecione um cliente.';
    if ($d['vendedor_id'] <= 0) $erros[] = 'Selecione um vendedor.';
    if ($d['preco_venda'] <= 0) $erros[] = 'Preço de venda inválido.';

    // Verifica se veículo ainda está disponível
    if ($d['veiculo_id'] > 0) {
        $vei = obterUmaLinha("SELECT id, marca, modelo, ano, tipo_propriedade, consignado_valor_minimo, consignado_proprietario_nome FROM veiculos WHERE id = ?", [$d['veiculo_id']]);
        if (!$vei) $erros[] = 'Veículo não encontrado.';
    }

    if (empty($erros)) {
        // Calcula comissão do vendedor
        $vendedor      = obterUmaLinha("SELECT comissao_percentual FROM usuarios WHERE id = ?", [$d['vendedor_id']]);
        $pct_comissao  = $vendedor ? (float)$vendedor['comissao_percentual'] : COMISSAO_PADRAO_VENDEDOR;
        $d['comissao_vendedor'] = calcularComissaoVendedor($d['preco_venda'], $pct_comissao);

        $novo_id = inserir('vendas', $d);

        // Registra carro de troca se forma de pagamento envolver troca
        if (in_array($d['forma_pagamento'], ['troca', 'troca_financiamento']) && !empty($troca['marca'])) {
            inserir('carros_troca', array_merge($troca, ['venda_id' => $novo_id]));
        }

        // Atualiza status do veículo para reservado
        atualizar('veiculos', ['status' => 'reservado'], 'id = ?', [$d['veiculo_id']]);

        // Se consignado, gera conta a pagar para o proprietário
        if (($vei['tipo_propriedade'] ?? '') === 'consignado' && ($vei['consignado_valor_minimo'] ?? 0) > 0) {
            $nome_prop = $vei['consignado_proprietario_nome'] ?? 'Proprietário';
            $carro_desc = $vei['marca'] . ' ' . $vei['modelo'] . ' ' . $vei['ano'];
            inserir('contas_pagar', [
                'descricao'       => 'Repasse consignado — ' . $carro_desc . ' (' . $nome_prop . ')',
                'valor'           => $vei['consignado_valor_minimo'],
                'data_vencimento' => $d['data_entrega'] ?: $d['data_venda'],
                'categoria'       => 'consignado',
                'status'          => 'pendente',
                'observacoes'     => 'Gerado automaticamente pela venda #' . $novo_id,
            ]);
        }

        header('Location: ' . ADMIN_URL . 'vendas/?msg=salvo');
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
        <h1 class="page__titulo">Nova Venda</h1>
        <p class="page__subtitulo">Registrar uma nova venda no sistema</p>
    </div>
</div>

<form method="POST" action="">
    <div class="form-section">
        <div class="form-section__titulo">🚗 Veículo e Partes</div>
        <div class="form-section__body">
            <div class="form-grid">
                <div class="form-grupo">
                    <label>Veículo <span class="obrigatorio">*</span></label>
                    <select name="veiculo_id" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($veiculos_disponiveis as $v): ?>
                        <option value="<?= $v['id'] ?>"
                                data-preco="<?= number_format((float)$v['preco_venda'], 2, '.', '') ?>"
                                data-consignado="<?= $v['tipo_propriedade'] === 'consignado' ? '1' : '0' ?>"
                                data-proprietario="<?= htmlspecialchars($v['consignado_proprietario_nome'] ?? '') ?>"
                                data-repasse="<?= number_format((float)($v['consignado_valor_minimo'] ?? 0), 2, ',', '.') ?>"
                                <?= (int)$d['veiculo_id'] === $v['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($v['marca'].' '.$v['modelo'].' '.$v['ano']) ?>
                            — <?= formatarMoeda($v['preco_venda']) ?>
                            <?= $v['tipo_propriedade'] === 'consignado' ? ' [CONSIGNADO]' : '' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-grupo">
                    <label>Cliente <span class="obrigatorio">*</span></label>
                    <select name="cliente_id" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($clientes_lista as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= (int)$d['cliente_id'] === $c['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['nome']) ?>
                            <?= $c['cpf'] ? '— '.htmlspecialchars(formatarCPF($c['cpf'])) : '' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="form-grupo__hint">
                        <a href="../clientes/novo.php" target="_blank" style="color:#D4AF37">+ Cadastrar novo cliente</a>
                    </span>
                </div>
                <div class="form-grupo form-grupo--2" id="aviso-consignado" style="display:none">
                    <div class="consignado-resumo" id="texto-consignado"></div>
                </div>
                <div class="form-grupo">
                    <label>Vendedor <span class="obrigatorio">*</span></label>
                    <select name="vendedor_id">
                        <?php foreach ($vendedores as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= (int)$d['vendedor_id'] === $u['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($u['nome']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="form-section">
        <div class="form-section__titulo">💰 Valores</div>
        <div class="form-section__body">
            <div class="form-grid">
                <div class="form-grupo">
                    <label>Preço de Venda (R$) <span class="obrigatorio">*</span></label>
                    <input type="text" name="preco_venda" id="precoVenda"
                           value="<?= $d['preco_venda'] > 0 ? number_format((float)$d['preco_venda'], 2, ',', '.') : '' ?>"
                           required placeholder="0,00">
                </div>
                <div class="form-grupo">
                    <label>Desconto (R$)</label>
                    <input type="text" name="desconto_aplicado"
                           value="<?= number_format((float)$d['desconto_aplicado'], 2, ',', '.') ?>"
                           placeholder="0,00">
                </div>
                <div class="form-grupo">
                    <label>Valor de Troca (R$)</label>
                    <input type="text" name="valor_troca"
                           value="<?= number_format((float)$d['valor_troca'], 2, ',', '.') ?>"
                           placeholder="0,00">
                    <span class="form-grupo__hint">Preencha se houver troca de veículo</span>
                </div>
                <div class="form-grupo">
                    <label>Forma de Pagamento <span class="obrigatorio">*</span></label>
                    <select name="forma_pagamento" id="formaPagamento">
                        <?php foreach ($formas_pagamento as $k => $v): ?>
                        <option value="<?= $k ?>" <?= $d['forma_pagamento'] === $k ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- CARRO DE TROCA (exibido condicionalmente via JS) -->
    <div class="form-section" id="secaoTroca" style="display:none">
        <div class="form-section__titulo">🔄 Carro Recebido na Troca</div>
        <div class="form-section__body">
            <div class="form-grid">
                <div class="form-grupo">
                    <label>Marca</label>
                    <input type="text" name="troca_marca" value="<?= htmlspecialchars($_POST['troca_marca'] ?? '') ?>" placeholder="Ex: Honda">
                </div>
                <div class="form-grupo">
                    <label>Modelo</label>
                    <input type="text" name="troca_modelo" value="<?= htmlspecialchars($_POST['troca_modelo'] ?? '') ?>" placeholder="Ex: Civic">
                </div>
                <div class="form-grupo">
                    <label>Ano</label>
                    <input type="number" name="troca_ano" value="<?= htmlspecialchars($_POST['troca_ano'] ?? '') ?>" min="1950" max="<?= (int)date('Y') + 1 ?>">
                </div>
                <div class="form-grupo">
                    <label>Cor</label>
                    <input type="text" name="troca_cor" value="<?= htmlspecialchars($_POST['troca_cor'] ?? '') ?>" placeholder="Ex: Prata">
                </div>
                <div class="form-grupo">
                    <label>Quilometragem</label>
                    <input type="number" name="troca_km" value="<?= htmlspecialchars($_POST['troca_km'] ?? '') ?>" min="0">
                </div>
                <div class="form-grupo">
                    <label>Valor Estimado (R$)</label>
                    <input type="text" name="troca_valor" value="<?= htmlspecialchars($_POST['troca_valor'] ?? '') ?>" placeholder="0,00">
                </div>
                <div class="form-grupo form-grupo--2">
                    <label>Condições do Veículo</label>
                    <textarea name="troca_condicao" rows="2" placeholder="Estado geral, avarias, revisões..."><?= htmlspecialchars($_POST['troca_condicao'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="form-section">
        <div class="form-section__titulo">📅 Datas e Contrato</div>
        <div class="form-section__body">
            <div class="form-grid">
                <div class="form-grupo">
                    <label>Data da Venda <span class="obrigatorio">*</span></label>
                    <input type="date" name="data_venda" value="<?= $d['data_venda'] ?>" required>
                </div>
                <div class="form-grupo">
                    <label>Data de Entrega</label>
                    <input type="date" name="data_entrega" value="<?= $d['data_entrega'] ?? '' ?>">
                </div>
                <div class="form-grupo">
                    <label>Número do Contrato</label>
                    <input type="text" name="numero_contrato"
                           value="<?= htmlspecialchars($d['numero_contrato']) ?>"
                           placeholder="REI-2025-001">
                </div>
                <div class="form-grupo">
                    <label>Prazo de Garantia (dias)</label>
                    <input type="number" name="prazo_garantia_dias" min="0" max="1825"
                           value="<?= (int)($d['prazo_garantia_dias'] ?? 90) ?>">
                    <span class="form-grupo__hint">Padrão: 90 dias. Digite 0 para sem garantia.</span>
                </div>
            </div>
            <div style="margin-top:14px">
                <div class="form-grupo">
                    <label>Observações</label>
                    <textarea name="observacoes" rows="3"><?= htmlspecialchars($d['observacoes']) ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="form-acoes">
        <a href="." class="btn-admin btn-admin--secondary">Cancelar</a>
        <button type="submit" class="btn-admin btn-admin--primary">Registrar Venda</button>
    </div>
</form>

<script>
// Preenche preço de venda e aviso consignado ao selecionar veículo
document.querySelector('select[name="veiculo_id"]').addEventListener('change', function () {
    var opt   = this.options[this.selectedIndex];
    var preco = opt.dataset.preco;
    if (preco) {
        var v     = parseFloat(preco);
        var campo = document.getElementById('precoVenda');
        campo.value = v.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    var aviso = document.getElementById('aviso-consignado');
    var texto = document.getElementById('texto-consignado');
    if (opt.dataset.consignado === '1') {
        aviso.style.display = '';
        texto.innerHTML = '🤝 <strong>Veículo Consignado</strong> — Proprietário: <strong>' + (opt.dataset.proprietario || 'Não informado') + '</strong><br>' +
            'Uma conta a pagar de <strong>R$ ' + opt.dataset.repasse + '</strong> será gerada automaticamente para repasse ao proprietário.';
    } else {
        aviso.style.display = 'none';
    }
});

// Exibe seção de troca quando forma de pagamento envolver troca
var fpSelect = document.getElementById('formaPagamento');
var secaoTroca = document.getElementById('secaoTroca');
function toggleTroca() {
    var v = fpSelect.value;
    secaoTroca.style.display = (v === 'troca' || v === 'troca_financiamento') ? '' : 'none';
}
fpSelect.addEventListener('change', toggleTroca);
toggleTroca(); // executa ao carregar (caso POST falhou com troca selecionada)
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
