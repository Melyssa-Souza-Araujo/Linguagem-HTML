CREATE DATABASE vnl_sistema;
USE vnl_sistema;

CREATE TABLE paises (
    id INT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    bandeira_url VARCHAR(255) DEFAULT NULL
);

CREATE TABLE partidas (
    id INT PRIMARY KEY,
    id_casa INT NOT NULL,
    id_fora INT NOT NULL,
    pontos_casa INT NOT NULL DEFAULT 0,
    pontos_fora INT NOT NULL DEFAULT 0,
    youtube_url VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (id_casa) REFERENCES paises(id),
    FOREIGN KEY (id_fora) REFERENCES paises(id)
);

ALTER TABLE partidas ADD COLUMN genero ENUM('M', 'F') NOT NULL DEFAULT 'F' AFTER youtube_url;
