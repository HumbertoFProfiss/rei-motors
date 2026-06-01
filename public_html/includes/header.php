<?php
/**
 * Include: Header
 * Rei Motors - Reutilizável em todas as páginas
 */
?>

<header class="header">
    <div class="container">
        <a href="<?php echo BASE_URL; ?>" class="header__logo">
            <img src="<?php echo UPLOAD_DIR; ?>logo.png" alt="<?php echo LOJA_NOME; ?>" class="logo" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2240%22 height=%2240%22 viewBox=%220 0 40 40%22%3E%3Crect fill=%22%23D4AF37%22 width=%2240%22 height=%2240%22 rx=%228%22/%3E%3Ctext x=%2220%22 y=%2226%22 font-size=%2224%22 font-weight=%22bold%22 fill=%22%230A0A0A%22 text-anchor=%22middle%22%3ERM%3C/text%3E%3C/svg%3E'">
            <span class="logo-text"><?php echo LOJA_NOME; ?></span>
        </a>

        <nav class="header__nav">
            <ul class="nav__menu">
                <li><a href="<?php echo BASE_URL; ?>" class="nav__link">Home</a></li>
                <li><a href="<?php echo BASE_URL; ?>estoque.php" class="nav__link">Estoque</a></li>
                <li><a href="<?php echo BASE_URL; ?>#venda-seu-carro" class="nav__link">Venda seu Carro</a></li>
                <li><a href="<?php echo BASE_URL; ?>#financiamento" class="nav__link">Financiamento</a></li>
                <li><a href="#contato" class="nav__link">Contato</a></li>
            </ul>
        </nav>

        <div class="header__actions">
            <button class="theme-toggle" id="themeToggle" aria-label="Alternar tema">
                <span class="theme-toggle__icon">🌙</span>
            </button>
            <a href="<?php echo CLIENTE_URL; ?>" class="btn btn--cliente" title="Área do Cliente">
                👤 Área do Cliente
            </a>
            <a href="https://wa.me/55<?php echo preg_replace('/\D/', '', LOJA_WHATSAPP); ?>"
               class="btn btn--primary" target="_blank">
                WhatsApp
            </a>
            <a href="<?php echo ADMIN_URL; ?>" class="btn-admin-mini" title="Administração">🔒</a>
        </div>

        <button class="hamburger" id="hamburger" aria-label="Menu">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </div>
</header>
