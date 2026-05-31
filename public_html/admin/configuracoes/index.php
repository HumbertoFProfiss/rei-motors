<?php
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/functions.php';

requerAdmin();

$titulo_pagina = 'Configurações';
$pagina_ativa  = 'configuracoes';
$admin_root    = '../';
$breadcrumb    = [['label' => 'Configurações', 'url' => '']];

$sucesso = '';
$erros   = [];

// Carrega configurações do banco
function getConfig(string $chave, string $default = ''): string {
    $row = obterUmaLinha("SELECT valor FROM configuracoes WHERE chave = ?", [$chave]);
    return $row ? (string)$row['valor'] : $default;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $campos_texto = [
        'loja_nome', 'loja_email', 'loja_whatsapp', 'loja_telefone',
        'loja_endereco', 'loja_horario',
        'fb_url', 'ig_url', 'yt_url',
        'taxa_juros_simulador',
    ];

    foreach ($campos_texto as $chave) {
        $valor = sanitizar($_POST[$chave] ?? '');
        // Upsert: update se existe, insert se não
        $existe = obterUmaLinha("SELECT id FROM configuracoes WHERE chave = ?", [$chave]);
        if ($existe) {
            executarQuery("UPDATE configuracoes SET valor = ? WHERE chave = ?", [$valor, $chave]);
        } else {
            inserir('configuracoes', ['chave' => $chave, 'valor' => $valor, 'tipo' => 'texto']);
        }
    }

    // Boolean
    $modo_manutencao = isset($_POST['modo_manutencao']) ? 'true' : 'false';
    $existe_mm = obterUmaLinha("SELECT id FROM configuracoes WHERE chave = 'modo_manutencao'");
    if ($existe_mm) {
        executarQuery("UPDATE configuracoes SET valor = ? WHERE chave = 'modo_manutencao'", [$modo_manutencao]);
    } else {
        inserir('configuracoes', ['chave' => 'modo_manutencao', 'valor' => $modo_manutencao, 'tipo' => 'boolean']);
    }

    $sucesso = 'Configurações salvas com sucesso!';
}

require_once __DIR__ . '/../includes/header.php';
?>

<?php if ($sucesso): ?>
<div class="alert-admin alert-admin--success">✓ <?= htmlspecialchars($sucesso) ?></div>
<?php endif; ?>

<div class="page__header">
    <div>
        <h1 class="page__titulo">Configurações</h1>
        <p class="page__subtitulo">Dados gerais da loja e preferências do sistema</p>
    </div>
    <div class="page__acoes">
        <a href="banners.php" class="btn-admin btn-admin--secondary">🖼️ Banners da Home</a>
    </div>
</div>

<form method="POST" action="">

    <!-- DADOS DA LOJA -->
    <div class="form-section">
        <div class="form-section__titulo">🏪 Dados da Loja</div>
        <div class="form-section__body">
            <div class="form-grid">
                <div class="form-grupo">
                    <label>Nome da Loja</label>
                    <input type="text" name="loja_nome"
                           value="<?= htmlspecialchars(getConfig('loja_nome', LOJA_NOME)) ?>">
                </div>
                <div class="form-grupo">
                    <label>E-mail de Contato</label>
                    <input type="email" name="loja_email"
                           value="<?= htmlspecialchars(getConfig('loja_email', LOJA_EMAIL)) ?>">
                </div>
                <div class="form-grupo">
                    <label>WhatsApp (somente números)</label>
                    <input type="text" name="loja_whatsapp"
                           value="<?= htmlspecialchars(getConfig('loja_whatsapp', LOJA_WHATSAPP)) ?>"
                           placeholder="11999999999">
                </div>
                <div class="form-grupo">
                    <label>Telefone</label>
                    <input type="text" name="loja_telefone"
                           value="<?= htmlspecialchars(getConfig('loja_telefone', LOJA_TELEFONE)) ?>">
                </div>
                <div class="form-grupo form-grupo--2">
                    <label>Endereço Completo</label>
                    <input type="text" name="loja_endereco"
                           value="<?= htmlspecialchars(getConfig('loja_endereco', LOJA_ENDERECO)) ?>">
                </div>
                <div class="form-grupo">
                    <label>Horário de Funcionamento</label>
                    <input type="text" name="loja_horario"
                           value="<?= htmlspecialchars(getConfig('loja_horario', LOJA_HORARIO)) ?>"
                           placeholder="Seg–Sex 09:00–18:00">
                </div>
            </div>
        </div>
    </div>

    <!-- REDES SOCIAIS -->
    <div class="form-section">
        <div class="form-section__titulo">📱 Redes Sociais</div>
        <div class="form-section__body">
            <div class="form-grid">
                <div class="form-grupo">
                    <label>URL Facebook</label>
                    <input type="url" name="fb_url"
                           value="<?= htmlspecialchars(getConfig('fb_url', 'https://facebook.com/reimotors')) ?>"
                           placeholder="https://facebook.com/...">
                </div>
                <div class="form-grupo">
                    <label>URL Instagram</label>
                    <input type="url" name="ig_url"
                           value="<?= htmlspecialchars(getConfig('ig_url', 'https://instagram.com/reimotors')) ?>"
                           placeholder="https://instagram.com/...">
                </div>
                <div class="form-grupo">
                    <label>URL YouTube</label>
                    <input type="url" name="yt_url"
                           value="<?= htmlspecialchars(getConfig('yt_url', 'https://youtube.com/@reimotors')) ?>"
                           placeholder="https://youtube.com/...">
                </div>
            </div>
        </div>
    </div>

    <!-- FINANCEIRO -->
    <div class="form-section">
        <div class="form-section__titulo">💰 Financeiro</div>
        <div class="form-section__body">
            <div class="form-grid">
                <div class="form-grupo">
                    <label>Taxa de Juros Simulador (% ao mês)</label>
                    <input type="text" name="taxa_juros_simulador"
                           value="<?= htmlspecialchars(getConfig('taxa_juros_simulador', '1.49')) ?>"
                           placeholder="1.49">
                    <span class="form-grupo__hint">Usada no simulador de financiamento da home</span>
                </div>
            </div>
        </div>
    </div>

    <!-- SISTEMA -->
    <div class="form-section">
        <div class="form-section__titulo">⚙ Sistema</div>
        <div class="form-section__body">
            <div class="form-check">
                <input type="checkbox" id="modo_manutencao" name="modo_manutencao" value="1"
                       <?= getConfig('modo_manutencao') === 'true' ? 'checked' : '' ?>>
                <label for="modo_manutencao">Modo de Manutenção</label>
            </div>
            <p class="form-grupo__hint" style="margin-top:6px;margin-left:23px">
                Quando ativado, visitantes veem uma página de manutenção. O painel admin continua funcionando.
            </p>
        </div>
    </div>

    <div class="form-acoes">
        <button type="submit" class="btn-admin btn-admin--primary">Salvar Configurações</button>
    </div>

</form>

<!-- INFO DO SISTEMA -->
<div style="margin-top:20px">
    <div class="form-section">
        <div class="form-section__titulo">ℹ Informações do Sistema</div>
        <div class="form-section__body">
            <div class="form-grid">
                <div class="form-grupo">
                    <label>Versão PHP</label>
                    <input type="text" value="<?= PHP_VERSION ?>" disabled style="color:#555">
                </div>
                <div class="form-grupo">
                    <label>MySQL</label>
                    <input type="text" value="<?= obterUmaLinha("SELECT VERSION() v")['v'] ?? '—' ?>" disabled style="color:#555">
                </div>
                <div class="form-grupo">
                    <label>Diretório de Uploads</label>
                    <input type="text" value="<?= htmlspecialchars(UPLOAD_PATH) ?>" disabled style="color:#555;font-size:0.72rem">
                </div>
                <div class="form-grupo">
                    <label>GD Library</label>
                    <input type="text" value="<?= extension_loaded('gd') ? '✓ Disponível' : '✗ Não disponível' ?>" disabled style="color:#555">
                </div>
                <div class="form-grupo">
                    <label>Data/Hora Servidor</label>
                    <input type="text" value="<?= date('d/m/Y H:i:s') ?>" disabled style="color:#555">
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
