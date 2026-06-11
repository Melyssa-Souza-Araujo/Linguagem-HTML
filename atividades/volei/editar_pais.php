<?php
include 'conexao.php';

if (!isset($_GET['id'])) { header("Location: cadastro.php"); exit; }
$id = $_GET['id'];

// Buscar dados do país atual
$stmt = $pdo->prepare("SELECT * FROM paises WHERE id = ?");
$stmt->execute([$id]);
$pais = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$pais) { header("Location: cadastro.php"); exit; }

// Processar edição
if (isset($_POST['atualizar_pais'])) {
    $nome = trim($_POST['nome_pais']);
    if (!empty($nome)) {
        $stmt = $pdo->prepare("UPDATE paises SET nome = ? WHERE id = ?");
        $stmt->execute([$nome, $id]);
        header("Location: cadastro.php?sucesso=editado");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar País</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 50px; background-color: #f4f4f9; }
        .box { background: white; padding: 20px; border-radius: 8px; max-width: 500px; margin: 0 auto; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        input { width: 100%; padding: 10px; margin: 10px 0; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        button { background: #0056b3; color: white; border: none; padding: 12px; width: 100%; border-radius: 4px; font-weight: bold; cursor: pointer; }
        a { display: block; text-align: center; margin-top: 15px; color: #555; text-decoration: none; }
    </style>
</head>
<body>
    <div class="box">
        <h2>Editar Nome do País</h2>
        <form method="POST">
            <label>Nome do País:</label>
            <input type="text" name="nome_pais" value="<?=$pais['nome']?>" required>
            <button type="submit" name="atualizar_pais">Atualizar Nome</button>
        </form>
        <a href="cadastro.php">Cancelar e Voltar</a>
    </div>
</body>
</html>
