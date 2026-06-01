<?php
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/functions.php';

requerAutenticacao();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: ' . ADMIN_URL . 'veiculos/'); exit; }

$veiculo = obterUmaLinha("SELECT * FROM veiculos WHERE id = ?", [$id]);
if (!$veiculo) { header('Location: ' . ADMIN_URL . 'veiculos/?msg=nao_encontrado'); exit; }

$titulo_pagina = 'Anotações — ' . $veiculo['marca'] . ' ' . $veiculo['modelo'];
$pagina_ativa  = 'veiculos';
$admin_root    = '../';
$breadcrumb    = [
    ['label' => 'Veículos', 'url' => '../veiculos/'],
    ['label' => $veiculo['marca'] . ' ' . $veiculo['modelo'], 'url' => 'editar.php?id=' . $id],
    ['label' => 'Anotações', 'url' => ''],
];

$erros = [];
$msg_ok = '';

// Atualizar checklist (via POST com action=checklist)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'checklist') {
    requerAutenticacao();
    $venda_id = (int)($_POST['venda_id'] ?? 0);
    if ($venda_id > 0) {
        atualizar('vendas', [
            'recibo_emitido'  => isset($_POST['recibo_emitido'])  ? 1 : 0,
            'recibo_entregue' => isset($_POST['recibo_entregue']) ? 1 : 0,
        ], 'id = ?', [$venda_id]);
        $msg_ok = 'Checklist atualizado.';
    }
}

// Salvar nova anotação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'anotacao') {
    requerAutenticacao();
    $texto = trim(sanitizar($_POST['texto'] ?? ''));
    if (empty($texto)) {
        $erros[] = 'A anotação não pode estar vazia.';
    } else {
        inserir('veiculos_anotacoes', [
            'veiculo_id' => $id,
            'usuario_id' => $_SESSION['usuario_id'],
            'texto'      => $texto,
        ]);
        $msg_ok = 'Anotação salva.';
    }
}

// Excluir anotação
if (isset($_GET['del']) && ehAdmin()) {
    $del_id = (int)$_GET['del'];
    deletar('veiculos_anotacoes', 'id = ? AND veiculo_id = ?', [$del_id, $id]);
    header('Location: anotacoes.php?id=' . $id . '&msg=deletado');
    exit;
}

// Buscar dados
$anotacoes = obterTodas(
    "SELECT va.*, u.nome AS autor
     FROM veiculos_anotacoes va
     JOIN usuarios u ON va.usuario_id = u.id
     WHERE va.veiculo_id = ?
     ORDER BY va.criado_em DESC",
    [$id]
);

$venda = obterUmaLinha(
    "SELECT ve.*, c.nome AS cliente_nome
     FROM vendas ve
     JOIN clientes c ON ve.cliente_id = c.id
     WHERE ve.veiculo_id = ? AND ve.status IN ('confirmada','entregue')
     ORDER BY ve.criado_em DESC LIMIT 1",
    [$id]
);

require_once __DIR__ . '/../includes/header.php';
?>

<?php if ($msg_ok): ?>
<div class="alert-admin alert-admin--success">✓ <?= htmlspecialchars($msg_ok) ?></div>
<?php endif; ?>
<?php if ($erros): ?>
<div class="alert-admin alert-admin--error"><?= implode('<br>', array_map('htmlspecialchars', $erros)) ?></div>
<?php endif; ?>
<?php if (trim($_GET['msg'] ?? '') === 'deletado'): ?>
<div class="alert-admin alert-admin--success">✓ Anotação removida.</div>
<?php endif; ?>

<div class="page__header">
    <div>
        <h1 class="page__titulo">📝 <?= htmlspecialchars($veiculo['marca'] . ' ' . $veiculo['modelo'] . ' ' . $veiculo['ano']) ?></h1>
        <p class="page__subtitulo">Anotações e documentação do veículo</p>
    </div>
    <div class="page__acoes">
        <a href="editar.php?id=<?= $id ?>" class="btn-admin btn-admin--secondary">← Voltar</a>
    </div>
</div>

<!-- CHECKLIST DOCUMENTAL -->
<?php if ($venda): ?>
<div class="form-section" style="margin-bottom:24px">
    <div class="form-section__titulo">📋 Checklist — Venda #<?= $venda['id'] ?> (<?= htmlspecialchars($venda['cliente_nome']) ?>)</div>
    <div class="form-section__body">
        <form method="POST" action="">
            <input type="hidden" name="action" value="checklist">
            <input type="hidden" name="venda_id" value="<?= $venda['id'] ?>">
            <div style="display:flex;flex-direction:column;gap:14px">
                <label class="form-check" style="font-size:0.88rem;gap:10px;cursor:pointer">
                    <input type="checkbox" name="recibo_emitido" value="1"
                           <?= $venda['recibo_emitido'] ? 'checked' : '' ?>
                           style="width:18px;height:18px;accent-color:#D4AF37">
                    <span>
                        <strong>Recibo de venda emitido</strong>
                        <small style="display:block;color:#555">O recibo/contrato foi gerado e assinado</small>
                    </span>
                </label>
                <label class="form-check" style="font-size:0.88rem;gap:10px;cursor:pointer">
                    <input type="checkbox" name="recibo_entregue" value="1"
                           <?= $venda['recibo_entregue'] ? 'checked' : '' ?>
                           style="width:18px;height:18px;accent-color:#D4AF37">
                    <span>
                        <strong>Recibo entregue ao cliente</strong>
                        <small style="display:block;color:#555">O cliente recebeu a via do recibo/contrato</small>
                    </span>
                </label>
                <label class="form-check" style="font-size:0.88rem;gap:10px;cursor:pointer">
                    <input type="checkbox" disabled
                           <?= $venda['status'] === 'entregue' ? 'checked' : '' ?>
                           style="width:18px;height:18px;accent-color:#D4AF37">
                    <span>
                        <strong>Carro entregue ao cliente</strong>
                        <small style="display:block;color:#555">Controlado pelo status da venda (atual: <?= ucfirst($venda['status']) ?>)</small>
                    </span>
                </label>
            </div>
            <div style="margin-top:16px">
                <button type="submit" class="btn-admin btn-admin--primary">Salvar Checklist</button>
            </div>
        </form>
    </div>
</div>
<?php else: ?>
<div class="alert-admin" style="margin-bottom:24px;background:rgba(212,175,55,0.06);border-color:rgba(212,175,55,0.2);color:#888">
    ℹ️ Nenhuma venda confirmada vinculada a este veículo. O checklist aparece após registrar a venda.
</div>
<?php endif; ?>

<!-- NOVA ANOTAÇÃO -->
<div class="form-section" style="margin-bottom:24px">
    <div class="form-section__titulo">✏️ Nova Anotação</div>
    <div class="form-section__body">
        <form method="POST" action="">
            <input type="hidden" name="action" value="anotacao">
            <div class="form-grupo" style="margin-bottom:12px">
                <textarea name="texto" rows="4" placeholder="Digite a anotação sobre este veículo..." style="width:100%"></textarea>
            </div>
            <button type="submit" class="btn-admin btn-admin--primary">Salvar Anotação</button>
        </form>
    </div>
</div>

<!-- LISTA DE ANOTAÇÕES -->
<div class="table-container">
    <div class="table-header">
        <h2 class="table-header__titulo">Histórico de Anotações (<?= count($anotacoes) ?>)</h2>
    </div>
    <?php if ($anotacoes): ?>
    <div style="display:flex;flex-direction:column;gap:12px;padding:16px">
        <?php foreach ($anotacoes as $a): ?>
        <div style="background:#0f0f0f;border:1px solid #222;border-radius:8px;padding:14px 16px">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px">
                <div style="flex:1">
                    <p style="margin:0 0 8px;color:#e0e0e0;line-height:1.6"><?= nl2br(htmlspecialchars($a['texto'])) ?></p>
                    <small style="color:#454545">
                        👤 <?= htmlspecialchars($a['autor']) ?> &nbsp;·&nbsp;
                        🕐 <?= formatarDataHora($a['criado_em']) ?>
                    </small>
                </div>
                <?php if (ehAdmin()): ?>
                <a href="?id=<?= $id ?>&del=<?= $a['id'] ?>"
                   onclick="return confirm('Excluir esta anotação?')"
                   style="color:#f87171;font-size:0.75rem;white-space:nowrap;flex-shrink:0">🗑 Excluir</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <div class="empty-state__icone">📝</div>
        <p class="empty-state__titulo">Nenhuma anotação ainda</p>
        <p class="empty-state__texto">Use o formulário acima para registrar informações sobre este veículo.</p>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
