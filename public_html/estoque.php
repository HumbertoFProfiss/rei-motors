<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// ===== FILTROS =====

$filtros = [
    'marca'      => sanitizar($_GET['marca']      ?? ''),
    'modelo'     => sanitizar($_GET['modelo']     ?? ''),
    'ano_min'    => intval($_GET['ano_min']        ?? 0),
    'ano_max'    => intval($_GET['ano_max']        ?? 0),
    'preco_min'  => floatval($_GET['preco_min']   ?? 0),
    'preco_max'  => floatval($_GET['preco_max']   ?? 0),
    'combustivel'=> sanitizar($_GET['combustivel'] ?? ''),
    'cambio'     => sanitizar($_GET['cambio']     ?? ''),
    'km_max'     => intval($_GET['km_max']         ?? 0),
    'ordem'      => sanitizar($_GET['ordem']      ?? 'recentes'),
];

$pagina = max(1, intval($_GET['pagina'] ?? 1));
$offset = ($pagina - 1) * ITEMS_POR_PAGINA;

// Whitelist de ordenação para evitar SQL injection
$ordens_validas = [
    'recentes'   => 'v.criado_em DESC',
    'preco_asc'  => 'v.preco_venda ASC',
    'preco_desc' => 'v.preco_venda DESC',
    'km_asc'     => 'v.quilometragem ASC',
    'ano_desc'   => 'v.ano DESC',
];
$ordem_sql = $ordens_validas[$filtros['ordem']] ?? $ordens_validas['recentes'];

// ===== QUERY DINÂMICA =====

$where  = ['v.status = ?'];
$params = ['disponivel'];

if ($filtros['marca']) {
    $where[]  = 'v.marca = ?';
    $params[] = $filtros['marca'];
}
if ($filtros['modelo']) {
    $where[]  = 'v.modelo LIKE ?';
    $params[] = '%' . $filtros['modelo'] . '%';
}
if ($filtros['ano_min'] > 0) {
    $where[]  = 'v.ano >= ?';
    $params[] = $filtros['ano_min'];
}
if ($filtros['ano_max'] > 0) {
    $where[]  = 'v.ano <= ?';
    $params[] = $filtros['ano_max'];
}
if ($filtros['preco_min'] > 0) {
    $where[]  = 'v.preco_venda >= ?';
    $params[] = $filtros['preco_min'];
}
if ($filtros['preco_max'] > 0) {
    $where[]  = 'v.preco_venda <= ?';
    $params[] = $filtros['preco_max'];
}
if ($filtros['combustivel'] && array_key_exists($filtros['combustivel'], $combustiveis)) {
    $where[]  = 'v.combustivel = ?';
    $params[] = $filtros['combustivel'];
}
if ($filtros['cambio'] && array_key_exists($filtros['cambio'], $cambios)) {
    $where[]  = 'v.cambio = ?';
    $params[] = $filtros['cambio'];
}
if ($filtros['km_max'] > 0) {
    $where[]  = 'v.quilometragem <= ?';
    $params[] = $filtros['km_max'];
}

$where_sql = implode(' AND ', $where);

// Contar total para paginação
$total_row = obterUmaLinha(
    "SELECT COUNT(*) as total FROM veiculos v WHERE $where_sql",
    $params
);
$total        = intval($total_row['total'] ?? 0);
$total_paginas = max(1, (int) ceil($total / ITEMS_POR_PAGINA));
$pagina        = min($pagina, $total_paginas);

// Buscar veículos
$veiculos = obterTodas(
    "SELECT v.*,
            (SELECT vf.caminho FROM veiculos_fotos vf
             WHERE vf.veiculo_id = v.id AND vf.principal = 1
             LIMIT 1) as foto_principal
     FROM veiculos v
     WHERE $where_sql
     ORDER BY $ordem_sql
     LIMIT ? OFFSET ?",
    array_merge($params, [ITEMS_POR_PAGINA, $offset])
);

// Marcas disponíveis para o select do filtro
$marcas_db = obterTodas(
    "SELECT DISTINCT marca FROM veiculos WHERE status = 'disponivel' ORDER BY marca"
);

// URL base para paginação (sem parâmetro 'pagina')
$query_base = http_build_query(array_filter([
    'marca'       => $filtros['marca'],
    'modelo'      => $filtros['modelo'],
    'ano_min'     => $filtros['ano_min']   ?: '',
    'ano_max'     => $filtros['ano_max']   ?: '',
    'preco_min'   => $filtros['preco_min'] ?: '',
    'preco_max'   => $filtros['preco_max'] ?: '',
    'combustivel' => $filtros['combustivel'],
    'cambio'      => $filtros['cambio'],
    'km_max'      => $filtros['km_max']    ?: '',
    'ordem'       => $filtros['ordem'] !== 'recentes' ? $filtros['ordem'] : '',
]));

function url_pagina($num, $query_base) {
    $sep = $query_base ? '&' : '';
    return 'estoque.php?' . $query_base . $sep . 'pagina=' . $num;
}

// Verificar se algum filtro está ativo
$tem_filtros = $filtros['marca'] || $filtros['modelo'] || $filtros['ano_min'] ||
               $filtros['ano_max'] || $filtros['preco_min'] || $filtros['preco_max'] ||
               $filtros['combustivel'] || $filtros['cambio'] || $filtros['km_max'];

// Título dinâmico da página
$titulo_pagina = 'Estoque';
if ($filtros['marca']) $titulo_pagina = 'Carros ' . $filtros['marca'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Rei Motors Botucatu — <?php echo $total; ?> seminovos disponíveis. Carros inspecionados, com procedência e financiamento facilitado. Venha conferir!">
    <link rel="icon" type="image/png" href="<?php echo UPLOAD_DIR; ?>favicon.png">

    <title><?php echo $titulo_pagina; ?> - <?php echo LOJA_NOME; ?></title>

    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/reset.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/variables.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/header.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/estoque.css">
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
                <a href="<?php echo ADMIN_URL; ?>" class="btn-admin" title="Área Administrativa">🔒</a>
                <a href="https://wa.me/55<?php echo preg_replace('/\D/', '', LOJA_WHATSAPP); ?>" class="btn btn--primary" target="_blank">
                    WhatsApp
                </a>
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
                <li class="breadcrumb__item">Estoque</li>
                <?php if ($filtros['marca']): ?>
                    <li class="breadcrumb__item"><?php echo htmlspecialchars($filtros['marca']); ?></li>
                <?php endif; ?>
            </ol>
        </div>
    </div>

    <!-- ===== FILTROS ===== -->
    <section class="filtros">
        <div class="container">
            <div class="filtros__header">
                <h2 class="filtros__titulo">Filtrar Veículos</h2>
                <button class="filtros__toggle" id="filtrosToggle">
                    <span>&#9776;</span> Filtros
                    <?php if ($tem_filtros): ?>
                        <span class="badge" style="font-size:0.65rem;padding:2px 8px;">Ativos</span>
                    <?php endif; ?>
                </button>
            </div>

            <form class="filtros__form" id="filtrosForm" method="GET" action="estoque.php">
                <div class="filtros__grid">

                    <!-- Marca -->
                    <div class="filtro__grupo">
                        <label for="marca">Marca</label>
                        <select name="marca" id="marca">
                            <option value="">Todas</option>
                            <?php foreach ($marcas_db as $m): ?>
                                <option value="<?php echo htmlspecialchars($m['marca']); ?>"
                                    <?php echo $filtros['marca'] === $m['marca'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($m['marca']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Modelo -->
                    <div class="filtro__grupo">
                        <label for="modelo">Modelo</label>
                        <input type="text" name="modelo" id="modelo"
                               placeholder="Ex: Civic"
                               value="<?php echo htmlspecialchars($filtros['modelo']); ?>">
                    </div>

                    <!-- Ano -->
                    <div class="filtro__grupo">
                        <label for="ano_min">Ano mínimo</label>
                        <input type="number" name="ano_min" id="ano_min"
                               placeholder="2015"
                               min="1990" max="<?php echo date('Y') + 1; ?>"
                               value="<?php echo $filtros['ano_min'] ?: ''; ?>">
                    </div>

                    <div class="filtro__grupo">
                        <label for="ano_max">Ano máximo</label>
                        <input type="number" name="ano_max" id="ano_max"
                               placeholder="<?php echo date('Y'); ?>"
                               min="1990" max="<?php echo date('Y') + 1; ?>"
                               value="<?php echo $filtros['ano_max'] ?: ''; ?>">
                    </div>

                    <!-- Preço -->
                    <div class="filtro__grupo">
                        <label for="preco_min">Preço mínimo</label>
                        <input type="number" name="preco_min" id="preco_min"
                               placeholder="R$ 0"
                               min="0" step="1000"
                               value="<?php echo $filtros['preco_min'] ?: ''; ?>">
                    </div>

                    <div class="filtro__grupo">
                        <label for="preco_max">Preço máximo</label>
                        <input type="number" name="preco_max" id="preco_max"
                               placeholder="R$ 300.000"
                               min="0" step="1000"
                               value="<?php echo $filtros['preco_max'] ?: ''; ?>">
                    </div>

                    <!-- Combustível -->
                    <div class="filtro__grupo">
                        <label for="combustivel">Combustível</label>
                        <select name="combustivel" id="combustivel">
                            <option value="">Todos</option>
                            <?php foreach ($combustiveis as $key => $label): ?>
                                <option value="<?php echo $key; ?>"
                                    <?php echo $filtros['combustivel'] === $key ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Câmbio -->
                    <div class="filtro__grupo">
                        <label for="cambio">Câmbio</label>
                        <select name="cambio" id="cambio">
                            <option value="">Todos</option>
                            <?php foreach ($cambios as $key => $label): ?>
                                <option value="<?php echo $key; ?>"
                                    <?php echo $filtros['cambio'] === $key ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- KM máximo -->
                    <div class="filtro__grupo">
                        <label for="km_max">KM máximo</label>
                        <input type="number" name="km_max" id="km_max"
                               placeholder="Ex: 100000"
                               min="0" step="5000"
                               value="<?php echo $filtros['km_max'] ?: ''; ?>">
                    </div>

                    <!-- Ações -->
                    <div class="filtros__acoes">
                        <button type="submit" class="btn btn--primary">Buscar</button>
                        <?php if ($tem_filtros): ?>
                            <a href="estoque.php" class="btn btn--ghost">Limpar</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <!-- ===== RESULTADOS ===== -->
    <section class="estoque">
        <div class="container">

            <!-- Header com contagem e ordenação -->
            <div class="estoque__header">
                <p class="estoque__info">
                    <strong><?php echo $total; ?></strong>
                    <?php echo $total === 1 ? 'veículo encontrado' : 'veículos encontrados'; ?>
                    <?php if ($tem_filtros): ?>
                        &nbsp;&mdash;&nbsp;<a href="estoque.php" style="font-size:0.8rem;">Ver todos</a>
                    <?php endif; ?>
                </p>

                <div class="estoque__ordenar">
                    <label for="ordem">Ordenar:</label>
                    <select id="ordem" name="ordem" onchange="aplicarOrdem(this.value)">
                        <option value="recentes"   <?php echo $filtros['ordem'] === 'recentes'   ? 'selected' : ''; ?>>Mais recentes</option>
                        <option value="preco_asc"  <?php echo $filtros['ordem'] === 'preco_asc'  ? 'selected' : ''; ?>>Menor preço</option>
                        <option value="preco_desc" <?php echo $filtros['ordem'] === 'preco_desc' ? 'selected' : ''; ?>>Maior preço</option>
                        <option value="km_asc"     <?php echo $filtros['ordem'] === 'km_asc'     ? 'selected' : ''; ?>>Menor quilometragem</option>
                        <option value="ano_desc"   <?php echo $filtros['ordem'] === 'ano_desc'   ? 'selected' : ''; ?>>Mais novo</option>
                    </select>
                </div>
            </div>

            <!-- Cards de veículos -->
            <?php if (!empty($veiculos)): ?>
                <div class="vitrine__grid">
                    <?php foreach ($veiculos as $carro): ?>
                        <article class="card__veiculo">
                            <a href="veiculo.php?slug=<?php echo $carro['slug']; ?>" class="card__link">
                                <div class="card__imagem">
                                    <?php if ($carro['foto_principal']): ?>
                                        <img src="<?php echo UPLOAD_DIR . $carro['foto_principal']; ?>"
                                             alt="<?php echo htmlspecialchars($carro['marca'] . ' ' . $carro['modelo']); ?>"
                                             loading="lazy">
                                    <?php else: ?>
                                        <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:var(--color-bg-tertiary);color:var(--color-text-tertiary);font-size:3rem;">🚗</div>
                                    <?php endif; ?>

                                    <?php if ($carro['destaque']): ?>
                                        <div class="card__badge">Destaque</div>
                                    <?php endif; ?>

                                    <?php if ($carro['status'] === 'reservado'): ?>
                                        <div class="card__badge" style="background:var(--color-warning);">Reservado</div>
                                    <?php endif; ?>
                                </div>
                            </a>

                            <div class="card__body">
                                <h3 class="card__titulo">
                                    <a href="veiculo.php?slug=<?php echo $carro['slug']; ?>">
                                        <?php echo htmlspecialchars($carro['marca'] . ' ' . $carro['modelo']); ?>
                                    </a>
                                </h3>

                                <p class="card__ano"><?php echo $carro['ano']; ?></p>

                                <div class="card__specs">
                                    <span class="spec">
                                        <strong><?php echo formatarKM($carro['quilometragem']); ?></strong> km
                                    </span>
                                    <span class="spec">
                                        <?php echo $combustiveis[$carro['combustivel']] ?? $carro['combustivel']; ?>
                                    </span>
                                    <span class="spec">
                                        <?php echo $cambios[$carro['cambio']] ?? $carro['cambio']; ?>
                                    </span>
                                </div>

                                <div class="card__preco">
                                    <p class="preco__destaque">
                                        <?php echo formatarMoeda($carro['preco_venda']); ?>
                                    </p>
                                </div>

                                <div class="card__acoes">
                                    <a href="veiculo.php?slug=<?php echo $carro['slug']; ?>"
                                       class="btn btn--small btn--primary">
                                        Ver Detalhes
                                    </a>
                                    <a href="https://wa.me/55<?php echo preg_replace('/\D/', '', LOJA_WHATSAPP); ?>?text=<?php echo urlencode('Olá! Tenho interesse no ' . $carro['marca'] . ' ' . $carro['modelo'] . ' ' . $carro['ano'] . '.'); ?>"
                                       class="btn btn--small btn--secondary" target="_blank">
                                        WhatsApp
                                    </a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <!-- Paginação -->
                <?php if ($total_paginas > 1): ?>
                    <nav class="paginacao" aria-label="Paginação">
                        <ul class="paginacao__lista">

                            <!-- Anterior -->
                            <li class="paginacao__item">
                                <?php if ($pagina > 1): ?>
                                    <a href="<?php echo url_pagina($pagina - 1, $query_base); ?>"
                                       class="paginacao__link" aria-label="Página anterior">&laquo;</a>
                                <?php else: ?>
                                    <span class="paginacao__link paginacao__link--desativado">&laquo;</span>
                                <?php endif; ?>
                            </li>

                            <!-- Páginas -->
                            <?php
                            $inicio = max(1, $pagina - 2);
                            $fim    = min($total_paginas, $pagina + 2);
                            if ($inicio > 1): ?>
                                <li class="paginacao__item">
                                    <a href="<?php echo url_pagina(1, $query_base); ?>" class="paginacao__link">1</a>
                                </li>
                                <?php if ($inicio > 2): ?>
                                    <li class="paginacao__item"><span class="paginacao__link paginacao__link--desativado">…</span></li>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php for ($i = $inicio; $i <= $fim; $i++): ?>
                                <li class="paginacao__item">
                                    <a href="<?php echo url_pagina($i, $query_base); ?>"
                                       class="paginacao__link <?php echo $i === $pagina ? 'paginacao__link--ativo' : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($fim < $total_paginas): ?>
                                <?php if ($fim < $total_paginas - 1): ?>
                                    <li class="paginacao__item"><span class="paginacao__link paginacao__link--desativado">…</span></li>
                                <?php endif; ?>
                                <li class="paginacao__item">
                                    <a href="<?php echo url_pagina($total_paginas, $query_base); ?>"
                                       class="paginacao__link"><?php echo $total_paginas; ?></a>
                                </li>
                            <?php endif; ?>

                            <!-- Próxima -->
                            <li class="paginacao__item">
                                <?php if ($pagina < $total_paginas): ?>
                                    <a href="<?php echo url_pagina($pagina + 1, $query_base); ?>"
                                       class="paginacao__link" aria-label="Próxima página">&raquo;</a>
                                <?php else: ?>
                                    <span class="paginacao__link paginacao__link--desativado">&raquo;</span>
                                <?php endif; ?>
                            </li>

                        </ul>
                    </nav>
                <?php endif; ?>

            <?php else: ?>
                <div class="sem-resultados">
                    <span class="sem-resultados__icone">🔍</span>
                    <h3>Nenhum veículo encontrado</h3>
                    <p>Tente remover alguns filtros ou entre em contato — podemos buscar o carro ideal para você.</p>
                    <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
                        <a href="estoque.php" class="btn btn--secondary">Ver Todo Estoque</a>
                        <a href="https://wa.me/55<?php echo preg_replace('/\D/', '', LOJA_WHATSAPP); ?>" class="btn btn--primary" target="_blank">Falar no WhatsApp</a>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </section>

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
            </div>
        </div>
    </footer>

    <a href="https://wa.me/55<?php echo preg_replace('/\D/', '', LOJA_WHATSAPP); ?>"
       class="whatsapp-flutuante" title="Fale conosco" target="_blank">💬</a>

    <script src="<?php echo BASE_URL; ?>assets/js/theme.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/menu-mobile.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
    <script>
        // Filtros mobile toggle
        document.getElementById('filtrosToggle').addEventListener('click', function () {
            document.getElementById('filtrosForm').classList.toggle('aberto');
        });

        // Ordenação sem submit manual
        function aplicarOrdem(valor) {
            const url = new URL(window.location.href);
            url.searchParams.set('ordem', valor);
            url.searchParams.delete('pagina');
            window.location.href = url.toString();
        }
    </script>

</body>
</html>
