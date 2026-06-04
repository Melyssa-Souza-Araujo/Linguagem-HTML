CREATE DATABASE IF NOT EXISTS sistema_catalogo;
USE sistema_catalogo;

CREATE TABLE IF NOT EXISTS itens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    tipo ENUM('filme', 'livro', 'jogo') NOT NULL,
    genero VARCHAR(50) NOT NULL,
    descricao TEXT NOT NULL,
    foto VARCHAR(255) DEFAULT 'padrao.png',
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
