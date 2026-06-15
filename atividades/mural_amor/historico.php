<?php
session_start();
require 'conexao.php';

// Bloqueia o acesso se não estiver logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.html");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// Busca os dados do parceiro
$stmt = $pdo->prepare("SELECT parceiro_id FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$usuario_atual = $stmt->fetch(PDO::FETCH_ASSOC);
$parceiro_id = $usuario_atual['parceiro_id'];

// Se não tiver parceiro conectado, manda de volta para o index se conectar
if (!$parceiro_id) {
    header("Location: index.php");
    exit;
}

// Captura os filtros da busca (se existirem)
$busca = filter_input(INPUT_GET, 'busca', FILTER_SANITIZE_SPECIAL_CHARS);
$filtro_tipo = filter_input(INPUT_GET, 'tipo', FILTER_SANITIZE_SPECIAL_CHARS);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nosso Baú de Memórias - Histórico</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@600&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-page: #fff5f5;
            --bg-card: #ffffff;
            --primary: #d32f2f;
            --primary-hover: #b71c1c;
            --text-main: #3a2020;
            --text-muted: #755b5b;
            --border-color: #ffcdd2;
            --shadow: rgba(211, 47, 47, 0.08);
        }

        [data-theme="dark"] {
            --bg-page: #1a0a0a;
            --bg-card: #2a1212;
            --primary: #e53935;
            --primary-hover: #ff5252;
            --text-main: #f5e6e6;
            --text-muted: #b39999;
            --border-color: #4a1c1c;
            --shadow: rgba(0, 0, 0, 0.4);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-page);
            color: var(--text-main);
            line-height: 1.6;
            padding-bottom: 60px;
        }

        header {
            background-color: var(--bg-card);
            border-bottom: 1px solid var(--border-color);
            padding: 15px 20px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px var(--shadow);
        }

        .header-container {
            max-width: 800px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-family: 'Dancing Script', cursive;
            font-size: 28px;
            color: var(--primary);
            text-decoration: none;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .btn-nav {
            color: var(--text-main);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }

        .btn-nav:hover { color: var(--primary); }

        main {
            max-width: 600px;
            margin: 30px auto;
            padding: 0 15px;
        }

        h1 {
            font-family: 'Dancing Script', cursive;
            font-size: 36px;
            color: var(--primary);
            margin-bottom: 20px;
            text-align: center;
        }

        /* Seção de Filtros */
        .filter-card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 15px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px var(--shadow);
        }

        .filter-form {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .search-input {
            flex: 1;
            min-width: 200px;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background-color: var(--bg-page);
            color: var(--text-main);
        }

        .select-input {
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background-color: var(--bg-page);
            color: var(--text-main);
        }

        .btn-filter {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }

        .btn-filter:hover { background-color: var(--primary-hover); }

        /* Estilo da Linha do Tempo (Timeline) */
        .timeline {
            position: relative;
            border-left: 2px solid var(--border-color);
            padding-left: 20px;
            margin-left: 10px;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 30px;
        }

        .timeline-item::before {
            content: '❤️';
            position: absolute;
            left: -31px;
            top: 2px;
            background: var(--bg-page);
            font-size: 14px;
            padding: 2px;
        }

        .post-card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 4px 12px var(--shadow);
        }

        .post-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .author-name {
            font-weight: 600;
            color: var(--primary);
            font-size: 15px;
        }

        .post-date {
            font-size: 12px;
            color: var(--text-muted);
        }

        .post-content {
            font-size: 15px;
            margin-bottom: 15px;
            white-space: pre-wrap;
        }

        .post-image {
            width: 100%;
            border-radius: 8px;
            max-height: 400px;
            object-fit: cover;
            border: 1px solid var(--border-color);
        }
    </style>
</head>
<body>

    <header>
        <div class="header-container">
            <a href="index.php" class="logo">❤️ Nosso Espaço</a>
            <div class="nav-actions">
                <a href="index.php" class="btn-nav">Mural Inicial</a>
                <a href="logout.php" class="btn-nav" style="color: var(--text-muted);">Sair</a>
            </div>
        </div>
    </header>

    <main>
        <h1>Nosso Baú de Memórias</h1>

        <div class="filter-card">
            <form method="GET" action="historico.php" class="filter-form">
                <input type="text" name="busca" class="search-input" placeholder="Pesquisar lembranças... (ex: jantar, te amo)" value="<?= htmlspecialchars($busca ?? '') ?>">
                
                <select name="tipo" class="select-input">
                    <option value="">Todos os posts</option>
                    <option value="fotos" <?= $filtro_tipo === 'fotos' ? 'selected' : '' ?>>Apenas Fotos</option>
                    <option value="textos" <?= $filtro_tipo === 'textos' ? 'selected' : '' ?>>Apenas Mensagens</option>
                </select>

                <button type="submit" class="btn-filter">Buscar</button>
            </form>
        </div>

        <div class="timeline">
            <?php
            try {
                // Base da Query SQL
                $sql = "SELECT posts.*, usuarios.nome FROM posts 
                        JOIN usuarios ON posts.usuario_id = usuarios.id 
                        WHERE (posts.usuario_id = :user_id OR posts.usuario_id = :parceiro_id)";
                
                // Adiciona filtro de busca textual se digitado
                if (!empty($busca)) {
                    $sql .= " AND posts.texto LIKE :busca";
                }

                // Adiciona filtros de tipo de arquivo
                if ($filtro_tipo === 'fotos') {
                    $sql .= " AND posts.imagem_url IS NOT NULL AND posts.imagem_url != ''";
                } elseif ($filtro_tipo === 'textos') {
                    $sql .= " AND (posts.imagem_url IS NULL OR posts.imagem_url = '')";
                }

                $sql .= " ORDER BY posts.criado_em DESC";

                $stmt = $pdo->prepare($sql);
                $params = ['user_id' => $usuario_id, 'parceiro_id' => $parceiro_id];
                
                if (!empty($busca)) {
                    $params['busca'] = "%$busca%";
                }

                $stmt->execute($params);
                $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (empty($posts)) {
                    echo '<p style="text-align: center; color: var(--text-muted); font-size: 14px; padding-top: 20px;">Nenhuma lembrança encontrada com esses filtros. 🌸</p>';
                }

                foreach ($posts as $post) {
                    $autor = ($post['usuario_id'] == $usuario_id) ? 'Você' : htmlspecialchars($post['nome']);
                    ?>
                    <div class="timeline-item">
                        <div class="post-card">
                            <div class="post-header">
                                <span class="author-name"><?= $autor ?></span>
                                <span class="post-date"><?= date('d/m/Y \à\s H:i', strtotime($post['criado_em'])) ?></span>
                            </div>
                            <?php if(!empty($post['texto'])): ?>
                                <div class="post-content"><?= nl2br(htmlspecialchars($post['texto'])) ?></div>
                            <?php endif; ?>
                            
                            <?php if (!empty($post['imagem_url'])): ?>
                                <img src="uploads/<?= htmlspecialchars($post['imagem_url']) ?>" alt="Foto do Casal" class="post-image">
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php
                }
            } catch (PDOException $e) {
                echo '<p style="color: var(--primary);">Erro ao carregar o histórico: ' . $e->getMessage() . '</p>';
            }
            ?>
        </div>
    </main>

    <script>
        // Mantém a sincronia do tema escuro escolhido no index
        const currentTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', currentTheme);
    </script>
</body>
</html>
