-- Lucro de financiamento: comissão que a loja recebe do banco/financeira por
-- venda financiada. Valor variável por venda (pode ser 0), lançado manualmente
-- ao registrar a venda, separado do valor do carro. Entra na receita do DRE.
-- Executar via phpMyAdmin no banco de produção.

ALTER TABLE vendas
    ADD COLUMN lucro_financiamento DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER comissao_vendedor;

-- Bug pré-existente encontrado ao testar: o enum de forma_pagamento só aceitava
-- ('avista','financiado','consorcio','troca'), mas o formulário sempre ofereceu
-- ('avista','financiamento','cartao','pix','troca','consorcio') — registrar venda
-- financiada, no cartão ou PIX quebrava com "Data truncated". Alinha o enum ao
-- formulário. Converte primeiro qualquer linha antiga com 'financiado' (se houver)
-- antes de trocar o enum, senão o MySQL zera o valor dessas linhas.
UPDATE vendas SET forma_pagamento = 'financiamento' WHERE forma_pagamento = 'financiado';

ALTER TABLE vendas
    MODIFY COLUMN forma_pagamento ENUM('avista','financiamento','cartao','pix','troca','consorcio') NOT NULL;
