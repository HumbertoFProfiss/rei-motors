-- Tabela de opcionais por veículo
-- Executar via phpMyAdmin antes de usar a funcionalidade de opcionais

CREATE TABLE IF NOT EXISTS veiculos_opcionais (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    veiculo_id INT         NOT NULL,
    opcional   VARCHAR(60) NOT NULL,
    UNIQUE KEY uq_veiculo_opcional (veiculo_id, opcional),
    FOREIGN KEY (veiculo_id) REFERENCES veiculos(id) ON DELETE CASCADE
);
