<?php
require_once 'conexao.php';
verificarLogin(); // Garante que apenas usuários logados vejam o catálogo

// 1. Buscar um banner de destaque dinâmico (último filme ou série cadastrado)
$stmt_destaque = $pdo->query("SELECT * FROM midias ORDER BY id DESC LIMIT 1");
$destaque = $stmt_destaque->fetch(PDO::FETCH_ASSOC);

// 2. Buscar todos os gêneros cadastrados que possuem mídias vinculadas
$stmt_generos = $pdo->query("
    SELECT DISTINCT g.* FROM generos g 
    INNER JOIN midia_genero mg ON g.id = mg.genero_id 
    ORDER BY g.nome ASC
");
$generos_ativos = $stmt_generos->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo - Pobreflix</title>
    <style>
        :root {
            --bg-color: #060913;
            --bg-card: #0d1222;
            --accent-color: #0084ff;
            --text-color: #ffffff;
            --text-muted: #8a99ad;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: sans-serif; }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            overflow-x: hidden;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 4%;
            position: absolute;
            width: 100%;
            z-index: 10;
            background: linear-gradient(to bottom, rgba(6,9,19,0.9), transparent);
        }

        .logo { font-size: 24px; font-weight: bold; text-transform: uppercase; }
        .logo span { color: var(--accent-color); }
        
        .nav-links { display: flex; align-items: center; gap: 20px; }
        .nav-links a { color: white; text-decoration: none; font-weight: bold; font-size: 14px; }
        .btn-admin { background-color: var(--accent-color); padding: 8px 15px; border-radius: 4px; }
        .btn-logout { color: #e74c3c !important; }

        /* Banner */
        .banner {
            height: 65vh;
            background: linear-gradient(to top, var(--bg-color), transparent), 
                        url('<?= $destaque['imagem_capa'] ?? "https://via.placeholder.com/1920x1080"; ?>') center/cover no-repeat;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 0 4%;
        }

        .banner-content { max-width: 600px; }
        .banner-title { font-size: 3rem; margin-bottom: 15px; font-weight: 800; }
        .btn-assistir {
            display: inline-block;
            background-color: var(--accent-color);
            color: white;
            padding: 12px 35px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: bold;
            margin-top: 20px;
            transition: 0.3s;
        }

        /* Container de Gêneros */
        .conteudo-container { padding: 20px 4%; }
        .categoria-titulo {
            font-size: 1.4rem;
            margin: 40px 0 15px 0;
            border-left: 4px solid var(--accent-color);
            padding-left: 10px;
        }

        /* Carrossel de Cards */
        .carrossel {
            display: flex;
            gap: 15px;
            overflow-x: auto;
            padding-bottom: 15px;
        }
        .carrossel::-webkit-scrollbar { height: 8px; }
        .carrossel::-webkit-scrollbar-thumb { background: #161f38; border-radius: 4px; }

        .card {
            min-width: 180px;
            max-width: 180px;
            background-color: var(--bg-card);
            border-radius: 8px;
            overflow: hidden;
            position: relative;
            cursor: pointer;
            transition: 0.3s;
        }
        .card:hover { transform: scale(1.05); z-index: 2; }
        .card img { width: 100%; height: 260px; object-fit: cover; }
        .card-info { padding: 10px; }
        .card-titulo { font-size: 14px; font-weight: bold; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .card-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background-color: var(--accent-color);
            color: white;
            font-size: 10px;
            font-weight: bold;
            padding: 3px 6px;
            border-radius: 4px;
            text-transform: uppercase;
        }
    </style>
</head>
<body>

    <header>
        <div class="logo">POBRE<span>FLIX</span></div>
        <div class="nav-links">
            <span>Olá, <?= htmlspecialchars($_SESSION['usuario_nome']); ?></span>
            
            <?php if ($_SESSION['usuario_nivel'] === 'admin'): ?>
                <a href="admin.php" class="btn-admin">Painel Admin</a>
            <?php endif; ?>
            
            <a href="logout.php" class="btn-logout">Sair</a>
        </div>
    </header>

    <?php if ($destaque): ?>
    <section class="banner">
        <div class="banner-content">
            <h1 class="banner-title"><?= $destaque['titulo']; ?></h1>
            <p style="color: var(--text-muted);"><?= mb_strimwidth($destaque['sinopse'], 0, 180, "..."); ?></p>
            <a href="midia.php?id=<?= $destaque['id']; ?>" class="btn-assistir">► ASSISTIR <?= $destaque['tipo'] === 'serie' ? 'SÉRIE' : 'FILME'; ?></a>
        </div>
    </section>
    <?php endif; ?>

    <main class="conteudo-container">
        <?php foreach ($generos_ativos as $genero): ?>
            <h2 class="categoria-titulo"><?= $genero['nome']; ?></h2>
            <div class="carrossel">
                <?php
                // Busca todas as mídias associadas a este gênero específico
                $stmt_midias = $pdo->prepare("
                    SELECT m.* FROM midias m
                    INNER JOIN midia_genero mg ON m.id = mg.midia_id
                    WHERE mg.genero_id = ?
                    ORDER BY m.id DESC
                ");
                $stmt_midias->execute([$genero['id']]);
                
                while ($item = $stmt_midias->fetch(PDO::FETCH_ASSOC)):
                ?>
                    <div class="card" onclick="location.href='midia.php?id=<?= $item['id']; ?>'">
                        <span class="card-badge"><?= $item['tipo']; ?></span>
                        <img src="<?= $item['imagem_capa']; ?>" alt="<?= $item['titulo']; ?>">
                        <div class="card-info">
                            <div class="card-titulo"><?= $item['titulo']; ?></div>
                            <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;"><?= $item['ano']; ?> • ★ <?= $item['avaliacao']; ?></div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endforeach; ?>
    </main>

</body>
</html>
