<?php
/**
 * Configurações Gerais do Projeto
 * Rei Motors - Loja de Carros Online
 */

// ===== DADOS DA LOJA =====
define('LOJA_NOME', 'Rei Motors');
define('LOJA_EMAIL', 'reimotorsoficial@gmail.com');
define('LOJA_WHATSAPP', '1438159000'); // Apenas números
define('LOJA_TELEFONE', '1438159000');
define('LOJA_ENDERECO', 'R. Maj. Matheus, 236 - Vila dos Lavradores, Botucatu - SP');
define('LOJA_HORARIO', 'Seg-Sex: 08h às 18h30 | Sáb: 08h às 13h');

// ===== URLs =====
$_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
if (str_contains($_host, 'worldcred.com.br')) {
    define('BASE_URL', 'https://worldcred.com.br/');
    // DOCUMENT_ROOT = /home2/reidosco/worldcred.com.br — uploads dentro do docroot
    define('UPLOAD_PATH', !empty($_SERVER['DOCUMENT_ROOT'])
        ? rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/uploads/'
        : __DIR__ . '/../worldcred.com.br/uploads/');
} else {
    define('BASE_URL', 'http://localhost:8080/');
    define('UPLOAD_PATH', __DIR__ . '/../public_html/uploads/');
}
unset($_host);
define('ADMIN_URL', BASE_URL . 'admin/');
define('CLIENTE_URL', BASE_URL . 'cliente/');
define('UPLOAD_DIR', '/uploads/');

// ===== SESSÃO =====
define('SESSION_TIMEOUT', 3600); // 1 hora em segundos

// ===== PAGINAÇÃO =====
define('ITEMS_POR_PAGINA', 12); // Carros por página (HostGator recomenda paginar)

// ===== CONFIGURAÇÕES DE IMAGEM =====
define('MAX_UPLOAD_SIZE', 5242880); // 5MB em bytes
define('IMG_MAX_WIDTH', 1200);
define('IMG_MAX_HEIGHT', 800);
define('IMG_THUMB_WIDTH', 300);
define('IMG_THUMB_HEIGHT', 200);

// ===== PERMISSÕES =====
define('ROLE_ADMIN', 'admin');
define('ROLE_VENDEDOR', 'vendedor');

// ===== PALETA DE CORES (Dark Mode Padrão) =====
define('COLOR_BG_PRIMARY', '#0A0A0A');
define('COLOR_BG_CARD', '#161616');
define('COLOR_TEXT_PRIMARY', '#FFFFFF');
define('COLOR_TEXT_SECONDARY', '#C0C0C0');
define('COLOR_ACCENT', '#D4AF37'); // Dourado

// Paleta Light Mode
define('COLOR_BG_PRIMARY_LIGHT', '#FAFAFA');
define('COLOR_BG_CARD_LIGHT', '#FFFFFF');
define('COLOR_TEXT_PRIMARY_LIGHT', '#0A0A0A');
define('COLOR_TEXT_SECONDARY_LIGHT', '#525252');
define('COLOR_ACCENT_LIGHT', '#B8860B');

// ===== COMISSÃO E PREÇOS =====
define('COMISSAO_PADRAO_VENDEDOR', 3.5); // 3.5% de comissão
define('GARANTIA_DIAS', 90); // 90 dias de garantia geral
define('GARANTIA_CAMBIO_DIAS', 90); // 90 dias de garantia para câmbio

// ===== EMAIL (Cron Jobs) =====
define('EMAIL_INTERVALO_MINIMO', 15); // Mínimo 15 minutos (HostGator)
define('SMTP_HOST', 'smtp.hostgator.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'reimotorsoficial@gmail.com');
define('SMTP_PASS', 'senha_email_aqui');

// ===== SEGREDOS (tokens, senhas de API) =====
// Arquivo separado, não versionado. Crie includes/secrets.php no servidor.
if (file_exists(__DIR__ . '/secrets.php')) {
    require_once __DIR__ . '/secrets.php';
}
if (!defined('WDAPI2_TOKEN')) {
    define('WDAPI2_TOKEN', ''); // fallback — configure secrets.php no servidor
}

// ===== REDES SOCIAIS =====
$redes_sociais = [
    'facebook' => 'https://www.facebook.com/profile.php?id=61565888583290',
    'instagram' => 'https://instagram.com/reimotors_',
    'whatsapp' => 'https://wa.me/55' . preg_replace('/\D/', '', LOJA_WHATSAPP),
    'youtube' => 'https://youtube.com/@reimotors',
];

// ===== FORMAS DE PAGAMENTO =====
$formas_pagamento = [
    'avista'        => 'À Vista',
    'pix'           => 'PIX',
    'financiamento' => 'Financiamento',
    'cartao'        => 'Cartão',
    'consorcio'     => 'Consórcio',
    'troca'         => 'Troca',
];

// ===== COMBUSTÍVEIS =====
$combustiveis = [
    'gasolina' => 'Gasolina',
    'flex' => 'Flex',
    'etanol' => 'Etanol',
    'diesel' => 'Diesel',
    'eletrico' => 'Elétrico',
];

// ===== CÂMBIOS =====
$cambios = [
    'manual' => 'Manual',
    'automatico' => 'Automático',
    'cvt' => 'CVT',
];

// ===== STATUS DOS VEÍCULOS =====
$status_veiculo = [
    'disponivel' => 'Disponível',
    'reservado' => 'Reservado',
    'vendido' => 'Vendido',
];

// ===== STATUS DOS CONTATOS =====
$status_contato = [
    'novo' => 'Novo',
    'contatado' => 'Contatado',
    'interessado' => 'Interessado',
    'nao_interessado' => 'Não Interessado',
];

// ===== CATEGORIAS DE DESPESAS =====
$categorias_despesas = [
    'manutencao'   => 'Manutenção',
    'seguro'       => 'Seguro / Tirar Seguro',
    'ipva'         => 'IPVA',
    'licenciamento'=> 'Licenciamento',
    'transferencia'=> 'Transferência',
    'vistoria'     => 'Vistoria',
    'impostos'     => 'Impostos',
    'combustivel'  => 'Combustível',
    'estetica'     => 'Estética Automotiva',
    'martelinho'   => 'Martelinho de Ouro',
    'funilaria'    => 'Funilaria e Pintura',
    'cortesia'     => 'Cortesia',
    'compra_peca'  => 'Compra de Peça / Material',
    'garantia'     => 'Garantia',
    'outro'        => 'Outro',
];

// ===== SESSÃO E HEADERS DE SEGURANÇA =====
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => SESSION_TIMEOUT,
        'path'     => '/',
        'secure'   => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Headers de segurança para todas as páginas
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

/**
 * Verificar se usuário está autenticado
 */
function estaAutenticado() {
    return isset($_SESSION['usuario_id']) && isset($_SESSION['usuario_role']);
}

/**
 * Verificar se é admin
 */
function ehAdmin() {
    return estaAutenticado() && $_SESSION['usuario_role'] === ROLE_ADMIN;
}

/**
 * Redirecionar se não autenticado; bloqueia POST sem token CSRF válido
 */
function requerAutenticacao() {
    if (!estaAutenticado()) {
        header('Location: ' . ADMIN_URL . 'login.php');
        exit;
    }
    // Protege todos os POST do painel admin contra CSRF
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if (!verificarTokenCSRF($token)) {
            http_response_code(403);
            echo '<!DOCTYPE html><html><body style="font-family:sans-serif;padding:2rem">';
            echo '<h2>&#128274; Token de segurança inválido</h2>';
            echo '<p>A sessão pode ter expirado. <a href="javascript:history.back()">Voltar</a></p>';
            echo '</body></html>';
            exit;
        }
    }
}

/**
 * Redirecionar se não for admin
 */
function requerAdmin() {
    if (!ehAdmin()) {
        header('Location: ' . ADMIN_URL);
        exit;
    }
}
?>
