<?php
include 'conexao.php';

// Processar Cadastro de País
if (isset($_POST['cadastrar_pais'])) {
    $nome = $_POST['nome_pais'];
    $stmt = $pdo->prepare("INSERT INTO paises (nome) VALUES (?)");
    $stmt->execute([$nome]);
    echo "<script>alert('País cadastrado!'); window.location.href='cadastro.php';</script>";
}

// Processar Cadastro de Partida
if (isset($_POST['cadastrar_partida'])) {
    $id_casa = $_POST['id_casa'];
    $id_fora = $_POST['id_fora'];
    $p_casa = $_POST['pontos_casa'];
    $p_fora = $_POST['pontos_fora'];
    $url = $_POST['youtube_url'];

    $stmt = $pdo->prepare("INSERT INTO partidas (id_casa, id_fora, pontos_casa, pontos_fora, youtube_url) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$id_casa, $id_fora, $p_casa, $p_fora, $url]);
    echo "<script>alert('Partida registrada!'); window.location.href='cadastro.php';</script>";
}

// Buscar todos os países para os menus de seleção
$paises = $pdo->query("SELECT * FROM paises ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciador VNL</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; background-color: #f4f4f9; color: #333; }
        .box { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        label { display: block; margin-top: 10px; font-weight: bold; }
        input, select { width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { background: #0056b3; color: white; border: none; padding: 10px 15px; margin-top: 15px; border-radius: 4px; cursor: pointer; font-weight: bold; }
        button:hover { background: #004085; }
        a { display: inline-block; margin-bottom: 15px; color: #0056b3; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

    <a href="index.php">← Ver Tabela de Classificação</a>

    <div class="box">
        <h2>Cadastrar Novo País</h2>
        <form method="POST">
            <label>Nome do País:</label>
            <input type="text" name="nome_pais" required placeholder="Ex: Brasil">
            <button type="submit" name="cadastrar_pais">Salvar País</button>
        </form>
    </div>

    <div class="box">
        <h2>Registrar Nova Partida (Resultado)</h2>
        <form method="POST">
            <label>País Mandante (Casa):</label>
            <select name="id_casa" required>
                <option value="">Selecione...</option>
                <?php foreach($paises as $p): ?>
                    <option value="<?=$p['id']?>"><?=$p['nome']?></option>
                <?php endphp ?>
            </select>

            <label>País Visitante (Fora):</label>
            <select name="id_fora" required>
                <option value="">Selecione...</option>
                <?php foreach($paises as $p): ?>
                    <option value="<?=$p['id']?>"><?=$p['nome']?></option>
                <?php endphp ?>
            </select>

            <label>Placar do Mandante (Sets ganhos):</label>
            <input type="number" name="pontos_casa" min="0" max="3" required text-align="center">

            <label>Placar do Visitante (Sets ganhos):</label>
            <input type="number" name="pontos_fora" min="0" max="3" required text-align="center">

            <label>Link da Partida no YouTube:</label>
            <input type="url" name="youtube_url" placeholder="https://www.youtube.com/watch?v=...">

            <button type="submit" name="cadastrar_partida">Registrar Partida</button>
        </form>
    </div>

</body>
</html>
  
