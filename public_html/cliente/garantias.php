<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

$titulo_pagina = 'Garantias';
$pagina_ativa  = 'garantias';

require_once __DIR__ . '/includes/header.php';

$id = (int)$_SESSION['cliente_id'];

// Compras com garantia configurada
$compras = obterTodas(
    "SELECT ve.id AS venda_id, ve.veiculo_id, ve.data_venda, ve.data_entrega,
            ve.prazo_garantia_dias, ve.status,
            v.marca, v.modelo, v.ano
     FROM vendas ve
     JOIN veiculos v ON ve.veiculo_id = v.id
     WHERE ve.cliente_id = ? AND ve.status IN ('confirmada','entregue')
     ORDER BY ve.data_venda DESC",
    [$id]
);

// Chamados de garantia do cliente
$chamados = obterTodas(
    "SELECT gc.*, v.marca, v.modelo, v.ano
     FROM garantias_chamados gc
     JOIN veiculos v ON gc.veiculo_id = v.id
     WHERE gc.cliente_id = ?
     ORDER BY gc.data_abertura DESC",
    [$id]
);

$chamados_por_venda = [];
foreach ($chamados as $ch) {
    $chamados_por_venda[$ch['veiculo_id']][] = $ch;
}

$status_chamado = [
    'aberto'       => ['label' => 'Aberto',       'badge' => 'blue'],
    'em_andamento' => ['label' => 'Em andamento',  'badge' => 'gold'],
    'resolvido'    => ['label' => 'Resolvido',     'badge' => 'green'],
    'recusado'     => ['label' => 'Recusado',      'badge' => 'red'],
];
?>

<div class="page__header">
    <div>
        <h1 class="page__titulo">Garantias</h1>
        <p class="page__subtitulo">Status da garantia dos seus veículos</p>
    </div>
</div>

<?php if ($compras): ?>
    <?php foreach ($compras as $c): ?>
    <?php
    $prazo  = (int)($c['prazo_garantia_dias'] ?? 0);
    $tem    = $prazo > 0 && !empty($c['data_entrega']);
    $fim_g  = $tem ? strtotime($c['data_entrega']) + $prazo * 86400 : 0;
    $dias_g = $fim_g ? (int)ceil(($fim_g - time()) / 86400) : 0;
    $nome_v = $c['marca'] . ' ' . $c['modelo'] . ' ' . $c['ano'];
    $chs    = $chamados_por_venda[$c['veiculo_id']] ?? [];
    ?>
    <div class="garantia-card">
        <div class="garantia-card__veiculo">
            <div class="garantia-card__nome"><?= htmlspecialchars($nome_v) ?></div>
            <div class="garantia-card__data">
                Comprado em <?= formatarData($c['data_venda']) ?>
                <?php if ($c['data_entrega']): ?>
                · Entregue em <?= formatarData($c['data_entrega']) ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="garantia-card__prazo">
            <div class="garantia-card__label">Garantia</div>
            <?php if (!$tem): ?>
                <span class="badge badge--gray">Sem garantia</span>
            <?php elseif ($dias_g > 0): ?>
                <span class="badge badge--green">🛡️ Ativa</span>
            <?php else: ?>
                <span class="badge badge--red">Expirada</span>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($chs): ?>
    <div style="margin:-14px 0 14px;padding:0 2px">
        <div style="border:1px solid var(--c-border,#1A1A1A);border-top:none;border-radius:0 0 10px 10px;padding:12px 18px">
            <div style="font-size:0.7rem;color:#888;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:8px">
                Chamados de garantia
            </div>
            <?php foreach ($chs as $ch): ?>
            <?php $info = $status_chamado[$ch['status']] ?? ['label'=>ucfirst($ch['status']),'badge'=>'gray']; ?>
            <div style="display:flex;align-items:flex-start;gap:10px;padding:8px 0;border-bottom:1px solid var(--c-border,#1A1A1A)">
                <span class="badge badge--<?= $info['badge'] ?>" style="flex-shrink:0;margin-top:2px">
                    <?= $info['label'] ?>
                </span>
                <div>
                    <div style="font-size:0.82rem;font-weight:600"><?= htmlspecialchars($ch['tipo_problema']) ?></div>
                    <?php if ($ch['descricao']): ?>
                    <div style="font-size:0.75rem;color:#888;margin-top:2px"><?= htmlspecialchars($ch['descricao']) ?></div>
                    <?php endif; ?>
                    <div style="font-size:0.7rem;color:#888;margin-top:4px">
                        Aberto em <?= formatarData($ch['data_abertura']) ?>
                        <?php if ($ch['data_resolucao']): ?>· Resolvido em <?= formatarData($ch['data_resolucao']) ?><?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php endforeach; ?>

<?php else: ?>
<div class="empty-state">
    <div class="empty-state__icone">🛡️</div>
    <p class="empty-state__titulo">Nenhuma garantia ativa</p>
    <p class="empty-state__texto">As garantias dos seus veículos aparecerão aqui.</p>
</div>
<?php endif; ?>

<!-- CONTATO PARA ACIONAMENTO -->
<div class="form-section" style="margin-top:20px">
    <div class="form-section__titulo">Precisa acionar a garantia?</div>
    <div class="form-section__body">
        <p style="font-size:0.82rem;color:#888;margin-bottom:12px">Entre em contato com a loja diretamente:</p>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
            <a href="https://wa.me/55<?= preg_replace('/\D/','',(string)LOJA_WHATSAPP) ?>?text=Olá%2C+preciso+acionar+a+garantia+do+meu+veículo"
               target="_blank" class="btn-c btn-c--primary">💬 WhatsApp</a>
            <a href="tel:<?= preg_replace('/\D/','',(string)LOJA_TELEFONE) ?>"
               class="btn-c btn-c--secondary">📞 Ligar</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
