<?php
/**
 * Funções Reutilizáveis
 * Rei Motors - Loja de Carros Online
 */

function listarOpcionais(): array {
    return [
        'Conforto' => [
            'ar_condicionado'       => 'Ar-condicionado',
            'direcao_hidraulica'    => 'Direção hidráulica',
            'direcao_eletrica'      => 'Direção elétrica',
            'vidro_eletrico_diant'  => 'Vidros elétricos dianteiros',
            'vidro_eletrico_4'      => 'Vidros elétricos (4 vidros)',
            'trava_eletrica'        => 'Travas elétricas',
            'banco_couro'           => 'Bancos em couro',
            'banco_eletrico'        => 'Banco c/ regulagem elétrica',
            'retrovisor_eletrico'   => 'Retrovisores elétricos',
            'retrovisor_rebativel'  => 'Retrovisores rebatíveis',
            'volante_multimidia'    => 'Volante com controle de áudio',
            'teto_solar'            => 'Teto solar',
        ],
        'Segurança' => [
            'airbag_duplo'          => 'Airbag duplo',
            'airbag_lateral'        => 'Airbag lateral/cortina',
            'abs'                   => 'Freios ABS',
            'freio_disco_4'         => 'Freios a disco nas 4 rodas',
            'controle_tracao'       => 'Controle de tração',
            'controle_estabilidade' => 'Controle de estabilidade (ESP)',
            'alarme'                => 'Alarme',
            'camera_re'             => 'Câmera de ré',
            'sensor_re'             => 'Sensor de ré',
            'sensor_dianteiro'      => 'Sensor dianteiro',
        ],
        'Tecnologia' => [
            'multimidia'            => 'Central multimídia',
            'bluetooth'             => 'Bluetooth',
            'gps'                   => 'GPS integrado',
            'cruise_control'        => 'Cruise control',
            'start_stop'            => 'Start/Stop automático',
            'carregador_wireless'   => 'Carregador wireless',
            'conexao_usb'           => 'Entrada USB',
        ],
        'Exterior' => [
            'rodas_liga_leve'       => 'Rodas de liga leve',
            'farol_milha'           => 'Faróis de neblina',
            'farol_led'             => 'Faróis de LED',
            'teto_panoramico'       => 'Teto panorâmico',
            'tracao_4x4'            => 'Tração 4x4',
            'kit_gnv'               => 'Kit GNV',
        ],
    ];
}

require_once __DIR__ . '/config.php';

// ===== FUNÇÕES DE VALIDAÇÃO =====

/**
 * Validar e sanitizar entrada de texto
 */
function sanitizar($texto) {
    return htmlspecialchars(trim($texto), ENT_QUOTES, 'UTF-8');
}

/**
 * Validar email
 */
function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validar CPF
 */
function validarCPF($cpf) {
    $cpf = preg_replace('/\D/', '', $cpf);
    if (strlen($cpf) != 11) return false;
    if (preg_match('/^(\d)\1{10}$/', $cpf)) return false;
    return true;
}

/**
 * Validar telefone
 */
function validarTelefone($telefone) {
    $telefone = preg_replace('/\D/', '', $telefone);
    return strlen($telefone) >= 10 && strlen($telefone) <= 11;
}

/**
 * Validar URL
 */
function validarURL($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

// ===== FUNÇÕES DE FORMATAÇÃO =====

/**
 * Formatar valor em moeda (Real)
 */
function formatarMoeda($valor) {
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

/**
 * Formatar CPF
 */
function formatarCPF($cpf) {
    $cpf = preg_replace('/\D/', '', $cpf);
    return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
}

/**
 * Formatar telefone
 */
function formatarTelefone($telefone) {
    $telefone = preg_replace('/\D/', '', $telefone);
    
    if (strlen($telefone) == 11) {
        return '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 5) . '-' . substr($telefone, 7);
    }
    return $telefone;
}

/**
 * Formatar data (d/m/Y)
 */
function formatarData($data) {
    return date('d/m/Y', strtotime($data));
}

/**
 * Formatar data e hora
 */
function formatarDataHora($data) {
    return date('d/m/Y H:i', strtotime($data));
}

/**
 * Formatar quilometragem
 */
function formatarKM($km) {
    return number_format($km, 0, ',', '.');
}

/**
 * Gerar slug a partir de string
 */
function gerarSlug($texto) {
    $texto = strtolower(trim($texto));
    $texto = preg_replace('/[^\w\s-]/', '', $texto);
    $texto = preg_replace('/[\s-]+/', '-', $texto);
    return trim($texto, '-');
}

/**
 * Gerar hash para uploads (segurança)
 */
function gerarHashArquivo() {
    return bin2hex(random_bytes(16));
}

// ===== FUNÇÕES DE IMAGEM =====

/**
 * Upload e otimização de imagem
 */
function uploadImagem($arquivo, $pasta = '') {
    if (!isset($arquivo['tmp_name']) || $arquivo['error'] !== UPLOAD_ERR_OK) {
        return ['sucesso' => false, 'erro' => 'Erro ao fazer upload do arquivo'];
    }
    
    // Validar tipo
    $tipos_permitidos = ['image/jpeg', 'image/png', 'image/webp'];
    $mime_type = mime_content_type($arquivo['tmp_name']);
    
    if (!in_array($mime_type, $tipos_permitidos)) {
        return ['sucesso' => false, 'erro' => 'Tipo de arquivo não permitido'];
    }
    
    // Validar tamanho
    if ($arquivo['size'] > MAX_UPLOAD_SIZE) {
        return ['sucesso' => false, 'erro' => 'Arquivo muito grande'];
    }
    
    // Criar pasta se não existir
    $pasta_completa = UPLOAD_PATH . $pasta;
    if (!is_dir($pasta_completa)) {
        mkdir($pasta_completa, 0755, true);
        @chmod($pasta_completa, 0755);
    }
    
    // Sempre salva como .jpg — otimizarImagem() converte tudo para JPEG
    $nome_arquivo     = gerarHashArquivo() . '.jpg';
    $caminho_completo = $pasta_completa . $nome_arquivo;

    // Move para local temporário com extensão original para o move_uploaded_file
    $tmp_ext  = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
    $tmp_path = $pasta_completa . gerarHashArquivo() . '.' . $tmp_ext;

    if (!move_uploaded_file($arquivo['tmp_name'], $tmp_path)) {
        return ['sucesso' => false, 'erro' => 'Erro ao salvar arquivo'];
    }

    // Otimizar/converter para JPEG final
    if (!otimizarImagem($tmp_path, $caminho_completo)) {
        // Fallback: se GD não estiver disponível, renomeia direto
        rename($tmp_path, $caminho_completo);
    } else {
        @unlink($tmp_path);
    }

    @chmod($caminho_completo, 0644);

    return [
        'sucesso' => true,
        'caminho' => $pasta . $nome_arquivo,
        'caminho_completo' => $caminho_completo
    ];
}

/**
 * Otimizar imagem com GD Library
 */
function otimizarImagem($origem, $destino = null) {
    if (!extension_loaded('gd')) {
        return false;
    }
    if ($destino === null) $destino = $origem;

    try {
        $info = getimagesize($origem);
        if (!$info) return false;
        $tipo = $info[2];

        switch ($tipo) {
            case IMAGETYPE_JPEG: $imagem = imagecreatefromjpeg($origem); break;
            case IMAGETYPE_PNG:  $imagem = imagecreatefrompng($origem);  break;
            case IMAGETYPE_WEBP: $imagem = imagecreatefromwebp($origem); break;
            default: return false;
        }

        if (!$imagem) return false;

        $larg = imagesx($imagem);
        $alt  = imagesy($imagem);

        if ($larg > IMG_MAX_WIDTH || $alt > IMG_MAX_HEIGHT) {
            $ratio = min(IMG_MAX_WIDTH / $larg, IMG_MAX_HEIGHT / $alt);
            $nw = (int)($larg * $ratio);
            $nh = (int)($alt  * $ratio);
            $novo = imagecreatetruecolor($nw, $nh);
            // Fundo branco para PNG com transparência
            imagefill($novo, 0, 0, imagecolorallocate($novo, 255, 255, 255));
            imagecopyresampled($novo, $imagem, 0, 0, 0, 0, $nw, $nh, $larg, $alt);
            imagejpeg($novo, $destino, 85);
            imagedestroy($novo);
        } else {
            // Para PNG/WebP com transparência: adiciona fundo branco
            if ($tipo === IMAGETYPE_PNG || $tipo === IMAGETYPE_WEBP) {
                $fundo = imagecreatetruecolor($larg, $alt);
                imagefill($fundo, 0, 0, imagecolorallocate($fundo, 255, 255, 255));
                imagecopy($fundo, $imagem, 0, 0, 0, 0, $larg, $alt);
                imagejpeg($fundo, $destino, 85);
                imagedestroy($fundo);
            } else {
                imagejpeg($imagem, $destino, 85);
            }
        }

        imagedestroy($imagem);
        return true;

    } catch (Exception $e) {
        error_log("Erro ao otimizar imagem: " . $e->getMessage());
        return false;
    }
}

// ===== FUNÇÕES DE AUTENTICAÇÃO =====

/**
 * Hash de senha segura
 */
function hashSenha($senha) {
    return password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verificar senha
 */
function verificarSenha($senha, $hash) {
    return password_verify($senha, $hash);
}

// ===== AUTENTICAÇÃO DO CLIENTE (ÁREA DO CLIENTE) =====

function clienteAutenticado() {
    return isset($_SESSION['cliente_id']);
}

function requerClienteAutenticado() {
    if (!clienteAutenticado()) {
        header('Location: ' . BASE_URL . 'cliente/login.php');
        exit;
    }
}

// ===== FUNÇÕES DE UTILIDADE =====

/**
 * Redirecionar com mensagem
 */
function redirecionar($url, $mensagem = '', $tipo = 'info') {
    if ($mensagem) {
        $_SESSION['mensagem'] = $mensagem;
        $_SESSION['tipo_mensagem'] = $tipo;
    }
    header('Location: ' . $url);
    exit;
}

/**
 * Obter e limpar mensagem da sessão
 */
function obterMensagem() {
    if (isset($_SESSION['mensagem'])) {
        $mensagem = $_SESSION['mensagem'];
        $tipo = $_SESSION['tipo_mensagem'] ?? 'info';
        unset($_SESSION['mensagem']);
        unset($_SESSION['tipo_mensagem']);
        return ['texto' => $mensagem, 'tipo' => $tipo];
    }
    return null;
}

/**
 * Calcular lucro do veículo
 */
function calcularLucroVeiculo($preco_venda, $preco_custo, $custos_adicionais = 0, $custos_garantia = 0) {
    $lucro = $preco_venda - ($preco_custo + $custos_adicionais + $custos_garantia);
    return $lucro;
}

/**
 * Calcular comissão do vendedor
 */
function calcularComissaoVendedor($preco_venda, $percentual_comissao) {
    return ($preco_venda * $percentual_comissao) / 100;
}

/**
 * Gerar token CSRF
 */
function gerarTokenCSRF() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verificar token CSRF
 */
function verificarTokenCSRF($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Enviar e-mail usando mail() nativo do PHP
 * Compatível com HostGator compartilhado (sendmail)
 *
 * @param string $para      Endereço destinatário
 * @param string $assunto   Assunto do e-mail
 * @param string $corpo_html HTML do corpo
 * @param string|null $responder_para Reply-To (opcional)
 * @return bool
 */
function enviarEmail(string $para, string $assunto, string $corpo_html, ?string $responder_para = null): bool {
    $de_nome   = LOJA_NOME;
    $de_email  = LOJA_EMAIL;
    $charset   = 'UTF-8';

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=$charset\r\n";
    $headers .= "From: =?$charset?B?" . base64_encode($de_nome) . "?= <$de_email>\r\n";
    $headers .= "Reply-To: " . ($responder_para ?? $de_email) . "\r\n";
    $headers .= "X-Mailer: PHP/" . PHP_VERSION . "\r\n";

    $assunto_encoded = "=?$charset?B?" . base64_encode($assunto) . "?=";

    $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="font-family:Arial,sans-serif;color:#222;max-width:600px;margin:0 auto">';
    $html .= '<div style="background:#0A0A0A;padding:20px;text-align:center">';
    $html .= '<h2 style="color:#D4AF37;margin:0">' . htmlspecialchars($de_nome) . '</h2></div>';
    $html .= '<div style="padding:24px">' . $corpo_html . '</div>';
    $html .= '<div style="background:#f5f5f5;padding:12px;text-align:center;font-size:12px;color:#888">';
    $html .= htmlspecialchars(LOJA_ENDERECO) . '</div></body></html>';

    try {
        return mail($para, $assunto_encoded, $html, $headers);
    } catch (\Throwable $e) {
        error_log("enviarEmail error: " . $e->getMessage());
        return false;
    }
}

/**
 * Monta e envia notificação de novo lead para o e-mail da loja
 */
function notificarNovoLead(array $lead): void {
    $veiculo_str = '';
    if (!empty($lead['veiculo_id'])) {
        $v = obterUmaLinha("SELECT marca, modelo, ano FROM veiculos WHERE id = ?", [$lead['veiculo_id']]);
        if ($v) $veiculo_str = htmlspecialchars($v['marca'] . ' ' . $v['modelo'] . ' ' . $v['ano']);
    }

    $origem_labels = [
        'formulario_interesse' => 'Formulário de Interesse',
        'venda_seu_carro'      => 'Venda seu Carro',
        'simulador'            => 'Simulador de Financiamento',
        'estoque'              => 'Página de Estoque',
        'site'                 => 'Site',
    ];
    $origem_label = $origem_labels[$lead['origem'] ?? ''] ?? ($lead['origem'] ?? 'Site');

    $corpo = '<h3 style="color:#D4AF37">Novo Lead Recebido</h3>';
    $corpo .= '<table style="width:100%;border-collapse:collapse">';
    $corpo .= '<tr><td style="padding:8px;border-bottom:1px solid #eee;font-weight:bold;width:140px">Nome</td><td style="padding:8px;border-bottom:1px solid #eee">' . htmlspecialchars($lead['nome']) . '</td></tr>';
    $corpo .= '<tr><td style="padding:8px;border-bottom:1px solid #eee;font-weight:bold">Telefone</td><td style="padding:8px;border-bottom:1px solid #eee">' . htmlspecialchars($lead['telefone']) . '</td></tr>';
    if (!empty($lead['email'])) {
        $corpo .= '<tr><td style="padding:8px;border-bottom:1px solid #eee;font-weight:bold">E-mail</td><td style="padding:8px;border-bottom:1px solid #eee">' . htmlspecialchars($lead['email']) . '</td></tr>';
    }
    if ($veiculo_str) {
        $corpo .= '<tr><td style="padding:8px;border-bottom:1px solid #eee;font-weight:bold">Veículo</td><td style="padding:8px;border-bottom:1px solid #eee">' . $veiculo_str . '</td></tr>';
    }
    $corpo .= '<tr><td style="padding:8px;border-bottom:1px solid #eee;font-weight:bold">Origem</td><td style="padding:8px;border-bottom:1px solid #eee">' . htmlspecialchars($origem_label) . '</td></tr>';
    if (!empty($lead['observacoes'])) {
        $corpo .= '<tr><td style="padding:8px;font-weight:bold">Observações</td><td style="padding:8px">' . nl2br(htmlspecialchars($lead['observacoes'])) . '</td></tr>';
    }
    $corpo .= '</table>';
    $corpo .= '<p style="margin-top:20px"><a href="' . ADMIN_URL . 'leads/" style="background:#D4AF37;color:#000;padding:10px 20px;text-decoration:none;border-radius:6px;font-weight:bold">Ver no Painel Admin</a></p>';

    $responder = !empty($lead['email']) ? $lead['email'] : null;
    enviarEmail(LOJA_EMAIL, 'Novo Lead: ' . $lead['nome'], $corpo, $responder);
}

/**
 * Log de ação do usuário
 */
function logarAcao($usuario_id, $acao, $detalhes = '') {
    global $pdo;
    
    try {
        $sql = "INSERT INTO logs_acoes (usuario_id, acao, detalhes, ip, data_hora) 
                VALUES (?, ?, ?, ?, NOW())";
        
        $pdo->prepare($sql)->execute([
            $usuario_id,
            $acao,
            $detalhes,
            $_SERVER['REMOTE_ADDR'] ?? 'desconhecido'
        ]);
    } catch (Exception $e) {
        error_log("Erro ao logar ação: " . $e->getMessage());
    }
}

?>
