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
    'cliente_id'  => '',
    'usuario_id'  => $_SESSION['usuario_id'],
    'tipo'        => 'ligacao',
    'descricao'   => '',
    'resultado'   => 'interesse',
    'data_chamada'=> date('Y-m-d\TH:i'),
];

$clientes   = obterTodas("SELECT id, nome, telefone FROM clientes ORDER BY nome");
$vendedores = obterTodas("SELECT id, nome FROM usuarios WHERE ativo = 1 ORDER BY nome");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $d['cliente_id']   = (int)($_POST['cliente_id'] ?? 0) ?: null;
    $d['usuario_id']   = (int)($_POST['usuario_id'] ?? $_SESSION['usuario_id']);
    $d['tipo']         = in_array($_POST['tipo'] ?? '', ['ligacao','whatsapp','presencial','email']) ? $_POST['tipo'] : 'ligacao';
    $d['descricao']    = sanitizar($_POST['descricao'] ?? '');
    $d['resultado']    = in_array($_POST['resultado'] ?? '', ['sem_resposta','interesse','proposta_enviada','fechado','desistiu']) ? $_POST['resultado'] : 'interesse';
    $d['data_chamada'] = sanitizar($_POST['data_chamada'] ?? date('Y-m-d H:i:s'));

    if (empty($d['descricao'])) $erros[] = 'Descrição é obrigatória.';

    if (empty($erros)) {
        inserir('chamadas_proposta', $d);
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
        <p class="page__subtitulo">Registrar contato com cliente sobre proposta de veículo</p>
    </div>
</div>

<form method="POST" action="">
    <div class="form-section">
        <div class="form-section__titulo">📞 Dados do Contato</div>
        <div class="form-section__body">
            <div class="form-grid">
                <div class="form-grupo">
                    <label>Cliente</label>
                    <select name="cliente_id">
                        <option value="">Sem cliente cadastrado</option>
                        <?php foreach ($clientes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= (int)$d['cliente_id'] === $c['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['nome']) ?>
                            <?= $c['telefone'] ? ' — ' . htmlspecialchars($c['telefone']) : '' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="form-grupo__hint">
                        <a href="../clientes/novo.php" target="_blank" style="color:#D4AF37">+ Cadastrar novo cliente</a>
                    </span>
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
                    <label>Data e Hora</label>
                    <input type="datetime-local" name="data_chamada"
                           value="<?= htmlspecialchars($d['data_chamada']) ?>">
                </div>
            </div>
            <div style="margin-top:14px">
                <div class="form-grupo">
                    <label>Descrição / O que o cliente quer <span class="obrigatorio">*</span></label>
                    <textarea name="descricao" rows="5"
                              placeholder="Ex: Cliente busca SUV até R$ 80.000, 2020 ou mais novo, prefere automático. Tem carro na troca (Civic 2018)."><?= htmlspecialchars($d['descricao']) ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="form-acoes">
        <a href="." class="btn-admin btn-admin--secondary">Cancelar</a>
        <button type="submit" class="btn-admin btn-admin--primary">Registrar Chamada</button>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
