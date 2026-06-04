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
    $d['numero_chassi']           = sanitizar($_POST['numero_chassi'] ?? '') ?: null;
    $d['placa']                   = strtoupper(sanitizar($_POST['placa'] ?? '')) ?: null;
    $d['documento']               = sanitizar($_POST['documento'] ?? '') ?: null;
    $d['renavam']                 = sanitizar($_POST['renavam'] ?? '') ?: null;
    $d['tipo_propriedade']        = ($_POST['tipo_propriedade'] ?? '') === 'consignado' ? 'consignado' : 'proprio';
    $d['consignado_valor_minimo'] = $d['tipo_propriedade'] === 'consignado'
        ? (float)str_replace(['.', ','], ['', '.'], $_POST['consignado_valor_minimo'] ?? '0')
        : null;
    $d['consignado_percentual']           = $d['tipo_propriedade'] === 'consignado'
        ? (float)str_replace(',', '.', $_POST['consignado_percentual'] ?? '0')
        : null;
    $nome_prop = $d['tipo_propriedade'] === 'consignado' ? sanitizar($_POST['consignado_proprietario_nome'] ?? '') : '';
    $d['consignado_proprietario_nome'] = ($nome_prop !== '') ? $nome_prop : null;
    $tel_prop = $d['tipo_propriedade'] === 'consignado' ? sanitizar($_POST['consignado_proprietario_telefone'] ?? '') : '';
    $d['consignado_proprietario_telefone'] = ($tel_prop !== '') ? $tel_prop : null;
    if ($d['tipo_propriedade'] === 'consignado') {
        $d['preco_custo'] = $d['consignado_valor_minimo'] ?? 0;
    }
    $url_youtube = sanitizar($_POST['url_youtube'] ?? '');

    if (empty($d['marca']))       $erros[] = 'Marca é obrigatória.';
    if (empty($d['modelo']))      $erros[] = 'Modelo é obrigatório.';
    if ($d['ano'] < 1950 || $d['ano'] > (int)date('Y') + 1) $erros[] = 'Ano inválido.';
    if ($d['preco_venda'] <= 0)   $erros[] = 'Preço de venda inválido.';
    if ($d['tipo_propriedade'] === 'proprio' && $d['preco_custo'] <= 0) $erros[] = 'Preço de custo inválido.';
    if ($d['tipo_propriedade'] === 'consignado' && $d['consignado_valor_minimo'] <= 0) $erros[] = 'Informe o valor mínimo do proprietário.';

    if (empty($erros)) {
        $campos = [
            'marca', 'modelo', 'ano', 'preco_venda', 'preco_custo', 'preco_tabela_fipe',
            'quilometragem', 'combustivel', 'cambio', 'cor', 'descricao',
            'status', 'destaque', 'numero_chassi', 'placa', 'documento', 'renavam',
            'tipo_propriedade', 'consignado_valor_minimo', 'consignado_percentual',
            'consignado_proprietario_nome', 'consignado_proprietario_telefone',
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
        <a href="anotacoes.php?id=<?= $id ?>" class="btn-admin btn-admin--secondary">📝 Anotações</a>
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

    <!-- TIPO DE PROPRIEDADE -->
    <div class="form-section">
        <div class="form-section__titulo">🏷️ Tipo do Veículo</div>
        <div class="form-section__body">
            <div style="display:flex;gap:1.5rem;flex-wrap:wrap">
                <label class="tipo-propriedade-opcao" id="label-proprio">
                    <input type="radio" name="tipo_propriedade" value="proprio"
                           <?= ($d['tipo_propriedade'] ?? 'proprio') !== 'consignado' ? 'checked' : '' ?>>
                    <span>
                        <strong>🏢 Carro da Loja</strong>
                        <small>Veículo comprado pela loja. Tem custo de aquisição.</small>
                    </span>
                </label>
                <label class="tipo-propriedade-opcao" id="label-consignado">
                    <input type="radio" name="tipo_propriedade" value="consignado"
                           <?= ($d['tipo_propriedade'] ?? '') === 'consignado' ? 'checked' : '' ?>>
                    <span>
                        <strong>🤝 Carro Consignado</strong>
                        <small>Veículo de terceiro. A loja vende e fica com a diferença.</small>
                    </span>
                </label>
            </div>
        </div>
    </div>

    <div class="form-section">
        <div class="form-section__titulo">💰 Preços</div>
        <div class="form-section__body">
            <div class="form-grid">

                <div class="form-grupo" id="campo-custo">
                    <label>Preço de Custo (R$) <span class="obrigatorio">*</span></label>
                    <input type="text" name="preco_custo" id="preco_custo"
                           value="<?= number_format((float)$d['preco_custo'], 2, ',', '.') ?>"
                           placeholder="0,00">
                    <span class="form-grupo__hint">Visível apenas internamente</span>
                </div>

                <div class="form-grupo">
                    <label>Preço de Venda (R$) <span class="obrigatorio">*</span></label>
                    <input type="text" name="preco_venda" id="preco_venda"
                           value="<?= number_format((float)$d['preco_venda'], 2, ',', '.') ?>"
                           required placeholder="0,00">
                </div>

                <div class="form-grupo form-grupo--2" id="bloco-fipe">
                    <label>Tabela FIPE</label>
                    <div style="display:flex;gap:8px;align-items:center">
                        <input type="text" name="preco_tabela_fipe" id="preco_tabela_fipe"
                               value="<?= $d['preco_tabela_fipe'] ? number_format((float)$d['preco_tabela_fipe'], 2, ',', '.') : '' ?>"
                               placeholder="Valor FIPE (R$)" style="flex:1">
                        <button type="button" id="btn_buscar_fipe" class="btn-admin btn-admin--secondary"
                                style="white-space:nowrap">🔍 Buscar FIPE</button>
                    </div>
                    <span id="fipe_referencia" style="font-size:0.72rem;display:block;margin-top:4px"></span>
                    <span class="form-grupo__hint">Preencha marca, modelo e ano acima e clique em "Buscar FIPE"</span>
                </div>

                <div class="form-grupo" id="campo-valor-minimo" style="display:none">
                    <label>Valor Mínimo do Proprietário (R$) <span class="obrigatorio">*</span></label>
                    <input type="text" name="consignado_valor_minimo" id="consignado_valor_minimo"
                           value="<?= $d['consignado_valor_minimo'] > 0 ? number_format((float)$d['consignado_valor_minimo'], 2, ',', '.') : '' ?>"
                           placeholder="0,00">
                    <span class="form-grupo__hint">Quanto o dono do carro quer receber no mínimo</span>
                </div>

                <div class="form-grupo" id="campo-percentual" style="display:none">
                    <label>Comissão da Loja (%)</label>
                    <input type="text" name="consignado_percentual" id="consignado_percentual"
                           value="<?= $d['consignado_percentual'] > 0 ? number_format((float)$d['consignado_percentual'], 2, ',', '.') : '' ?>"
                           placeholder="0,00">
                    <span class="form-grupo__hint">Calculado automaticamente sobre o preço de venda</span>
                </div>

                <div class="form-grupo" id="campo-proprietario-nome" style="display:none">
                    <label>Nome do Proprietário <span class="obrigatorio">*</span></label>
                    <input type="text" name="consignado_proprietario_nome" id="consignado_proprietario_nome"
                           value="<?= htmlspecialchars($d['consignado_proprietario_nome'] ?? '') ?>"
                           placeholder="Nome completo">
                </div>

                <div class="form-grupo" id="campo-proprietario-tel" style="display:none">
                    <label>Telefone do Proprietário</label>
                    <input type="text" name="consignado_proprietario_telefone" id="consignado_proprietario_telefone"
                           value="<?= htmlspecialchars($d['consignado_proprietario_telefone'] ?? '') ?>"
                           placeholder="(14) 99999-9999">
                </div>

                <div class="form-grupo form-grupo--2" id="campo-resumo-consignado" style="display:none">
                    <div class="consignado-resumo" id="consignado-resumo"></div>
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

<script>
// ===== FIPE =====
(function () {
    var btnBuscar   = document.getElementById('btn_buscar_fipe');
    var inputFipe   = document.getElementById('preco_tabela_fipe');
    var spanRef     = document.getElementById('fipe_referencia');
    var inputMarca  = document.querySelector('input[name="marca"]');
    var inputModelo = document.querySelector('input[name="modelo"]');
    var inputAno    = document.querySelector('input[name="ano"]');
    var apiBase     = '../../api/fipe.php';

    if (!btnBuscar) return;

    function norm(s) {
        return (s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().replace(/[^a-z0-9\s]/g, '').trim();
    }
    function lev(a, b) {
        var m = a.length, n = b.length, i, j;
        if (!m) return n; if (!n) return m;
        var dp = [];
        for (i = 0; i <= m; i++) { dp[i] = [i]; }
        for (j = 0; j <= n; j++) { dp[0][j] = j; }
        for (i = 1; i <= m; i++)
            for (j = 1; j <= n; j++)
                dp[i][j] = a[i-1] === b[j-1] ? dp[i-1][j-1] : 1 + Math.min(dp[i-1][j], dp[i][j-1], dp[i-1][j-1]);
        return dp[m][n];
    }
    function melhorMatch(lista, texto, chave) {
        var t = norm(texto);
        if (!t) return null;
        // 1. exato normalizado
        var r = lista.find(function(i){ return norm(i[chave]) === t; });
        if (r) return r;
        // 2. começa com / está contido
        r = lista.find(function(i){ var n = norm(i[chave]); return n.startsWith(t) || t.startsWith(n); });
        if (r) return r;
        // 3. contém
        r = lista.find(function(i){ var n = norm(i[chave]); return n.includes(t) || t.includes(n); });
        if (r) return r;
        // 4. partes de palavras (ex: "golf gti" acha "Golf 1.4 TSI GTI")
        var partes = t.split(/\s+/).filter(function(p){ return p.length > 2; });
        if (partes.length) {
            r = lista.find(function(i){
                var n = norm(i[chave]);
                return partes.every(function(p){ return n.includes(p); });
            }) || lista.find(function(i){
                var n = norm(i[chave]);
                return partes.some(function(p){ return n.includes(p); });
            });
            if (r) return r;
        }
        // 5. Levenshtein — tolerância ~35% do comprimento, mínimo 2 erros
        var maxDist = Math.max(2, Math.floor(t.length * 0.35));
        var melhor = null, menorDist = Infinity;
        lista.forEach(function(i) {
            var candidatos = [norm(i[chave])].concat(norm(i[chave]).split(/\s+/));
            candidatos.forEach(function(c) {
                var d = lev(t, c);
                if (d < menorDist) { menorDist = d; melhor = i; }
            });
        });
        return (melhor && menorDist <= maxDist) ? melhor : null;
    }

    btnBuscar.addEventListener('click', function () {
        var marca  = inputMarca  ? inputMarca.value.trim()  : '';
        var modelo = inputModelo ? inputModelo.value.trim() : '';
        var ano    = parseInt(inputAno ? inputAno.value : '0');

        if (!marca || !modelo || !ano) {
            spanRef.style.color = '#ef4444';
            spanRef.textContent = '⚠ Preencha marca, modelo e ano antes de buscar.';
            return;
        }

        btnBuscar.textContent = '⏳ Buscando...';
        btnBuscar.disabled = true;
        spanRef.style.color = '#888';
        spanRef.textContent = 'Procurando marca...';

        fetch(apiBase + '?acao=marcas')
        .then(function(r){ return r.json(); })
        .then(function(marcas) {
            if (!Array.isArray(marcas)) throw new Error('Erro ao conectar à API FIPE');
            var marcaObj = melhorMatch(marcas, marca, 'nome');
            if (!marcaObj) throw new Error('Marca "' + marca + '" não encontrada na FIPE');
            spanRef.textContent = '✓ ' + marcaObj.nome + ' — procurando modelo...';

            return fetch(apiBase + '?acao=modelos&marca_codigo=' + marcaObj.codigo)
            .then(function(r){ return r.json(); })
            .then(function(data) {
                var modelos = data.modelos || data;
                if (!Array.isArray(modelos)) throw new Error('Erro ao buscar modelos');
                var modeloObj = melhorMatch(modelos, modelo, 'nome');
                if (!modeloObj) throw new Error('Modelo "' + modelo + '" não encontrado para ' + marcaObj.nome);
                spanRef.textContent = '✓ ' + marcaObj.nome + ' ' + modeloObj.nome + ' — procurando ano...';

                return fetch(apiBase + '?acao=anos&marca_codigo=' + marcaObj.codigo + '&modelo_codigo=' + modeloObj.codigo)
                .then(function(r){ return r.json(); })
                .then(function(anos) {
                    if (!Array.isArray(anos)) throw new Error('Erro ao buscar anos');
                    var anoObj = anos.find(function(a){ return a.nome.startsWith(String(ano)); })
                              || anos.find(function(a){ return a.nome.startsWith(String(ano - 1)); })
                              || anos[0];
                    if (!anoObj) throw new Error('Ano ' + ano + ' não encontrado na FIPE');

                    return fetch(apiBase + '?acao=preco&marca_codigo=' + marcaObj.codigo + '&modelo_codigo=' + modeloObj.codigo + '&ano_codigo=' + encodeURIComponent(anoObj.codigo))
                    .then(function(r){ return r.json(); })
                    .then(function(preco) {
                        if (preco.erro) throw new Error(preco.erro);
                        if (!preco.Valor) throw new Error('Preço não retornado');
                        var v = preco.Valor.replace(/R\$\s?/, '').replace(/\./g, '').replace(',', '.');
                        inputFipe.value = parseFloat(v).toLocaleString('pt-BR', {minimumFractionDigits:2, maximumFractionDigits:2});
                        spanRef.style.color = '#22c55e';
                        spanRef.textContent = '✓ ' + marcaObj.nome + ' ' + modeloObj.nome + ' ' + anoObj.nome + ' — Ref: ' + (preco.MesReferencia || '');
                    });
                });
            });
        })
        .catch(function(err) {
            spanRef.style.color = '#ef4444';
            spanRef.textContent = '✗ ' + (err.message || 'Erro ao buscar FIPE');
        })
        .then(function() {
            btnBuscar.textContent = '🔍 Buscar FIPE';
            btnBuscar.disabled = false;
        });
    });
})();
// ===== CONSIGNADO =====
(function () {
    var radios = document.querySelectorAll('input[name="tipo_propriedade"]');
    var campoCusto = document.getElementById('campo-custo');
    var campoValorMin = document.getElementById('campo-valor-minimo');
    var campoPerc = document.getElementById('campo-percentual');
    var campoResumo = document.getElementById('campo-resumo-consignado');
    var campoNome = document.getElementById('campo-proprietario-nome');
    var campoTel = document.getElementById('campo-proprietario-tel');
    var inputCusto = document.getElementById('preco_custo');
    var inputVenda = document.getElementById('preco_venda');
    var inputValorMin = document.getElementById('consignado_valor_minimo');
    var inputPerc = document.getElementById('consignado_percentual');
    var resumoDiv = document.getElementById('consignado-resumo');

    function parseBR(v) {
        return parseFloat((v || '0').replace(/\./g, '').replace(',', '.')) || 0;
    }
    function formatBR(v) {
        return v.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    function atualizarModo() {
        var consignado = document.querySelector('input[name="tipo_propriedade"]:checked').value === 'consignado';
        campoCusto.style.display = consignado ? 'none' : '';
        campoValorMin.style.display = consignado ? '' : 'none';
        campoPerc.style.display = consignado ? '' : 'none';
        campoResumo.style.display = consignado ? '' : 'none';
        campoNome.style.display = consignado ? '' : 'none';
        campoTel.style.display = consignado ? '' : 'none';
        inputCusto.required = !consignado;
        if (consignado) recalcularResumo();
    }

    function recalcularResumo() {
        var venda = parseBR(inputVenda.value);
        var minimo = parseBR(inputValorMin.value);
        if (venda > 0 && minimo > 0) {
            var lucroLoja = venda - minimo;
            var perc = (lucroLoja / venda) * 100;
            inputPerc.value = formatBR(perc);
            resumoDiv.innerHTML =
                '<strong>Resumo:</strong> Venda R$ ' + formatBR(venda) +
                ' &minus; Proprietário R$ ' + formatBR(minimo) +
                ' = <span style="color:var(--color-success)">Loja R$ ' + formatBR(lucroLoja) + ' (' + formatBR(perc) + '%)</span>';
        } else {
            resumoDiv.innerHTML = '';
        }
    }

    function recalcularPorPerc() {
        var venda = parseBR(inputVenda.value);
        var perc = parseBR(inputPerc.value);
        if (venda > 0 && perc > 0 && perc < 100) {
            var minimo = venda * (1 - perc / 100);
            inputValorMin.value = formatBR(minimo);
            recalcularResumo();
        }
    }

    radios.forEach(function (r) { r.addEventListener('change', atualizarModo); });
    inputVenda.addEventListener('blur', function () {
        if (document.querySelector('input[name="tipo_propriedade"]:checked').value === 'consignado') recalcularResumo();
    });
    inputValorMin.addEventListener('blur', recalcularResumo);
    inputPerc.addEventListener('blur', recalcularPorPerc);

    atualizarModo();
})();
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
