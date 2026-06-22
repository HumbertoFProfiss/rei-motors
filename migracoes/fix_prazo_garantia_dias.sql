-- Renomeia coluna prazo_garantia_meses → prazo_garantia_dias na tabela vendas
-- Rodar no phpMyAdmin SOMENTE se a coluna ainda se chama prazo_garantia_meses
-- Para verificar: execute "SHOW COLUMNS FROM vendas LIKE 'prazo%';" antes

ALTER TABLE vendas
    CHANGE prazo_garantia_meses prazo_garantia_dias SMALLINT DEFAULT 90;
