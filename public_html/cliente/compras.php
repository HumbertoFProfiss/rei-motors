<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

$titulo_pagina = 'Minhas Compras';
$pagina_ativa  = 'compras';

require_once __DIR__ . '/includes/header.php';

$id = (int)$_SESSION['cliente_id'];

$compras = obterTodas(
    "SELECT ve.*, v.marca, v.modelo, v.ano, v.quilometragem, v.combustivel, v.cambio, v.cor, v.slug,
            (SELECT caminho FROM veiculos_fotos WHERE veiculo_id = ve.veiculo_id AND principal = 1 LIMIT 1) AS foto
     FROM vendas ve
     JOIN veiculos v ON ve.veiculo_id = v.id
     WHERE ve.cliente_id = ?
     ORDER BY ve.data_venda DESC",
    [$id]
);

$formas_pagamento = ['avista'=>'À Vista','financiado'=>'Financiado','consorcio'=>'Consórcio','troca'=>'Troca + Valor'];
$status_labels    = ['pendente'=>'Pendente','confirmada'=>'Confirmada','entregue'=>'Entregue','cancelada'=>'Cancelada'];
$status_badge     = ['pendente'=>'gold','confirmada'=>'blue','entregue'=>'green','cancelada'=>'red'];
$combustiveis_l   = ['gasolina'=>'Gasolina','flex'=>'Flex','etanol'=>'Etanol','diesel'=>'Diesel','eletrico'=>'Elétrico'];
$cambios_l        = ['manual'=>'Manual','automatico'=>'Automático','cvt'=>'CVT'];
?>

<div class="page__header">
    <div>
        <h1 class="page__titulo">Minhas Compras</h1>
        <p class="page__subtitulo"><?= count($compras) ?> veículo<?= count($compras) !== 1 ? 's' : '' ?> comprado<?= count($compras) !== 1 ? 's' : '' ?></p>
    </div>
</div>

<?php if ($compras): ?>
    <?php foreach ($compras as $c): ?>
    <?php
    $s       = $c['status'];
    $prazo   = (int)($c['prazo_garantia_meses'] ?? 0);
    $tem_gar = $prazo > 0 && !empty($c['data_entrega']);
    $fim_g   = $tem_gar ? strtotime($c['data_entrega'] . ' +' . $prazo . ' months') : 0;
    $dias_g  = $fim_g ? (int)ceil(($fim_g - time()) / 86400) : 0;
    ?>
    <div class="veiculo-card">
        <?php if (!empty($c['foto'])): ?>
        <div class="veiculo-card__foto">
            <img src="<?= UPLOAD_DIR . htmlspecialchars($c['foto']) ?>"
                 alt="<?= htmlspecialchars($c['marca'] . ' ' . $c['modelo']) ?>">
        </div>
        <?php else: ?>
        <div class="veiculo-card__foto-placeholder">🚗</div>
        <?php endif; ?>

        <div class="veiculo-card__corpo">
            <div class="veiculo-card__titulo">
                <?= htmlspecialchars($c['marca'] . ' ' . $c['modelo'] . ' ' . $c['ano']) ?>
            </div>
            <div class="veiculo-card__meta">
                <?php if ($c['cor']): ?><span><?= htmlspecialchars($c['cor']) ?></span><?php endif; ?>
                <?php if ($c['quilometragem']): ?><span><?= formatarKM($c['quilometragem']) ?> km</span><?php endif; ?>
                <span><?= $combustiveis_l[$c['combustivel']] ?? $c['combustivel'] ?></span>
                <span><?= $cambios_l[$c['cambio']] ?? $c['cambio'] ?></span>
            </div>

            <div class="veiculo-card__preco"><?= formatarMoeda($c['preco_venda']) ?></div>

            <div class="veiculo-card__info" style="margin-bottom:10px">
                <span class="badge badge--<?= $status_badge[$s] ?? 'gray' ?>">
                    <?= $status_labels[$s] ?? ucfirst($s) ?>
                </span>
                <?php if ($tem_gar): ?>
                    <?php if ($dias_g > 30): ?>
                    <span class="badge badge--green">🛡️ Garantia: <?= $dias_g ?>d</span>
                    <?php elseif ($dias_g > 0): ?>
                    <span class="badge badge--gold">🛡️ Garantia: <?= $dias_g ?>d</span>
                    <?php else: ?>
                    <span class="badge badge--gray">🛡️ Expirada</span>
                    <?php endif; ?>
                <?php endif; ?>
                <span class="badge badge--gray"><?= $formas_pagamento[$c['forma_pagamento']] ?? $c['forma_pagamento'] ?></span>
            </div>

            <!-- detalhes da compra -->
            <div style="display:flex;gap:24px;flex-wrap:wrap;font-size:0.78rem;color:#555">
                <div>
                    <div style="color:#3A3A3A;font-size:0.65rem;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:2px">Data da compra</div>
                    <?= formatarData($c['data_venda']) ?>
                </div>
                <?php if ($c['data_entrega']): ?>
                <div>
                    <div style="color:#3A3A3A;font-size:0.65rem;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:2px">Entrega</div>
                    <?= formatarData($c['data_entrega']) ?>
                </div>
                <?php endif; ?>
                <?php if ($tem_gar): ?>
                <div>
                    <div style="color:#3A3A3A;font-size:0.65rem;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:2px">Garantia até</div>
                    <?= date('d/m/Y', $fim_g) ?>
                </div>
                <?php endif; ?>
                <?php if ($c['numero_contrato']): ?>
                <div>
                    <div style="color:#3A3A3A;font-size:0.65rem;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:2px">Contrato</div>
                    <?= htmlspecialchars($c['numero_contrato']) ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- checklist documentação -->
            <?php if ($s !== 'pendente'): ?>
            <div style="margin-top:12px;display:flex;gap:16px;flex-wrap:wrap;font-size:0.78rem">
                <span style="color:<?= $c['recibo_emitido'] ? '#4ade80' : '#3A3A3A' ?>">
                    <?= $c['recibo_emitido'] ? '✓' : '○' ?> Recibo emitido
                </span>
                <span style="color:<?= $c['recibo_entregue'] ? '#4ade80' : '#3A3A3A' ?>">
                    <?= $c['recibo_entregue'] ? '✓' : '○' ?> Recibo entregue
                </span>
                <span style="color:<?= $s === 'entregue' ? '#4ade80' : '#3A3A3A' ?>">
                    <?= $s === 'entregue' ? '✓' : '○' ?> Carro entregue
                </span>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>

<?php else: ?>
<div class="empty-state">
    <div class="empty-state__icone">🚗</div>
    <p class="empty-state__titulo">Nenhuma compra registrada</p>
    <p class="empty-state__texto">Suas compras aparecerão aqui após serem registradas pela loja.</p>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
