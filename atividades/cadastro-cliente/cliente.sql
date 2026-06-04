CREATE DATABASE IF NOT EXISTS sistema_clientes;
USE sistema_clientes;

CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    telefone VARCHAR(20) NOT NULL,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO clientes (nome, email, telefone) VALUES
('Ana Silva', 'ana.silva@email.com', '(11) 98765-4321'),
('Bruno Oliveira', 'bruno.oliveira@email.com', '(21) 97654-3210'),
('Carlos Souza', 'carlos.souza@email.com', '(31) 96543-2109'),
('Daniela Lima', 'daniela.lima@email.com', '(41) 95432-1098'),
('Eduardo Santos', 'eduardo.santos@email.com', '(51) 94321-0987'),
('Fernanda Costa', 'fernanda.costa@email.com', '(61) 93210-9876'),
('Gabriel Rodrigues', 'gabriel.rodrigues@email.com', '(71) 92109-8765'),
('Amanda Martins', 'amanda.martins@email.com', '(81) 91098-7654'),
('Igor Pereira', 'igor.pereira@email.com', '(91) 90987-6543'),
('Juliana Alves', 'juliana.alves@email.com', '(11) 99887-7665'),
('Lucas Ribeiro', 'lucas.ribeiro@email.com', '(21) 98776-6554'),
('Mariana Carvalho', 'mariana.carvalho@email.com', '(31) 97665-5443'),
('Nicolas Almeida', 'nicolas.almeida@email.com', '(41) 96554-4332'),
('Olívia Gomes', 'olivia.gomes@email.com', '(51) 95443-3221'),
('Pedro Barbosa', 'pedro.barbosa@email.com', '(61) 94332-2110'),
('Beatriz Ramos', 'beatriz.ramos@email.com', '(71) 93221-1009'),
('Rafael Melo', 'rafael.melo@email.com', '(81) 92110-0998'),
('Sofia Castro', 'sofia.castro@email.com', '(91) 91009-8887'),
('Thiago Rocha', 'thiago.rocha@email.com', '(11) 90998-7776'),
('Vanessa Teixeira', 'vanessa.teixeira@email.com', '(21) 98887-6665'),
('William Cunha', 'william.cunha@email.com', '(31) 97776-5554'),
('Yasmin Freitas', 'yasmin.freitas@email.com', '(41) 96665-4443'),
('Rodrigo Cardoso', 'rodrigo.cardoso@email.com', '(51) 95554-3332'),
('Camila Nogueira', 'camila.nogueira@email.com', '(61) 94443-2221'),
('Felipe Marques', 'felipe.marques@email.com', '(71) 93332-1110');
