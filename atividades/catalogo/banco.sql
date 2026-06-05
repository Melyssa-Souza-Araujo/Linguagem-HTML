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

USE sistema_catalogo;

INSERT INTO itens (titulo, tipo, genero, descricao, foto) VALUES
-- FILMES
('A Origem', 'filme', 'Ficção Científica, Ação, Suspense', 'Um ladrão que rouba segredos corporativos por meio do uso de tecnologia de compartilhamento de sonhos recebe a tarefa inversa de plantar uma ideia na mente de um herdeiro.', 'padrao.png'),
('Interestelar', 'filme', 'Ficção Científica, Drama, Aventura', 'Uma equipe de exploradores viaja através de um buraco de minhoca no espaço na tentativa de garantir a sobrevivência da humanidade.', 'padrao.png'),
('O Labirinto do Fauno', 'filme', 'Fantasia, Drama, Guerra', 'No norte da Espanha de 1944, a jovem enteada de um sádico oficial do exército escapa para um mundo de fantasia misterioso, mas cativante.', 'padrao.png'),
('Tudo em Todo o Lugar ao Mesmo Tempo', 'filme', 'Ação, Comédia, Ficção Científica', 'Uma imigrante chinesa idosa é envolvida em uma aventura insana, onde ela sozinha deve salvar o mundo explorando outros universos que se conectam com as vidas que ela poderia ter vivido.', 'padrao.png'),
('O Iluminado', 'filme', 'Terror, Suspense, Psicológico', 'Uma família passa o inverno em um hotel isolado onde uma presença espiritual sinistra influencia o pai a se tornar violento, enquanto seu filho tem visões psíquicas assustadoras.', 'padrao.png'),

-- LIVROS
('Duna', 'livro', 'Ficção Científica, Épico, Literatura', 'Ambientado num futuro distante no planeta desértico de Arrakis, a história acompanha o jovem Paul Atreides cuja família aceita o controle do planeta, a única fonte da substância mais valiosa do universo.', 'padrao.png'),
('O Hobbit', 'livro', 'Fantasia, Aventura, Infanto-Juvenil', 'Bilbo Bolseiro é um hobbit que vive uma vida pacata até ser recrutado pelo mago Gandalf e um grupo de anões para resgatar um tesouro guardado pelo temível dragão Smaug.', 'padrao.png'),
('Sherlock Holmes: Um Estudo em Vermelho', 'livro', 'Mistério, Policial, Romance', 'A obra que apresenta o detetive mais famoso do mundo e seu fiel parceiro, Dr. Watson, enquanto investigam um assassinato misterioso em uma casa abandonada em Londres.', 'padrao.png'),
('Corte de Espinhos e Rosas', 'livro', 'Fantasia, Romance, Drama', 'Após matar um lobo na floresta, a jovem caçadora Feyre é sequestrada por uma criatura bestial e levada para uma terra mágica e traiçoeira que ela só conhecia por meio de lendas.', 'padrao.png'),
('O Iluminado (Livro)', 'livro', 'Terror, Suspense, Sobrenatural', 'O clássico livro de Stephen King que inspirou o cinema, detalhando a lenta descida de Jack Torrance à loucura nas profundezas do isolado Hotel Overlook.', 'padrao.png'),

-- JOGOS
('The Witcher 3: Wild Hunt', 'jogo', 'RPG, Fantasia, Mundo Aberto', 'O caçador de monstros Geralt de Rívia precisa encontrar a Criança da Profecia em um vasto mundo aberto cheio de cidades mercantis, ilhas vikings e passagens montanhosas perigosas.', 'padrao.png'),
('Elden Ring', 'jogo', 'RPG, Soulslike, Fantasia Escura', 'Levante-se, Maculado, e seja guiado pela graça para portar o poder do Anel Prístino e se tornar um Lorde Prístino nas Terras Intermédias.', 'padrao.png'),
('The Last of Us Part I', 'jogo', 'Ação, Aventura, Sobrevivência', 'Em uma civilização devastada, onde infectados e sobreviventes cruéis correm soltos, Joel, um contrabandista amargurado, é contratado para tirar Ellie, uma garota de 14 anos, de uma zona de quarentena militar.', 'padrao.png'),
('Hollow Knight', 'jogo', 'Metroidvania, Plataforma, Indie', 'Forje seu próprio caminho em Hollow Knight! Uma aventura épica e de ação através de um vasto reino arruinado de insetos e heróis abaixo da superfície.', 'padrao.png'),
('Resident Evil 4', 'jogo', 'Terror, Ação, Sobrevivência', 'Seis anos após o desastre biológico em Raccoon City, o agente Leon S. Kennedy é enviado para resgatar a filha raptada do presidente dos EUA em uma isolada vila europeia onde algo está terrivelmente errado.', 'padrao.png');
