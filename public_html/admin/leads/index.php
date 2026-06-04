<?php
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/functions.php';

$titulo_pagina = 'Leads';
$pagina_ativa  = 'leads';
$admin_root    = '../';
$breadcrumb    = [['label' => 'Leads', 'url' => '']];

// Atualizar status do lead via GET (ação rápida)
if (isset($_GET['status_lead'], $_GET['id'])) {
    requerAutenticacao();
    $lead_id     = (int)$_GET['id'];
    $novo_status = trim($_GET['status_lead']);
    $validos     = ['novo', 'contatado', 'interessado', 'nao_interessado', 'convertido'];
    if (in_array($novo_status, $validos, true) && $lead_id > 0) {
        atualizar('leads', ['tipo_lead' => $novo_status], 'id = ?', [$lead_id]);
    }
    header('Location: ' . ADMIN_URL . 'leads/?msg=atualizado');
    exit;
}

// Filtros
$status_f  = trim($_GET['status'] ?? '');
$origem_f  = trim($_GET['origem'] ?? '');
$busca_f   = trim($_GET['busca'] ?? '');
$pagina    = max(1, (int)($_GET['pagina'] ?? 1));
$por_pagina = 25;

$where  = ['1=1'];
$params = [];

if ($busca_f !== '') {
    $where[]  = '(l.nome LIKE ? OR l.email LIKE ? OR l.telefone LIKE ?)';
    $params[] = "%$busca_f%";
    $params[] = "%$busca_f%";
    $params[] = "%$busca_f%";
}

$status_validos = ['novo', 'contatado', 'interessado', 'nao_interessado', 'convertido'];
if ($status_f !== '' && in_array($status_f, $status_validos, true)) {
    $where[]  = 'l.tipo_lead = ?';
    $params[] = $status_f;
}

$origens_validas = ['estoque', 'formulario_interesse', 'simulador', 'site', 'outro'];
if ($origem_f !== '' && in_array($origem_f, $origens_validas, true)) {
    $where[]  = 'l.origem = ?';
    $params[] = $origem_f;
}

$where_sql = implode(' AND ', $where);
$total     = (int)obterUmaLinha("SELECT COUNT(*) c FROM leads l WHERE $where_sql", $params)['c'];
$total_pag = max(1, (int)ceil($total / $por_pagina));
$pagina    = min($pagina, $total_pag);
$offset    = ($pagina - 1) * $por_pagina;

$leads = obterTodas(
    "SELECT l.*, v.marca, v.modelo, v.slug
     FROM leads l
     LEFT JOIN veiculos v ON l.veiculo_id = v.id
     WHERE $where_sql
     ORDER BY l.criado_em DESC LIMIT ? OFFSET ?",
    array_merge($params, [$por_pagina, $offset])
);

$msg = trim($_GET['msg'] ?? '');

require_once __DIR__ . '/../includes/header.php';
?>

<?php if ($msg === 'atualizado'): ?>
<div class="alert-admin alert-admin--success">✓ Status do lead atualizado.</div>
<?php endif; ?>

<div class="page__header">
    <div>
        <h1 class="page__titulo">Leads</h1>
        <p class="page__subtitulo"><?= $total ?> lead<?= $total !== 1 ? 's' : '' ?> encontrado<?= $total !== 1 ? 's' : '' ?></p>
    </div>
</div>

<!-- FILTROS -->
<form method="GET" class="filtros-admin">
    <div class="filtro-grupo">
        <label>Buscar</label>
        <input type="text" name="busca" value="<?= htmlspecialchars($busca_f) ?>"
               placeholder="Nome, e-mail, telefone..." style="min-width:200px">
    </div>
    <div class="filtro-grupo">
        <label>Status</label>
        <select name="status">
            <option value="">Todos</option>
            <option value="novo"            <?= $status_f === 'novo' ? 'selected' : '' ?>>Novo</option>
            <option value="contatado"       <?= $status_f === 'contatado' ? 'selected' : '' ?>>Contatado</option>
            <option value="interessado"     <?= $status_f === 'interessado' ? 'selected' : '' ?>>Interessado</option>
            <option value="nao_interessado" <?= $status_f === 'nao_interessado' ? 'selected' : '' ?>>Não Interessado</option>
            <option value="convertido"      <?= $status_f === 'convertido' ? 'selected' : '' ?>>Convertido</option>
        </select>
    </div>
    <div class="filtro-grupo">
        <label>Origem</label>
        <select name="origem">
            <option value="">Todas</option>
            <option value="formulario_interesse" <?= $origem_f === 'formulario_interesse' ? 'selected' : '' ?>>Formulário Interesse</option>
            <option value="estoque"              <?= $origem_f === 'estoque' ? 'selected' : '' ?>>Estoque</option>
            <option value="simulador"            <?= $origem_f === 'simulador' ? 'selected' : '' ?>>Simulador</option>
            <option value="site"                 <?= $origem_f === 'site' ? 'selected' : '' ?>>Site</option>
        </select>
    </div>
    <div class="filtro-grupo" style="padding-top:17px">
        <button type="submit" class="btn-admin btn-admin--secondary">Filtrar</button>
    </div>
    <?php if ($busca_f !== '' || $status_f !== '' || $origem_f !== ''): ?>
    <div class="filtro-grupo" style="padding-top:17px">
        <a href="?" class="btn-admin btn-admin--secondary">Limpar</a>
    </div>
    <?php endif; ?>
</form>

<!-- TABELA -->
<div class="table-container">
    <div class="table-header">
        <h2 class="table-header__titulo">Lista de Leads</h2>
    </div>

    <?php if ($leads): ?>
    <table class="table">
        <thead>
            <tr>
                <th>Data</th>
                <th>Nome</th>
                <th>Contato</th>
                <th>Veículo de Interesse</th>
                <th>Origem</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($leads as $l): ?>
        <tr>
            <td style="white-space:nowrap"><?= formatarData($l['criado_em']) ?><br>
                <small style="color:#888"><?= date('H:i', strtotime($l['criado_em'])) ?></small></td>
            <td class="td-titulo"><?= htmlspecialchars($l['nome']) ?></td>
            <td>
                <?php if ($l['telefone']): ?>
                <a href="https://wa.me/55<?= preg_replace('/\D/','',$l['telefone']) ?>"
                   target="_blank" style="color:#22c55e">
                    <?= htmlspecialchars($l['telefone']) ?>
                </a><br>
                <?php endif; ?>
                <?php if ($l['email']): ?>
                <small style="color:#888"><?= htmlspecialchars($l['email']) ?></small>
                <?php endif; ?>
            </td>
            <td>
                <?php if ($l['marca']): ?>
                <a href="<?= ADMIN_URL ?>veiculos/editar.php?id=<?= $l['veiculo_id'] ?>"
                   style="color:#D4AF37">
                    <?= htmlspecialchars($l['marca'].' '.$l['modelo']) ?>
                </a>
                <?php else: ?>—<?php endif; ?>
            </td>
            <td><?= htmlspecialchars(str_replace('_', ' ', $l['origem'])) ?></td>
            <td>
                <span class="badge-admin badge-admin--<?= htmlspecialchars($l['tipo_lead']) ?>">
                    <?= ucfirst(str_replace('_', ' ', $l['tipo_lead'])) ?>
                </span>
            </td>
            <td class="td-acoes">
                <select onchange="window.location='?id=<?= $l['id'] ?>&status_lead='+this.value"
                        style="background:var(--admin-bg);border:1px solid var(--admin-border);color:inherit;border-radius:5px;padding:3px 6px;font-size:0.72rem;cursor:pointer">
                    <option value="">Alterar...</option>
                    <option value="novo">Novo</option>
                    <option value="contatado">Contatado</option>
                    <option value="interessado">Interessado</option>
                    <option value="nao_interessado">Não interessado</option>
                    <option value="convertido">Convertido</option>
                </select>
            </td>
        </tr>
        <?php if (!empty($l['observacoes'])): ?>
        <tr>
            <td></td>
            <td colspan="6" style="color:#888;font-size:0.72rem;padding-top:0;padding-bottom:10px">
                💬 <?= nl2br(htmlspecialchars($l['observacoes'])) ?>
            </td>
        </tr>
        <?php endif; ?>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($total_pag > 1):
        $qb  = http_build_query(array_filter(['busca' => $busca_f, 'status' => $status_f, 'origem' => $origem_f]));
        $sep = $qb ? '&' : '';
    ?>
    <div class="paginacao-admin">
        <?php if ($pagina > 1): ?><a href="?<?= $qb.$sep ?>pagina=<?= $pagina-1 ?>">‹</a>
        <?php else: ?><span class="disabled">‹</span><?php endif; ?>
        <?php for ($i = 1; $i <= $total_pag; $i++):
            if ($i === $pagina): ?><span class="ativo"><?= $i ?></span>
            <?php elseif ($i <= 2 || $i >= $total_pag-1 || abs($i-$pagina) <= 1): ?>
            <a href="?<?= $qb.$sep ?>pagina=<?= $i ?>"><?= $i ?></a>
            <?php elseif ($i === 3 || $i === $total_pag-2): ?><span>…</span>
            <?php endif;
        endfor; ?>
        <?php if ($pagina < $total_pag): ?><a href="?<?= $qb.$sep ?>pagina=<?= $pagina+1 ?>">›</a>
        <?php else: ?><span class="disabled">›</span><?php endif; ?>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <div class="empty-state">
        <div class="empty-state__icone">📬</div>
        <p class="empty-state__titulo">Nenhum lead encontrado</p>
        <p class="empty-state__texto">Quando visitantes preencherem formulários, aparecerão aqui.</p>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
