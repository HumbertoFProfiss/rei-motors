<?php
/**
 * Rei Motors - Homepage
 * Site Público - Single Page
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Obter destaques (máximo 6 carros)
$destaques = obterTodas(
    "SELECT v.*, 
            GROUP_CONCAT(vf.caminho SEPARATOR ',') as fotos
     FROM veiculos v
     LEFT JOIN veiculos_fotos vf ON v.id = vf.veiculo_id AND vf.principal = 1
     WHERE v.destaque = 1 AND v.status = 'disponivel'
     GROUP BY v.id
     LIMIT 6"
);

// Obter últimos carros adicionados (para vitrine resumida)
$ultimos_carros = obterTodas(
    "SELECT v.*, 
            GROUP_CONCAT(vf.caminho SEPARATOR ',') as fotos
     FROM veiculos v
     LEFT JOIN veiculos_fotos vf ON v.id = vf.veiculo_id AND vf.principal = 1
     WHERE v.status = 'disponivel'
     GROUP BY v.id
     ORDER BY v.criado_em DESC
     LIMIT 3"
);

// Contar carros em estoque
$total_estoque = obterUmaLinha(
    "SELECT COUNT(*) as total FROM veiculos WHERE status = 'disponivel'"
);

// ===== FORMULÁRIO VENDA SEU CARRO =====
$venda_sucesso = false;
$venda_erro    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_venda'])) {
    $nome_v    = sanitizar($_POST['nome_venda']    ?? '');
    $telefone_v = sanitizar($_POST['telefone_venda'] ?? '');
    $email_v   = sanitizar($_POST['email_venda']   ?? '');
    $marca_v   = sanitizar($_POST['marca_venda']   ?? '');
    $modelo_v  = sanitizar($_POST['modelo_venda']  ?? '');
    $ano_v     = (int)($_POST['ano_venda']         ?? 0);
    $km_v      = sanitizar($_POST['km_venda']      ?? '');
    $valor_v   = sanitizar($_POST['valor_venda']   ?? '');
    $obs_v     = sanitizar($_POST['obs_venda']     ?? '');

    if (!$nome_v || !$telefone_v) {
        $venda_erro = 'Por favor, preencha nome e telefone.';
    } elseif ($email_v && !validarEmail($email_v)) {
        $venda_erro = 'E-mail inválido.';
    } else {
        $obs_completo = "Carro: $marca_v $modelo_v $ano_v | KM: $km_v | Valor pretendido: $valor_v";
        if ($obs_v) $obs_completo .= " | Obs: $obs_v";

        $lead_venda = [
            'veiculo_id'  => null,
            'nome'        => $nome_v,
            'email'       => $email_v ?: null,
            'telefone'    => $telefone_v,
            'origem'      => 'venda_seu_carro',
            'tipo_lead'   => 'novo',
            'observacoes' => $obs_completo,
            'ip_origem'   => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent'  => $_SERVER['HTTP_USER_AGENT'] ?? '',
        ];
        inserir('leads', $lead_venda);
        notificarNovoLead($lead_venda);
        $venda_sucesso = true;
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Rei Motors - Seminovos com qualidade e procedência em Botucatu e região. Atendimento REAL, financiamento facilitado e garantia assegurada.">
    <meta name="keywords" content="carros seminovos, veículos usados, Botucatu, Rei Motors, financiamento, comprar carro">
    
    <title><?php echo LOJA_NOME; ?> - Loja de Carros Online</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo UPLOAD_DIR; ?>favicon.ico">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/reset.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/variables.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/header.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/hero.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/diferenciais.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/destaques.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/vitrine.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/simulador.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/footer.css">
</head>
<body>

    <!-- ===== HEADER ===== -->
    <header class="header">
        <div class="container">
            <div class="header__logo">
                <img src="<?php echo UPLOAD_DIR; ?>logo.png" alt="<?php echo LOJA_NOME; ?>" class="logo">
                <span class="logo-text"><?php echo LOJA_NOME; ?></span>
            </div>

            <nav class="header__nav">
                <ul class="nav__menu">
                    <li><a href="#home" class="nav__link">Home</a></li>
                    <li><a href="<?php echo BASE_URL; ?>estoque.php" class="nav__link">Estoque</a></li>
                    <li><a href="#venda-seu-carro" class="nav__link">Venda seu Carro</a></li>
                    <li><a href="#financiamento" class="nav__link">Financiamento</a></li>
                    <li><a href="#contato" class="nav__link">Contato</a></li>
                </ul>
            </nav>

            <div class="header__actions">
                <button class="theme-toggle" id="themeToggle" aria-label="Alternar tema">
                    <span class="theme-toggle__icon">🌙</span>
                </button>
                <a href="<?php echo ADMIN_URL; ?>" class="btn-admin" title="Área Administrativa">🔒</a>
                <a href="https://wa.me/55<?php echo preg_replace('/\D/', '', LOJA_WHATSAPP); ?>" class="btn btn--primary" target="_blank">
                    WhatsApp
                </a>
            </div>

            <!-- Menu Mobile -->
            <button class="hamburger" id="hamburger" aria-label="Menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </header>

    <!-- ===== HERO SECTION ===== -->
    <section class="hero" id="home">
        <div class="hero__background">
            <div class="hero__overlay"></div>
        </div>
        
        <div class="hero__content">
            <div class="container">
                <div class="hero__text">
                    <h1 class="hero__title">Seu novo carro está aqui!</h1>
                    <p class="hero__subtitle">A melhor experiência em compra de seminovos em Botucatu e região — qualidade garantida, atendimento REAL.</p>
                    
                    <div class="hero__ctas">
                        <a href="<?php echo BASE_URL; ?>estoque.php" class="btn btn--large btn--primary">
                            Ver Estoque (<?php echo $total_estoque['total'] ?? 0; ?> carros)
                        </a>
                        <a href="#financiamento" class="btn btn--large btn--secondary">
                            Simular Financiamento
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== DIFERENCIAIS ===== -->
    <section class="diferenciais">
        <div class="container">
            <h2 class="section__title">Por Que Escolher a Rei Motors?</h2>

            <div class="diferenciais__grid">
                <div class="diferencial__card">
                    <div class="diferencial__icon">👑</div>
                    <h3 class="diferencial__title">Atendimento REAL</h3>
                    <p class="diferencial__text">Aqui você é tratado como realeza. Equipe dedicada a encontrar o carro perfeito para você</p>
                </div>

                <div class="diferencial__card">
                    <div class="diferencial__icon">📍</div>
                    <h3 class="diferencial__title">Localização Privilegiada</h3>
                    <p class="diferencial__text">Estacionamento exclusivo e fácil acesso em Botucatu. R. Maj. Matheus, 236 — Vila dos Lavradores</p>
                </div>

                <div class="diferencial__card">
                    <div class="diferencial__icon">✅</div>
                    <h3 class="diferencial__title">Qualidade Assegurada</h3>
                    <p class="diferencial__text">Todos os veículos inspecionados, com documentação em dia e histórico verificado</p>
                </div>

                <div class="diferencial__card">
                    <div class="diferencial__icon">💳</div>
                    <h3 class="diferencial__title">Financiamento Facilitado</h3>
                    <p class="diferencial__text">Simule agora e saia de carro no mesmo dia. Trabalhamos com os melhores bancos do mercado</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== DESTAQUES DA SEMANA ===== -->
    <section class="destaques">
        <div class="container">
            <h2 class="section__title">Destaques da Semana</h2>
            
            <?php if (!empty($destaques)): ?>
                <div class="vitrine__grid">
                    <?php foreach ($destaques as $carro): ?>
                        <article class="card__veiculo">
                            <div class="card__imagem">
                                <?php if ($carro['fotos']): ?>
                                    <img src="<?php echo UPLOAD_DIR . $carro['fotos']; ?>"
                                         alt="<?php echo htmlspecialchars($carro['marca'] . ' ' . $carro['modelo']); ?>"
                                         loading="lazy">
                                <?php else: ?>
                                    <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:var(--color-bg-tertiary);font-size:3rem;">🚗</div>
                                <?php endif; ?>
                                <div class="card__badge">Destaque</div>
                            </div>
                            
                            <div class="card__body">
                                <h3 class="card__titulo">
                                    <?php echo $carro['marca'] . ' ' . $carro['modelo']; ?>
                                </h3>
                                
                                <p class="card__ano"><?php echo $carro['ano']; ?></p>
                                
                                <div class="card__specs">
                                    <span class="spec">
                                        <strong><?php echo formatarKM($carro['quilometragem']); ?></strong> km
                                    </span>
                                    <span class="spec">
                                        <?php echo $combustiveis[$carro['combustivel']] ?? 'N/A'; ?>
                                    </span>
                                </div>
                                
                                <div class="card__preco">
                                    <p class="preco__destaque">
                                        <?php echo formatarMoeda($carro['preco_venda']); ?>
                                    </p>
                                </div>
                                
                                <div class="card__acoes">
                                    <a href="<?php echo BASE_URL; ?>veiculo.php?slug=<?php echo $carro['slug']; ?>" 
                                       class="btn btn--small btn--primary">
                                        Ver Detalhes
                                    </a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="mensagem-vazia">
                    <p>Nenhum destaque disponível no momento.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- ===== VITRINE RESUMIDA ===== -->
    <section class="vitrine">
        <div class="container">
            <h2 class="section__title">Últimas Adições</h2>
            
            <?php if (!empty($ultimos_carros)): ?>
                <div class="vitrine__grid vitrine__grid--3cols">
                    <?php foreach ($ultimos_carros as $carro): ?>
                        <article class="card__veiculo card__veiculo--pequeno">
                            <div class="card__imagem">
                                <img src="<?php echo UPLOAD_DIR; ?><?php echo $carro['fotos']; ?>" 
                                     alt="<?php echo $carro['marca'] . ' ' . $carro['modelo']; ?>"
                                     class="img-responsive">
                            </div>
                            
                            <div class="card__body">
                                <h3 class="card__titulo">
                                    <?php echo $carro['marca'] . ' ' . $carro['modelo']; ?>
                                </h3>
                                <p class="card__preco">
                                    <?php echo formatarMoeda($carro['preco_venda']); ?>
                                </p>
                                <a href="<?php echo BASE_URL; ?>veiculo.php?slug=<?php echo $carro['slug']; ?>" 
                                   class="btn btn--small btn--secondary">
                                    Saiba Mais
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <div class="vitrine__cta">
                <a href="<?php echo BASE_URL; ?>estoque.php" class="btn btn--large btn--primary">
                    Ver Todo o Estoque
                </a>
            </div>
        </div>
    </section>

    <!-- ===== SOBRE ===== -->
    <section class="sobre">
        <div class="container">
            <div class="sobre__content">
                <div class="sobre__texto">
                    <h2 class="section__title">Sobre a Rei Motors</h2>
                    <p>
                        A Rei Motors nasceu para ser diferente. Localizada em Botucatu — SP, somos especializados
                        em seminovos selecionados, oferecendo a melhor experiência de compra de veículos da região.
                    </p>
                    <p>
                        Acreditamos que comprar um carro deve ser uma experiência incrível. Por isso, tratamos cada
                        cliente como realeza: atendimento personalizado, total transparência e suporte do começo ao
                        fim. Cada veículo do nosso estoque é inspecionado e está pronto para rodar.
                    </p>
                    <p>
                        <strong>Fala Reis!</strong> Venha nos visitar ou entre em contato — temos o carro certo pra você.
                    </p>
                </div>

                <div class="sobre__valores">
                    <h3>Nossos Valores</h3>
                    <ul>
                        <li><strong>Transparência:</strong> Sem surpresas, sem pegadinhas</li>
                        <li><strong>Qualidade:</strong> Só seminovos aprovados e inspecionados</li>
                        <li><strong>Atendimento REAL:</strong> Você em primeiro lugar, sempre</li>
                        <li><strong>Agilidade:</strong> Saia de carro no mesmo dia</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== SIMULADOR DE FINANCIAMENTO ===== -->
    <section class="simulador" id="financiamento">
        <div class="container">
            <h2 class="section__title">Simulador de Financiamento</h2>
            <p class="simulador__aviso">* Valores sujeitos a análise de crédito</p>
            
            <form class="simulador__form" id="formSimulador">
                <div class="form__group">
                    <label for="valor">Valor do Carro</label>
                    <input type="number" id="valor" name="valor" placeholder="Ex: 50000" required>
                </div>

                <div class="form__group">
                    <label for="entrada">Entrada (%)</label>
                    <input type="number" id="entrada" name="entrada" min="0" max="100" value="20" required>
                </div>

                <div class="form__group">
                    <label for="prazo">Prazo (meses)</label>
                    <select id="prazo" name="prazo" required>
                        <option value="12">12 meses</option>
                        <option value="24" selected>24 meses</option>
                        <option value="36">36 meses</option>
                        <option value="48">48 meses</option>
                        <option value="60">60 meses</option>
                    </select>
                </div>

                <button type="submit" class="btn btn--primary btn--large">
                    Simular
                </button>
            </form>

            <div id="resultadoSimulador" class="simulador__resultado" style="display: none;">
                <div class="resultado__card">
                    <p class="resultado__item">
                        <strong>Valor da Entrada:</strong> 
                        <span id="resultadoEntrada" class="valor-destaque">R$ 0,00</span>
                    </p>
                    <p class="resultado__item">
                        <strong>Valor Financiado:</strong> 
                        <span id="resultadoFinanciado" class="valor-destaque">R$ 0,00</span>
                    </p>
                    <p class="resultado__item resultado__item--destaque">
                        <strong>Parcela Mensal:</strong> 
                        <span id="resultadoParcela" class="valor-destaque">R$ 0,00</span>
                    </p>
                </div>

                <div class="simulador__cta">
                    <a href="https://wa.me/55<?php echo preg_replace('/\D/', '', LOJA_WHATSAPP); ?>" class="btn btn--primary btn--large" target="_blank">
                        Quero Mais Informações
                    </a>
                </div>

                <div id="simCaptura" style="display:none;margin-top:1.5rem;text-align:center">
                    <p style="margin-bottom:0.75rem;color:var(--color-text-secondary);font-size:0.9rem">Quer receber a simulação detalhada? Deixe seu contato:</p>
                    <form id="formSimCaptura" style="display:flex;gap:0.5rem;justify-content:center;flex-wrap:wrap">
                        <input type="text" name="sim_nome" placeholder="Seu nome" required
                               style="padding:0.6rem 1rem;border-radius:8px;border:1px solid var(--color-border);background:var(--color-bg-tertiary);color:var(--color-text-primary);flex:1;min-width:140px">
                        <input type="tel" name="sim_telefone" placeholder="WhatsApp" required
                               style="padding:0.6rem 1rem;border-radius:8px;border:1px solid var(--color-border);background:var(--color-bg-tertiary);color:var(--color-text-primary);flex:1;min-width:140px">
                        <button type="submit" class="btn btn--primary">Enviar</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== VENDA SEU CARRO ===== -->
    <section class="venda-carro" id="venda-seu-carro">
        <div class="container">
            <h2 class="section__title">Venda seu Carro</h2>
            <p class="section__subtitle">Avaliamos seu veículo de forma rápida e transparente. Preencha os dados e entraremos em contato.</p>

            <div class="venda-carro__box">
                <?php if ($venda_sucesso): ?>
                    <div class="venda-carro__sucesso">
                        <span style="font-size:2.5rem">✅</span>
                        <h3>Proposta recebida!</h3>
                        <p>Recebemos as informações do seu veículo e entraremos em contato em breve. Ou fale agora pelo WhatsApp!</p>
                        <a href="https://wa.me/55<?php echo preg_replace('/\D/', '', LOJA_WHATSAPP); ?>" class="btn btn--whatsapp" target="_blank">💬 Falar no WhatsApp</a>
                    </div>
                <?php else: ?>
                    <?php if ($venda_erro): ?>
                        <div class="alert alert--error" style="margin-bottom:1.5rem"><?php echo htmlspecialchars($venda_erro); ?></div>
                    <?php endif; ?>
                    <form method="POST" action="#venda-seu-carro">
                        <input type="hidden" name="form_venda" value="1">
                        <div class="venda-carro__grid">
                            <div class="form__grupo">
                                <label>Nome *</label>
                                <input type="text" name="nome_venda" required placeholder="Seu nome"
                                       value="<?php echo htmlspecialchars($_POST['nome_venda'] ?? ''); ?>">
                            </div>
                            <div class="form__grupo">
                                <label>Telefone / WhatsApp *</label>
                                <input type="tel" name="telefone_venda" required placeholder="(11) 99999-9999"
                                       value="<?php echo htmlspecialchars($_POST['telefone_venda'] ?? ''); ?>">
                            </div>
                            <div class="form__grupo">
                                <label>E-mail</label>
                                <input type="email" name="email_venda" placeholder="seu@email.com"
                                       value="<?php echo htmlspecialchars($_POST['email_venda'] ?? ''); ?>">
                            </div>
                            <div class="form__grupo">
                                <label>Marca do Veículo</label>
                                <input type="text" name="marca_venda" placeholder="Ex: Toyota"
                                       value="<?php echo htmlspecialchars($_POST['marca_venda'] ?? ''); ?>">
                            </div>
                            <div class="form__grupo">
                                <label>Modelo</label>
                                <input type="text" name="modelo_venda" placeholder="Ex: Corolla"
                                       value="<?php echo htmlspecialchars($_POST['modelo_venda'] ?? ''); ?>">
                            </div>
                            <div class="form__grupo">
                                <label>Ano</label>
                                <input type="number" name="ano_venda" placeholder="2020" min="1950" max="<?php echo (int)date('Y') + 1; ?>"
                                       value="<?php echo htmlspecialchars($_POST['ano_venda'] ?? ''); ?>">
                            </div>
                            <div class="form__grupo">
                                <label>Quilometragem</label>
                                <input type="text" name="km_venda" placeholder="Ex: 50.000 km"
                                       value="<?php echo htmlspecialchars($_POST['km_venda'] ?? ''); ?>">
                            </div>
                            <div class="form__grupo">
                                <label>Valor Pretendido (R$)</label>
                                <input type="text" name="valor_venda" placeholder="Ex: R$ 45.000"
                                       value="<?php echo htmlspecialchars($_POST['valor_venda'] ?? ''); ?>">
                            </div>
                            <div class="form__grupo form__grupo--full">
                                <label>Observações</label>
                                <textarea name="obs_venda" rows="3" placeholder="Estado do carro, opcionais, histórico..."><?php echo htmlspecialchars($_POST['obs_venda'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        <div style="text-align:center;margin-top:1.5rem">
                            <button type="submit" class="btn btn--primary btn--large">Enviar Proposta</button>
                        </div>
                        <p style="text-align:center;margin-top:0.75rem;font-size:0.8rem;color:var(--color-text-secondary)">* Campos obrigatórios. Seus dados são usados apenas para contato.</p>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- ===== LOCALIZAÇÃO ===== -->
    <section class="localizacao" id="contato">
        <div class="container">
            <h2 class="section__title">Onde Estamos</h2>
            <p class="section__subtitle"><?php echo htmlspecialchars(LOJA_ENDERECO); ?></p>

            <div class="localizacao__grid">
                <div class="localizacao__mapa">
                    <iframe
                        src="https://maps.google.com/maps?q=<?php echo urlencode('Rua Major Matheus 236, Vila dos Lavradores, Botucatu, SP'); ?>&output=embed&z=16"
                        width="100%" height="400" style="border:0;border-radius:12px"
                        allowfullscreen loading="lazy"
                        title="Localização <?php echo htmlspecialchars(LOJA_NOME); ?>">
                    </iframe>
                </div>
                <div class="localizacao__info">
                    <div class="localizacao__item">
                        <span class="localizacao__icone">📍</span>
                        <div>
                            <strong>Endereço</strong>
                            <p><?php echo htmlspecialchars(LOJA_ENDERECO); ?></p>
                        </div>
                    </div>
                    <div class="localizacao__item">
                        <span class="localizacao__icone">📞</span>
                        <div>
                            <strong>Telefone</strong>
                            <p><a href="tel:<?php echo LOJA_TELEFONE; ?>"><?php echo formatarTelefone(LOJA_TELEFONE); ?></a></p>
                        </div>
                    </div>
                    <div class="localizacao__item">
                        <span class="localizacao__icone">💬</span>
                        <div>
                            <strong>WhatsApp</strong>
                            <p><a href="https://wa.me/55<?php echo preg_replace('/\D/', '', LOJA_WHATSAPP); ?>" target="_blank"><?php echo formatarTelefone(LOJA_WHATSAPP); ?></a></p>
                        </div>
                    </div>
                    <div class="localizacao__item">
                        <span class="localizacao__icone">🕐</span>
                        <div>
                            <strong>Horário</strong>
                            <p><?php echo htmlspecialchars(LOJA_HORARIO); ?></p>
                        </div>
                    </div>
                    <div class="localizacao__item">
                        <span class="localizacao__icone">✉️</span>
                        <div>
                            <strong>E-mail</strong>
                            <p><a href="mailto:<?php echo LOJA_EMAIL; ?>"><?php echo LOJA_EMAIL; ?></a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== FOOTER ===== -->
    <footer class="footer">
        <div class="container">
            <div class="footer__grid">
                <div class="footer__col">
                    <h3><?php echo LOJA_NOME; ?></h3>
                    <p><?php echo LOJA_ENDERECO; ?></p>
                </div>

                <div class="footer__col">
                    <h4>Contato</h4>
                    <p>
                        <a href="tel:<?php echo LOJA_TELEFONE; ?>">
                            📞 <?php echo formatarTelefone(LOJA_TELEFONE); ?>
                        </a>
                    </p>
                    <p>
                        <a href="mailto:<?php echo LOJA_EMAIL; ?>">
                            ✉️ <?php echo LOJA_EMAIL; ?>
                        </a>
                    </p>
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
                    <h4>Horário</h4>
                    <p><?php echo LOJA_HORARIO; ?></p>
                </div>
            </div>

            <div class="footer__bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo LOJA_NOME; ?> - Todos os direitos reservados</p>
                <a href="<?php echo ADMIN_URL; ?>" class="footer__admin-link" title="Área Administrativa">🔒 Admin</a>
            </div>
        </div>
    </footer>

    <!-- ===== WHATSAPP FLUTUANTE ===== -->
    <a href="https://wa.me/55<?php echo preg_replace('/\D/', '', LOJA_WHATSAPP); ?>" 
       class="whatsapp-flutuante" 
       title="Fale conosco via WhatsApp"
       target="_blank">
        💬
    </a>

    <!-- JavaScript -->
    <script src="<?php echo BASE_URL; ?>assets/js/theme.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/menu-mobile.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/simulador.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>

</body>
</html>
