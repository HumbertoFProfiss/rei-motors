<?php
/**
 * Rei Motors - Comparador de Veículos
 * Compare até 3 carros lado a lado
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Obter IDs dos carros a comparar da URL
$ids_selecionados = [];
if (!empty($_GET['ids'])) {
    $ids_selecionados = array_slice(
        array_map('intval', array_filter((array)$_GET['ids'])),
        0, 3
    );
}

// Buscar veículos selecionados
$veiculos_comparar = [];
if (!empty($ids_selecionados)) {
    $placeholders = implode(',', array_fill(0, count($ids_selecionados), '?'));
    $veiculos_comparar = obterTodas(
        "SELECT v.*, GROUP_CONCAT(vf.caminho SEPARATOR ',') as fotos
         FROM veiculos v
         LEFT JOIN veiculos_fotos vf ON v.id = vf.veiculo_id AND vf.principal = 1
         WHERE v.id IN ({$placeholders}) AND v.status = 'disponivel'
         GROUP BY v.id",
        $ids_selecionados
    );
}

// Buscar todos os carros disponíveis (para seleção)
$todos_veiculos = obterTodas(
    "SELECT id, marca, modelo, ano, preco_venda FROM veiculos 
     WHERE status = 'disponivel' 
     ORDER BY marca, modelo
     LIMIT 50"
);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Compare seminovos da Rei Motors lado a lado e escolha o carro ideal. Botucatu - SP.">
    
    <title>Comparador de Veículos - <?php echo LOJA_NOME; ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/variables.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/reset.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/header.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/footer.css">
    
    <style>
        .page-header {
            background: linear-gradient(135deg, #1a1a1a 0%, #0a0a0a 100%);
            padding: 100px 0 60px;
            margin-top: 80px;
            border-bottom: 1px solid var(--color-border);
        }

        .page-header h1 {
            font-size: var(--font-size-4xl);
            color: var(--color-accent-primary);
            margin-bottom: var(--spacing-sm);
        }

        .page-header p {
            color: var(--color-text-secondary);
            font-size: var(--font-size-lg);
        }

        /* ===== SELETOR ===== */
        .seletor__container {
            background-color: var(--color-bg-secondary);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-lg);
            padding: var(--spacing-2xl);
            margin-bottom: var(--spacing-3xl);
        }

        .seletor__title {
            font-size: var(--font-size-lg);
            font-weight: var(--font-weight-bold);
            margin-bottom: var(--spacing-lg);
            color: var(--color-accent-primary);
        }

        .seletor__info {
            color: var(--color-text-secondary);
            font-size: var(--font-size-sm);
            margin-bottom: var(--spacing-lg);
        }

        .seletor__grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: var(--spacing-lg);
            margin-bottom: var(--spacing-lg);
            max-height: 400px;
            overflow-y: auto;
            padding: var(--spacing-lg);
            background-color: var(--color-bg-primary);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-md);
        }

        .seletor__item {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
            padding: var(--spacing-md);
            background-color: var(--color-bg-secondary);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: all var(--transition-fast);
        }

        .seletor__item:hover {
            border-color: var(--color-accent-primary);
            background-color: rgba(212, 175, 55, 0.1);
        }

        .seletor__item input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .seletor__item.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .seletor__item.disabled input[type="checkbox"] {
            cursor: not-allowed;
        }

        .seletor__actions {
            display: flex;
            gap: var(--spacing-md);
        }

        .seletor__actions .btn {
            flex: 1;
        }

        /* ===== COMPARADOR ===== */
        .comparador__container {
            overflow-x: auto;
            margin-bottom: var(--spacing-3xl);
        }

        .comparador__table {
            width: 100%;
            border-collapse: collapse;
            background-color: var(--color-bg-secondary);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-lg);
            overflow: hidden;
        }

        .comparador__table thead {
            background-color: var(--color-bg-primary);
            border-bottom: 2px solid var(--color-border);
        }

        .comparador__table th {
            padding: var(--spacing-lg);
            text-align: center;
            font-weight: var(--font-weight-bold);
            color: var(--color-accent-primary);
            border-right: 1px solid var(--color-border);
        }

        .comparador__table th:first-child {
            text-align: left;
            min-width: 200px;
        }

        .comparador__table td {
            padding: var(--spacing-lg);
            text-align: center;
            border: 1px solid var(--color-border);
            vertical-align: middle;
        }

        .comparador__table td:first-child {
            text-align: left;
            font-weight: var(--font-weight-semibold);
            background-color: var(--color-bg-primary);
        }

        .comparador__table tbody tr:hover {
            background-color: rgba(212, 175, 55, 0.05);
        }

        /* ===== FOTO VEICULO ===== */
        .comparador__foto {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-md);
        }

        .comparador__nome {
            font-size: var(--font-size-lg);
            font-weight: var(--font-weight-bold);
            color: var(--color-text-primary);
            margin-bottom: var(--spacing-sm);
        }

        /* ===== AÇÕES ===== */
        .comparador__acoes {
            display: flex;
            gap: var(--spacing-sm);
            flex-direction: column;
        }

        .comparador__acoes .btn {
            font-size: var(--font-size-sm);
            padding: var(--spacing-sm) var(--spacing-md);
        }

        /* ===== PREÇO DESTACADO ===== */
        .preco__comparador {
            font-size: var(--font-size-xl);
            font-weight: var(--font-weight-bold);
            color: var(--color-accent-primary);
        }

        .diferenca__preco {
            font-size: var(--font-size-sm);
            color: var(--color-text-secondary);
            margin-top: var(--spacing-sm);
        }

        /* ===== MENSAGEM VAZIA ===== */
        .vazio__message {
            text-align: center;
            padding: var(--spacing-4xl);
            background-color: var(--color-bg-secondary);
            border: 1px dashed var(--color-border);
            border-radius: var(--radius-lg);
            color: var(--color-text-secondary);
        }

        .vazio__message h3 {
            font-size: var(--font-size-xl);
            margin-bottom: var(--spacing-md);
        }

        @media (max-width: 1024px) {
            .comparador__table {
                font-size: var(--font-size-sm);
            }

            .comparador__table th,
            .comparador__table td {
                padding: var(--spacing-md);
            }
        }

        @media (max-width: 768px) {
            .page-header h1 {
                font-size: var(--font-size-3xl);
            }

            .seletor__grid {
                grid-template-columns: 1fr;
                max-height: none;
            }

            .comparador__container {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .comparador__table th,
            .comparador__table td {
                padding: var(--spacing-sm);
                font-size: var(--font-size-xs);
            }

            .comparador__foto {
                height: 120px;
            }

            .comparador__nome {
                font-size: var(--font-size-base);
            }

            .preco__comparador {
                font-size: var(--font-size-base);
            }
        }
    </style>
</head>
<body>

<!-- ===== HEADER ===== -->
<?php require_once __DIR__ . '/includes/header.php'; ?>

<!-- ===== PAGE HEADER ===== -->
<section class="page-header">
    <div class="container">
        <h1>Comparar Veículos</h1>
        <p>Compare até 3 carros e encontre o ideal para você</p>
    </div>
</section>

<!-- ===== CONTEÚDO ===== -->
<section class="container" style="margin: var(--spacing-3xl) 0;">

    <!-- ===== SELETOR ===== -->
    <form method="GET" class="seletor__container">
        <h3 class="seletor__title">🚗 Selecione os Veículos</h3>
        <p class="seletor__info">Selecione até 3 carros para comparar (<?php echo count($veiculos_comparar); ?>/3 selecionados)</p>
        
        <div class="seletor__grid">
            <?php foreach ($todos_veiculos as $v): ?>
                <?php 
                $esta_selecionado = in_array($v['id'], $ids_selecionados);
                $pode_selecionar = $esta_selecionado || count($ids_selecionados) < 3;
                $classe_disabled = !$pode_selecionar ? 'disabled' : '';
                ?>
                <label class="seletor__item <?php echo $classe_disabled; ?>">
                    <input type="checkbox" 
                           name="ids[]" 
                           value="<?php echo $v['id']; ?>"
                           <?php echo $esta_selecionado ? 'checked' : ''; ?>
                           <?php echo !$pode_selecionar ? 'disabled' : ''; ?>
                           onchange="atualizarComparador()">
                    <div>
                        <strong><?php echo $v['marca']; ?> <?php echo $v['modelo']; ?></strong>
                        <br>
                        <small><?php echo $v['ano']; ?> - <?php echo formatarMoeda($v['preco_venda']); ?></small>
                    </div>
                </label>
            <?php endforeach; ?>
        </div>

        <div class="seletor__actions">
            <button type="submit" class="btn btn--primary">
                ✓ Comparar
            </button>
            <a href="<?php echo BASE_URL; ?>comparar.php" class="btn btn--secondary">
                Limpar
            </a>
        </div>
    </form>

    <!-- ===== COMPARADOR ===== -->
    <?php if (!empty($veiculos_comparar)): ?>
        <div class="comparador__container">
            <table class="comparador__table">
                <thead>
                    <tr>
                        <th>Especificação</th>
                        <?php foreach ($veiculos_comparar as $v): ?>
                            <th style="width: <?php echo 100 / (count($veiculos_comparar) + 1); ?>%">
                                <div class="comparador__nome"><?php echo $v['marca']; ?></div>
                                <img src="<?php echo UPLOAD_DIR . ($v['fotos'] ? explode(',', $v['fotos'])[0] : 'placeholder.jpg'); ?>" 
                                     alt="<?php echo $v['marca']; ?>" 
                                     class="comparador__foto">
                                <p style="font-size: var(--font-size-sm);"><?php echo $v['modelo']; ?></p>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <!-- Preço -->
                    <tr>
                        <td>💰 Preço</td>
                        <?php 
                        $precos = array_map(fn($v) => $v['preco_venda'], $veiculos_comparar);
                        $preco_min = min($precos);
                        ?>
                        <?php foreach ($veiculos_comparar as $v): ?>
                            <td>
                                <div class="preco__comparador"><?php echo formatarMoeda($v['preco_venda']); ?></div>
                                <?php if ($v['preco_venda'] > $preco_min): ?>
                                    <div class="diferenca__preco">+<?php echo formatarMoeda($v['preco_venda'] - $preco_min); ?></div>
                                <?php elseif ($v['preco_venda'] === $preco_min && count($precos) > 1): ?>
                                    <div class="diferenca__preco">✓ Mais barato</div>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>

                    <!-- Ano -->
                    <tr>
                        <td>📅 Ano</td>
                        <?php foreach ($veiculos_comparar as $v): ?>
                            <td><?php echo $v['ano']; ?></td>
                        <?php endforeach; ?>
                    </tr>

                    <!-- Quilometragem -->
                    <tr>
                        <td>🛣️ Quilometragem</td>
                        <?php 
                        $kms = array_map(fn($v) => $v['quilometragem'], $veiculos_comparar);
                        $km_min = min($kms);
                        ?>
                        <?php foreach ($veiculos_comparar as $v): ?>
                            <td>
                                <?php echo formatarKM($v['quilometragem']); ?> km
                                <?php if ($v['quilometragem'] === $km_min && count($kms) > 1): ?>
                                    <br><small style="color: var(--color-accent-primary);">✓ Menos km</small>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>

                    <!-- Combustível -->
                    <tr>
                        <td>⛽ Combustível</td>
                        <?php foreach ($veiculos_comparar as $v): ?>
                            <td><?php echo $combustiveis[$v['combustivel']] ?? 'N/A'; ?></td>
                        <?php endforeach; ?>
                    </tr>

                    <!-- Câmbio -->
                    <tr>
                        <td>⚙️ Câmbio</td>
                        <?php foreach ($veiculos_comparar as $v): ?>
                            <td><?php echo $cambios[$v['cambio']] ?? 'N/A'; ?></td>
                        <?php endforeach; ?>
                    </tr>

                    <!-- Cor -->
                    <tr>
                        <td>🎨 Cor</td>
                        <?php foreach ($veiculos_comparar as $v): ?>
                            <td><?php echo $v['cor'] ?? 'N/A'; ?></td>
                        <?php endforeach; ?>
                    </tr>

                    <!-- Ações -->
                    <tr>
                        <td>🎯 Ações</td>
                        <?php foreach ($veiculos_comparar as $v): ?>
                            <td>
                                <div class="comparador__acoes">
                                    <a href="<?php echo BASE_URL; ?>veiculo.php?slug=<?php echo $v['slug']; ?>" 
                                       class="btn btn--small btn--primary">
                                        Ver Detalhes
                                    </a>
                                    <a href="https://wa.me/55<?php echo preg_replace('/\D/', '', LOJA_WHATSAPP); ?>?text=Tenho%20interesse%20no%20<?php echo urlencode($v['marca'] . ' ' . $v['modelo']); ?>" 
                                       class="btn btn--small btn--secondary"
                                       target="_blank">
                                        WhatsApp
                                    </a>
                                </div>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="vazio__message">
            <h3>👀 Nenhum veículo selecionado</h3>
            <p>Selecione até 3 veículos acima para começar a comparação.</p>
        </div>
    <?php endif; ?>

</section>

<!-- ===== FOOTER ===== -->
<?php require_once __DIR__ . '/includes/footer.php'; ?>

<!-- ===== SCRIPTS ===== -->
<script>
function atualizarComparador() {
    const checkboxes = document.querySelectorAll('input[name="ids[]"]');
    const selecionados = Array.from(checkboxes).filter(cb => cb.checked);
    
    // Atualizar status dos checkboxes
    checkboxes.forEach(cb => {
        if (selecionados.length >= 3 && !cb.checked) {
            cb.parentElement.classList.add('disabled');
            cb.disabled = true;
        } else {
            cb.parentElement.classList.remove('disabled');
            cb.disabled = false;
        }
    });
    
    // Atualizar texto de contagem
    document.querySelector('.seletor__info').textContent = 
        `Selecione até 3 carros para comparar (${selecionados.length}/3 selecionados)`;
}

// Chamar ao carregar
document.addEventListener('DOMContentLoaded', atualizarComparador);
</script>

<script src="<?php echo BASE_URL; ?>assets/js/theme.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/menu-mobile.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>

</body>
</html>
