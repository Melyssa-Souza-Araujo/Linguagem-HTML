<?php
session_start();
include 'conexao.php';
/** @var PDO $pdo */

// Proteção para administradores
if (!isset($_SESSION['logado']) || $_SESSION['usuario_nivel'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$msg_sucesso = "";
$msg_erro = "";

// 1. Cadastro de Países
if (isset($_POST['cadastrar_pais'])) {
    $nome = trim($_POST['nome_pais']);
    $sigla = strtolower(trim($_POST['sigla_pais']));

    if (!empty($nome) && !empty($sigla)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO paises (nome, sigla) VALUES (?, ?)");
            $stmt->execute([$nome, $sigla]);
            $msg_sucesso = "País cadastrado com sucesso!";
        } catch (PDOException $e) {
            $msg_erro = "Erro ao cadastrar país: " . $e->getMessage();
        }
    }
}

// 2. Cadastro de Partidas com Seleção de Fase
if (isset($_POST['cadastrar_partida'])) {
    $id_casa = (int)$_POST['id_casa'];
    $id_fora = (int)$_POST['id_fora'];
    $pts_casa = (int)$_POST['pts_casa'];
    $pts_fora = (int)$_POST['pts_fora'];
    $genero = $_POST['genero'];
    $fase = $_POST['fase']; // Captura a fase escolhida
    $data_partida = $_POST['data_partida'];

    if ($id_casa !== $id_fora && ($pts_casa == 3 || $pts_fora == 3)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO partidas (id_casa, id_fora, pontos_casa, pontos_fora, genero, fase, data_partida) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$id_casa, $id_fora, $pts_casa, $pts_fora, $genero, $fase, $data_partida]);
            $msg_sucesso = "Partida cadastrada com sucesso!";
        } catch (PDOException $e) {
            $msg_erro = "Erro ao cadastrar partida: " . $e->getMessage();
        }
    } else {
        $msg_erro = "Selecione times diferentes e certifique-se de que o vencedor obteve 3 sets!";
    }
}

$paises = $pdo->query("SELECT * FROM paises ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Admin - VNL</title>
    <style>
        :root {
            --bg-body: #0b132b; --txt-main: #f1f5f9; --txt-heading: #48cae4;
            --bg-card: #1c2541; --border-line: #3a506b; --btn-bg: #48cae4; 
            --btn-txt: #0b132b; --accent-blue: #00b4d8; --input-bg: #0b132b;
        }
        [data-theme="light"] {
            --bg-body: #e0f2fe; --txt-main: #0f172a; --txt-heading: #0369a1;
            --bg-card: #ffffff; --border-line: #bae6fd; --btn-bg: #0284c7; 
            --btn-txt: #ffffff; --accent-blue: #0284c7; --input-bg: #f8fafc;
        }

        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: var(--bg-body); color: var(--txt-main); }
        .container { max-width: 800px; margin: 0 auto; }
        .card { background: var(--bg-card); border: 1px solid var(--border-line); border-radius: 8px; padding: 20px; margin-bottom: 25px; }
        h1, h2 { color: var(--txt-heading); }
        label { display: block; margin-top: 10px; font-weight: bold; font-size: 14px; }
        input, select { width: 100%; padding: 10px; margin-top: 5px; background: var(--input-bg); border: 1px solid var(--border-line); color: var(--txt-main); border-radius: 4px; box-sizing: border-box; }
        button { background: var(--btn-bg); color: var(--btn-txt); border: none; padding: 12px 20px; font-weight: bold; border-radius: 4px; cursor: pointer; margin-top: 15px; width: 100%; }
        .flex-row { display: flex; gap: 15px; flex-wrap: wrap; }
        .flex-col { flex: 1; min-width: 200px; }
        .alert-success { background: #10b981; color: white; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .alert-error { background: #ef4444; color: white; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .btn-top { display: inline-block; background: var(--border-line); color: var(--txt-main); padding: 8px 16px; border-radius: 20px; text-decoration: none; margin-bottom: 20px; font-weight: bold; font-size: 13px; }
    </style>
</head>
<body>

<div class="container">
    <a href="index.php" class="btn-top">← Voltar para Classificação</a>
    <h1>⚙️ Painel de Administração - VNL</h1>

    <?php if(!empty($msg_sucesso)): ?><div class="alert-success"><?=$msg_sucesso?></div><?php endif; ?>
    <?php if(!empty($msg_erro)): ?><div class="alert-error"><?=$msg_erro?></div><?php endif; ?>

    <!-- CADASTRAR PAÍS -->
    <div class="card">
        <h2>🏳️ Cadastrar Nova Seleção</h2>
        <form method="POST">
            <div class="flex-row">
                <div class="flex-col">
                    <label>Nome do País:</label>
                    <input type="text" name="nome_pais" placeholder="Ex: Brasil" required>
                </div>
                <div class="flex-col">
                    <label>Sigla (código ISO 2 letras para bandeira):</label>
                    <input type="text" name="sigla_pais" placeholder="Ex: br" maxlength="2" required>
                </div>
            </div>
            <button type="submit" name="cadastrar_pais">Cadastrar País</button>
        </form>
    </div>

    <!-- CADASTRAR PARTIDA -->
    <div class="card">
        <h2>🏐 Cadastrar Partida</h2>
        <form method="POST">
            <div class="flex-row">
                <div class="flex-col">
                    <label>Gênero:</label>
                    <select name="genero" required>
                        <option value="F">Feminino</option>
                        <option value="M">Masculino</option>
                    </select>
                </div>
                <div class="flex-col">
                    <!-- ITEM 2: SELETOR DE FASE DO TORNEIO -->
                    <label>Fase do Torneio:</label>
                    <select name="fase" required>
                        <option value="Fase de Grupos">Fase de Grupos (Soma na Classificação)</option>
                        <option value="Quartas de Final">Quartas de Final (Playoffs / Mata-Mata)</option>
                        <option value="Semifinal">Semifinal (Playoffs / Mata-Mata)</option>
                        <option value="3º Lugar / Bronze">3º Lugar / Bronze</option>
                        <option value="Final">Grande Final 🏆</option>
                    </select>
                </div>
                <div class="flex-col">
                    <label>Data da Partida:</label>
                    <input type="date" name="data_partida" value="<?=date('Y-m-d')?>" required>
                </div>
            </div>

            <div class="flex-row" style="margin-top:10px;">
                <div class="flex-col">
                    <label>Mandante / Casa:</label>
                    <select name="id_casa" required>
                        <option value="">Selecione...</option>
                        <?php foreach($paises as $p): ?>
                            <option value="<?=$p['id']?>"><?=$p['nome']?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex-col">
                    <label>Sets Mandante:</label>
                    <input type="number" name="pts_casa" min="0" max="3" value="3" required>
                </div>
                <div class="flex-col">
                    <label>Sets Visitante:</label>
                    <input type="number" name="pts_fora" min="0" max="3" value="0" required>
                </div>
                <div class="flex-col">
                    <label>Visitante / Fora:</label>
                    <select name="id_fora" required>
                        <option value="">Selecione...</option>
                        <?php foreach($paises as $p): ?>
                            <option value="<?=$p['id']?>"><?=$p['nome']?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <button type="submit" name="cadastrar_partida">Cadastrar Partida</button>
        </form>
    </div>
</div>

</body>
</html>
