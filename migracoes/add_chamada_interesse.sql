-- Campos de interesse em qualquer carro (não limitado ao estoque)
ALTER TABLE chamadas_proposta
    ADD COLUMN marca_interesse VARCHAR(100) NULL,
    ADD COLUMN modelo_interesse VARCHAR(100) NULL,
    ADD COLUMN ano_interesse SMALLINT NULL;
