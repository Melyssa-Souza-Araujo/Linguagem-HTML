<?php
session_start();
include 'conexao.php';
/** @var PDO $pdo */

if (!isset($_SESSION['logado']) || $_SESSION['usuario_nivel'] !== 'admin') {
    header("Location: login.php");
    exit;
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$mensagem_erro = "";

if (!isset($_GET['id'])) {
    header("Location: cadastro.php");
    exit;
}

$id_partida = intval($_GET['id']);

if (isset($_POST['atualizar_partida'])) {
    $id_casa = $_POST['id_casa'];
    $id_fora = $_POST['id_fora'];
    $p_casa = intval($_POST['pontos_casa']);
    $p_fora = intval($_POST['pontos_fora']);
    $genero = $_POST['genero'];
    $fase = $_POST['fase'];
    $data_partida = $_POST['data_partida'];

    $placar_valido = false;
    if (($p_casa == 3 && ($p_fora >= 0 && $p_fora <= 2)) || ($p_fora == 3 && ($p_casa >= 0 && $p_casa <= 2))) {
        $placar_valido = true;
    }

    if ($id_casa == $id_fora) {
        $mensagem_erro = "Um país não pode jogar contra ele mesmo.";
    } elseif (!$placar_valido) {
        $mensagem_erro = "Placar inválido! Um dos times deve fazer exatamente 3 sets, e o outro no máximo 2.";
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("UPDATE partidas SET id_casa = ?, id_fora = ?, pontos_casa = ?, pontos_fora = ?, genero = ?, fase = ?, data_partida = ? WHERE id = ?");
            $stmt->execute([$id_casa, $id_fora, $p_casa, $p_fora, $genero, $fase, $data_partida, $id_partida]);
            
            $pdo->prepare("DELETE FROM detalhes_sets WHERE id_partida = ?")->execute([$id_partida]);

            if (isset($_POST['pontos_set_casa'])) {
                foreach ($_POST['pontos_set_casa'] as $index => $pts_c) {
                    $pts_f = $_POST['pontos_set_fora'][$index];
                    $numero_set = $index + 1;

                    if (($pts_c !== '' && $pts_f !== '') && ($pts_c > 0 || $pts_f > 0)) {
                        $stmt_set = $pdo->prepare("INSERT INTO detalhes_sets (id_partida, numero_set, pontos_casa, pontos_fora) VALUES (?, ?, ?, ?)");
                        $stmt_set->execute([$id_partida, $numero_set, intval($pts_c), intval($pts_f)]);
                    }
                }
            }

            $pdo->commit();
            header("Location: cadastro.php?sucesso=partida");
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $mensagem_erro = "Erro ao atualizar partida: " . $e->getMessage();
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM partidas WHERE id = ?");
$stmt->execute([$id_partida]);
$partida = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$partida) {
    header("Location: cadastro.php");
    exit;
}

$stmt_sets = $pdo->prepare("SELECT * FROM detalhes_sets WHERE id_partida = ? ORDER BY numero_set ASC");
$stmt_sets->execute([$id_partida]);
$sets_carregados = $stmt_sets->fetchAll(PDO::FETCH_ASSOC);

$valores_sets = [];
foreach ($sets_carregados as $s) {
    $valores_sets[$s['numero_set']] = ['casa' => $s['pontos_casa'], 'fora' => $s['pontos_fora']];
}

$paises = $pdo->query("SELECT * FROM paises ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Partida - VNL</title>
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

        body, .box, input, select { transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease; }
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: var(--bg-body); color: var(--txt-main); }
        h1, h2 { text-align: center; color: var(--txt-heading); }
        
        .header-top { display: flex; justify-content: space-between; align-items: center; max-width: 500px; margin: 0 auto; }
        .theme-toggle { background: var(--btn-bg); color: var(--btn-txt); border: none; padding: 8px 18px; font-weight: bold; border-radius: 20px; cursor: pointer; font-size: 13px; }

        .box { background: var(--bg-card); padding: 25px; border-radius: 8px; width: 100%; max-width: 500px; border: 1px solid var(--border-line); box-sizing: border-box; margin: 20px auto; }
        label { display: block; margin-top: 12px; font-weight: bold; font-size: 14px; }
        input, select { width: 100%; padding: 8px; margin: 4px 0 12px 0; box-sizing: border-box; border: 1px solid var(--border-line); background: var(--input-bg); color: var(--txt-main); border-radius: 4px; font-weight: bold; }
        
        button { background: var(--accent-blue); color: white; border: none; padding: 12px; width: 100%; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 15px; margin-top: 15px; }
        button:hover { filter: brightness(1.1); }
        .alert-error { background: #ef4444; color: white; padding: 10px; border-radius: 4px; margin-bottom: 15px; text-align: center; font-weight: bold; }
        
        .grid-sets { display: flex; flex-direction: column; gap: 8px; background: var(--bg-body); padding: 12px; border-radius: 6px; border: 1px dashed var(--border-line); margin-bottom: 12px; }
        .linha-set { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
        .linha-set span { font-weight: bold; font-size: 13px; min-width: 45px; }
        .linha-set input { margin: 0; text-align: center; padding: 6px; }
    </style>
</head>
<body>

    <div class="header-top">
        <a href="cadastro.php" style="text-decoration: none; font-weight: bold; color: var(--accent-blue);">← Voltar</a>
        <button class="theme-toggle" onclick="toggleTheme()" id="btnTema">☀️ Modo Claro</button>
    </div>

    <h1>✏️ Editar Resultado de Partida</h1>

    <div class="box">
        <?php if(!empty($mensagem_erro)): ?><div class="alert-error"><?=$mensagem_erro?></div><?php endif; ?>

        <form method="POST">
            <label>Categoria/Gênero:</label>
            <select name="genero" required>
                <option value="F" <?=$partida['genero'] == 'F' ? 'selected' : ''?>>Feminino (F)</option>
                <option value="M" <?=$partida['genero'] == 'M' ? 'selected' : ''?>>Masculino (M)</option>
            </select>

            <label>Fase do Campeonato:</label>
            <select name="fase" required>
                <option value="Fase de Grupos" <?=$partida['fase'] == 'Fase de Grupos' ? 'selected' : ''?>>Fase de Grupos</option>
                <option value="Quartas de Final" <?=$partida['fase'] == 'Quartas de Final' ? 'selected' : ''?>>Quartas de Final</option>
                <option value="Semifinal" <?=$partida['fase'] == 'Semifinal' ? 'selected' : ''?>>Semifinal</option>
                <option value="Final" <?=$partida['fase'] == 'Final' ? 'selected' : ''?>>Final</option>
            </select>

            <label>Data do Confronto:</label>
            <input type="date" name="data_partida" required value="<?=htmlspecialchars($partida['data_partida'])?>">

            <div style="display: flex; gap: 10px;">
                <div style="flex: 1;">
                    <label>Time da Casa:</label>
                    <select name="id_casa" required>
                        <?php foreach($paises as $p): ?>
                            <option value="<?=$p['id']?>" <?=$partida['id_casa'] == $p['id'] ? 'selected' : ''?>><?=$p['nome']?></option>
                        <?php endforeach; ?>
                    </select>
                    <label>Placar (Sets):</label>
                    <input type="number" name="pontos_casa" min="0" max="3" value="<?=$partida['pontos_casa']?>" required>
                </div>
                
                <div style="flex: 1;">
                    <label>Time Visitante:</label>
                    <select name="id_fora" required>
                        <?php foreach($paises as $p): ?>
                            <option value="<?=$p['id']?>" <?=$partida['id_fora'] == $p['id'] ? 'selected' : ''?>><?=$p['nome']?></option>
                        <?php endforeach; ?>
                    </select>
                    <label>Placar (Sets):</label>
                    <input type="number" name="pontos_fora" min="0" max="3" value="<?=$partida['pontos_fora']?>" required>
                </div>
            </div>

            <label>Pontuação por Set (Gráfico):</label>
            <div class="grid-sets">
                <?php for($i = 0; $i < 5; $i++): 
                    $num_set = $i + 1;
                    $val_casa = isset($valores_sets[$num_set]) ? $valores_sets[$num_set]['casa'] : '';
                    $val_fora = isset($valores_sets[$num_set]) ? $valores_sets[$num_set]['fora'] : '';
                ?>
                    <div class="linha-set">
                        <span>Set <?=$num_set?>:</span>
                        <input type="number" name="pontos_set_casa[<?=$i?>]" value="<?=$val_casa?>" placeholder="Casa">
                        x
                        <input type="number" name="pontos_set_fora[<?=$i?>]" value="<?=$val_fora?>" placeholder="Fora">
                    </div>
                <?php endfor; ?>
            </div>

            <button type="submit" name="atualizar_partida">Salvar Alterações</button>
        </form>
    </div>

    <script>
    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        const btn = document.getElementById('btnTema');
        if (theme === 'light') { btn.innerHTML = '🌙 Modo Escuro'; } else { btn.innerHTML = '☀️ Modo Claro'; }
    }
    function toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        localStorage.setItem('vnl-theme', newTheme);
        applyTheme(newTheme);
    }
    applyTheme(localStorage.getItem('vnl-theme') || 'dark');
    </script>
</body>
</html>
