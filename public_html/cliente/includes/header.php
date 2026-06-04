<?php
// Requires (definidos na página que inclui este arquivo):
//   $titulo_pagina  — string
//   $pagina_ativa   — string ('dashboard','compras','garantias','perfil')

requerClienteAutenticado();

$_cliente_logado = obterUmaLinha(
    "SELECT id, nome FROM clientes WHERE id = ?",
    [$_SESSION['cliente_id']]
);
if (!$_cliente_logado) {
    session_destroy();
    header('Location: ' . BASE_URL . 'cliente/login.php');
    exit;
}
$_inicial_c = mb_strtoupper(mb_substr($_cliente_logado['nome'], 0, 1, 'UTF-8'), 'UTF-8');
$_nome_c    = $_cliente_logado['nome'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo_pagina) ?> — Rei Motors</title>
    <meta name="robots" content="noindex,nofollow">
    <link rel="stylesheet" href="<?= CLIENTE_URL ?>assets/css/cliente.css">
</head>
<body>
<div class="cliente-wrapper">

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar__logo">
        <a href="<?= BASE_URL ?>"><img src="<?= BASE_URL ?>uploads/logo.png" alt="Rei Motors" style="height:48px;width:auto;object-fit:contain;display:block"></a>
    </div>

    <nav class="sidebar__nav">
        <a href="<?= CLIENTE_URL ?>"
           class="sidebar__link <?= $pagina_ativa === 'dashboard' ? 'ativo' : '' ?>">
            <span class="sidebar__link-icone">⊞</span> Início
        </a>
        <a href="<?= CLIENTE_URL ?>compras.php"
           class="sidebar__link <?= $pagina_ativa === 'compras' ? 'ativo' : '' ?>">
            <span class="sidebar__link-icone">🚗</span> Minhas Compras
        </a>
        <a href="<?= CLIENTE_URL ?>favoritos.php"
           class="sidebar__link <?= $pagina_ativa === 'favoritos' ? 'ativo' : '' ?>">
            <span class="sidebar__link-icone">❤️</span> Favoritos
        </a>
        <a href="<?= CLIENTE_URL ?>perfil.php"
           class="sidebar__link <?= $pagina_ativa === 'perfil' ? 'ativo' : '' ?>">
            <span class="sidebar__link-icone">👤</span> Meu Perfil
        </a>
    </nav>

    <div class="sidebar__footer">
        <div class="sidebar__user">
            <div class="sidebar__user-avatar"><?= htmlspecialchars($_inicial_c) ?></div>
            <div>
                <div class="sidebar__user-nome"><?= htmlspecialchars($_nome_c) ?></div>
                <div class="sidebar__user-role">Cliente</div>
            </div>
        </div>
        <a href="<?= CLIENTE_URL ?>logout.php" class="sidebar__logout">⏻ Sair</a>
    </div>
</aside>

<!-- CONTEÚDO PRINCIPAL -->
<div class="cliente-content">

    <!-- TOPBAR -->
    <header class="topbar">
        <div class="topbar__left">
            <button class="topbar__menu-btn" id="menuBtn" aria-label="Menu">☰</button>
            <span class="topbar__titulo"><?= htmlspecialchars($titulo_pagina) ?></span>
        </div>
        <div class="topbar__right">
            <button id="btnTheme" onclick="toggleTheme()" title="Alternar tema"
                style="background:none;border:1px solid #333;color:#888;padding:4px 10px;border-radius:5px;font-size:0.85rem;cursor:pointer;transition:all 0.15s">
                🌙
            </button>
            <a href="<?= BASE_URL ?>" class="topbar__site-link">↗ Ver Site</a>
        </div>
    </header>
    <script>
    (function(){
        var t = localStorage.getItem('rei_theme');
        if (t === 'light') { document.body.classList.add('light'); document.getElementById('btnTheme').textContent = '☀️'; }
    })();
    function toggleTheme() {
        var btn = document.getElementById('btnTheme');
        btn.style.transform = 'scale(0.8)';
        btn.style.transition = 'transform 0.15s ease';
        setTimeout(function() {
            var isLight = document.body.classList.toggle('light');
            localStorage.setItem('rei_theme', isLight ? 'light' : 'dark');
            btn.textContent = isLight ? '☀️' : '🌙';
            btn.style.transform = 'scale(1)';
        }, 150);
    }
    </script>

    <!-- CONTEÚDO DA PÁGINA -->
    <main class="page">
