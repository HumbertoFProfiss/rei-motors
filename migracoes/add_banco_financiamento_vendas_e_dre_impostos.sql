-- Campo livre pra anotar qual banco/financeira fez o financiamento da venda
-- (Itaú, Santander, C6, etc.) — pedido do dono junto com o lucro de
-- financiamento. Texto livre porque varia muito e não há lista fixa por enquanto
-- (ideia de lista padronizada com todos os bancos foi adiada pra um projeto futuro).
ALTER TABLE vendas
    ADD COLUMN banco_financiamento VARCHAR(100) NULL AFTER lucro_financiamento;

-- Nenhuma mudança de schema necessária pra separar "Impostos" como linha própria
-- no DRE — já usa a categoria 'impostos' existente em custos_veiculo.
