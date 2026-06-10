CREATE DATABASE rpg_hub;
USE rpg_hub;

CREATE TABLE usuarios (
    id INT PRIMARY KEY IDENTITY(1,1),
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    tipo VARCHAR(10) NOT NULL CHECK (tipo IN ('jogador', 'mestre')),
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE mesas (
    id INT PRIMARY KEY IDENTITY(1,1),
    nome VARCHAR(255) NOT NULL,
    descricao TEXT,
    mestre_id INT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mestre_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

CREATE TABLE mesa_jogadores (
    mesa_id INT,
    jogador_id INT,
    PRIMARY KEY (mesa_id, jogador_id),
    FOREIGN KEY (mesa_id) REFERENCES mesas(id) ON DELETE CASCADE,
    FOREIGN KEY (jogador_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

CREATE TABLE personagens (
    id INT PRIMARY KEY IDENTITY(1,1),
    usuario_id INT NULL,
    mesa_id INT NULL,
    nome VARCHAR(255) NOT NULL,
    tipo VARCHAR(10) DEFAULT 'jogador' CHECK (tipo IN ('jogador', 'npc', 'monstro')),
    forca INT DEFAULT 10,
    destreza INT DEFAULT 10,
    constituicao INT DEFAULT 10,
    inteligencia INT DEFAULT 10,
    sabedoria INT DEFAULT 10,
    carisma INT DEFAULT 10,
    vida_max INT DEFAULT 10,
    vida_atual INT DEFAULT 10,
    pdf_caminho VARCHAR(255) NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (mesa_id) REFERENCES mesas
);

CREATE TABLE arquivos_mesa (
    id INT PRIMARY KEY IDENTITY(1,1),
    mesa_id INT,
    nome_arquivo VARCHAR(255) NOT NULL,
    caminho_arquivo VARCHAR(255) NOT NULL,
    postado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mesa_id) REFERENCES mesas(id) ON DELETE CASCADE
);

CREATE TABLE resumos_sessoes (
    id INT PRIMARY KEY IDENTITY(1,1),
    mesa_id INT,
    titulo VARCHAR(255) NOT NULL,
    conteudo TEXT NOT NULL,
    data_sessao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mesa_id) REFERENCES mesas(id) ON DELETE CASCADE
);