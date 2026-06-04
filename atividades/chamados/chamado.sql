CREATE DATABASE IF NOT EXISTS sistema_chamados;
USE sistema_chamados;

-- 1. TABELA DE USUÁRIOS
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL, -- Guardará a senha criptografada
    perfil ENUM('cliente', 'tecnico') NOT NULL -- Define o que o usuário é
);

-- 2. TABELA DE CHAMADOS
CREATE TABLE chamados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL, -- Quem abriu o chamado
    tecnico_id INT DEFAULT NULL, -- Qual técnico assumiu (começa vazio)
    titulo VARCHAR(150) NOT NULL,
    descricao TEXT NOT NULL,
    status ENUM('aberto', 'em_atendimento', 'resolvido') DEFAULT 'aberto',
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES usuarios(id),
    FOREIGN KEY (tecnico_id) REFERENCES usuarios(id)
);

-- 3. TABELA DE MENSAGENS (O chat do chamado)
CREATE TABLE mensagens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chamado_id INT NOT NULL, -- A qual chamado essa mensagem pertence
    usuario_id INT NOT NULL, -- Quem enviou a mensagem (pode ser o cliente ou o técnico)
    mensagem TEXT NOT NULL,
    data_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (chamado_id) REFERENCES chamados(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);
