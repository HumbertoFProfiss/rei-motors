<?php
// Requires (definidos na página que inclui este arquivo):
//   $titulo_pagina  — string, ex: 'Veículos'
//   $pagina_ativa   — string, ex: 'veiculos'
//   $breadcrumb     — array  [ ['label'=>'', 'url'=>''] ]
//   $admin_root     — string, ex: '../'  (caminho relativo até /admin/)

requerAutenticacao();

$_usuario = obterUmaLinha(
    "SELECT id, nome, role FROM usuarios WHERE id = ? AND ativo = 1",
    [$_SESSION['usuario_id']]
);
if (!$_usuario) {
    session_destroy();
    header('Location: ' . ADMIN_URL . 'login.php');
    exit;
}
$_inicial = mb_strtoupper(mb_substr($_usuario['nome'], 0, 1, 'UTF-8'), 'UTF-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo_pagina) ?> — Rei Motors Admin</title>
    <meta name="robots" content="noindex,nofollow">
    <link rel="stylesheet" href="<?= $admin_root ?>assets/css/admin.css">
</head>
<body>
<div class="admin-wrapper">

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar__logo">
        <span style="font-size:1.4rem">👑</span>
        <div>
            <div class="sidebar__logo-nome">Rei Motors</div>
            <div class="sidebar__logo-sub">Painel Admin</div>
        </div>
    </div>

    <nav class="sidebar__nav">
        <a href="<?= $admin_root ?>index.php"
           class="sidebar__link <?= $pagina_ativa === 'dashboard' ? 'ativo' : '' ?>">
            <span class="sidebar__link-icone">⊞</span> Dashboard
        </a>

        <div class="sidebar__section">Estoque</div>
        <a href="<?= $admin_root ?>veiculos/"
           class="sidebar__link <?= $pagina_ativa === 'veiculos' ? 'ativo' : '' ?>">
            <span class="sidebar__link-icone">🚗</span> Veículos
        </a>

        <div class="sidebar__section">Comercial</div>
        <a href="<?= $admin_root ?>leads/"
           class="sidebar__link <?= $pagina_ativa === 'leads' ? 'ativo' : '' ?>">
            <span class="sidebar__link-icone">📬</span> Leads
        </a>
        <a href="<?= $admin_root ?>clientes/"
           class="sidebar__link <?= $pagina_ativa === 'clientes' ? 'ativo' : '' ?>">
            <span class="sidebar__link-icone">👥</span> Clientes
        </a>
        <a href="<?= $admin_root ?>vendas/"
           class="sidebar__link <?= $pagina_ativa === 'vendas' ? 'ativo' : '' ?>">
            <span class="sidebar__link-icone">💰</span> Vendas
        </a>
        <a href="<?= $admin_root ?>garantias/"
           class="sidebar__link <?= $pagina_ativa === 'garantias' ? 'ativo' : '' ?>">
            <span class="sidebar__link-icone">🛡️</span> Garantias
        </a>

        <?php if (ehAdmin()): ?>
        <div class="sidebar__section">Financeiro</div>
        <a href="<?= $admin_root ?>financeiro/"
           class="sidebar__link <?= $pagina_ativa === 'financeiro' ? 'ativo' : '' ?>">
            <span class="sidebar__link-icone">📊</span> Financeiro
        </a>

        <div class="sidebar__section">Administração</div>
        <a href="<?= $admin_root ?>usuarios/"
           class="sidebar__link <?= $pagina_ativa === 'usuarios' ? 'ativo' : '' ?>">
            <span class="sidebar__link-icone">👤</span> Usuários
        </a>
        <a href="<?= $admin_root ?>configuracoes/"
           class="sidebar__link <?= $pagina_ativa === 'configuracoes' ? 'ativo' : '' ?>">
            <span class="sidebar__link-icone">⚙</span> Configurações
        </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar__footer">
        <div class="sidebar__user">
            <div class="sidebar__user-avatar"><?= htmlspecialchars($_inicial) ?></div>
            <div>
                <div class="sidebar__user-nome"><?= htmlspecialchars($_usuario['nome']) ?></div>
                <div class="sidebar__user-role"><?= $_usuario['role'] === 'admin' ? 'Administrador' : 'Vendedor' ?></div>
            </div>
        </div>
        <a href="<?= $admin_root ?>logout.php" class="sidebar__logout">⏻ Sair</a>
    </div>
</aside>

<!-- CONTEÚDO PRINCIPAL -->
<div class="admin-content">

    <!-- TOPBAR -->
    <header class="topbar">
        <div class="topbar__left">
            <button class="topbar__menu-btn" id="menuBtn" aria-label="Menu">☰</button>
            <nav class="topbar__breadcrumb" aria-label="Breadcrumb">
                <a href="<?= $admin_root ?>index.php">Admin</a>
                <?php foreach ($breadcrumb as $item): ?>
                    <span>›</span>
                    <?php if (!empty($item['url'])): ?>
                        <a href="<?= htmlspecialchars($item['url']) ?>"><?= htmlspecialchars($item['label']) ?></a>
                    <?php else: ?>
                        <span><?= htmlspecialchars($item['label']) ?></span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>
        </div>
        <div class="topbar__right">
            <a href="<?= BASE_URL ?>" target="_blank" class="topbar__site-link">↗ Ver Site</a>
        </div>
    </header>

    <!-- CONTEÚDO DA PÁGINA -->
    <main class="page">
