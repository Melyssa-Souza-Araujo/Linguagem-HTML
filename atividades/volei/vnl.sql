CREATE DATABASE vnl_sistema;
USE vnl_sistema;

-- Criação da tabela de Países
CREATE TABLE paises (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    bandeira_url VARCHAR(255) DEFAULT NULL
);

-- Criação da tabela de Partidas JÁ COM A COLUNA GENERO integrada
CREATE TABLE partidas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_casa INT NOT NULL,
    id_fora INT NOT NULL,
    pontos_casa INT NOT NULL DEFAULT 0,
    pontos_fora INT NOT NULL DEFAULT 0,
    youtube_url VARCHAR(255) DEFAULT NULL,
    genero ENUM('M', 'F') NOT NULL DEFAULT 'F',
    FOREIGN KEY (id_casa) REFERENCES paises(id),
    FOREIGN KEY (id_fora) REFERENCES paises(id)
);

ALTER TABLE partidas DROP FOREIGN KEY partidas_ibfk_1;
ALTER TABLE partidas DROP FOREIGN KEY partidas_ibfk_2;

ALTER TABLE partidas ADD CONSTRAINT fk_partidas_casa FOREIGN KEY (id_casa) REFERENCES paises(id) ON DELETE CASCADE;
ALTER TABLE partidas ADD CONSTRAINT fk_partidas_fora FOREIGN KEY (id_fora) REFERENCES paises(id) ON DELETE CASCADE;

ALTER TABLE partidas DROP COLUMN youtube_url;
TRUNCATE TABLE partidas;

ALTER TABLE paises ADD COLUMN sigla VARCHAR(2) DEFAULT 'BR';

ALTER TABLE partidas ADD COLUMN fase VARCHAR(50) DEFAULT 'Fase de Grupos';

-- Criar a tabela de usuários
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    login VARCHAR(50) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    nivel VARCHAR(10) DEFAULT 'usuario' -- 'admin' ou 'usuario'
);

-- Inserir o Administrador Padrão (Login: admin / Senha: 123)
-- A senha já está criptografada com password_hash
INSERT INTO usuarios (login, senha, nivel) 
VALUES ('admin', '$2y$10$wN3tN3G7XNHeK4Dk9xI.XebScltU.A2I7bY4mZc3Rfe76Q9/uVq2q', 'admin')
ON DUPLICATE KEY UPDATE login=login;
