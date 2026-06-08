<?php
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/functions.php';

requerAutenticacao();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: ' . ADMIN_URL . 'veiculos/');
    exit;
}

$veiculo = obterUmaLinha("SELECT id, marca, modelo, ano, preco_venda, preco_custo FROM veiculos WHERE id = ?", [$id]);
if (!$veiculo) {
    header('Location: ' . ADMIN_URL . 'veiculos/');
    exit;
}

$categorias_despesas = [
    'revisao'      => 'Revisão / Mecânica',
    'funilaria'    => 'Funilaria / Pintura',
    'limpeza'      => 'Limpeza / Estética',
    'documentacao' => 'Documentação / Taxas',
    'pneus'        => 'Pneus / Suspensão',
    'eletrica'     => 'Elétrica / Eletrônica',
    'garantia'     => 'Garantia / Pós-Venda',
    'outros'       => 'Outros',
];

$titulo_pagina = 'Custos — ' . $veiculo['marca'] . ' ' . $veiculo['modelo'];
$pagina_ativa  = 'veiculos';
$admin_root    = '../';
$breadcrumb    = [
    ['label' => 'Veículos',  'url' => '../veiculos/'],
    ['label' => htmlspecialchars($veiculo['marca'].' '.$veiculo['modelo']), 'url' => 'editar.php?id='.$id],
    ['label' => 'Custos',    'url' => ''],
];

$erros   = [];
$sucesso = '';

// DELETE custo
if (isset($_GET['deletar']) && ehAdmin()) {
    $cid = (int)$_GET['deletar'];
    $custo = obterUmaLinha("SELECT arquivo_nf FROM custos_veiculo WHERE id = ? AND veiculo_id = ?", [$cid, $id]);
    if ($custo) {
        if ($custo['arquivo_nf'] && file_exists(UPLOAD_PATH . $custo['arquivo_nf'])) {
            unlink(UPLOAD_PATH . $custo['arquivo_nf']);
        }
        executarQuery("DELETE FROM custos_veiculo WHERE id = ?", [$cid]);
    }
    header('Location: ' . ADMIN_URL . 'veiculos/custos.php?id=' . $id . '&msg=deletado');
    exit;
}

// ADD custo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoria   = sanitizar($_POST['categoria']   ?? '');
    $descricao   = sanitizar($_POST['descricao']   ?? '');
    $valor       = (float)str_replace(['.', ','], ['', '.'], $_POST['valor'] ?? '0');
    $data_desp   = sanitizar($_POST['data_despesa'] ?? date('Y-m-d'));
    $numero_nf   = sanitizar($_POST['numero_nf']   ?? '');

    if (!isset($categorias_despesas[$categoria])) $erros[] = 'Categoria inválida.';
    if (empty($descricao))  $erros[] = 'Descrição é obrigatória.';
    if ($valor <= 0)        $erros[] = 'Valor deve ser maior que zero.';

    $arquivo_nf = null;
    if (!empty($_FILES['arquivo_nf']['tmp_name'])) {
        $upload = uploadImagem($_FILES['arquivo_nf'], 'nf/');
        if ($upload['sucesso']) {
            $arquivo_nf = $upload['caminho'];
        } else {
            $erros[] = 'Erro ao enviar arquivo da NF.';
        }
    }

    if (empty($erros)) {
        $custo_id = inserir('custos_veiculo', [
            'veiculo_id'   => $id,
            'categoria'    => $categoria,
            'descricao'    => $descricao,
            'valor'        => $valor,
            'data_despesa' => $data_desp,
            'numero_nf'    => $numero_nf ?: null,
            'arquivo_nf'   => $arquivo_nf,
        ]);

        // Vincular com contas a pagar se solicitado
        if (!empty($_POST['lancar_contas_pagar'])) {
            $veiculo_desc = $veiculo['marca'] . ' ' . $veiculo['modelo'] . ' ' . $veiculo['ano'];
            $cp_id = inserir('contas_pagar', [
                'descricao'       => $descricao . ' — ' . $veiculo_desc,
                'valor'           => $valor,
                'data_vencimento' => $data_desp,
                'categoria'       => $categoria,
                'status'          => 'pendente',
                'numero_nf'       => $numero_nf ?: null,
                'veiculo_id'      => $id,
                'observacoes'     => 'Gerado automaticamente de custos_veiculo #' . $custo_id,
            ]);
        }

        $sucesso = 'Custo adicionado' . (!empty($_POST['lancar_contas_pagar']) ? ' e lançado em Contas a Pagar.' : '.');
    }
}

// Filtro por categoria
$filtro_cat = sanitizar($_GET['cat'] ?? '');
$where_cat  = ($filtro_cat && isset($categorias_despesas[$filtro_cat])) ? ' AND categoria = ?' : '';
$params_cat = $where_cat ? [$id, $filtro_cat] : [$id];

// Listar custos
$custos = obterTodas(
    "SELECT * FROM custos_veiculo WHERE veiculo_id = ?" . $where_cat . " ORDER BY data_despesa DESC",
    $params_cat
);

// Total geral (sem filtro) para percentuais
$total_geral_custos = (float)(obterUmaLinha(
    "SELECT COALESCE(SUM(valor),0) t FROM custos_veiculo WHERE veiculo_id = ?",
    [$id]
)['t'] ?? 0);

// Totais para cálculo de lucro (sempre sem filtro de categoria para não distorcer)
$total_todos_custos = (float)(obterUmaLinha(
    "SELECT COALESCE(SUM(valor),0) t FROM custos_veiculo WHERE veiculo_id = ?",
    [$id]
)['t'] ?? 0);

$total_garantias = (float)(obterUmaLinha(
    "SELECT COALESCE(SUM(valor),0) t FROM custos_veiculo WHERE veiculo_id = ? AND categoria = 'garantia'",
    [$id]
)['t'] ?? 0);

$total_despesas_prep = $total_todos_custos - $total_garantias;

// Total filtrado (para rodapé da tabela)
$total_custos_adicionais = array_sum(array_column($custos, 'valor'));

$lucro = calcularLucroVeiculo(
    $veiculo['preco_venda'],
    $veiculo['preco_custo'],
    $total_todos_custos,
    0
);

$msg = trim($_GET['msg'] ?? '');

require_once __DIR__ . '/../includes/header.php';
?>

<?php if ($msg === 'deletado'): ?><div class="alert-admin alert-admin--success">✓ Custo removido.</div><?php endif; ?>
<?php if ($sucesso): ?><div class="alert-admin alert-admin--success">✓ <?= htmlspecialchars($sucesso) ?></div><?php endif; ?>
<?php if ($erros): ?>
<div class="alert-admin alert-admin--error">
    <?php foreach ($erros as $e): ?><?= htmlspecialchars($e) ?><br><?php endforeach; ?>
</div>
<?php endif; ?>

<div class="page__header">
    <div>
        <h1 class="page__titulo"><?= htmlspecialchars($veiculo['marca'].' '.$veiculo['modelo'].' '.$veiculo['ano']) ?></h1>
        <p class="page__subtitulo">Custos e cálculo de lucro</p>
    </div>
    <div class="page__acoes">
        <a href="editar.php?id=<?= $id ?>" class="btn-admin btn-admin--secondary">← Voltar ao Veículo</a>
    </div>
</div>

<!-- RESUMO FINANCEIRO -->
<div class="stats-grid" style="margin-bottom:1.5rem">
    <div class="stat-card stat-card--blue">
        <div class="stat-card__label">Preço de Custo</div>
        <div class="stat-card__valor"><?= formatarMoeda($veiculo['preco_custo']) ?></div>
    </div>
    <div class="stat-card stat-card--orange">
        <div class="stat-card__label">Despesas de Preparação</div>
        <div class="stat-card__valor"><?= formatarMoeda($total_despesas_prep) ?></div>
    </div>
    <div class="stat-card stat-card--red">
        <div class="stat-card__label">Custos Pós-Venda / Garantia</div>
        <div class="stat-card__valor"><?= formatarMoeda($total_garantias) ?></div>
    </div>
    <div class="stat-card <?= $lucro >= 0 ? 'stat-card--green' : 'stat-card--red' ?>">
        <div class="stat-card__label">Lucro Estimado</div>
        <div class="stat-card__valor"><?= formatarMoeda($lucro) ?></div>
        <div class="stat-card__label" style="margin-top:4px">Venda: <?= formatarMoeda($veiculo['preco_venda']) ?></div>
    </div>
</div>

<!-- ADICIONAR CUSTO -->
<div class="table-container" style="margin-bottom:1.5rem">
    <div class="table-header">
        <h2 class="table-header__titulo">Adicionar Despesa</h2>
    </div>
    <div style="padding:1.5rem">
        <form method="POST" enctype="multipart/form-data" action="">
            <div class="form-grid">
                <div class="form-grupo">
                    <label>Categoria <span class="obrigatorio">*</span></label>
                    <select name="categoria" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($categorias_despesas as $k => $v): ?>
                        <option value="<?= $k ?>" <?= ($_POST['categoria'] ?? '') === $k ? 'selected' : '' ?>><?= htmlspecialchars($v) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-grupo">
                    <label>Valor (R$) <span class="obrigatorio">*</span></label>
                    <input type="text" name="valor" value="<?= htmlspecialchars($_POST['valor'] ?? '') ?>" placeholder="0,00" required>
                </div>
                <div class="form-grupo">
                    <label>Data da Despesa</label>
                    <input type="date" name="data_despesa" value="<?= htmlspecialchars($_POST['data_despesa'] ?? date('Y-m-d')) ?>">
                </div>
                <div class="form-grupo">
                    <label>Número da NF</label>
                    <input type="text" name="numero_nf" value="<?= htmlspecialchars($_POST['numero_nf'] ?? '') ?>" placeholder="NF-000123">
                </div>
                <div class="form-grupo form-grupo--2">
                    <label>Descrição <span class="obrigatorio">*</span></label>
                    <input type="text" name="descricao" value="<?= htmlspecialchars($_POST['descricao'] ?? '') ?>" placeholder="Ex: Revisão preventiva 30.000 km" required>
                </div>
                <div class="form-grupo form-grupo--2">
                    <label>Arquivo da NF (PDF/imagem)</label>
                    <input type="file" name="arquivo_nf" accept=".pdf,image/jpeg,image/png,image/webp">
                </div>
            </div>
            <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--admin-border);display:flex;align-items:center;gap:16px;flex-wrap:wrap">
                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;color:var(--admin-text-muted)">
                    <input type="checkbox" name="lancar_contas_pagar" value="1" style="width:16px;height:16px">
                    Lançar também em <strong style="color:var(--admin-text)">Contas a Pagar</strong>
                </label>
                <button type="submit" class="btn-admin btn-admin--primary">+ Adicionar Despesa</button>
            </div>
        </form>
    </div>
</div>

<!-- LISTA DE CUSTOS -->
<div class="table-container">
    <div class="table-header" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
        <h2 class="table-header__titulo" style="flex:1">Despesas Cadastradas (<?= count($custos) ?>)</h2>
        <form method="GET" action="" style="display:flex;gap:8px;align-items:center">
            <input type="hidden" name="id" value="<?= $id ?>">
            <select name="cat" onchange="this.form.submit()" style="font-size:0.82rem;padding:5px 8px;background:var(--admin-card);border:1px solid var(--admin-border);color:inherit;border-radius:6px">
                <option value="">Todas as categorias</option>
                <?php foreach ($categorias_despesas as $k => $v): ?>
                <option value="<?= $k ?>" <?= $filtro_cat === $k ? 'selected' : '' ?>><?= htmlspecialchars($v) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if ($filtro_cat): ?>
            <a href="?id=<?= $id ?>" style="font-size:0.8rem;color:#D4AF37">✕ Limpar</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($custos): ?>
    <table class="table">
        <thead>
            <tr>
                <th>Data</th>
                <th>Categoria</th>
                <th>Descrição</th>
                <th>NF</th>
                <th>Valor</th>
                <th>% do Total</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($custos as $c): ?>
        <tr>
            <td><?= formatarData($c['data_despesa']) ?></td>
            <td><span class="badge-admin badge-admin--info"><?= htmlspecialchars($categorias_despesas[$c['categoria']] ?? $c['categoria']) ?></span></td>
            <td><?= htmlspecialchars($c['descricao']) ?></td>
            <td>
                <?php if ($c['numero_nf']): ?>
                    <small><?= htmlspecialchars($c['numero_nf']) ?></small>
                <?php endif; ?>
                <?php if ($c['arquivo_nf']): ?>
                    <a href="<?= BASE_URL . 'uploads/' . htmlspecialchars($c['arquivo_nf']) ?>" target="_blank"
                       class="btn-admin btn-admin--secondary btn-admin--sm">📄 NF</a>
                <?php endif; ?>
            </td>
            <td class="td-titulo"><?= formatarMoeda($c['valor']) ?></td>
            <td style="color:#888;font-size:0.82rem">
                <?= $total_geral_custos > 0 ? number_format(($c['valor'] / $total_geral_custos) * 100, 1) . '%' : '—' ?>
            </td>
            <td class="td-acoes">
                <?php if (ehAdmin()): ?>
                <button onclick="confirmarAcao('?deletar=<?= $c['id'] ?>&id=<?= $id ?>', 'Remover despesa', 'Deseja remover esta despesa? Esta ação não pode ser desfeita.')"
                        class="btn-admin btn-admin--danger btn-admin--sm">✕</button>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" style="text-align:right;font-weight:600;padding:0.75rem 1rem">Total Filtrado:</td>
                <td class="td-titulo" style="color:#D4AF37"><?= formatarMoeda($total_custos_adicionais) ?></td>
                <td style="color:#888;font-size:0.82rem">
                    <?= $total_geral_custos > 0 && $total_geral_custos !== $total_custos_adicionais
                        ? number_format(($total_custos_adicionais / $total_geral_custos) * 100, 1) . '% do geral'
                        : '' ?>
                </td>
                <td></td>
            </tr>
        </tfoot>
    </table>
    <?php else: ?>
    <div class="empty-state">
        <div class="empty-state__icone">💰</div>
        <p class="empty-state__titulo">Nenhuma despesa cadastrada</p>
        <p class="empty-state__subtitulo">Use o formulário acima para adicionar despesas deste veículo</p>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
