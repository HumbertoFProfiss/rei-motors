-- Migração: Adicionar coluna senha à tabela clientes (área do cliente)
ALTER TABLE clientes ADD COLUMN IF NOT EXISTS senha VARCHAR(255) NULL AFTER cep;
