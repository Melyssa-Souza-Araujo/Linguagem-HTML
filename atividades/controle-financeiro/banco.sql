-- Criar o banco de dados se não existir
CREATE DATABASE IF NOT EXISTS controle_gastos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE controle_gastos;

-- Tabela para armazenar o orçamento global configurado pelo usuário
CREATE TABLE IF NOT EXISTS orcamento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    valor_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Inserir um orçamento padrão inicial caso a tabela esteja vazia (R$ 3000,00)
INSERT INTO orcamento (id, valor_total) 
SELECT 1, 3000.00 FROM DUAL 
WHERE NOT EXISTS (SELECT 1 FROM orcamento WHERE id = 1);

-- Tabela para armazenar cada um dos gastos individuais
CREATE TABLE IF NOT EXISTS despesas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    descricao VARCHAR(150) NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    categoria VARCHAR(50) NOT NULL,
    data_gasto DATE NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
