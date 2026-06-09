CREATE DATABASE IF NOT EXISTS dev_academy;
USE dev_academy;

-- Tabela de Usuários
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    tipo VARCHAR(20) DEFAULT 'aluno', -- 'aluno' ou 'admin'
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de Cursos/Aulas
CREATE TABLE IF NOT EXISTS aulas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    descricao TEXT NOT NULL,
    url_video VARCHAR(255) NOT NULL, -- Link do YouTube ou similar
    ordem INT DEFAULT 0
);

-- Inserir um administrador padrão (Senha: admin123)
-- Em produção, use password_hash no PHP para gerar essa hash.
INSERT INTO usuarios (nome, email, senha, tipo) 
VALUES ('Administrador', 'admin@dev.com', '$2y$10$89JbSsn1eCHg/0Pz98kXyeV6k8R.ZgC3.Qk0F1Z/nSshYy38v71pG', 'admin')
ON DUPLICATE KEY UPDATE id=id;
