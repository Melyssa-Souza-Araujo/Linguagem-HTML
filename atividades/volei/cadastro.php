<?php
// Ativa a exibição de erros na tela para ajudar no diagnóstico
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'conexao.php';

// Processar Cadastro de País
if (isset($_POST['cadastrar_pais'])) {
    $nome = trim($_POST['nome_pais']);
    if (!empty($nome)) {
        $stmt = $pdo->prepare("INSERT INTO paises (nome) VALUES (?)");
        $stmt->execute([$nome]);
        echo "<script>alert('País cadastrado com sucesso!'); window.location.href='cadastro.php';</script>";
        exit;
    }
}

// Processar Cadastro de Partida (Com a inclusão do Gênero)
if (isset($_POST['cadastrar_partida'])) {
    $id_casa = $_POST['id_casa'];
    $id_fora = $_POST['id_fora'];
    $p_casa = $_POST['pontos_casa'];
    $p_fora = $_POST['pontos_fora'];
    $url = trim($_POST['youtube_url']);
    $genero = $_POST['genero']; // Captura o valor do novo campo

    if ($id_casa == $id_fora) {
        echo "<script>alert('Erro: Um país não pode jogar contra ele mesmo!'); window.location.href='cadastro.php';</script>";
        exit;
    }

    // Query atualizada incluindo o campo 'genero'
    $stmt = $pdo->prepare("INSERT INTO partidas (id_casa, id_fora, pontos_casa, pontos_fora, youtube_url, genero) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$id_casa, $id_fora, $p_casa, $p_fora, $url, $genero]);
    
    echo "<script>alert('Partida registrada com sucesso!'); window.location.href='cadastro.php';</script>";
    exit;
}

// Buscar todos os países cadastrados para listar nos selects
$paises = $pdo->query("SELECT * FROM paises ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciador VNL - Cadastro</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; background-color: #f4f4f9; color: #333; }
        .box { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); max-width: 600px; margin-left: auto; margin-right: auto; }
        label { display: block; margin-top: 15px; font-weight: bold; }
        input, select { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { background: #0056b3; color: white; border: none; padding: 12px 20px; margin-top: 20px; border-radius: 4px; cursor: pointer; font-weight: bold; width: 100%; }
        button:hover { background: #004085; }
        .voltar { display: block; text-align: center; margin-bottom: 25px; color: #0056b3; text-decoration: none; font-weight: bold; }
        h2 { margin-top: 0; color: #0056b3; border-bottom: 2px solid #0056b3; padding-bottom: 8px; }
        .radio-group { margin-top: 8px; display: flex; gap: 20px; }
        .radio-group label { display: inline; font-weight: normal; margin-top: 0; cursor: pointer; }
        .radio-group input { width: auto; margin-top: 0; margin-right: 5px; }
    </style>
</head>
<body>

    <a href="index.php" class="voltar">← Ver Tabela de Classificação Geral</a>

    <div class="box">
        <h2>Cadastrar Novo País</h2>
        <form method="POST" action="cadastro.php">
            <label for="nome_pais">Nome do País:</label>
            <input type="text" id="nome_pais" name="nome_pais" required placeholder="Ex: Brasil">
            <button type="submit" name="cadastrar_pais">Salvar País</button>
        </form>
    </div>

    <div class="box">
        <h2>Registrar Nova Partida (Resultado)</h2>
        <form method="POST" action="cadastro.php">
            
            <label>Categoria do Torneio:</label>
            <div class="radio-group">
                <label><input type="radio" name="genero" value="F" checked> VNL Feminina</label>
                <label><input type="radio" name="genero" value="M"> VNL Masculina</label>
            </div>

            <label for="id_casa">País Mandante (Casa):</label>
            <select id="id_casa" name="id_casa" required>
                <option value="">Selecione o time da casa...</option>
                <?php foreach($paises as $p): ?>
                    <option value="<?php echo $p['id']; ?>"><?php echo $p['nome']; ?></option>
                <?php endforeach; ?>
            </select>

            <label for="id_fora">País Visitante (Fora):</label>
            <select id="id_fora" name="id_fora" required>
                <option value="">Selecione o time visitante...</option>
                <?php foreach($paises as $p): ?>
                    <option value="<?php echo $p['id']; ?>"><?php echo $p['nome']; ?></option>
                <?php endforeach; ?>
            </select>

            <label for="pontos_casa">Placar do Mandante (Sets ganhos):</label>
            <input type="number" id="pontos_casa" name="pontos_casa" min="0" max="3" required placeholder="0 a 3">

            <label for="pontos_fora">Placar do Visitante (Sets ganhos):</label>
            <input type="number" id="pontos_fora" name="pontos_fora" min="0" max="3" required placeholder="0 a 3">

            <label for="youtube_url">Link da Partida no YouTube:</label>
            <input type="url" id="youtube_url" name="youtube_url" placeholder="https://www.youtube.com/watch?v=...">

            <button type="submit" name="cadastrar_partida">Registrar Partida</button>
        </form>
    </div>

</body>
</html>
