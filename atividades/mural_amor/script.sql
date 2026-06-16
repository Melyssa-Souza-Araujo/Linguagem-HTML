-- 1. Cria o banco de dados com suporte a emojis e caracteres especiais
CREATE DATABASE IF NOT EXISTS mural_amor;
USE mural_amor;

-- 2. Cria a tabela de Usuários (O casal)
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    parceiro_id INT DEFAULT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_parceiro FOREIGN KEY (parceiro_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- 3. Cria a tabela de Postagens (O mural de memórias)
CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    texto TEXT DEFAULT NULL,
    imagem_url VARCHAR(255) DEFAULT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_usuario_post FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);
