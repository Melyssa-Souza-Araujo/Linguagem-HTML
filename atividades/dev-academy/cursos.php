<?php
include 'db.php';
session_start();

// Bloqueio de segurança: Se não houver sessão ativa, manda de volta pro login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

// Buscar as aulas cadastradas no banco
$stmt = $pdo->query("SELECT * FROM aulas ORDER BY ordem ASC");
$aulas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>DevAcademy - Meus Cursos</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background: #f9fafb; color: #1f2937; }
        header { background: #6d28d9; color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center; }
        header h1 span { font-weight: 300; opacity: 0.8; }
        .btn-sair { background: #4c1d95; color: white; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: bold; }
        .container { max-width: 1100px; margin: 40px auto; padding: 0 20px; }
        .boas-vindas { margin-bottom: 30px; }
        .grid-aulas { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 30px; }
        .card-aula { background: white; border-radius: 12px; border: 1px solid #e9d5ff; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.02); display: flex; flex-direction: column; }
        .video-container { position: relative; padding-bottom: 56.25%; height: 0; background: #000; }
        .video-container iframe { position: absolute; top: 0; left: 0; wIdth: 100%; height: 100%; border: none; }
        .conteudo-aula { padding: 20px; flex-grow: 1; display: flex; flex-direction: column; justify-content: space-between; }
        .conteudo-aula h3 { color: #5b21b6; margin-bottom: 10px; }
        .conteudo-aula p { color: #6b7280; font-size: 14px; line-height: 1.5; }
    </style>
</head>
<body>

    <header>
        <h1>Dev<span>Academy</span></h1>
        <div style="display: flex; align-items: center; gap: 15px;">
            <?php if ($_SESSION['usuario_tipo'] === 'admin'): ?>
                <a href="admin.php" style="color: #ddd6fe; text-decoration: none; font-weight: bold;">Painel Admin</a>
            <?php endif; ?>
            <a href="logout.php" class="btn-sair">Sair</a>
        </div>
    </header>

    <div class="container">
        <div class="boas-vindas">
            <h2>Olá, <?= htmlspecialchars($_SESSION['usuario_nome']) ?>! 👋</h2>
            <p>Selecione uma aula abaixo e bons estudos no seu treinamento.</p>
        </div>

        <main class="grid-aulas">
            <?php if (count($aulas) === 0): ?>
                <p style="color: #6b7280;">Nenhuma aula cadastrada ainda pelo administrador.</p>
            <?php else: ?>
                <?php foreach ($aulas as $aula): ?>
                    <div class="card-aula">
                        <div class="video-container">
                            <!-- Converte links normais do youtube em formato embed caso necessário -->
                            <?php 
                                $url = $aula['url_video'];
                                $embed = str_replace("watch?v=", "embed/", $url);
                            ?>
                            <iframe src="<?= htmlspecialchars($embed) ?>" allowfullscreen></iframe>
                        </div>
                        <div class="conteudo-aula">
                            <div>
                                <h3><?= htmlspecialchars($aula['titulo']) ?></h3>
                                <p><?= nl2br(htmlspecialchars($aula['descricao'])) ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>
    </div>

</body>
</html>
