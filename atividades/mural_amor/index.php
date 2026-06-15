<?php
session_start();
require 'conexao.php';

// Bloqueia o acesso direto se o usuário não estiver logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.html");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// Busca os dados do usuário atual para saber se ele já tem um parceiro
$stmt = $pdo->prepare("SELECT parceiro_id FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$usuario_atual = $stmt->fetch(PDO::FETCH_ASSOC);
$parceiro_id = $usuario_atual['parceiro_id'];

// Se NÃO tiver parceiro, exibe a tela de busca e conexão
if (!$parceiro_id): 
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conectar com seu Amor</title>
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@600&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #fff5f5; color: #3a2020; padding: 40px 20px; display: flex; justify-content: center; }
        .connect-container { background: white; padding: 30px; border-radius: 20px; box-shadow: 0 4px 15px rgba(211,47,47,0.08); width: 100%; max-width: 450px; text-align: center; }
        h1 { font-family: 'Dancing Script', cursive; color: #d32f2f; font-size: 36px; margin-bottom: 10px; }
        p { color: #755b5b; font-size: 14px; margin-bottom: 25px; }
        .user-list { list-style: none; padding: 0; text-align: left; }
        .user-item { display: flex; justify-content: space-between; align-items: center; padding: 12px; border-bottom: 1px solid #ffcdd2; }
        .user-name { font-weight: 600; font-size: 15px; }
        .btn-connect { background-color: #d32f2f; color: white; border: none; padding: 8px 15px; border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 600; text-decoration: none;}
        .btn-connect:hover { background-color: #b71c1c; }
        .logout-link { display: inline-block; margin-top: 20px; color: #755b5b; text-decoration: none; font-size: 13px; }
    </style>
</head>
<body>
    <div class="connect-container">
        <h1>❤️ Quase lá...</h1>
        <p>Para ver o mural, conecte-se com o seu amor abaixo:</p>
        
        <ul class="user-list">
            <?php
            // Lista todos os usuários cadastrados (exceto você mesmo e quem já tem parceiro)
            $stmt_users = $pdo->prepare("SELECT id, nome FROM usuarios WHERE id != ? AND parceiro_id IS NULL");
            $stmt_users->execute([$usuario_id]);
            $usuarios = $stmt_users->fetchAll(PDO::FETCH_ASSOC);

            if (empty($usuarios)) {
                echo '<p style="text-align:center; color:#755b5b;">Seu amor ainda não se cadastrou? Peça para ele(a) criar uma conta!</p>';
            } else {
                foreach ($usuarios as $user) {
                    echo '<li class="user-item">';
                    echo '  <span class="user-name">' . htmlspecialchars($user['nome']) . '</span>';
                    echo '  <a href="conectar.php?id=' . $user['id'] . '" class="btn-connect">Conectar</a>';
                    echo '</li>';
                }
            }
            ?>
        </ul>
        <a href="logout.php" class="logout-link">Sair da Conta</a>
    </div>
</body>
</html>
<?php 
exit; // Interrompe o arquivo para não mostrar o mural antes da conexão
endif; 

// -------------------------------------------------------------------------
// SE CHEGOU AQUI, SIGNIFICA QUE JÁ ESTÁ CONECTADO! EXIBE O MURAL NORMALMENTE
// -------------------------------------------------------------------------
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nosso Mural de Amor - Dia dos Namorados</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@600&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-page: #fff5f5;
            --bg-card: #ffffff;
            --primary: #d32f2f;
            --primary-hover: #b71c1c;
            --accent: #ff8a80;
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
            --accent: #ff8a80;
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

        .theme-toggle {
            background: none;
            border: 1px solid var(--border-color);
            color: var(--text-main);
            padding: 8px 12px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .theme-toggle:hover {
            background-color: var(--bg-page);
        }

        .btn-logout {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 14px;
        }

        .btn-logout:hover {
            color: var(--primary);
        }

        main {
            max-width: 600px;
            margin: 30px auto;
            padding: 0 15px;
        }

        .publish-card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px var(--shadow);
        }

        .publish-card h3 {
            margin-bottom: 15px;
            font-size: 18px;
            color: var(--primary);
        }

        .form-group {
            margin-bottom: 15px;
        }

        textarea {
            width: 100%;
            min-height: 100px;
            background-color: var(--bg-page);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 12px;
            color: var(--text-main);
            font-family: inherit;
            resize: vertical;
            font-size: 14px;
        }

        textarea:focus {
            outline: none;
            border-color: var(--primary);
        }

        .file-input-wrapper {
            position: relative;
            display: inline-block;
        }

        .btn-secondary {
            background-color: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
        }

        .btn-secondary:hover {
            background-color: var(--primary);
            color: #ffffff;
        }

        input[type="file"] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            float: right;
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
        }

        .publish-actions::after {
            content: "";
            clear: both;
            display: table;
        }

        .feed-title {
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            margin-bottom: 15px;
            font-weight: 600;
        }

        .post-card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px var(--shadow);
            position: relative;
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
            margin-top: 5px;
            border: 1px solid var(--border-color);
        }
    </style>
</head>
<body>

    <header>
        <div class="header-container">
            <a href="#" class="logo">❤️ Nosso Espaço</a>
            <div class="nav-actions">
                <button class="theme-toggle" id="themeBtn">
                    <span id="themeIcon">🌙</span> <span id="themeText">Modo Escuro</span>
                </button>
                <a href="logout.php" class="btn-logout">Sair</a>
            </div>
        </div>
    </header>

    <main>
        <div class="publish-card">
            <h3>Compartilhe um momento fofo...</h3>
            <form id="postForm" action="publicar.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <textarea name="texto" placeholder="Escreva um bilhete de amor ou conte sobre o nosso dia hoje..."></textarea>
                </div>
                <div class="publish-actions">
                    <div class="file-input-wrapper">
                        <button type="button" class="btn-secondary">📸 Adicionar Foto</button>
                        <input type="file" name="imagem" accept="image/*" id="imageInput">
                    </div>
                    <button type="submit" class="btn-primary">Publicar</button>
                </div>
                <div id="fileSelected" style="font-size: 12px; margin-top: 5px; color: var(--text-muted);"></div>
            </form>
        </div>

        <h2 class="feed-title">Nossas Memórias</h2>
        
        <div id="feedContainer">
            <?php
            try {
                // AGORA O FILTRO SÓ TRAZ POSTS SEUS OU DO SEU PARCEIRO CONECTADO
                $stmt = $pdo->prepare("
                    SELECT posts.*, usuarios.nome 
                    FROM posts 
                    JOIN usuarios ON posts.usuario_id = usuarios.id 
                    WHERE posts.usuario_id = ? OR posts.usuario_id = ?
                    ORDER BY posts.criado_em DESC
                ");
                $stmt->execute([$usuario_id, $parceiro_id]);
                $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (empty($posts)) {
                    echo '<p style="text-align: center; color: var(--text-muted); font-size: 14px; margin-top: 20px;">Nenhuma memória publicada ainda. Escreva algo lindo acima! ✨</p>';
                }

                foreach ($posts as $post) {
                    $autor = ($post['usuario_id'] == $usuario_id) ? 'Você' : htmlspecialchars($post['nome']);

                    echo '<div class="post-card">';
                    echo '  <div class="post-header">';
                    echo '    <span class="author-name">' . $autor . '</span>';
                    echo '    <span class="post-date">' . date('d/m/Y H:i', strtotime($post['criado_em'])) . '</span>';
                    echo '  </div>';
                    echo '  <div class="post-content">' . nl2br(htmlspecialchars($post['texto'])) . '</div>';
                    
                    if (!empty($post['imagem_url'])) {
                        echo '  <img src="uploads/' . htmlspecialchars($post['imagem_url']) . '" alt="Foto do Casal" class="post-image">';
                    }
                    echo '</div>';
                }
            } catch (PDOException $e) {
                echo '<p style="color: var(--primary);">Erro ao carregar o feed: ' . $e->getMessage() . '</p>';
            }
            ?>
        </div>
    </main>

    <script>
        const themeBtn = document.getElementById('themeBtn');
        const themeIcon = document.getElementById('themeIcon');
        const themeText = document.getElementById('themeText');
        
        const currentTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', currentTheme);
        updateThemeUI(currentTheme);

        themeBtn.addEventListener('click', () => {
            let theme = document.documentElement.getAttribute('data-theme');
            let newTheme = theme === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeUI(newTheme);
        });

        function updateThemeUI(theme) {
            if (theme === 'dark') {
                themeIcon.textContent = '☀️';
                themeText.textContent = 'Modo Claro';
            } else {
                themeIcon.textContent = '🌙';
                themeText.textContent = 'Modo Escuro';
            }
        }

        const imageInput = document.getElementById('imageInput');
        const fileSelected = document.getElementById('fileSelected');
        imageInput.addEventListener('change', (e) => {
            if(e.target.files.length > 0) {
                fileSelected.textContent = "Imagem selecionada: " + e.target.files[0].name;
            } else {
                fileSelected.textContent = "";
            }
        });
    </script>
</body>
</html>
