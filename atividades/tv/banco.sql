CREATE DATABASE IF NOT EXISTS pobreflixtv;
USE pobreflixtv;

-- Tabela de Usuários com Níveis de Acesso
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    nivel_acesso ENUM('usuario', 'admin') DEFAULT 'usuario',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de Gêneros Cadastrados (Ex: Ação, Comédia, etc.)
CREATE TABLE IF NOT EXISTS generos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL UNIQUE
);

-- Tabela Principal de Mídias
CREATE TABLE IF NOT EXISTS midias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    sinopse TEXT,
    tipo ENUM('filme', 'serie') NOT NULL,
    imagem_capa VARCHAR(255), -- Caminho da imagem salva no servidor
    ano INT,
    avaliacao DECIMAL(2,1) DEFAULT 0.0,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela Associativa: Mídias <=> Gêneros (Múltiplos gêneros por filme/série)
CREATE TABLE IF NOT EXISTS midia_genero (
    midia_id INT,
    genero_id INT,
    PRIMARY KEY (midia_id, genero_id),
    FOREIGN KEY (midia_id) REFERENCES midias(id) ON DELETE CASCADE,
    FOREIGN KEY (genero_id) REFERENCES generos(id) ON DELETE CASCADE
);

-- Tabela de Episódios / Links de Mídia
-- Se for Filme, temporada e numero_episodio podem ficar vazios ou como 0/NULL
CREATE TABLE IF NOT EXISTS conteudos_midia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    midia_id INT NOT NULL,
    temporada INT NULL,
    numero_episodio INT NULL,
    titulo_episodio VARCHAR(255) NULL,
    tipo_midia ENUM('link', 'arquivo') NOT NULL,
    origem_midia TEXT NOT NULL, -- Guardará o link URL ou o nome do arquivo MP4 salvo
    FOREIGN KEY (midia_id) REFERENCES midias(id) ON DELETE CASCADE
);

-- Inserir alguns gêneros padrão para teste
INSERT IGNORE INTO generos (nome) VALUES ('Ação'), ('Comédia'), ('Ficção Científica'), ('Drama'), ('Fantasia'), ('Terror');

-- Inserir uma conta padrão de Administrador para o primeiro login (Senha: admin123)
INSERT IGNORE INTO usuarios (nome, email, senha, nivel_acesso) 
VALUES ('Administrador', 'admin@pobreflix.com', '$2y$10$fWkWYvV4s.g5Uo42CshgRe0w3Y1aYjRCOW5W6rDeM697BscXFp3W.', 'admin');
