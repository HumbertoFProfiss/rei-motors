<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

$titulo_pagina = 'Favoritos';
$pagina_ativa  = 'favoritos';

require_once __DIR__ . '/includes/header.php';

$id = (int)$_SESSION['cliente_id'];

// Remover favorito
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remover_id'])) {
    $vid = (int)$_POST['remover_id'];
    executar("DELETE FROM cliente_favoritos WHERE cliente_id = ? AND veiculo_id = ?", [$id, $vid]);
    header('Location: ' . CLIENTE_URL . 'favoritos.php');
    exit;
}

try {
    $favoritos = obterTodas(
        "SELECT v.id, v.marca, v.modelo, v.ano, v.preco_venda, v.quilometragem, v.combustivel, v.cambio, v.status, v.slug,
                (SELECT caminho FROM veiculos_fotos WHERE veiculo_id = v.id AND principal = 1 LIMIT 1) AS foto,
                cf.id AS fav_id
         FROM cliente_favoritos cf
         JOIN veiculos v ON cf.veiculo_id = v.id
         WHERE cf.cliente_id = ?
         ORDER BY cf.criado_em DESC",
        [$id]
    );
} catch (Exception $e) {
    $favoritos = [];
}

$combustiveis_l = ['gasolina'=>'Gasolina','flex'=>'Flex','etanol'=>'Etanol','diesel'=>'Diesel','eletrico'=>'Elétrico'];
$cambios_l      = ['manual'=>'Manual','automatico'=>'Automático','cvt'=>'CVT'];
$status_l       = ['disponivel'=>'Disponível','reservado'=>'Reservado','vendido'=>'Vendido'];
$status_badge   = ['disponivel'=>'green','reservado'=>'gold','vendido'=>'red'];
?>

<div class="page__header">
    <div>
        <h1 class="page__titulo">Favoritos</h1>
        <p class="page__subtitulo"><?= count($favoritos) ?> veículo<?= count($favoritos) !== 1 ? 's' : '' ?> salvo<?= count($favoritos) !== 1 ? 's' : '' ?></p>
    </div>
</div>

<?php if ($favoritos): ?>
    <?php foreach ($favoritos as $v): ?>
    <div class="veiculo-card">
        <?php if (!empty($v['foto'])): ?>
        <div class="veiculo-card__foto">
            <img src="<?= UPLOAD_DIR . htmlspecialchars($v['foto']) ?>"
                 alt="<?= htmlspecialchars($v['marca'] . ' ' . $v['modelo']) ?>">
        </div>
        <?php else: ?>
        <div class="veiculo-card__foto-placeholder">🚗</div>
        <?php endif; ?>

        <div class="veiculo-card__corpo">
            <div class="veiculo-card__titulo">
                <?= htmlspecialchars($v['marca'] . ' ' . $v['modelo'] . ' ' . $v['ano']) ?>
            </div>
            <div class="veiculo-card__meta">
                <?php if ($v['quilometragem']): ?><span><?= formatarKM($v['quilometragem']) ?> km</span><?php endif; ?>
                <span><?= $combustiveis_l[$v['combustivel']] ?? $v['combustivel'] ?></span>
                <span><?= $cambios_l[$v['cambio']] ?? $v['cambio'] ?></span>
            </div>
            <div class="veiculo-card__info" style="margin-bottom:12px">
                <span class="badge badge--<?= $status_badge[$v['status']] ?? 'gray' ?>">
                    <?= $status_l[$v['status']] ?? $v['status'] ?>
                </span>
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap">
                <a href="<?= BASE_URL ?>veiculo.php?slug=<?= htmlspecialchars($v['slug']) ?>"
                   class="btn-c btn-c--primary" target="_blank">Ver Carro</a>
                <form method="POST" style="display:inline">
                    <input type="hidden" name="remover_id" value="<?= $v['id'] ?>">
                    <button type="submit" class="btn-c btn-c--secondary"
                            onclick="return confirm('Remover dos favoritos?')">❤️ Remover</button>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

<?php else: ?>
<div class="empty-state">
    <div class="empty-state__icone">❤️</div>
    <p class="empty-state__titulo">Nenhum favorito ainda</p>
    <p class="empty-state__texto">Navegue pelo estoque e salve os carros que te interessam.</p>
    <a href="<?= BASE_URL ?>estoque.php" class="btn-c btn-c--primary" style="margin-top:12px">Ver Estoque</a>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
