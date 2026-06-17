<?php
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/functions.php';

requerAutenticacao();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: ' . ADMIN_URL . 'chamadas/');
    exit;
}

$chamada = obterUmaLinha("SELECT * FROM chamadas_proposta WHERE id = ?", [$id]);
if (!$chamada) {
    header('Location: ' . ADMIN_URL . 'chamadas/');
    exit;
}

$titulo_pagina = 'Editar Chamada #' . $id;
$pagina_ativa  = 'chamadas';
$admin_root    = '../';
$breadcrumb    = [
    ['label' => 'Chamadas', 'url' => '../chamadas/'],
    ['label' => 'Chamada #' . $id, 'url' => ''],
];

$erros = [];
$d = $chamada;
// Formatar data para datetime-local
if (!empty($d['data_chamada'])) {
    $d['data_chamada'] = date('Y-m-d\TH:i', strtotime($d['data_chamada']));
}

$clientes      = obterTodas("SELECT id, nome, telefone FROM clientes ORDER BY nome");
$vendedores    = obterTodas("SELECT id, nome FROM usuarios WHERE ativo = 1 ORDER BY nome");
$veiculos_disp = obterTodas("SELECT id, marca, modelo, ano, preco_venda FROM veiculos WHERE status = 'disponivel' ORDER BY marca, modelo, ano");
$marcas_disp   = array_values(array_unique(array_column($veiculos_disp, 'marca')));
sort($marcas_disp);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $d['cliente_id']       = (int)($_POST['cliente_id'] ?? 0) ?: null;
    $d['nome_contato']     = sanitizar($_POST['nome_contato']     ?? '') ?: null;
    $d['telefone_contato'] = sanitizar($_POST['telefone_contato'] ?? '') ?: null;
    $d['intencao']         = in_array($_POST['intencao'] ?? '', ['comprar','vender','consignar','outro']) ? $_POST['intencao'] : null;
    $d['usuario_id']       = (int)($_POST['usuario_id'] ?? $_SESSION['usuario_id']);
    $d['tipo']             = in_array($_POST['tipo'] ?? '', ['ligacao','whatsapp','presencial','email']) ? $_POST['tipo'] : 'ligacao';
    $d['descricao']        = sanitizar($_POST['descricao'] ?? '');
    $d['resultado']        = in_array($_POST['resultado'] ?? '', ['sem_resposta','interesse','proposta_enviada','fechado','desistiu']) ? $_POST['resultado'] : 'interesse';
    $d['data_chamada']     = sanitizar($_POST['data_chamada'] ?? date('Y-m-d H:i:s'));
    $d['veiculo_id']       = (int)($_POST['veiculo_id'] ?? 0) ?: null;
    $d['marca_interesse']  = sanitizar($_POST['marca_interesse']  ?? '') ?: null;
    $d['modelo_interesse'] = sanitizar($_POST['modelo_interesse'] ?? '') ?: null;
    $d['ano_interesse']    = (int)($_POST['ano_interesse'] ?? 0) ?: null;

    if (empty($d['descricao']) && empty($d['nome_contato'])) {
        $erros[] = 'Informe ao menos o nome do contato ou uma descrição.';
    }

    if (empty($erros)) {
        atualizar('chamadas_proposta', [
            'cliente_id'       => $d['cliente_id'],
            'nome_contato'     => $d['nome_contato'],
            'telefone_contato' => $d['telefone_contato'],
            'intencao'         => $d['intencao'],
            'veiculo_id'       => $d['veiculo_id'],
            'marca_interesse'  => $d['marca_interesse'],
            'modelo_interesse' => $d['modelo_interesse'],
            'ano_interesse'    => $d['ano_interesse'],
            'usuario_id'       => $d['usuario_id'],
            'tipo'             => $d['tipo'],
            'descricao'        => $d['descricao'],
            'resultado'        => $d['resultado'],
            'data_chamada'     => $d['data_chamada'],
        ], 'id = ?', [$id]);

        header('Location: ' . ADMIN_URL . 'chamadas/?msg=editado');
        exit;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<?php if ($erros): ?>
<div class="alert-admin alert-admin--error">
    <?= implode('<br>', array_map('htmlspecialchars', $erros)) ?>
</div>
<?php endif; ?>

<div class="page__header">
    <div>
        <h1 class="page__titulo">Editar Chamada #<?= $id ?></h1>
        <p class="page__subtitulo">Registrada em <?= formatarData($chamada['data_chamada']) ?></p>
    </div>
</div>

<form method="POST" action="">
    <div class="form-section">
        <div class="form-section__titulo">👤 Identificação do Contato</div>
        <div class="form-section__body">
            <div class="form-grid">
                <div class="form-grupo">
                    <label>Nome Completo</label>
                    <input type="text" name="nome_contato"
                           value="<?= htmlspecialchars($d['nome_contato'] ?? '') ?>"
                           placeholder="Ex: João da Silva">
                    <span class="form-grupo__hint">Não precisa ser um cliente cadastrado</span>
                </div>
                <div class="form-grupo">
                    <label>Telefone / WhatsApp</label>
                    <input type="text" name="telefone_contato"
                           value="<?= htmlspecialchars($d['telefone_contato'] ?? '') ?>"
                           placeholder="(14) 99999-9999">
                </div>
                <div class="form-grupo">
                    <label>Intenção</label>
                    <select name="intencao">
                        <option value="">Não informada</option>
                        <option value="comprar"   <?= ($d['intencao'] ?? '') === 'comprar' ? 'selected' : '' ?>>🛒 Quer Comprar</option>
                        <option value="vender"    <?= ($d['intencao'] ?? '') === 'vender' ? 'selected' : '' ?>>💰 Quer Vender</option>
                        <option value="consignar" <?= ($d['intencao'] ?? '') === 'consignar' ? 'selected' : '' ?>>🤝 Quer Consignar</option>
                        <option value="outro"     <?= ($d['intencao'] ?? '') === 'outro' ? 'selected' : '' ?>>Outro</option>
                    </select>
                </div>
                <div class="form-grupo">
                    <label>Cliente Cadastrado (opcional)</label>
                    <select name="cliente_id">
                        <option value="">Não vinculado</option>
                        <?php foreach ($clientes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= (int)($d['cliente_id'] ?? 0) === $c['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['nome']) ?>
                            <?= $c['telefone'] ? ' — ' . htmlspecialchars($c['telefone']) : '' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="form-section">
        <div class="form-section__titulo">🚗 Veículo de Interesse (opcional)</div>
        <div class="form-section__body">
            <div class="form-grid">
                <div class="form-grupo">
                    <label>Marca</label>
                    <select name="marca_interesse" id="selMarcaInteresse" onchange="filtrarModelosInteresse()">
                        <option value="">— Selecione a marca —</option>
                    </select>
                </div>
                <div class="form-grupo">
                    <label>Modelo</label>
                    <select name="modelo_interesse" id="selModeloInteresse">
                        <option value="">— Selecione o modelo —</option>
                    </select>
                </div>
                <div class="form-grupo">
                    <label>Ano</label>
                    <input type="number" name="ano_interesse"
                           value="<?= htmlspecialchars($d['ano_interesse'] ?? '') ?>"
                           placeholder="Ex: 2024" min="1990" max="2035" style="max-width:140px">
                </div>
                <div class="form-grupo">
                    <label>Tem no Estoque? (opcional)</label>
                    <select name="veiculo_id" id="selectVeiculoChamada">
                        <option value="">— Nenhum específico —</option>
                        <?php foreach ($veiculos_disp as $v): ?>
                        <option value="<?= $v['id'] ?>"
                                <?= (int)($d['veiculo_id'] ?? 0) === (int)$v['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($v['marca'] . ' ' . $v['modelo'] . ' ' . $v['ano']) ?> — <?= formatarMoeda($v['preco_venda']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="form-grupo__hint">Vincule se o carro que o cliente quer já está no estoque</span>
                </div>
            </div>
        </div>
    </div>

    <div class="form-section">
        <div class="form-section__titulo">📞 Detalhes do Contato</div>
        <div class="form-section__body">
            <div class="form-grid">
                <div class="form-grupo">
                    <label>Tipo de Contato</label>
                    <select name="tipo">
                        <option value="ligacao"    <?= $d['tipo'] === 'ligacao' ? 'selected' : '' ?>>📞 Ligação</option>
                        <option value="whatsapp"   <?= $d['tipo'] === 'whatsapp' ? 'selected' : '' ?>>💬 WhatsApp</option>
                        <option value="presencial" <?= $d['tipo'] === 'presencial' ? 'selected' : '' ?>>🤝 Presencial</option>
                        <option value="email"      <?= $d['tipo'] === 'email' ? 'selected' : '' ?>>✉️ E-mail</option>
                    </select>
                </div>
                <div class="form-grupo">
                    <label>Resultado</label>
                    <select name="resultado">
                        <option value="interesse"        <?= $d['resultado'] === 'interesse' ? 'selected' : '' ?>>Com interesse</option>
                        <option value="sem_resposta"     <?= $d['resultado'] === 'sem_resposta' ? 'selected' : '' ?>>Sem resposta</option>
                        <option value="proposta_enviada" <?= $d['resultado'] === 'proposta_enviada' ? 'selected' : '' ?>>Proposta enviada</option>
                        <option value="fechado"          <?= $d['resultado'] === 'fechado' ? 'selected' : '' ?>>Fechado ✓</option>
                        <option value="desistiu"         <?= $d['resultado'] === 'desistiu' ? 'selected' : '' ?>>Desistiu</option>
                    </select>
                </div>
                <div class="form-grupo">
                    <label>Vendedor <span class="obrigatorio">*</span></label>
                    <select name="usuario_id">
                        <?php foreach ($vendedores as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= (int)$d['usuario_id'] === $u['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($u['nome']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-grupo">
                    <label>Data e Hora</label>
                    <input type="datetime-local" name="data_chamada"
                           value="<?= htmlspecialchars($d['data_chamada']) ?>">
                </div>
                <div class="form-grupo form-grupo--2">
                    <label>Descrição / O que o cliente quer</label>
                    <textarea name="descricao" rows="4"
                              placeholder="Ex: Busca SUV até R$ 80.000, 2020+, automático."><?= htmlspecialchars($d['descricao']) ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="form-acoes">
        <a href="." class="btn-admin btn-admin--secondary">Cancelar</a>
        <button type="submit" class="btn-admin btn-admin--primary">Salvar Alterações</button>
    </div>
</form>

<script>
var MARCAS_MODELOS = {
    "Chevrolet":    ["Onix","Onix Plus","Tracker","Montana","S10","Spin","Cruze","Equinox","Trailblazer","Camaro","Blazer","Cobalt"],
    "Volkswagen":   ["Gol","Polo","Virtus","T-Cross","Taos","Jetta","Tiguan","Amarok","Saveiro","Up","Golf","Nivus"],
    "Fiat":         ["Argo","Cronos","Pulse","Fastback","Toro","Strada","Mobi","Ducato","500","Doblo","Uno","Bravo"],
    "Toyota":       ["Corolla","Corolla Cross","Yaris","Hilux","SW4","RAV4","Camry","Etios","Prius","Land Cruiser"],
    "Hyundai":      ["HB20","HB20S","Creta","Tucson","Santa Fe","Elantra","i30","Ioniq 5","Ioniq 6"],
    "Renault":      ["Kwid","Sandero","Logan","Duster","Captur","Oroch","Kardian","Stepway","Clio","Megane"],
    "Ford":         ["Ka","EcoSport","Ranger","Bronco","Mustang","Territory","Maverick","Edge","Explorer","F-150"],
    "Honda":        ["Civic","City","City Hatch","HR-V","CR-V","WR-V","Fit","Accord","ZR-V"],
    "Nissan":       ["Kicks","Frontier","Sentra","March","Versa","Leaf","Murano","Pathfinder"],
    "Jeep":         ["Renegade","Compass","Commander","Wrangler","Gladiator","Cherokee","Grand Cherokee"],
    "Mitsubishi":   ["L200","Eclipse Cross","Outlander","Pajero","Pajero Sport","ASX","Lancer","Galant"],
    "Peugeot":      ["208","2008","3008","408","508","5008","Partner"],
    "Citroën":      ["C3","C4","C4 Cactus","C5 Aircross","Berlingo","Jumper"],
    "BMW":          ["320i","330i","530i","X1","X3","X4","X5","X6","M3","M5","Serie 1","Serie 2"],
    "Mercedes-Benz":["A200","C180","C200","E300","GLA","GLB","GLC","GLE","GLS","Sprinter","AMG GT"],
    "Audi":         ["A3","A4","A5","A6","Q3","Q5","Q7","Q8","TT","RS3","RS5"],
    "Kia":          ["Sportage","Sorento","Stinger","Cerato","Carnival","Niro","Telluride","EV6","Picanto"],
    "Land Rover":   ["Discovery","Discovery Sport","Range Rover","Range Rover Sport","Defender","Freelander","Evoque"],
    "Volvo":        ["XC40","XC60","XC90","S60","S90","V60","V90","C40"],
    "Dodge":        ["RAM 1500","RAM 2500","RAM 700","Durango","Challenger","Charger","Journey"],
    "BYD":          ["Dolphin","Seal","Atto 3","Han","Yuan Plus","Tan","Song Plus","King"],
    "GWM":          ["Haval H6","Haval H2","ORA 03","Poer","Wingle","Jolion"],
    "Caoa Chery":   ["Tiggo 2","Tiggo 5x","Tiggo 7","Tiggo 8","Arrizo 6","iCar"],
    "JAC":          ["T50","T60","T8","e-JS1","e-JS4"],
    "Subaru":       ["Outback","Forester","Impreza","XV","WRX","BRZ","Levorg"],
    "Suzuki":       ["Jimny","Swift","Vitara","S-Cross","Baleno"],
    "Porsche":      ["911","Cayenne","Macan","Panamera","Taycan","718"],
    "Ferrari":      ["F8","SF90","Roma","Portofino","812"],
    "Lamborghini":  ["Huracan","Urus","Revuelto"],
    "Outras":       ["Outro modelo"]
};

function preencherMarcasInteresse(marcaSel, modeloSel) {
    marcaSel.innerHTML = '<option value="">— Selecione a marca —</option>';
    Object.keys(MARCAS_MODELOS).forEach(function(m) {
        var opt = document.createElement('option');
        opt.value = m;
        opt.textContent = m;
        marcaSel.appendChild(opt);
    });
    if (marcaSel.dataset.val) marcaSel.value = marcaSel.dataset.val;
    filtrarModelosInteresse(marcaSel, modeloSel);
}

function filtrarModelosInteresse(marcaSel, modeloSel) {
    marcaSel = marcaSel || document.getElementById('selMarcaInteresse');
    modeloSel = modeloSel || document.getElementById('selModeloInteresse');
    var marca = marcaSel.value;
    var modelos = marca && MARCAS_MODELOS[marca] ? MARCAS_MODELOS[marca] : [];
    modeloSel.innerHTML = '<option value="">— Selecione o modelo —</option>';
    modelos.forEach(function(mo) {
        var opt = document.createElement('option');
        opt.value = mo;
        opt.textContent = mo;
        if (modeloSel.dataset.val === mo) opt.selected = true;
        modeloSel.appendChild(opt);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    var ms = document.getElementById('selMarcaInteresse');
    var mo = document.getElementById('selModeloInteresse');
    if (ms) {
        ms.dataset.val = <?= json_encode($d['marca_interesse'] ?? '') ?>;
        mo.dataset.val = <?= json_encode($d['modelo_interesse'] ?? '') ?>;
        preencherMarcasInteresse(ms, mo);
        ms.onchange = function() { filtrarModelosInteresse(ms, mo); };
    }
});

function filtrarVeiculosChamada() {
    var marca = document.getElementById('filtraMarcaChamada').value;
    var sel = document.getElementById('selectVeiculoChamada');
    sel.querySelectorAll('option[data-marca]').forEach(function(opt) {
        opt.style.display = (!marca || opt.dataset.marca === marca) ? '' : 'none';
    });
    if (sel.value && marca) {
        var cur = sel.options[sel.selectedIndex];
        if (cur && cur.dataset.marca && cur.dataset.marca !== marca) sel.value = '';
    }
}
// Restaura o filtro de marca ao carregar (quando há veículo já salvo)
(function() {
    var sel = document.getElementById('selectVeiculoChamada');
    if (sel && sel.value) {
        var cur = sel.options[sel.selectedIndex];
        if (cur && cur.dataset.marca) {
            document.getElementById('filtraMarcaChamada').value = cur.dataset.marca;
            filtrarVeiculosChamada();
        }
    }
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
