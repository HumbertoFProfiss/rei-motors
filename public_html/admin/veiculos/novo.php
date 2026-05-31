<?php
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/functions.php';

$titulo_pagina = 'Novo Veículo';
$pagina_ativa  = 'veiculos';
$admin_root    = '../';
$breadcrumb    = [
    ['label' => 'Veículos', 'url' => '../veiculos/'],
    ['label' => 'Novo', 'url' => ''],
];

$erros = [];
$d = [
    'marca' => '', 'modelo' => '', 'ano' => (int)date('Y'),
    'preco_venda' => '', 'preco_custo' => '', 'preco_tabela_fipe' => '',
    'quilometragem' => 0, 'combustivel' => 'flex', 'cambio' => 'manual',
    'cor' => '', 'descricao' => '', 'status' => 'disponivel', 'destaque' => 0,
    'numero_chassi' => '', 'placa' => '', 'documento' => '', 'renavam' => '',
];
$url_youtube = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requerAutenticacao();

    $d['marca']           = sanitizar($_POST['marca'] ?? '');
    $d['modelo']          = sanitizar($_POST['modelo'] ?? '');
    $d['ano']             = (int)($_POST['ano'] ?? 0);
    $d['preco_venda']     = (float)str_replace(['.', ','], ['', '.'], $_POST['preco_venda'] ?? '0');
    $d['preco_custo']     = (float)str_replace(['.', ','], ['', '.'], $_POST['preco_custo'] ?? '0');
    $fipe_raw             = trim($_POST['preco_tabela_fipe'] ?? '');
    $d['preco_tabela_fipe'] = $fipe_raw !== '' ? (float)str_replace(['.', ','], ['', '.'], $fipe_raw) : null;
    $d['quilometragem']   = (int)str_replace(['.', ',', ' '], '', $_POST['quilometragem'] ?? '0');
    $d['combustivel']     = array_key_exists($_POST['combustivel'] ?? '', $combustiveis) ? $_POST['combustivel'] : '';
    $d['cambio']          = array_key_exists($_POST['cambio'] ?? '', $cambios) ? $_POST['cambio'] : '';
    $d['cor']             = sanitizar($_POST['cor'] ?? '');
    $d['descricao']       = trim(htmlspecialchars_decode(sanitizar($_POST['descricao'] ?? ''), ENT_QUOTES));
    $d['status']          = array_key_exists($_POST['status'] ?? '', $status_veiculo) ? $_POST['status'] : 'disponivel';
    $d['destaque']        = isset($_POST['destaque']) ? 1 : 0;
    $d['numero_chassi']   = sanitizar($_POST['numero_chassi'] ?? '') ?: null;
    $d['placa']           = strtoupper(sanitizar($_POST['placa'] ?? '')) ?: null;
    $d['documento']       = sanitizar($_POST['documento'] ?? '') ?: null;
    $d['renavam']         = sanitizar($_POST['renavam'] ?? '') ?: null;
    $url_youtube          = sanitizar($_POST['url_youtube'] ?? '');

    if (empty($d['marca']))      $erros[] = 'Marca é obrigatória.';
    if (empty($d['modelo']))     $erros[] = 'Modelo é obrigatório.';
    if ($d['ano'] < 1950 || $d['ano'] > (int)date('Y') + 1) $erros[] = 'Ano inválido.';
    if ($d['preco_venda'] <= 0)  $erros[] = 'Preço de venda inválido.';
    if ($d['preco_custo'] <= 0)  $erros[] = 'Preço de custo inválido.';
    if (empty($d['combustivel'])) $erros[] = 'Selecione o combustível.';
    if (empty($d['cambio']))     $erros[] = 'Selecione o câmbio.';

    if (empty($erros)) {
        // Gerar slug único
        $slug_base = gerarSlug($d['marca'].' '.$d['modelo'].' '.$d['ano']);
        $slug = $slug_base;
        $i = 0;
        while (obterUmaLinha("SELECT id FROM veiculos WHERE slug = ?", [$slug])) {
            $slug = $slug_base . '-' . (++$i);
        }
        $d['slug'] = $slug;

        $novo_id = inserir('veiculos', $d);

        if ($url_youtube !== '') {
            inserir('veiculos_videos', ['veiculo_id' => $novo_id, 'url_youtube' => $url_youtube]);
        }

        header('Location: ' . ADMIN_URL . 'veiculos/fotos.php?id=' . $novo_id . '&msg=criado');
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
        <h1 class="page__titulo">Novo Veículo</h1>
        <p class="page__subtitulo">Após salvar, você será direcionado para adicionar as fotos</p>
    </div>
</div>

<form method="POST" action="">

    <!-- DADOS DO VEÍCULO -->
    <div class="form-section">
        <div class="form-section__titulo">🚗 Dados do Veículo</div>
        <div class="form-section__body">
            <div class="form-grid">
                <div class="form-grupo">
                    <label>Marca <span class="obrigatorio">*</span></label>
                    <input type="text" name="marca" value="<?= htmlspecialchars($d['marca']) ?>"
                           required placeholder="Ex: Volkswagen">
                </div>
                <div class="form-grupo">
                    <label>Modelo <span class="obrigatorio">*</span></label>
                    <input type="text" name="modelo" value="<?= htmlspecialchars($d['modelo']) ?>"
                           required placeholder="Ex: Golf GTI">
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
                    <input type="text" name="cor" value="<?= htmlspecialchars($d['cor']) ?>"
                           placeholder="Ex: Prata">
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
                    <textarea name="descricao" rows="4"><?= htmlspecialchars($d['descricao']) ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- PREÇOS -->
    <div class="form-section">
        <div class="form-section__titulo">💰 Preços</div>
        <div class="form-section__body">
            <div class="form-grid">
                <div class="form-grupo">
                    <label>Preço de Custo (R$) <span class="obrigatorio">*</span></label>
                    <input type="text" name="preco_custo"
                           value="<?= $d['preco_custo'] > 0 ? number_format((float)$d['preco_custo'], 2, ',', '.') : '' ?>"
                           required placeholder="0,00">
                    <span class="form-grupo__hint">Visível apenas internamente</span>
                </div>
                <div class="form-grupo">
                    <label>Preço de Venda (R$) <span class="obrigatorio">*</span></label>
                    <input type="text" name="preco_venda"
                           value="<?= $d['preco_venda'] > 0 ? number_format((float)$d['preco_venda'], 2, ',', '.') : '' ?>"
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
                    <span class="form-grupo__hint">Cole o link do YouTube do vídeo deste veículo</span>
                </div>
            </div>
        </div>
    </div>

    <!-- DOCUMENTAÇÃO -->
    <div class="form-section">
        <div class="form-section__titulo">📋 Documentação</div>
        <div class="form-section__body">
            <div class="form-grid">
                <div class="form-grupo">
                    <label>Placa</label>
                    <input type="text" name="placa"
                           value="<?= htmlspecialchars($d['placa'] ?? '') ?>"
                           placeholder="ABC-1D23" maxlength="8"
                           style="text-transform:uppercase">
                </div>
                <div class="form-grupo">
                    <label>Número de Chassi</label>
                    <input type="text" name="numero_chassi"
                           value="<?= htmlspecialchars($d['numero_chassi'] ?? '') ?>"
                           placeholder="9BWZZZ377VT004251" maxlength="50">
                </div>
                <div class="form-grupo">
                    <label>RENAVAM</label>
                    <input type="text" name="renavam"
                           value="<?= htmlspecialchars($d['renavam'] ?? '') ?>"
                           maxlength="20">
                </div>
            </div>
        </div>
    </div>

    <div class="form-acoes">
        <a href="." class="btn-admin btn-admin--secondary">Cancelar</a>
        <button type="submit" class="btn-admin btn-admin--primary">Salvar e Adicionar Fotos →</button>
    </div>

</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
