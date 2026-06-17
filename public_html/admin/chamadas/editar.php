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
                    <label>Marca Desejada</label>
                    <input type="text" name="marca_interesse"
                           value="<?= htmlspecialchars($d['marca_interesse'] ?? '') ?>"
                           placeholder="Ex: Mitsubishi, Volkswagen, Toyota…">
                </div>
                <div class="form-grupo">
                    <label>Modelo Desejado</label>
                    <input type="text" name="modelo_interesse"
                           value="<?= htmlspecialchars($d['modelo_interesse'] ?? '') ?>"
                           placeholder="Ex: Lancer, Gol, Corolla…">
                </div>
                <div class="form-grupo">
                    <label>Ano Desejado</label>
                    <input type="number" name="ano_interesse"
                           value="<?= htmlspecialchars($d['ano_interesse'] ?? '') ?>"
                           placeholder="Ex: 2024" min="1990" max="2035" style="max-width:140px">
                </div>
                <div class="form-grupo">
                    <label>Tem no Estoque? (opcional)</label>
                    <select name="veiculo_id" id="selectVeiculoChamada">
                        <option value="">— Nenhum específico —</option>
                        <?php foreach ($veiculos_disp as $v): ?>
                        <option value="<?= $v['id'] ?>" data-marca="<?= htmlspecialchars($v['marca']) ?>"
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
