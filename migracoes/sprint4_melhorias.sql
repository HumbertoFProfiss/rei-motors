-- =============================================================
-- SPRINT 4 — Migração de melhorias
-- =============================================================

-- 1. Garantia: mudar de meses para dias
ALTER TABLE vendas
    CHANGE prazo_garantia_meses prazo_garantia_dias SMALLINT DEFAULT 90;

-- 2. Garantia: campos de fornecedor, peça e custos separados
ALTER TABLE garantias_chamados
    ADD COLUMN fornecedor      VARCHAR(150)   NULL AFTER observacoes,
    ADD COLUMN peca_descricao  VARCHAR(255)   NULL AFTER fornecedor,
    ADD COLUMN custo_peca      DECIMAL(12,2)  DEFAULT 0 AFTER peca_descricao,
    ADD COLUMN custo_servico   DECIMAL(12,2)  DEFAULT 0 AFTER custo_peca;

-- 3. Chamadas: nome livre, telefone e intenção
ALTER TABLE chamadas_proposta
    ADD COLUMN nome_contato     VARCHAR(100)                             NULL AFTER cliente_id,
    ADD COLUMN telefone_contato VARCHAR(20)                              NULL AFTER nome_contato,
    ADD COLUMN intencao         ENUM('comprar','vender','consignar','outro') NULL AFTER telefone_contato;

-- 4. Contas a pagar: recorrência + vínculo com veículo
ALTER TABLE contas_pagar
    ADD COLUMN recorrencia ENUM('nenhuma','mensal','bimestral','trimestral','anual') NOT NULL DEFAULT 'nenhuma' AFTER observacoes,
    ADD COLUMN veiculo_id  INT NULL AFTER recorrencia,
    ADD CONSTRAINT fk_cp_veiculo FOREIGN KEY (veiculo_id) REFERENCES veiculos(id) ON DELETE SET NULL;

-- 5. Contas a receber: categoria + recorrência
ALTER TABLE contas_receber
    ADD COLUMN categoria   VARCHAR(50) NULL AFTER observacoes,
    ADD COLUMN recorrencia ENUM('nenhuma','mensal','bimestral','trimestral','anual') NOT NULL DEFAULT 'nenhuma' AFTER categoria;
