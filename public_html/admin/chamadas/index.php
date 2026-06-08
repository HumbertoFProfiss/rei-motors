<?php
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/functions.php';

requerAutenticacao();

$titulo_pagina = 'Chamadas e Propostas';
$pagina_ativa  = 'chamadas';
$admin_root    = '../';
$breadcrumb    = [['label' => 'Chamadas', 'url' => '']];

// DELETE
if (isset($_GET['deletar'])) {
    $cid = (int)$_GET['deletar'];
    if ($cid > 0) executarQuery("DELETE FROM chamadas_proposta WHERE id = ?", [$cid]);
    header('Location: ' . ADMIN_URL . 'chamadas/?msg=deletado');
    exit;
}

// Filtros
$resultado_f = trim($_GET['resultado'] ?? '');
$usuario_f   = (int)($_GET['usuario'] ?? 0);
$pagina      = max(1, (int)($_GET['pagina'] ?? 1));
$por_pag     = 25;

$where  = ['1=1'];
$params = [];

$resultados_validos = ['sem_resposta','interesse','proposta_enviada','fechado','desistiu'];
if ($resultado_f !== '' && in_array($resultado_f, $resultados_validos, true)) {
    $where[]  = 'cp.resultado = ?';
    $params[] = $resultado_f;
}
if ($usuario_f > 0) {
    $where[]  = 'cp.usuario_id = ?';
    $params[] = $usuario_f;
}

$where_sql = implode(' AND ', $where);
$total     = (int)obterUmaLinha("SELECT COUNT(*) c FROM chamadas_proposta cp WHERE $where_sql", $params)['c'];
$total_pag = max(1, (int)ceil($total / $por_pag));
$pagina    = min($pagina, $total_pag);
$offset    = ($pagina - 1) * $por_pag;

$chamadas = obterTodas(
    "SELECT cp.*, c.nome AS cliente_nome, c.telefone AS cliente_telefone, c.whatsapp AS cliente_whatsapp,
            u.nome AS vendedor_nome
     FROM chamadas_proposta cp
     LEFT JOIN clientes c ON cp.cliente_id = c.id
     JOIN usuarios u ON cp.usuario_id = u.id
     WHERE $where_sql
     ORDER BY cp.data_chamada DESC LIMIT ? OFFSET ?",
    array_merge($params, [$por_pag, $offset])
);

$vendedores = obterTodas("SELECT id, nome FROM usuarios WHERE ativo = 1 ORDER BY nome");

$labels_resultado = [
    'sem_resposta'    => ['label' => 'Sem resposta',    'cls' => 'cancelada'],
    'interesse'       => ['label' => 'Com interesse',   'cls' => 'pendente'],
    'proposta_enviada'=> ['label' => 'Proposta enviada','cls' => 'gold'],
    'fechado'         => ['label' => 'Fechado',         'cls' => 'green'],
    'desistiu'        => ['label' => 'Desistiu',        'cls' => 'cancelada'],
];
$labels_tipo = [
    'ligacao'    => '📞 Ligação',
    'whatsapp'   => '💬 WhatsApp',
    'presencial' => '🤝 Presencial',
    'email'      => '✉️ E-mail',
];

$msg = trim($_GET['msg'] ?? '');
require_once __DIR__ . '/../includes/header.php';
?>

<?php if ($msg === 'salvo'):   ?><div class="alert-admin alert-admin--success">✓ Chamada registrada.</div><?php endif; ?>
<?php if ($msg === 'editado'): ?><div class="alert-admin alert-admin--success">✓ Chamada atualizada.</div><?php endif; ?>
<?php if ($msg === 'deletado'): ?><div class="alert-admin alert-admin--success">✓ Chamada removida.</div><?php endif; ?>

<div class="page__header">
    <div>
        <h1 class="page__titulo">Chamadas e Propostas</h1>
        <p class="page__subtitulo"><?= $total ?> registro<?= $total !== 1 ? 's' : '' ?></p>
    </div>
    <div class="page__acoes">
        <a href="novo.php" class="btn-admin btn-admin--primary">+ Nova Chamada</a>
    </div>
</div>

<!-- FILTROS -->
<form method="GET" class="filtros-admin">
    <div class="filtro-grupo">
        <label>Resultado</label>
        <select name="resultado">
            <option value="">Todos</option>
            <?php foreach ($labels_resultado as $k => $r): ?>
            <option value="<?= $k ?>" <?= $resultado_f === $k ? 'selected' : '' ?>><?= $r['label'] ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filtro-grupo">
        <label>Vendedor</label>
        <select name="usuario">
            <option value="">Todos</option>
            <?php foreach ($vendedores as $u): ?>
            <option value="<?= $u['id'] ?>" <?= $usuario_f === $u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['nome']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filtro-grupo" style="padding-top:17px">
        <button type="submit" class="btn-admin btn-admin--secondary">Filtrar</button>
    </div>
    <?php if ($resultado_f !== '' || $usuario_f > 0): ?>
    <div class="filtro-grupo" style="padding-top:17px">
        <a href="?" class="btn-admin btn-admin--secondary">Limpar</a>
    </div>
    <?php endif; ?>
</form>

<div class="table-container">
    <div class="table-header">
        <h2 class="table-header__titulo">Histórico de Chamadas</h2>
    </div>
    <?php if ($chamadas): ?>
    <table class="table">
        <thead>
            <tr>
                <th>Data</th>
                <th>Tipo</th>
                <th>Contato</th>
                <th>Vendedor</th>
                <th>Descrição</th>
                <th>Resultado</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($chamadas as $ch): ?>
        <?php
            // Nome: cliente cadastrado tem prioridade; senão usa nome_contato livre
            $nome_exib = $ch['cliente_nome'] ?? $ch['nome_contato'] ?? null;
            // Telefone: whatsapp > telefone do cliente; senão telefone_contato avulso
            $fone_exib = $ch['cliente_whatsapp'] ?: ($ch['cliente_telefone'] ?: ($ch['telefone_contato'] ?? null));
        ?>
        <tr>
            <td style="white-space:nowrap"><?= formatarData($ch['data_chamada']) ?></td>
            <td><?= $labels_tipo[$ch['tipo']] ?? $ch['tipo'] ?></td>
            <td>
                <?php if ($nome_exib): ?>
                    <span style="font-weight:600"><?= htmlspecialchars($nome_exib) ?></span>
                    <?php if ($fone_exib): ?>
                    <br><a href="https://wa.me/55<?= preg_replace('/\D/', '', $fone_exib) ?>" target="_blank"
                           style="font-size:0.75rem;color:#D4AF37"><?= htmlspecialchars(formatarTelefone($fone_exib)) ?></a>
                    <?php endif; ?>
                <?php else: ?>
                    <span style="color:#888">—</span>
                <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($ch['vendedor_nome']) ?></td>
            <td style="max-width:280px;white-space:normal"><?= nl2br(htmlspecialchars($ch['descricao'])) ?></td>
            <td>
                <?php $r = $labels_resultado[$ch['resultado']] ?? ['label' => $ch['resultado'], 'cls' => 'pendente']; ?>
                <span class="badge-admin badge-admin--<?= $r['cls'] ?>"><?= $r['label'] ?></span>
            </td>
            <td class="td-acoes">
                <a href="editar.php?id=<?= $ch['id'] ?>" class="btn-admin btn-admin--secondary btn-admin--sm">✏️</a>
                <button onclick="confirmarAcao('?deletar=<?= $ch['id'] ?>', 'Remover chamada', 'Deseja remover esta chamada?')"
                        class="btn-admin btn-admin--danger btn-admin--sm">✕</button>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($total_pag > 1):
        $qb = http_build_query(array_filter(['resultado' => $resultado_f, 'usuario' => $usuario_f ?: '']));
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
        <div class="empty-state__icone">📞</div>
        <p class="empty-state__titulo">Nenhuma chamada registrada</p>
        <p class="empty-state__texto">Registre contatos com clientes interessados em carros.</p>
        <a href="novo.php" class="btn-admin btn-admin--primary" style="margin-top:12px">+ Nova Chamada</a>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
