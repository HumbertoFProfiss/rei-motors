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

$veiculo = obterUmaLinha("SELECT * FROM veiculos WHERE id = ?", [$id]);
if (!$veiculo) {
    header('Location: ' . ADMIN_URL . 'veiculos/?msg=nao_encontrado');
    exit;
}

$titulo_pagina = 'Editar Veículo';
$pagina_ativa  = 'veiculos';
$admin_root    = '../';
$breadcrumb    = [
    ['label' => 'Veículos', 'url' => '../veiculos/'],
    ['label' => htmlspecialchars($veiculo['marca'].' '.$veiculo['modelo']), 'url' => ''],
];

$erros = [];
$d = $veiculo;
$video_existente = obterUmaLinha("SELECT id, url_youtube FROM veiculos_videos WHERE veiculo_id = ?", [$id]);
$url_youtube = $video_existente['url_youtube'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $d['marca']           = sanitizar($_POST['marca'] ?? '');
    $d['modelo']          = sanitizar($_POST['modelo'] ?? '');
    $d['ano']             = (int)($_POST['ano'] ?? 0);
    $d['preco_venda']     = (float)str_replace(['.', ','], ['', '.'], $_POST['preco_venda'] ?? '0');
    $d['preco_custo']     = (float)str_replace(['.', ','], ['', '.'], $_POST['preco_custo'] ?? '0');
    $fipe_raw             = trim($_POST['preco_tabela_fipe'] ?? '');
    $d['preco_tabela_fipe'] = $fipe_raw !== '' ? (float)str_replace(['.', ','], ['', '.'], $fipe_raw) : null;
    $d['quilometragem']   = (int)str_replace(['.', ',', ' '], '', $_POST['quilometragem'] ?? '0');
    $d['combustivel']     = array_key_exists($_POST['combustivel'] ?? '', $combustiveis) ? $_POST['combustivel'] : $veiculo['combustivel'];
    $d['cambio']          = array_key_exists($_POST['cambio'] ?? '', $cambios) ? $_POST['cambio'] : $veiculo['cambio'];
    $d['cor']             = sanitizar($_POST['cor'] ?? '');
    $d['descricao']       = trim(htmlspecialchars_decode(sanitizar($_POST['descricao'] ?? ''), ENT_QUOTES));
    $d['status']          = array_key_exists($_POST['status'] ?? '', $status_veiculo) ? $_POST['status'] : $veiculo['status'];
    $d['destaque']        = isset($_POST['destaque']) ? 1 : 0;
    $d['numero_chassi']   = sanitizar($_POST['numero_chassi'] ?? '') ?: null;
    $d['placa']           = strtoupper(sanitizar($_POST['placa'] ?? '')) ?: null;
    $d['documento']       = sanitizar($_POST['documento'] ?? '') ?: null;
    $d['renavam']         = sanitizar($_POST['renavam'] ?? '') ?: null;
    $url_youtube          = sanitizar($_POST['url_youtube'] ?? '');

    if (empty($d['marca']))       $erros[] = 'Marca é obrigatória.';
    if (empty($d['modelo']))      $erros[] = 'Modelo é obrigatório.';
    if ($d['ano'] < 1950 || $d['ano'] > (int)date('Y') + 1) $erros[] = 'Ano inválido.';
    if ($d['preco_venda'] <= 0)   $erros[] = 'Preço de venda inválido.';
    if ($d['preco_custo'] <= 0)   $erros[] = 'Preço de custo inválido.';

    if (empty($erros)) {
        $campos = [
            'marca', 'modelo', 'ano', 'preco_venda', 'preco_custo', 'preco_tabela_fipe',
            'quilometragem', 'combustivel', 'cambio', 'cor', 'descricao',
            'status', 'destaque', 'numero_chassi', 'placa', 'documento', 'renavam',
        ];
        $update = [];
        foreach ($campos as $c) $update[$c] = $d[$c];

        atualizar('veiculos', $update, 'id = ?', [$id]);

        if ($video_existente) {
            if ($url_youtube !== '') {
                executarQuery("UPDATE veiculos_videos SET url_youtube = ? WHERE id = ?", [$url_youtube, $video_existente['id']]);
            } else {
                executarQuery("DELETE FROM veiculos_videos WHERE id = ?", [$video_existente['id']]);
            }
        } elseif ($url_youtube !== '') {
            inserir('veiculos_videos', ['veiculo_id' => $id, 'url_youtube' => $url_youtube]);
        }

        header('Location: ' . ADMIN_URL . 'veiculos/?msg=salvo');
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
        <h1 class="page__titulo"><?= htmlspecialchars($veiculo['marca'].' '.$veiculo['modelo']) ?></h1>
        <p class="page__subtitulo">Editando veículo #<?= $id ?></p>
    </div>
    <div class="page__acoes">
        <a href="fotos.php?id=<?= $id ?>" class="btn-admin btn-admin--secondary">📷 Gerenciar Fotos</a>
        <a href="custos.php?id=<?= $id ?>" class="btn-admin btn-admin--secondary">💰 Custos</a>
        <a href="<?= BASE_URL ?>veiculo.php?slug=<?= urlencode($veiculo['slug']) ?>"
           target="_blank" class="btn-admin btn-admin--secondary">↗ Ver no Site</a>
    </div>
</div>

<form method="POST" action="">

    <div class="form-section">
        <div class="form-section__titulo">🚗 Dados do Veículo</div>
        <div class="form-section__body">
            <div class="form-grid">
                <div class="form-grupo">
                    <label>Marca <span class="obrigatorio">*</span></label>
                    <input type="text" name="marca" value="<?= htmlspecialchars($d['marca']) ?>" required>
                </div>
                <div class="form-grupo">
                    <label>Modelo <span class="obrigatorio">*</span></label>
                    <input type="text" name="modelo" value="<?= htmlspecialchars($d['modelo']) ?>" required>
                </div>
                <div class="form-grupo">
                    <label>Ano <span class="obrigatorio">*</span></label>
                    <input type="number" name="ano" value="<?= $d['ano'] ?>"
                           required min="1950" max="<?= (int)date('Y') + 1 ?>">
                </div>
                <div class="form-grupo">
                    <label>Combustível <span class="obrigatorio">*</span></label>
                    <select name="combustivel">
                        <?php foreach ($combustiveis as $k => $v): ?>
                        <option value="<?= $k ?>" <?= $d['combustivel'] === $k ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-grupo">
                    <label>Câmbio <span class="obrigatorio">*</span></label>
                    <select name="cambio">
                        <?php foreach ($cambios as $k => $v): ?>
                        <option value="<?= $k ?>" <?= $d['cambio'] === $k ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-grupo">
                    <label>Cor</label>
                    <input type="text" name="cor" value="<?= htmlspecialchars($d['cor'] ?? '') ?>">
                </div>
                <div class="form-grupo">
                    <label>Quilometragem</label>
                    <input type="number" name="quilometragem" value="<?= $d['quilometragem'] ?>" min="0">
                </div>
                <div class="form-grupo">
                    <label>Status</label>
                    <select name="status">
                        <?php foreach ($status_veiculo as $k => $v): ?>
                        <option value="<?= $k ?>" <?= $d['status'] === $k ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-grupo" style="align-items:flex-start;padding-top:20px">
                    <div class="form-check">
                        <input type="checkbox" id="destaque" name="destaque" value="1"
                               <?= $d['destaque'] ? 'checked' : '' ?>>
                        <label for="destaque">Destaque na Home</label>
                    </div>
                </div>
            </div>
            <div style="margin-top:14px">
                <div class="form-grupo">
                    <label>Descrição</label>
                    <textarea name="descricao" rows="4"><?= htmlspecialchars($d['descricao'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="form-section">
        <div class="form-section__titulo">💰 Preços</div>
        <div class="form-section__body">
            <div class="form-grid">
                <div class="form-grupo">
                    <label>Preço de Custo (R$) <span class="obrigatorio">*</span></label>
                    <input type="text" name="preco_custo"
                           value="<?= number_format((float)$d['preco_custo'], 2, ',', '.') ?>"
                           required placeholder="0,00">
                    <span class="form-grupo__hint">Visível apenas internamente</span>
                </div>
                <div class="form-grupo">
                    <label>Preço de Venda (R$) <span class="obrigatorio">*</span></label>
                    <input type="text" name="preco_venda"
                           value="<?= number_format((float)$d['preco_venda'], 2, ',', '.') ?>"
                           required placeholder="0,00">
                </div>
                <div class="form-grupo">
                    <label>Tabela FIPE (R$)</label>
                    <input type="text" name="preco_tabela_fipe"
                           value="<?= $d['preco_tabela_fipe'] ? number_format((float)$d['preco_tabela_fipe'], 2, ',', '.') : '' ?>"
                           placeholder="0,00">
                </div>
            </div>
        </div>
    </div>

    <!-- MÍDIA -->
    <div class="form-section">
        <div class="form-section__titulo">🎬 Vídeo (opcional)</div>
        <div class="form-section__body">
            <div class="form-grid">
                <div class="form-grupo form-grupo--2">
                    <label>Link do YouTube</label>
                    <input type="url" name="url_youtube"
                           value="<?= htmlspecialchars($url_youtube) ?>"
                           placeholder="https://www.youtube.com/watch?v=...">
                    <span class="form-grupo__hint">Cole o link do YouTube. Deixe vazio para remover o vídeo.</span>
                </div>
            </div>
        </div>
    </div>

    <div class="form-section">
        <div class="form-section__titulo">📋 Documentação</div>
        <div class="form-section__body">
            <div class="form-grid">
                <div class="form-grupo">
                    <label>Placa</label>
                    <input type="text" name="placa"
                           value="<?= htmlspecialchars($d['placa'] ?? '') ?>"
                           maxlength="8" style="text-transform:uppercase">
                </div>
                <div class="form-grupo">
                    <label>Número de Chassi</label>
                    <input type="text" name="numero_chassi"
                           value="<?= htmlspecialchars($d['numero_chassi'] ?? '') ?>" maxlength="50">
                </div>
                <div class="form-grupo">
                    <label>RENAVAM</label>
                    <input type="text" name="renavam"
                           value="<?= htmlspecialchars($d['renavam'] ?? '') ?>" maxlength="20">
                </div>
            </div>
        </div>
    </div>

    <div class="form-acoes">
        <a href="." class="btn-admin btn-admin--secondary">Cancelar</a>
        <a href="fotos.php?id=<?= $id ?>" class="btn-admin btn-admin--secondary">📷 Fotos</a>
        <button type="submit" class="btn-admin btn-admin--primary">Salvar Alterações</button>
    </div>

</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
