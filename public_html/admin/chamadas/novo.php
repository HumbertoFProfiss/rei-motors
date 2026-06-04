<?php
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/functions.php';

requerAutenticacao();

$titulo_pagina = 'Nova Chamada';
$pagina_ativa  = 'chamadas';
$admin_root    = '../';
$breadcrumb    = [
    ['label' => 'Chamadas', 'url' => '../chamadas/'],
    ['label' => 'Nova', 'url' => ''],
];

$erros = [];
$d = [
    'cliente_id'      => '',
    'nome_contato'    => '',
    'telefone_contato'=> '',
    'intencao'        => '',
    'usuario_id'      => $_SESSION['usuario_id'],
    'tipo'            => 'ligacao',
    'descricao'       => '',
    'resultado'       => 'interesse',
    'data_chamada'    => date('Y-m-d\TH:i'),
];

$clientes   = obterTodas("SELECT id, nome, telefone FROM clientes ORDER BY nome");
$vendedores = obterTodas("SELECT id, nome FROM usuarios WHERE ativo = 1 ORDER BY nome");

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

    if (empty($d['descricao']) && empty($d['nome_contato'])) $erros[] = 'Informe ao menos o nome do contato ou uma descrição.';

    if (empty($erros)) {
        $novo_id = inserir('chamadas_proposta', $d);

        // Se intenção é comprar → redirecionar para nova venda com cliente pré-selecionado
        if ($d['intencao'] === 'comprar' && $d['cliente_id']) {
            header('Location: ' . ADMIN_URL . 'vendas/nova.php?cliente_id=' . $d['cliente_id'] . '&origem=chamada&chamada_id=' . $novo_id);
            exit;
        }

        header('Location: ' . ADMIN_URL . 'chamadas/?msg=salvo');
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
        <h1 class="page__titulo">Nova Chamada</h1>
        <p class="page__subtitulo">Registrar contato — compra, venda ou consignação</p>
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
                           value="<?= htmlspecialchars($d['nome_contato']) ?>"
                           placeholder="Ex: João da Silva">
                    <span class="form-grupo__hint">Não precisa ser um cliente cadastrado</span>
                </div>
                <div class="form-grupo">
                    <label>Telefone / WhatsApp</label>
                    <input type="text" name="telefone_contato"
                           value="<?= htmlspecialchars($d['telefone_contato']) ?>"
                           placeholder="(14) 99999-9999">
                </div>
                <div class="form-grupo">
                    <label>Intenção</label>
                    <select name="intencao" id="campoIntencao" onchange="toggleBtnSalvar()">
                        <option value="">Não informada</option>
                        <option value="comprar"   <?= $d['intencao'] === 'comprar' ? 'selected' : '' ?>>🛒 Quer Comprar</option>
                        <option value="vender"    <?= $d['intencao'] === 'vender' ? 'selected' : '' ?>>💰 Quer Vender</option>
                        <option value="consignar" <?= $d['intencao'] === 'consignar' ? 'selected' : '' ?>>🤝 Quer Consignar</option>
                        <option value="outro"     <?= $d['intencao'] === 'outro' ? 'selected' : '' ?>>Outro</option>
                    </select>
                </div>
                <div class="form-grupo">
                    <label>Cliente Cadastrado (opcional)</label>
                    <select name="cliente_id">
                        <option value="">Não vinculado</option>
                        <?php foreach ($clientes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= (int)$d['cliente_id'] === $c['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['nome']) ?>
                            <?= $c['telefone'] ? ' — ' . htmlspecialchars($c['telefone']) : '' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="form-grupo__hint">
                        <a href="../clientes/novo.php" style="color:#D4AF37">+ Cadastrar novo cliente</a>
                    </span>
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
                              placeholder="Ex: Busca SUV até R$ 80.000, 2020+, automático. Tem Civic 2018 na troca."><?= htmlspecialchars($d['descricao']) ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="form-acoes">
        <a href="." class="btn-admin btn-admin--secondary">Cancelar</a>
        <button type="submit" id="btnSalvar" class="btn-admin btn-admin--primary">Registrar Chamada</button>
    </div>
</form>

<script>
function toggleBtnSalvar() {
    var intencao = document.getElementById('campoIntencao').value;
    var btn = document.getElementById('btnSalvar');
    if (intencao === 'comprar') {
        btn.textContent = '🛒 Registrar e Abrir Nova Venda';
    } else {
        btn.textContent = 'Registrar Chamada';
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
