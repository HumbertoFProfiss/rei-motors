-- Adiciona vínculo de veículo de interesse em chamadas_proposta
ALTER TABLE chamadas_proposta
    ADD COLUMN veiculo_id INT NULL;
