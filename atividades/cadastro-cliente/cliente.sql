CREATE DATABASE IF NOT EXISTS sistema_clientes;
USE sistema_clientes;

CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    telefone VARCHAR(20) NOT NULL,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE clientes
ADD COLUMN cpf VARCHAR(14) NOT NULL UNIQUE AFTER email,
ADD COLUMN cep VARCHAR(9) NOT NULL AFTER telefone,
ADD COLUMN logradouro VARCHAR(100) NOT NULL AFTER cpf,
ADD COLUMN bairro VARCHAR(50) NOT NULL AFTER logradouro,
ADD COLUMN cidade VARCHAR(50) NOT NULL AFTER bairro,
ADD COLUMN estado VARCHAR(50) NOT NULL AFTER cidade,
ADD COLUMN cnpj VARCHAR(14) NULL UNIQUE AFTER cpf


INSERT INTO clientes (nome, email, cpf, cnpj, telefone, cep, logradouro, bairro, cidade, estado) VALUES
('Carlos Eduardo da Silva', 'carlos.silva@email.com', '14235896412', NULL, '11999998888', '01001000', 'Praça da Sé', 'Sé', 'São Paulo', 'SP'),
('Mariana Souza Ribeiro', 'mari.souza@gmail.com', '25814736952', NULL, '21988887777', '20040002', 'Avenida Rio Branco', 'Centro', 'Rio de Janeiro', 'RJ'),
('Tech Inovações LTDA', 'contato@techinova.com.br', '36925814785', '12345678000195', '1133334444', '04538132', 'Avenida Brigadeiro Faria Lima', 'Itaim Bibi', 'São Paulo', 'SP'),
('Roberto dos Santos', 'roberto.santos@outlook.com', '47185296311', NULL, '31977776666', '30110010', 'Avenida Afonso Pena', 'Centro', 'Belo Horizonte', 'MG'),
('Amanda Lima Oliveira', 'amanda.lima@uol.com.br', '58296314722', NULL, '71966665555', '40020010', 'Praça da Sé', 'Centro', 'Salvador', 'BA'),
('Padaria do Bairro LTDA', 'financeiro@padariabairro.com', '69314725833', '98765432000110', '2122223333', '22020001', 'Avenida Atlântica', 'Copacabana', 'Rio de Janeiro', 'RJ'),
('Bruno Costa Melo', 'bruno.costa@hotmail.com', '70425836944', NULL, '41955554444', '80010010', 'Rua XV de Novembro', 'Centro', 'Curitiba', 'PR'),
('Consultoria Alfa S/A', 'diretoria@alfaconsultoria.com', '81536947055', '45612378000122', '3134445555', '31270901', 'Avenida Antônio Carlos', 'Pampulha', 'Belo Horizonte', 'MG'),
('Juliana Ribeiro Mendes', 'ju.mendes@gmail.com', '92647058166', NULL, '13991112222', '11701000', 'Avenida Presidente Castelo Branco', 'Boqueirão', 'Praia Grande', 'SP'),
('Posto Combustível Sul', 'gerencia@postosul.com.br', '03758169277', '78945612000133', '5132221111', '90010280', 'Rua dos Andradas', 'Centro Histórico', 'Porto Alegre', 'RS');
