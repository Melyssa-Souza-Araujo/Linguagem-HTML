<?php
include 'conexao.php';

if (!isset($_GET['id'])) { header("Location: cadastro.php"); exit; }
$id = $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM partidas WHERE id = ?");
$stmt->execute([$id]);
$partida = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$partida) { header("Location: cadastro.php"); exit; }

if (isset($_POST['atualizar_partida'])) {
    $id_casa = $_POST['id_casa'];
    $id_fora = $_POST['id_fora'];
    $p_casa = $_POST['pontos_casa'];
    $p_fora = $_POST['pontos_fora'];
    $genero = $_POST['genero'];

    if ($id_casa != $id_fora) {
        // Query de UPDATE sem youtube_url
        $stmt = $pdo->prepare("UPDATE partidas SET id_casa = ?, id_fora = ?, pontos_casa = ?, pontos_fora = ?, genero = ? WHERE id = ?");
        $stmt->execute([$id_casa, $id_fora, $p_casa, $p_fora, $genero, $id]);
        header("Location: cadastro.php?sucesso=editado");
        exit;
    }
}

$paises = $pdo->query("SELECT * FROM paises ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Partida</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; background-color: #f4f4f9; }
        .box { background: white; padding: 20px; border-radius: 8px; max-width: 500px; margin: 0 auto; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        label { display: block; margin-top: 10px; font-weight: bold; }
        select, input { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { background: #0056b3; color: white; border: none; padding: 12px; width: 100%; margin-top: 15px; border-radius: 4px; font-weight: bold; cursor: pointer; }
        a { display: block; text-align: center; margin-top: 15px; color: #555; text-decoration: none; }
    </style>
</head>
<body>
    <div class="box">
        <h2>Editar Dados da Partida</h2>
        <form method="POST">
            <label>Categoria:</label>
            <select name="genero">
                <option value="F" <?=$partida['genero'] == 'F' ? 'selected' : ''?>>Feminina</option>
                <option value="M" <?=$partida['genero'] == 'M' ? 'selected' : ''?>>Masculina</option>
            </select>

            <label>Time Casa:</label>
            <select name="id_casa" required>
                <?php foreach($paises as $p): ?>
                    <option value="<?=$p['id']?>" <?=$p['id'] == $partida['id_casa'] ? 'selected' : ''?>><?=$p['nome']?></option>
                <?php endforeach; ?>
            </select>

            <label>Time Visita:</label>
            <select name="id_fora" required>
                <?php foreach($paises as $p): ?>
                    <option value="<?=$p['id']?>" <?=$p['id'] == $partida['id_fora'] ? 'selected' : ''?>><?=$p['nome']?></option>
                <?php endforeach; ?>
            </select>

            <label>Sets Casa:</label>
            <input type="number" name="pontos_casa" min="0" max="3" value="<?=$partida['pontos_casa']?>" required>

            <label>Sets Visita:</label>
            <input type="number" name="pontos_fora" min="0" max="3" value="<?=$partida['pontos_fora']?>" required>

            <button type="submit" name="atualizar_partida">Salvar Alterações</button>
        </form>
        <a href="cadastro.php">Cancelar</a>
    </div>
</body>
</html>
