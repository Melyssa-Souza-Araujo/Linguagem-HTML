-- ====================================================================
-- 1. CRIAÇÃO DO BANCO DE DADOS E SELEÇÃO
-- ====================================================================
CREATE DATABASE IF NOT EXISTS vnl_sistema;
USE vnl_sistema;

-- ====================================================================
-- 2. CRIAÇÃO DAS TABELAS (ESTRUTURA DEFINITIVA LIMPA)
-- ====================================================================

-- Tabela de Países
CREATE TABLE paises (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    sigla VARCHAR(2) NOT NULL DEFAULT 'BR'
);

-- Tabela de Partidas (Já estruturada com todos os campos e chaves estrangeiras)
CREATE TABLE partidas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_casa INT NOT NULL,
    id_fora INT NOT NULL,
    pontos_casa INT NOT NULL DEFAULT 0,
    pontos_fora INT NOT NULL DEFAULT 0,
    genero ENUM('M', 'F') NOT NULL DEFAULT 'F',
    fase VARCHAR(50) NOT NULL DEFAULT 'Fase de Grupos',
    data_partida DATE NOT NULL DEFAULT (CURRENT_DATE), -- Salva a data do jogo de forma nativa
    CONSTRAINT fk_partidas_casa FOREIGN KEY (id_casa) REFERENCES paises(id) ON DELETE CASCADE,
    CONSTRAINT fk_partidas_fora FOREIGN KEY (id_fora) REFERENCES paises(id) ON DELETE CASCADE
);

-- Tabela de Usuários (Controle de Acesso)
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    login VARCHAR(50) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    nivel VARCHAR(10) DEFAULT 'usuario' -- 'admin' ou 'usuario'
);

-- ====================================================================
-- 3. INSERÇÃO DO ADMINISTRADOR PADRÃO
-- ====================================================================

-- Cadastra o admin padrão (Login: admin / Senha: 123) com o hash seguro nativo do PHP
INSERT INTO usuarios (login, senha, nivel) 
VALUES ('admin', '$2y$10$wN3tN3G7XNHeK4Dk9xI.XebScltU.A2I7bY4mZc3Rfe76Q9/uVq2q', 'admin')
ON DUPLICATE KEY UPDATE login=login;
