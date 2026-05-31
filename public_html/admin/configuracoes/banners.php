<?php
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/functions.php';

requerAdmin();

$titulo_pagina = 'Banners da Home';
$pagina_ativa  = 'configuracoes';
$admin_root    = '../';
$breadcrumb    = [
    ['label' => 'Configurações', 'url' => '../configuracoes/'],
    ['label' => 'Banners',       'url' => ''],
];

$erros   = [];
$sucesso = '';

// DELETE
if (isset($_GET['deletar']) && ehAdmin()) {
    $bid = (int)$_GET['deletar'];
    $banner = obterUmaLinha("SELECT imagem FROM banners_home WHERE id = ?", [$bid]);
    if ($banner) {
        if ($banner['imagem'] && file_exists(UPLOAD_PATH . $banner['imagem'])) {
            unlink(UPLOAD_PATH . $banner['imagem']);
        }
        executarQuery("DELETE FROM banners_home WHERE id = ?", [$bid]);
    }
    header('Location: ' . ADMIN_URL . 'configuracoes/banners.php?msg=deletado');
    exit;
}

// TOGGLE ativo/inativo
if (isset($_GET['toggle'], $_GET['id'])) {
    $bid = (int)$_GET['id'];
    $b = obterUmaLinha("SELECT ativo FROM banners_home WHERE id = ?", [$bid]);
    if ($b) atualizar('banners_home', ['ativo' => $b['ativo'] ? 0 : 1], 'id = ?', [$bid]);
    header('Location: ' . ADMIN_URL . 'configuracoes/banners.php?msg=atualizado');
    exit;
}

// MOVER ORDEM
if (isset($_GET['ordem'], $_GET['id'])) {
    $bid  = (int)$_GET['id'];
    $dir  = $_GET['ordem'] === 'up' ? -1 : 1;
    $curr = obterUmaLinha("SELECT ordem FROM banners_home WHERE id = ?", [$bid]);
    if ($curr) {
        $nova = max(1, (int)$curr['ordem'] + $dir);
        executarQuery("UPDATE banners_home SET ordem = ? WHERE id = ?", [$nova, $bid]);
    }
    header('Location: ' . ADMIN_URL . 'configuracoes/banners.php');
    exit;
}

// SAVE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo    = sanitizar($_POST['titulo']    ?? '');
    $subtitulo = sanitizar($_POST['subtitulo'] ?? '');
    $link_cta  = sanitizar($_POST['link_cta']  ?? '');
    $texto_cta = sanitizar($_POST['texto_cta'] ?? '');
    $ativo     = 1;

    if (empty($titulo)) $erros[] = 'Título é obrigatório.';

    $imagem = null;
    if (!empty($_FILES['imagem']['tmp_name'])) {
        $upload = uploadImagem($_FILES['imagem'], 'banners/');
        if ($upload['sucesso']) {
            $imagem = $upload['caminho'];
        } else {
            $erros[] = 'Erro ao enviar imagem.';
        }
    }

    // Calcular próxima ordem
    $max_ordem = (int)(obterUmaLinha("SELECT COALESCE(MAX(ordem),0)+1 as n FROM banners_home")['n'] ?? 1);

    if (empty($erros)) {
        inserir('banners_home', [
            'titulo'    => $titulo,
            'subtitulo' => $subtitulo ?: null,
            'imagem'    => $imagem,
            'link_cta'  => $link_cta ?: null,
            'texto_cta' => $texto_cta ?: 'Ver Estoque',
            'ordem'     => $max_ordem,
            'ativo'     => $ativo,
        ]);
        $sucesso = 'Banner adicionado!';
    }
}

$banners = obterTodas("SELECT * FROM banners_home ORDER BY ordem ASC, criado_em ASC");
$msg     = trim($_GET['msg'] ?? '');

require_once __DIR__ . '/../includes/header.php';
?>

<?php if ($msg === 'deletado'):   ?><div class="alert-admin alert-admin--success">✓ Banner removido.</div><?php endif; ?>
<?php if ($msg === 'atualizado'): ?><div class="alert-admin alert-admin--success">✓ Status atualizado.</div><?php endif; ?>
<?php if ($sucesso): ?><div class="alert-admin alert-admin--success">✓ <?= htmlspecialchars($sucesso) ?></div><?php endif; ?>
<?php if ($erros): ?>
<div class="alert-admin alert-admin--error">
    <?php foreach ($erros as $e): ?><?= htmlspecialchars($e) ?><br><?php endforeach; ?>
</div>
<?php endif; ?>

<div class="page__header">
    <div>
        <h1 class="page__titulo">Banners da Home</h1>
        <p class="page__subtitulo">Gerencie os banners/slides exibidos no hero da página inicial</p>
    </div>
    <div class="page__acoes">
        <a href="." class="btn-admin btn-admin--secondary">← Configurações</a>
    </div>
</div>

<!-- ADICIONAR BANNER -->
<div class="table-container" style="margin-bottom:1.5rem">
    <div class="table-header">
        <h2 class="table-header__titulo">Adicionar Banner</h2>
    </div>
    <div style="padding:1.5rem">
        <form method="POST" enctype="multipart/form-data" action="">
            <div class="form-grid">
                <div class="form-grupo">
                    <label>Título <span class="obrigatorio">*</span></label>
                    <input type="text" name="titulo" value="<?= htmlspecialchars($_POST['titulo'] ?? '') ?>"
                           required placeholder="Ex: Os melhores veículos em um só lugar">
                </div>
                <div class="form-grupo">
                    <label>Subtítulo</label>
                    <input type="text" name="subtitulo" value="<?= htmlspecialchars($_POST['subtitulo'] ?? '') ?>"
                           placeholder="Ex: Rei Motors — qualidade e confiança">
                </div>
                <div class="form-grupo">
                    <label>Link do Botão CTA</label>
                    <input type="text" name="link_cta" value="<?= htmlspecialchars($_POST['link_cta'] ?? '') ?>"
                           placeholder="Ex: /estoque.php ou #financiamento">
                </div>
                <div class="form-grupo">
                    <label>Texto do Botão CTA</label>
                    <input type="text" name="texto_cta" value="<?= htmlspecialchars($_POST['texto_cta'] ?? 'Ver Estoque') ?>"
                           placeholder="Ex: Ver Estoque">
                </div>
                <div class="form-grupo form-grupo--2">
                    <label>Imagem do Banner (recomendado 1400×600px)</label>
                    <input type="file" name="imagem" accept="image/jpeg,image/png,image/webp">
                </div>
            </div>
            <div class="form-acoes" style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--admin-border)">
                <button type="submit" class="btn-admin btn-admin--primary">+ Adicionar Banner</button>
            </div>
        </form>
    </div>
</div>

<!-- LISTA DE BANNERS -->
<div class="table-container">
    <div class="table-header">
        <h2 class="table-header__titulo">Banners Cadastrados (<?= count($banners) ?>)</h2>
    </div>

    <?php if ($banners): ?>
    <table class="table">
        <thead>
            <tr>
                <th style="width:60px">Ordem</th>
                <th>Preview</th>
                <th>Título / Subtítulo</th>
                <th>Botão CTA</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($banners as $b): ?>
        <tr>
            <td style="text-align:center">
                <a href="?ordem=up&id=<?= $b['id'] ?>" title="Subir" style="text-decoration:none">▲</a>
                <strong style="display:block"><?= $b['ordem'] ?></strong>
                <a href="?ordem=down&id=<?= $b['id'] ?>" title="Descer" style="text-decoration:none">▼</a>
            </td>
            <td>
                <?php if ($b['imagem']): ?>
                <img src="<?= BASE_URL ?>uploads/<?= htmlspecialchars($b['imagem']) ?>"
                     style="width:120px;height:50px;object-fit:cover;border-radius:6px"
                     alt="banner">
                <?php else: ?>
                <span style="color:var(--admin-text-muted);font-size:0.8rem">Sem imagem</span>
                <?php endif; ?>
            </td>
            <td class="td-titulo">
                <?= htmlspecialchars($b['titulo']) ?>
                <?php if ($b['subtitulo']): ?>
                <br><small style="color:var(--admin-text-muted)"><?= htmlspecialchars($b['subtitulo']) ?></small>
                <?php endif; ?>
            </td>
            <td>
                <?php if ($b['link_cta']): ?>
                <a href="<?= htmlspecialchars($b['link_cta']) ?>" style="color:#D4AF37"><?= htmlspecialchars($b['texto_cta']) ?></a>
                <?php else: ?>
                <span style="color:var(--admin-text-muted)"><?= htmlspecialchars($b['texto_cta']) ?></span>
                <?php endif; ?>
            </td>
            <td>
                <a href="?toggle=1&id=<?= $b['id'] ?>"
                   class="badge-admin badge-admin--<?= $b['ativo'] ? 'ativo' : 'inativo' ?>">
                    <?= $b['ativo'] ? 'Ativo' : 'Inativo' ?>
                </a>
            </td>
            <td class="td-acoes">
                <button onclick="confirmarAcao('?deletar=<?= $b['id'] ?>', 'Remover banner', 'Deseja remover este banner permanentemente?')"
                        class="btn-admin btn-admin--danger btn-admin--sm">✕</button>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="empty-state">
        <div class="empty-state__icone">🖼️</div>
        <p class="empty-state__titulo">Nenhum banner cadastrado</p>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
