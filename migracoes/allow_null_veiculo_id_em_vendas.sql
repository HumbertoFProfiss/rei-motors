-- Permite excluir veículo com vendas canceladas
-- Executar via phpMyAdmin em reidosco_motors2

ALTER TABLE vendas
    MODIFY COLUMN veiculo_id INT NULL;

ALTER TABLE garantias_chamados
    MODIFY COLUMN veiculo_id INT NULL;
