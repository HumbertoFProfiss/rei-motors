-- =====================================================
-- SCHEMA DO BANCO DE DADOS - REI MOTORS
-- HostGator - MySQL 8.0+
-- PHP 8.2
-- =====================================================

-- ===== USUÁRIOS E AUTENTICAÇÃO =====

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    cpf VARCHAR(14) UNIQUE,
    telefone VARCHAR(20),
    endereco TEXT,
    role ENUM('admin', 'vendedor') NOT NULL DEFAULT 'vendedor',
    ativo TINYINT(1) DEFAULT 1,
    comissao_percentual DECIMAL(5,2) DEFAULT 3.50,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
);

-- ===== VEÍCULOS =====

CREATE TABLE veiculos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    marca VARCHAR(50) NOT NULL,
    modelo VARCHAR(100) NOT NULL,
    slug VARCHAR(150) UNIQUE,
    ano INT NOT NULL,
    preco_venda DECIMAL(12,2) NOT NULL,
    preco_custo DECIMAL(12,2) NOT NULL,
    preco_tabela_fipe DECIMAL(12,2),
    quilometragem INT DEFAULT 0,
    combustivel ENUM('gasolina', 'flex', 'etanol', 'diesel', 'eletrico') NOT NULL,
    cambio ENUM('manual', 'automatico', 'cvt') NOT NULL,
    cor VARCHAR(50),
    descricao TEXT,
    status ENUM('disponivel', 'reservado', 'vendido') DEFAULT 'disponivel',
    destaque TINYINT(1) DEFAULT 0,
    numero_chassi VARCHAR(50) UNIQUE,
    placa VARCHAR(20) UNIQUE,
    documento VARCHAR(20),
    renavam VARCHAR(20),
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_marca (marca),
    INDEX idx_modelo (modelo),
    INDEX idx_ano (ano),
    INDEX idx_preco (preco_venda),
    INDEX idx_status (status),
    INDEX idx_destaque (destaque)
);

CREATE TABLE veiculos_fotos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    veiculo_id INT NOT NULL,
    caminho VARCHAR(255) NOT NULL,
    principal TINYINT(1) DEFAULT 0,
    ordem INT DEFAULT 0,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (veiculo_id) REFERENCES veiculos(id) ON DELETE CASCADE,
    INDEX idx_veiculo (veiculo_id)
);

CREATE TABLE veiculos_videos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    veiculo_id INT NOT NULL,
    url_youtube VARCHAR(255),
    caminho_arquivo VARCHAR(255),
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (veiculo_id) REFERENCES veiculos(id) ON DELETE CASCADE,
    INDEX idx_veiculo (veiculo_id)
);

-- ===== CLIENTES =====

CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cpf VARCHAR(14) UNIQUE,
    rg VARCHAR(20),
    email VARCHAR(150),
    telefone VARCHAR(20),
    whatsapp VARCHAR(20),
    endereco VARCHAR(255),
    numero VARCHAR(10),
    complemento VARCHAR(100),
    bairro VARCHAR(100),
    cidade VARCHAR(100),
    estado VARCHAR(2),
    cep VARCHAR(10),
    profissao VARCHAR(100),
    renda_estimada DECIMAL(12,2),
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_cpf (cpf),
    INDEX idx_email (email),
    INDEX idx_telefone (telefone)
);

-- ===== VENDAS =====

CREATE TABLE vendas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    veiculo_id INT NOT NULL,
    cliente_id INT NOT NULL,
    vendedor_id INT NOT NULL,
    forma_pagamento ENUM('avista', 'financiado', 'consorcio', 'troca') NOT NULL,
    preco_venda DECIMAL(12,2) NOT NULL,
    desconto_aplicado DECIMAL(12,2) DEFAULT 0,
    valor_troca DECIMAL(12,2) DEFAULT 0,
    comissao_vendedor DECIMAL(12,2) DEFAULT 0,
    status ENUM('pendente', 'confirmada', 'entregue', 'cancelada') DEFAULT 'pendente',
    data_venda DATE NOT NULL,
    data_entrega DATE,
    numero_contrato VARCHAR(50),
    observacoes TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (veiculo_id) REFERENCES veiculos(id) ON DELETE RESTRICT,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE RESTRICT,
    FOREIGN KEY (vendedor_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
    INDEX idx_veiculo (veiculo_id),
    INDEX idx_cliente (cliente_id),
    INDEX idx_vendedor (vendedor_id),
    INDEX idx_status (status),
    INDEX idx_data (data_venda)
);

CREATE TABLE parcelas_financiamento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venda_id INT NOT NULL,
    numero_parcela INT NOT NULL,
    valor_parcela DECIMAL(12,2) NOT NULL,
    data_vencimento DATE NOT NULL,
    data_pagamento DATE,
    status ENUM('pendente', 'paga', 'vencida', 'cancelada') DEFAULT 'pendente',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (venda_id) REFERENCES vendas(id) ON DELETE CASCADE,
    INDEX idx_venda (venda_id),
    INDEX idx_status (status),
    INDEX idx_vencimento (data_vencimento)
);

CREATE TABLE carros_troca (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venda_id INT NOT NULL,
    marca VARCHAR(50) NOT NULL,
    modelo VARCHAR(100) NOT NULL,
    ano INT NOT NULL,
    cor VARCHAR(50),
    quilometragem INT,
    valor_estimado DECIMAL(12,2),
    condicao_veiculo VARCHAR(255),
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (venda_id) REFERENCES vendas(id) ON DELETE CASCADE,
    INDEX idx_venda (venda_id)
);

-- ===== CUSTOS E DESPESAS =====

CREATE TABLE custos_veiculo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    veiculo_id INT NOT NULL,
    categoria VARCHAR(50) NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    valor DECIMAL(12,2) NOT NULL,
    data_despesa DATE NOT NULL,
    numero_nf VARCHAR(50),
    arquivo_nf VARCHAR(255),
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (veiculo_id) REFERENCES veiculos(id) ON DELETE CASCADE,
    INDEX idx_veiculo (veiculo_id),
    INDEX idx_categoria (categoria)
);

CREATE TABLE contas_pagar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    descricao VARCHAR(255) NOT NULL,
    valor DECIMAL(12,2) NOT NULL,
    data_vencimento DATE NOT NULL,
    data_pagamento DATE,
    categoria VARCHAR(50) NOT NULL,
    status ENUM('pendente', 'paga', 'vencida', 'cancelada') DEFAULT 'pendente',
    numero_nf VARCHAR(50),
    arquivo_nf VARCHAR(255),
    observacoes TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_vencimento (data_vencimento),
    INDEX idx_categoria (categoria)
);

CREATE TABLE contas_receber (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venda_id INT,
    descricao VARCHAR(255) NOT NULL,
    valor DECIMAL(12,2) NOT NULL,
    data_vencimento DATE NOT NULL,
    data_pagamento DATE,
    status ENUM('pendente', 'recebida', 'vencida', 'cancelada') DEFAULT 'pendente',
    observacoes TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (venda_id) REFERENCES vendas(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_vencimento (data_vencimento)
);

-- ===== GARANTIAS =====

CREATE TABLE garantias_chamados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    veiculo_id INT NOT NULL,
    cliente_id INT NOT NULL,
    venda_id INT,
    tipo_problema VARCHAR(255) NOT NULL,
    descricao TEXT,
    status ENUM('aberto', 'em_andamento', 'resolvido', 'recusado') DEFAULT 'aberto',
    data_abertura DATE NOT NULL,
    data_resolucao DATE,
    custo_reparo DECIMAL(12,2) DEFAULT 0,
    observacoes TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (veiculo_id) REFERENCES veiculos(id) ON DELETE RESTRICT,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE RESTRICT,
    FOREIGN KEY (venda_id) REFERENCES vendas(id) ON DELETE SET NULL,
    INDEX idx_veiculo (veiculo_id),
    INDEX idx_cliente (cliente_id),
    INDEX idx_status (status)
);

-- ===== LEADS =====

CREATE TABLE leads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    veiculo_id INT,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(150),
    telefone VARCHAR(20),
    whatsapp VARCHAR(20),
    origem ENUM('estoque', 'formulario_interesse', 'simulador', 'site', 'outro') DEFAULT 'site',
    tipo_lead ENUM('novo', 'contatado', 'interessado', 'nao_interessado', 'convertido') DEFAULT 'novo',
    ip_origem VARCHAR(45),
    user_agent TEXT,
    observacoes TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (veiculo_id) REFERENCES veiculos(id) ON DELETE SET NULL,
    INDEX idx_veiculo (veiculo_id),
    INDEX idx_email (email),
    INDEX idx_telefone (telefone),
    INDEX idx_tipo (tipo_lead),
    INDEX idx_criado (criado_em)
);

CREATE TABLE leads_historico (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lead_id INT NOT NULL,
    acao VARCHAR(255) NOT NULL,
    descricao TEXT,
    usuario_id INT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_lead (lead_id)
);

-- ===== LOGS =====

CREATE TABLE logs_acoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    acao VARCHAR(255) NOT NULL,
    tabela_afetada VARCHAR(100),
    registro_id INT,
    detalhes JSON,
    ip VARCHAR(45),
    user_agent TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
    INDEX idx_usuario (usuario_id),
    INDEX idx_acao (acao),
    INDEX idx_criado (criado_em)
);

-- ===== CONFIGURAÇÕES =====

CREATE TABLE configuracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chave VARCHAR(100) NOT NULL UNIQUE,
    valor LONGTEXT,
    tipo ENUM('texto', 'numero', 'boolean', 'json') DEFAULT 'texto',
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ===== BANNERS =====

CREATE TABLE banners_home (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    subtitulo VARCHAR(255),
    imagem VARCHAR(255) NOT NULL,
    link_cta VARCHAR(255),
    texto_cta VARCHAR(50),
    ordem INT DEFAULT 0,
    ativo TINYINT(1) DEFAULT 1,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ===== INSERÇÕES INICIAIS =====

-- Inserir usuário admin padrão (senha: admin123)
INSERT INTO usuarios (nome, email, senha, role, ativo) VALUES 
('Administrador', 'admin@reimotors.com', '$2y$12$...', 'admin', 1);

-- Inserir configurações padrão
INSERT INTO configuracoes (chave, valor, tipo) VALUES
('loja_nome', 'Rei Motors', 'texto'),
('loja_email', 'reimotorsoficial@gmail.com', 'texto'),
('loja_whatsapp', '11999999999', 'texto'),
('loja_telefone', '1133333333', 'texto'),
('loja_endereco', 'Rua Exemplo, 123 - São Paulo - SP', 'texto'),
('loja_horario', '09:00 - 18:00', 'texto'),
('modo_manutencao', 'false', 'boolean'),
('comissao_padrao', '3.50', 'numero'),
('garantia_dias', '90', 'numero'),
('garantia_cambio_dias', '90', 'numero');

-- ===== ÍNDICES ADICIONAIS PARA PERFORMANCE =====

-- Índices para otimização de queries frequentes
ALTER TABLE veiculos ADD FULLTEXT INDEX ft_search (marca, modelo, descricao);
ALTER TABLE clientes ADD FULLTEXT INDEX ft_clientes (nome, email, telefone);
ALTER TABLE leads ADD FULLTEXT INDEX ft_leads (nome, email, telefone);

-- ===== FIM DO SCHEMA =====
