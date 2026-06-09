<?php
include 'db.php';
session_start();

// Segurança dupla: Só entra se for admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Cadastrar Nova Aula
if (isset($_POST['cadastrar_aula'])) {
    $titulo = trim($_POST['titulo']);
    $desc = trim($_POST['descricao']);
    $url = trim($_POST['url_video']);
    $ordem = (int)$_POST['ordem'];

    $stmt = $pdo->prepare("INSERT INTO aulas (titulo, descricao, url_video, ordem) VALUES (?, ?, ?, ?)");
    $stmt->execute([$titulo, $desc, $url, $ordem]);
}

// Excluir Aula
if (isset($_GET['excluir'])) {
    $id = (int)$_GET['excluir'];
    $stmt = $pdo->prepare("DELETE FROM aulas WHERE Id = ?");
    $stmt->execute([$id]);
    header("Location: admin.php");
    exit;
}

// Listar todas
$stmt = $pdo->query("SELECT * FROM aulas ORDER BY ordem ASC");
$aulas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Painel Admin - DevAcademy</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background: #f3f4f6; color: #111827; }
        header { background: #4c1d95; color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center; }
        .container { max-width: 1000px; margin: 40px auto; padding: 0 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 40px; }
        @media(max-width: 768px) { .container { grid-template-columns: 1fr; } }
        .box { background: white; padding: 30px; border-radius: 12px; border: 1px solid #ddd6fe; box-shadow: 0 4px 12px rgba(0,0,0,0.02); }
        h2 { color: #5b21b6; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #4c1d95; }
        input, textarea { wIdth: 100%; padding: 10px; border: 1px solid #ddd6fe; border-radius: 6px; }
        button { background: #7c3aed; color: white; border: none; padding: 12px 20px; border-radius: 6px; font-weight: bold; cursor: pointer; }
        button:hover { background: #6d28d9; }
        .lista-itens { list-style: none; }
        .item-aula { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px solid #f3f4f6; }
        .btn-excluir { color: #ef4444; text-decoration: none; font-size: 14px; font-weight: bold; }
    </style>
</head>
<body>

    <header>
        <h1>Painel do Administrador</h1>
        <div>
            <a href="cursos.php" style="color: white; margin-right: 20px; text-decoration: none;">Ver Site</a>
            <a href="logout.php" style="background: #7c3aed; padding: 8px 16px; border-radius: 6px; color: white; text-decoration: none;">Sair</a>
        </div>
    </header>

    <div class="container">
        <!-- Formulário de inserção -->
        <div class="box">
            <h2>Publicar Nova Aula</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Título da Aula</label>
                    <input type="text" name="titulo" required placeholder="Ex: Introdução ao PHP">
                </div>
                <div class="form-group">
                    <label>Link do Vídeo (YouTube)</label>
                    <input type="url" name="url_video" required placeholder="Ex: https://www.youtube.com/watch?v=XYZ123">
                </div>
                <div class="form-group">
                    <label>Ordem de Exibição</label>
                    <input type="number" name="ordem" value="1" required>
                </div>
                <div class="form-group">
                    <label>Descrição / Resumo</label>
                    <textarea name="descricao" rows="4" required placeholder="O que o aluno aprenderá nessa aula..."></textarea>
                </div>
                <button type="submit" name="cadastrar_aula">Salvar no Banco</button>
            </form>
        </div>

        <!-- Listagem e controle de exclusão -->
        <div class="box">
            <h2>Aulas Ativas (<?= count($aulas) ?>)</h2>
            <ul class="lista-itens">
                <?php foreach ($aulas as $aula): ?>
                    <li class="item-aula">
                        <div>
                            <strong>[#<?= $aula['ordem'] ?>] <?= htmlspecialchars($aula['titulo']) ?></strong>
                        </div>
                        <a href="admin.php?excluir=<?= $aula['id'] ?>" class="btn-excluir" onclick="return confirm('Tem certeza que deseja apagar essa aula?')">Excluir</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

</body>
</html>
