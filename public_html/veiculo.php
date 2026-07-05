<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Favorito do cliente logado
$e_favorito = false;
if (clienteAutenticado()) {
    $chk = obterUmaLinha(
        "SELECT id FROM cliente_favoritos WHERE cliente_id = ? AND veiculo_id = (SELECT id FROM veiculos WHERE slug = ? LIMIT 1)",
        [$_SESSION['cliente_id'], sanitizar($_GET['slug'] ?? '')]
    );
    $e_favorito = (bool)$chk;
}

// ===== BUSCAR VEÍCULO =====

$slug = sanitizar($_GET['slug'] ?? '');

if (!$slug) {
    header('Location: ' . BASE_URL . 'estoque.php');
    exit;
}

$veiculo = obterUmaLinha(
    "SELECT * FROM veiculos WHERE slug = ? AND status IN ('disponivel','reservado')",
    [$slug]
);

if (!$veiculo) {
    http_response_code(404);
    header('Location: ' . BASE_URL . 'estoque.php');
    exit;
}

// Fotos do veículo (principal primeiro)
$fotos = obterTodas(
    "SELECT * FROM veiculos_fotos WHERE veiculo_id = ? ORDER BY principal DESC, ordem ASC",
    [$veiculo['id']]
);

// Vídeo YouTube
$video = obterUmaLinha("SELECT url_youtube FROM veiculos_videos WHERE veiculo_id = ? LIMIT 1", [$veiculo['id']]);
$youtube_embed_url = null;
if ($video && !empty($video['url_youtube'])) {
    preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/', $video['url_youtube'], $m);
    if (!empty($m[1])) {
        $youtube_embed_url = 'https://www.youtube.com/embed/' . $m[1] . '?rel=0';
    }
}

// Opcionais do veículo
$opcionais_veiculo = array_column(
    obterTodas("SELECT opcional FROM veiculos_opcionais WHERE veiculo_id = ?", [$veiculo['id']]),
    'opcional'
);

// Veículos relacionados (mesma marca, excluindo este)
$relacionados = obterTodas(
    "SELECT v.*,
            (SELECT vf.caminho FROM veiculos_fotos vf
             WHERE vf.veiculo_id = v.id AND vf.principal = 1 LIMIT 1) as foto_principal
     FROM veiculos v
     WHERE v.marca = ? AND v.slug != ? AND v.status = 'disponivel'
     ORDER BY RAND()
     LIMIT 3",
    [$veiculo['marca'], $slug]
);

// ===== FORMULÁRIO DE INTERESSE =====

$form_sucesso = isset($_GET['interesse']) && $_GET['interesse'] === 'ok';
$form_erro    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificarTokenCSRF($_POST['csrf_token'] ?? '')) {
        $form_erro = 'Sessão expirada. Recarregue a página e tente novamente.';
    } else {
        $nome     = sanitizar($_POST['nome']     ?? '');
        $email    = sanitizar($_POST['email']    ?? '');
        $telefone = sanitizar($_POST['telefone'] ?? '');
        $mensagem = sanitizar($_POST['mensagem'] ?? '');

        if (!$nome || !$telefone) {
            $form_erro = 'Por favor, preencha nome e telefone.';
        } elseif ($email && !validarEmail($email)) {
            $form_erro = 'E-mail inválido.';
        } else {
            $lead_data = [
                'veiculo_id'  => $veiculo['id'],
                'nome'        => $nome,
                'email'       => $email ?: null,
                'telefone'    => $telefone,
                'origem'      => 'formulario_interesse',
                'tipo_lead'   => 'novo',
                'observacoes' => $mensagem ?: null,
                'ip_origem'   => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent'  => $_SERVER['HTTP_USER_AGENT'] ?? '',
            ];
            inserir('leads', $lead_data);
            notificarNovoLead($lead_data);

            header('Location: ' . BASE_URL . 'veiculo.php?slug=' . urlencode($slug) . '&interesse=ok#interesse');
            exit;
        }
    }
}

// ===== DADOS PARA EXIBIÇÃO =====

$foto_principal = !empty($fotos) ? $fotos[0]['caminho'] : null;

$msg_whatsapp = urlencode(
    'Olá! Vi o ' . $veiculo['marca'] . ' ' . $veiculo['modelo'] . ' ' . $veiculo['ano'] .
    ' no site e gostaria de mais informações. ' .
    BASE_URL . 'veiculo.php?slug=' . $slug
);

$csrf_token = gerarTokenCSRF();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($veiculo['marca'] . ' ' . $veiculo['modelo'] . ' ' . $veiculo['ano'] . ' — ' . formatarMoeda($veiculo['preco_venda']) . '. Seminovo revisado na Rei Motors, Botucatu - SP. Financiamento facilitado.'); ?>">
    <link rel="icon" type="image/png" href="<?php echo UPLOAD_DIR; ?>favicon.png">

    <title><?php echo htmlspecialchars($veiculo['marca'] . ' ' . $veiculo['modelo'] . ' ' . $veiculo['ano']); ?> - <?php echo LOJA_NOME; ?></title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/reset.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/variables.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/header.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/estoque.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/veiculo.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/footer.css">
</head>
<body>

    <!-- ===== HEADER ===== -->
    <header class="header">
        <div class="container">
            <a href="<?php echo BASE_URL; ?>" class="header__logo">
                <img src="<?php echo UPLOAD_DIR; ?>logo.png" alt="<?php echo LOJA_NOME; ?>" class="logo"
                     onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2240%22 height=%2240%22 viewBox=%220 0 40 40%22%3E%3Crect fill=%22%23D4AF37%22 width=%2240%22 height=%2240%22 rx=%228%22/%3E%3Ctext x=%2220%22 y=%2226%22 font-size=%2224%22 font-weight=%22bold%22 fill=%22%230A0A0A%22 text-anchor=%22middle%22%3ERM%3C/text%3E%3C/svg%3E'">
                <span class="logo-text"><?php echo LOJA_NOME; ?></span>
            </a>
            <nav class="header__nav">
                <ul class="nav__menu">
                    <li><a href="<?php echo BASE_URL; ?>" class="nav__link">Home</a></li>
                    <li><a href="<?php echo BASE_URL; ?>estoque.php" class="nav__link">Estoque</a></li>
                    <li><a href="<?php echo BASE_URL; ?>#venda-seu-carro" class="nav__link">Venda seu Carro</a></li>
                    <li><a href="<?php echo BASE_URL; ?>#financiamento" class="nav__link">Financiamento</a></li>
                    <li><a href="<?php echo BASE_URL; ?>#contato" class="nav__link">Contato</a></li>
                </ul>
            </nav>
            <div class="header__actions">
                <button class="theme-toggle" id="themeToggle" aria-label="Alternar tema">
                    <span class="theme-toggle__icon">🌙</span>
                </button>
                <a href="<?php echo CLIENTE_URL; ?>" class="btn btn--cliente" title="Área do Cliente">
                    👤 Área do Cliente
                </a>
                <a href="https://wa.me/55<?php echo preg_replace('/\D/', '', LOJA_WHATSAPP); ?>" class="btn btn--primary" target="_blank">
                    WhatsApp
                </a>
                <a href="<?php echo ADMIN_URL; ?>" class="btn-admin-mini" title="Administração">🔒</a>
            </div>
            <button class="hamburger" id="hamburger" aria-label="Menu">
                <span></span><span></span><span></span>
            </button>
        </div>
    </header>

    <!-- ===== BREADCRUMB ===== -->
    <div class="breadcrumb" style="margin-top: 72px;">
        <div class="container">
            <ol class="breadcrumb__lista">
                <li class="breadcrumb__item"><a href="<?php echo BASE_URL; ?>">Home</a></li>
                <li class="breadcrumb__item"><a href="<?php echo BASE_URL; ?>estoque.php">Estoque</a></li>
                <li class="breadcrumb__item"><a href="estoque.php?marca=<?php echo urlencode($veiculo['marca']); ?>"><?php echo htmlspecialchars($veiculo['marca']); ?></a></li>
                <li class="breadcrumb__item"><?php echo htmlspecialchars($veiculo['modelo'] . ' ' . $veiculo['ano']); ?></li>
            </ol>
        </div>
    </div>

    <!-- ===== DETALHE DO VEÍCULO ===== -->
    <section class="veiculo-detalhe">
        <div class="container">
            <div class="veiculo-detalhe__grid">

                <!-- Galeria -->
                <div class="veiculo__galeria">

                    <?php if ($foto_principal): ?>
                        <div class="galeria__principal" id="galeriaMain" onclick="abrirModal(indiceAtivo)">
                            <img id="fotoMain"
                                 src="<?php echo UPLOAD_DIR . htmlspecialchars($foto_principal); ?>"
                                 alt="<?php echo htmlspecialchars($veiculo['marca'] . ' ' . $veiculo['modelo']); ?>">
                            <?php if (count($fotos) > 1): ?>
                                <span class="galeria__contador" id="galeriaContador">1 / <?php echo count($fotos); ?></span>
                            <?php endif; ?>
                        </div>

                        <?php if (count($fotos) > 1): ?>
                            <div class="galeria__thumbs">
                                <?php foreach ($fotos as $i => $foto): ?>
                                    <div class="galeria__thumb <?php echo $i === 0 ? 'galeria__thumb--ativa' : ''; ?>"
                                         onclick="trocarFoto(<?php echo $i; ?>)">
                                        <img src="<?php echo UPLOAD_DIR . htmlspecialchars($foto['caminho']); ?>"
                                             alt="Foto <?php echo $i + 1; ?>"
                                             loading="lazy">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <div class="galeria__sem-foto">🚗</div>
                    <?php endif; ?>

                </div>

                <!-- Info -->
                <div class="veiculo__info">

                    <!-- Badges -->
                    <div class="veiculo__badges">
                        <span class="badge--status-<?php echo $veiculo['status']; ?>">
                            <?php echo $status_veiculo[$veiculo['status']] ?? $veiculo['status']; ?>
                        </span>
                        <?php if ($veiculo['destaque']): ?>
                            <span class="badge--destaque">⭐ Destaque</span>
                        <?php endif; ?>
                    </div>

                    <!-- Título -->
                    <div>
                        <h1 class="veiculo__titulo">
                            <?php echo htmlspecialchars($veiculo['marca'] . ' ' . $veiculo['modelo']); ?>
                        </h1>
                        <p class="veiculo__subtitulo">
                            <?php echo $veiculo['ano']; ?>
                            <?php if ($veiculo['cor']): ?> &middot; <?php echo htmlspecialchars($veiculo['cor']); ?><?php endif; ?>
                            &middot; <?php echo formatarKM($veiculo['quilometragem']); ?> km
                        </p>
                    </div>

                    <!-- Preço -->
                    <div class="veiculo__preco-box">
                        <p class="veiculo__preco-label">Preço de venda</p>
                        <p class="veiculo__preco-valor"><?php echo formatarMoeda($veiculo['preco_venda']); ?></p>
                    </div>

                    <!-- CTAs -->
                    <div class="veiculo__acoes">
                        <a href="https://wa.me/55<?php echo preg_replace('/\D/', '', LOJA_WHATSAPP); ?>?text=<?php echo $msg_whatsapp; ?>"
                           class="btn btn--large btn--whatsapp" target="_blank">
                            💬 Falar no WhatsApp
                        </a>
                        <a href="#interesse" class="btn btn--large btn--secondary">
                            📩 Quero mais informações
                        </a>
                        <button class="btn-fav btn-fav--grande<?= $e_favorito ? ' ativo' : '' ?>"
                                data-id="<?= $veiculo['id'] ?>"
                                title="<?= $e_favorito ? 'Remover dos favoritos' : 'Salvar nos favoritos' ?>">
                            <?= $e_favorito ? '❤️' : '🤍' ?> <?= $e_favorito ? 'Salvo' : 'Salvar' ?>
                        </button>
                    </div>

                    <!-- Specs rápidas -->
                    <div class="veiculo__specs">
                        <h3 class="veiculo__specs-titulo">Especificações</h3>
                        <div class="veiculo__specs-grid">
                            <div class="veiculo__spec">
                                <span class="veiculo__spec-label">Ano</span>
                                <span class="veiculo__spec-valor"><?php echo $veiculo['ano']; ?></span>
                            </div>
                            <div class="veiculo__spec">
                                <span class="veiculo__spec-label">Quilometragem</span>
                                <span class="veiculo__spec-valor"><?php echo formatarKM($veiculo['quilometragem']); ?> km</span>
                            </div>
                            <div class="veiculo__spec">
                                <span class="veiculo__spec-label">Combustível</span>
                                <span class="veiculo__spec-valor"><?php echo $combustiveis[$veiculo['combustivel']] ?? $veiculo['combustivel']; ?></span>
                            </div>
                            <div class="veiculo__spec">
                                <span class="veiculo__spec-label">Câmbio</span>
                                <span class="veiculo__spec-valor"><?php echo $cambios[$veiculo['cambio']] ?? $veiculo['cambio']; ?></span>
                            </div>
                            <?php if ($veiculo['cor']): ?>
                            <div class="veiculo__spec">
                                <span class="veiculo__spec-label">Cor</span>
                                <span class="veiculo__spec-valor"><?php echo htmlspecialchars($veiculo['cor']); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($veiculo['placa']): ?>
                            <div class="veiculo__spec">
                                <span class="veiculo__spec-label">Placa</span>
                                <span class="veiculo__spec-valor"><?php echo htmlspecialchars($veiculo['placa']); ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="veiculo__spec">
                                <span class="veiculo__spec-label">Garantia</span>
                                <span class="veiculo__spec-valor"><?php echo GARANTIA_DIAS; ?> dias</span>
                            </div>
                            <div class="veiculo__spec">
                                <span class="veiculo__spec-label">Câmbio (garantia)</span>
                                <span class="veiculo__spec-valor"><?php echo GARANTIA_CAMBIO_DIAS; ?> dias</span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>

    <!-- ===== DESCRIÇÃO ===== -->
    <?php if ($veiculo['descricao']): ?>
    <section class="veiculo__descricao-section">
        <div class="container">
            <h2 class="section__title">Descrição</h2>
            <p class="veiculo__descricao-texto"><?php echo nl2br(htmlspecialchars($veiculo['descricao'])); ?></p>
        </div>
    </section>
    <?php endif; ?>

    <!-- ===== OPCIONAIS ===== -->
    <?php if ($opcionais_veiculo): ?>
    <section class="veiculo__opcionais-section">
        <div class="container">
            <h2 class="section__title">Opcionais e Acessórios</h2>
            <?php
            $todos_opcionais = listarOpcionais();
            foreach ($todos_opcionais as $categoria => $itens):
                $itens_veiculo = array_intersect_key($itens, array_flip($opcionais_veiculo));
                if (!$itens_veiculo) continue;
            ?>
            <div class="opcionais-categoria">
                <div class="opcionais-categoria__titulo"><?= htmlspecialchars($categoria) ?></div>
                <ul class="opcionais-lista">
                    <?php foreach ($itens_veiculo as $label): ?>
                    <li class="opcionais-item"><?= htmlspecialchars($label) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- ===== VÍDEO ===== -->
    <?php if ($youtube_embed_url): ?>
    <section class="veiculo__video-section">
        <div class="container">
            <h2 class="section__title">Vídeo</h2>
            <div class="veiculo__video-wrapper">
                <iframe src="<?php echo $youtube_embed_url; ?>"
                        title="Vídeo <?php echo htmlspecialchars($veiculo['marca'].' '.$veiculo['modelo']); ?>"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen
                        loading="lazy"></iframe>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ===== FORMULÁRIO DE INTERESSE ===== -->
    <section class="form-interesse" id="interesse">
        <div class="container">
            <h2 class="section__title">Tenho Interesse</h2>

            <div class="form-interesse__box">
                <?php if ($form_sucesso): ?>
                    <div class="form-interesse__sucesso">
                        <span class="form-interesse__sucesso-icone">✅</span>
                        <h3>Mensagem enviada!</h3>
                        <p>Recebemos seu contato e entraremos em breve. Ou fale agora pelo WhatsApp!</p>
                        <br>
                        <a href="https://wa.me/55<?php echo preg_replace('/\D/', '', LOJA_WHATSAPP); ?>?text=<?php echo $msg_whatsapp; ?>"
                           class="btn btn--whatsapp" target="_blank">💬 Falar no WhatsApp</a>
                    </div>

                <?php else: ?>
                    <p class="form-interesse__intro">
                        Preencha seus dados e entraremos em contato sobre o
                        <strong><?php echo htmlspecialchars($veiculo['marca'] . ' ' . $veiculo['modelo'] . ' ' . $veiculo['ano']); ?></strong>.
                    </p>

                    <?php if ($form_erro): ?>
                        <div class="alert alert--error" style="margin-bottom:1.5rem;">
                            <?php echo htmlspecialchars($form_erro); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="#interesse">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                        <div class="form-interesse__grid">
                            <div class="form__grupo">
                                <label for="nome">Nome *</label>
                                <input type="text" id="nome" name="nome" required
                                       placeholder="Seu nome completo"
                                       value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>">
                            </div>

                            <div class="form__grupo">
                                <label for="telefone">Telefone / WhatsApp *</label>
                                <input type="tel" id="telefone" name="telefone" required
                                       placeholder="(11) 99999-9999"
                                       value="<?php echo htmlspecialchars($_POST['telefone'] ?? ''); ?>">
                            </div>

                            <div class="form__grupo form__grupo--full">
                                <label for="email">E-mail</label>
                                <input type="email" id="email" name="email"
                                       placeholder="seu@email.com"
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                            </div>

                            <div class="form__grupo form__grupo--full">
                                <label for="mensagem">Mensagem</label>
                                <textarea id="mensagem" name="mensagem" rows="3"
                                          placeholder="Alguma dúvida ou observação?"><?php echo htmlspecialchars($_POST['mensagem'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <div class="form-interesse__submit">
                            <button type="submit" class="btn btn--large btn--primary">
                                Enviar interesse
                            </button>
                        </div>

                        <p class="form-interesse__aviso">
                            * Campos obrigatórios. Seus dados são usados apenas para contato.
                        </p>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- ===== RELACIONADOS ===== -->
    <?php if (!empty($relacionados)): ?>
    <section class="relacionados">
        <div class="container">
            <h2 class="section__title">Outros <?php echo htmlspecialchars($veiculo['marca']); ?> Disponíveis</h2>
            <div class="vitrine__grid vitrine__grid--3cols">
                <?php foreach ($relacionados as $rel): ?>
                    <article class="card__veiculo card__veiculo--pequeno">
                        <a href="veiculo.php?slug=<?php echo $rel['slug']; ?>">
                            <div class="card__imagem">
                                <?php if ($rel['foto_principal']): ?>
                                    <img src="<?php echo UPLOAD_DIR . htmlspecialchars($rel['foto_principal']); ?>"
                                         alt="<?php echo htmlspecialchars($rel['marca'] . ' ' . $rel['modelo']); ?>"
                                         loading="lazy">
                                <?php else: ?>
                                    <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:var(--color-bg-tertiary);font-size:2.5rem;">🚗</div>
                                <?php endif; ?>
                            </div>
                        </a>
                        <div class="card__body">
                            <h3 class="card__titulo">
                                <a href="veiculo.php?slug=<?php echo $rel['slug']; ?>">
                                    <?php echo htmlspecialchars($rel['marca'] . ' ' . $rel['modelo']); ?>
                                </a>
                            </h3>
                            <p class="card__ano"><?php echo $rel['ano']; ?> &middot; <?php echo formatarKM($rel['quilometragem']); ?> km</p>
                            <p class="card__preco preco__destaque"><?php echo formatarMoeda($rel['preco_venda']); ?></p>
                            <a href="veiculo.php?slug=<?php echo $rel['slug']; ?>" class="btn btn--small btn--secondary">Ver detalhes</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ===== FOOTER ===== -->
    <footer class="footer">
        <div class="container">
            <div class="footer__grid">
                <div class="footer__col">
                    <h3><?php echo LOJA_NOME; ?></h3>
                    <p><?php echo LOJA_ENDERECO; ?></p>
                    <p>Horário: <?php echo LOJA_HORARIO; ?></p>
                </div>
                <div class="footer__col">
                    <h4>Contato</h4>
                    <p><a href="tel:<?php echo LOJA_TELEFONE; ?>">📞 <?php echo formatarTelefone(LOJA_TELEFONE); ?></a></p>
                    <p><a href="mailto:<?php echo LOJA_EMAIL; ?>">✉️ <?php echo LOJA_EMAIL; ?></a></p>
                </div>
                <div class="footer__col">
                    <h4>Redes Sociais</h4>
                    <div class="footer__sociais">
                        <a href="<?php echo $redes_sociais['facebook']; ?>" target="_blank">Facebook</a>
                        <a href="<?php echo $redes_sociais['instagram']; ?>" target="_blank">Instagram</a>
                        <a href="<?php echo $redes_sociais['youtube']; ?>" target="_blank">YouTube</a>
                    </div>
                </div>
                <div class="footer__col">
                    <h4>Navegação</h4>
                    <div class="footer__sociais">
                        <a href="<?php echo BASE_URL; ?>">Home</a>
                        <a href="<?php echo BASE_URL; ?>estoque.php">Estoque</a>
                    </div>
                </div>
            </div>
            <div class="footer__bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo LOJA_NOME; ?> - Todos os direitos reservados</p>
                <p style="font-size:0.75rem;color:#888;margin-top:6px">
                    Site desenvolvido por <a href="https://rstechbr.com" target="_blank" rel="noopener"
                    style="color:#D4AF37;text-decoration:none;font-weight:600">RS TECH</a>
                </p>
            </div>
        </div>
    </footer>

    <a href="https://wa.me/55<?php echo preg_replace('/\D/', '', LOJA_WHATSAPP); ?>"
       class="whatsapp-flutuante" title="Fale conosco" target="_blank">💬</a>

    <!-- ===== LIGHTBOX ===== -->
    <?php if (!empty($fotos)): ?>
    <div class="galeria-modal" id="galeriaModal" role="dialog" aria-modal="true" aria-label="Galeria de fotos">
        <div class="galeria-modal__overlay" onclick="fecharModal()"></div>
        <div class="galeria-modal__conteudo">
            <button class="galeria-modal__fechar" onclick="fecharModal()" aria-label="Fechar">&times;</button>
            <button class="galeria-modal__nav" onclick="navegarModal(-1)" aria-label="Foto anterior">&#8249;</button>
            <img class="galeria-modal__imagem" id="modalImg" src="" alt="">
            <button class="galeria-modal__nav" onclick="navegarModal(1)" aria-label="Próxima foto">&#8250;</button>
            <span class="galeria-modal__contador" id="modalContador"></span>
        </div>
    </div>

    <script>
        const fotos = <?php echo json_encode(array_map(fn($f) => UPLOAD_DIR . $f['caminho'], $fotos)); ?>;
        let indiceAtivo = 0;

        function trocarFoto(indice) {
            indiceAtivo = indice;
            document.getElementById('fotoMain').src = fotos[indice];

            const contador = document.getElementById('galeriaContador');
            if (contador) contador.textContent = (indice + 1) + ' / ' + fotos.length;

            document.querySelectorAll('.galeria__thumb').forEach((t, i) => {
                t.classList.toggle('galeria__thumb--ativa', i === indice);
            });
        }

        function abrirModal(indice) {
            indiceAtivo = indice;
            atualizarModal();
            document.getElementById('galeriaModal').classList.add('ativo');
            document.body.style.overflow = 'hidden';
        }

        function fecharModal() {
            document.getElementById('galeriaModal').classList.remove('ativo');
            document.body.style.overflow = '';
        }

        function navegarModal(direcao) {
            indiceAtivo = (indiceAtivo + direcao + fotos.length) % fotos.length;
            atualizarModal();
        }

        function atualizarModal() {
            document.getElementById('modalImg').src = fotos[indiceAtivo];
            document.getElementById('modalContador').textContent = (indiceAtivo + 1) + ' / ' + fotos.length;
        }

        // Fechar com ESC e navegar com setas
        document.addEventListener('keydown', function (e) {
            const modal = document.getElementById('galeriaModal');
            if (!modal.classList.contains('ativo')) return;
            if (e.key === 'Escape')      fecharModal();
            if (e.key === 'ArrowLeft')  navegarModal(-1);
            if (e.key === 'ArrowRight') navegarModal(1);
        });
    </script>
    <?php endif; ?>

    <script src="<?php echo BASE_URL; ?>assets/js/theme.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/menu-mobile.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>

</body>
</html>
