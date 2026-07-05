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

$veiculo = obterUmaLinha("SELECT id, marca, modelo, ano, slug FROM veiculos WHERE id = ?", [$id]);
if (!$veiculo) {
    header('Location: ' . ADMIN_URL . 'veiculos/');
    exit;
}

$titulo_pagina = 'Fotos — ' . $veiculo['marca'] . ' ' . $veiculo['modelo'];
$pagina_ativa  = 'veiculos';
$admin_root    = '../';
$breadcrumb    = [
    ['label' => 'Veículos', 'url' => '../veiculos/'],
    ['label' => htmlspecialchars($veiculo['marca'].' '.$veiculo['modelo']), 'url' => 'editar.php?id='.$id],
    ['label' => 'Fotos', 'url' => ''],
];

$erros    = [];
$sucesso  = '';

// ===== UPLOAD DE FOTOS =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['fotos'])) {
    $arquivos = $_FILES['fotos'];
    $count    = count($arquivos['name']);
    $ok       = 0;

    // Quantas fotos já existem para definir ordem
    $ordem_atual = (int)(obterUmaLinha(
        "SELECT COALESCE(MAX(ordem),0) m FROM veiculos_fotos WHERE veiculo_id = ?", [$id]
    )['m']);

    $tem_principal = (bool)obterUmaLinha(
        "SELECT id FROM veiculos_fotos WHERE veiculo_id = ? AND principal = 1 LIMIT 1", [$id]
    );

    for ($i = 0; $i < $count; $i++) {
        $arquivo = [
            'name'     => $arquivos['name'][$i],
            'type'     => $arquivos['type'][$i],
            'tmp_name' => $arquivos['tmp_name'][$i],
            'error'    => $arquivos['error'][$i],
            'size'     => $arquivos['size'][$i],
        ];

        $resultado = uploadImagem($arquivo, 'veiculos/');
        if ($resultado['sucesso']) {
            // Garante .jpg e permissão 644 independente da versão do functions.php
            $caminho_atual = $resultado['caminho_completo'];
            $dir  = rtrim(UPLOAD_PATH . 'veiculos', '/') . '/';
            $hash = pathinfo($caminho_atual, PATHINFO_FILENAME);
            $jpg  = $dir . $hash . '.jpg';
            if ($caminho_atual !== $jpg) {
                rename($caminho_atual, $jpg);
                $resultado['caminho_completo'] = $jpg;
                $resultado['caminho'] = 'veiculos/' . $hash . '.jpg';
            }
            @chmod($resultado['caminho_completo'], 0644);

            $ordem_atual++;
            $eh_principal = (!$tem_principal && $i === 0) ? 1 : 0;
            inserir('veiculos_fotos', [
                'veiculo_id' => $id,
                'caminho'    => $resultado['caminho'],
                'principal'  => $eh_principal,
                'ordem'      => $ordem_atual,
            ]);
            if ($eh_principal) $tem_principal = true;
            $ok++;
        } else {
            $erros[] = htmlspecialchars($arquivo['name']) . ': ' . $resultado['erro'];
        }
    }

    if ($ok > 0) {
        header('Location: fotos.php?id=' . $id . '&msg=enviadas&ok=' . $ok);
        exit;
    }
}

// ===== SALVAR ORDEM VIA AJAX =====
if (isset($_GET['salvar_ordem'])) {
    header('Content-Type: application/json');
    $body  = json_decode(file_get_contents('php://input'), true);
    $itens = $body['ordem'] ?? [];
    foreach ($itens as $item) {
        $fid = (int)($item['id']    ?? 0);
        $ord = (int)($item['ordem'] ?? 0);
        if ($fid > 0 && $ord > 0) {
            executarQuery(
                "UPDATE veiculos_fotos SET ordem = ? WHERE id = ? AND veiculo_id = ?",
                [$ord, $fid, $id]
            );
        }
    }
    echo json_encode(['ok' => true]);
    exit;
}

// ===== DEFINIR COMO PRINCIPAL =====
if (isset($_GET['principal'])) {
    $foto_id = (int)$_GET['principal'];
    // Remove principal de todas as fotos do veículo
    executarQuery("UPDATE veiculos_fotos SET principal = 0 WHERE veiculo_id = ?", [$id]);
    // Define a nova principal
    executarQuery("UPDATE veiculos_fotos SET principal = 1 WHERE id = ? AND veiculo_id = ?", [$foto_id, $id]);
    header('Location: fotos.php?id=' . $id . '&msg=principal');
    exit;
}

// ===== EXCLUIR FOTO =====
if (isset($_GET['deletar_foto'])) {
    $foto_id = (int)$_GET['deletar_foto'];
    $foto    = obterUmaLinha("SELECT * FROM veiculos_fotos WHERE id = ? AND veiculo_id = ?", [$foto_id, $id]);
    if ($foto) {
        $caminho_arquivo = UPLOAD_PATH . $foto['caminho'];
        if (file_exists($caminho_arquivo)) unlink($caminho_arquivo);
        deletar('veiculos_fotos', 'id = ?', [$foto_id]);

        // Se era a principal, promove a próxima
        if ($foto['principal']) {
            $proxima = obterUmaLinha(
                "SELECT id FROM veiculos_fotos WHERE veiculo_id = ? ORDER BY ordem ASC LIMIT 1", [$id]
            );
            if ($proxima) {
                executarQuery("UPDATE veiculos_fotos SET principal = 1 WHERE id = ?", [$proxima['id']]);
            }
        }
    }
    header('Location: fotos.php?id=' . $id . '&msg=deletada');
    exit;
}

$msg   = trim($_GET['msg'] ?? '');
$ok_count = (int)($_GET['ok'] ?? 0);
$fotos = obterTodas(
    "SELECT * FROM veiculos_fotos WHERE veiculo_id = ? ORDER BY principal DESC, ordem ASC",
    [$id]
);

require_once __DIR__ . '/../includes/header.php';
?>

<?php if ($erros): ?><div class="alert-admin alert-admin--error"><?= implode('<br>', $erros) ?></div><?php endif; ?>
<?php if ($msg === 'enviadas' && $ok_count > 0): ?><div class="alert-admin alert-admin--success">✓ <?= $ok_count ?> foto(s) enviada(s) com sucesso.</div><?php endif; ?>
<?php if ($msg === 'criado'):   ?><div class="alert-admin alert-admin--success">✓ Veículo criado! Adicione as fotos agora.</div><?php endif; ?>
<?php if ($msg === 'principal'): ?><div class="alert-admin alert-admin--success">✓ Foto principal definida.</div><?php endif; ?>
<?php if ($msg === 'deletada'): ?><div class="alert-admin alert-admin--success">✓ Foto removida.</div><?php endif; ?>

<div class="page__header">
    <div>
        <h1 class="page__titulo">Fotos — <?= htmlspecialchars($veiculo['marca'].' '.$veiculo['modelo'].' '.$veiculo['ano']) ?></h1>
        <p class="page__subtitulo"><?= count($fotos) ?> foto(s) cadastrada(s)</p>
    </div>
    <div class="page__acoes">
        <a href="editar.php?id=<?= $id ?>" class="btn-admin btn-admin--secondary">← Voltar ao Veículo</a>
        <a href="<?= BASE_URL ?>veiculo.php?slug=<?= urlencode($veiculo['slug']) ?>"
           class="btn-admin btn-admin--secondary">↗ Ver no Site</a>
    </div>
</div>

<!-- UPLOAD -->
<div class="form-section">
    <div class="form-section__titulo">📤 Enviar Fotos</div>
    <div class="form-section__body">
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="foto-upload-zone" onclick="this.querySelector('input').click()">
                <input type="file" id="fotoInput" name="fotos[]"
                       accept="image/jpeg,image/png,image/webp" multiple>
                <div class="foto-upload-zone__icone">🖼️</div>
                <p class="foto-upload-zone__texto">Clique ou arraste as fotos aqui</p>
                <p class="foto-upload-zone__sub">JPG, PNG ou WebP — máx. 5 MB por foto — múltiplas permitidas</p>
            </div>

            <!-- Preview antes do envio -->
            <div id="previewContainer" class="fotos-grid"></div>

            <div style="margin-top:14px;text-align:right">
                <button type="submit" class="btn-admin btn-admin--primary">Enviar Fotos</button>
            </div>
        </form>
    </div>
</div>

<!-- FOTOS EXISTENTES -->
<?php if ($fotos): ?>
<div class="form-section">
    <div class="form-section__titulo">🖼️ Fotos Cadastradas</div>
    <div class="form-section__body">
        <div class="fotos-grid" id="fotosGrid">
            <?php foreach ($fotos as $foto): ?>
            <div class="foto-item <?= $foto['principal'] ? 'foto-item--principal' : '' ?>" data-foto-id="<?= $foto['id'] ?>">
                <img src="<?= BASE_URL ?>uploads/<?= htmlspecialchars($foto['caminho']) ?>"
                     alt="Foto <?= $foto['ordem'] ?>" loading="lazy" draggable="false">
                <?php if ($foto['principal']): ?>
                <span class="foto-item__badge-principal">Principal</span>
                <?php endif; ?>
                <div class="foto-item__acoes">
                    <?php if (!$foto['principal']): ?>
                    <a href="?id=<?= $id ?>&principal=<?= $foto['id'] ?>"
                       class="btn-admin btn-admin--secondary btn-admin--sm" title="Definir como principal">★</a>
                    <?php endif; ?>
                    <button onclick="confirmarAcao(
                        '?id=<?= $id ?>&deletar_foto=<?= $foto['id'] ?>',
                        'Excluir foto',
                        'Remover esta foto permanentemente?'
                    )" class="btn-admin btn-admin--danger btn-admin--sm" title="Excluir">✕</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <p style="margin-top:12px;font-size:0.72rem;color:#888">
            ★ Clique em uma foto para defini-la como principal (capa do card e galeria) &nbsp;|&nbsp; ☰ Arraste as fotos para reordenar
        </p>
    </div>
</div>
<?php else: ?>
<div class="form-section">
    <div class="form-section__body">
        <div class="empty-state">
            <div class="empty-state__icone">🖼️</div>
            <p class="empty-state__titulo">Nenhuma foto ainda</p>
            <p class="empty-state__texto">Envie as primeiras fotos acima.</p>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
.foto-item { cursor: grab; user-select: none; }
.foto-item:active { cursor: grabbing; }
.foto-item.drag-sobre { outline: 2px dashed #D4AF37; outline-offset: 2px; }
.foto-item.drag-arrastando { opacity: .35; }
/* Overlay só visual — botões dentro ainda recebem cliques */
#fotosGrid .foto-item__acoes { pointer-events: none; }
#fotosGrid .foto-item__acoes a,
#fotosGrid .foto-item__acoes button { pointer-events: auto; }
#msg-ordem {
    position: fixed; bottom: 24px; right: 24px;
    background: #22c55e; color: #fff;
    padding: 8px 18px; border-radius: 6px;
    font-size: .85rem; pointer-events: none;
    opacity: 0; transition: opacity .3s;
    z-index: 9999;
}
#msg-ordem.visivel { opacity: 1; }
</style>

<div id="msg-ordem">✓ Ordem salva</div>

<script>
(function() {
    var grid    = document.getElementById('fotosGrid');
    var msgEl   = document.getElementById('msg-ordem');
    var msgTimer;
    if (!grid) return;

    var items = grid.querySelectorAll('.foto-item[data-foto-id]');
    if (items.length < 1) return;

    var arrastando = null;

    items.forEach(function(item) {
        item.setAttribute('draggable', 'true');

        item.addEventListener('dragstart', function(e) {
            arrastando = this;
            e.dataTransfer.effectAllowed = 'move';
            setTimeout(function() { arrastando.classList.add('drag-arrastando'); }, 0);
        });

        item.addEventListener('dragend', function() {
            this.classList.remove('drag-arrastando');
            grid.querySelectorAll('.foto-item').forEach(function(i) {
                i.classList.remove('drag-sobre');
            });
            salvarOrdem();
        });

        item.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            if (this !== arrastando) {
                grid.querySelectorAll('.foto-item').forEach(function(i) { i.classList.remove('drag-sobre'); });
                this.classList.add('drag-sobre');
            }
        });

        item.addEventListener('dragleave', function() {
            this.classList.remove('drag-sobre');
        });

        item.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('drag-sobre');
            if (this === arrastando) return;
            var all   = Array.from(grid.querySelectorAll('.foto-item'));
            var fromI = all.indexOf(arrastando);
            var toI   = all.indexOf(this);
            if (fromI < toI) {
                grid.insertBefore(arrastando, this.nextSibling);
            } else {
                grid.insertBefore(arrastando, this);
            }
        });
    });

    function salvarOrdem() {
        var ordem = Array.from(grid.querySelectorAll('.foto-item[data-foto-id]')).map(function(el, idx) {
            return { id: el.dataset.fotoId, ordem: idx + 1 };
        });
        fetch('fotos.php?id=<?= $id ?>&salvar_ordem=1', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ordem: ordem })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.ok) {
                clearTimeout(msgTimer);
                msgEl.classList.add('visivel');
                msgTimer = setTimeout(function() { msgEl.classList.remove('visivel'); }, 2000);
            }
        });
    }
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
