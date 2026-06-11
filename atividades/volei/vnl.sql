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
