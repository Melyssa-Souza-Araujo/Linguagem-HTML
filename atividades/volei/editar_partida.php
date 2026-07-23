<?php
session_start();
include 'conexao.php';
/** @var PDO $pdo */

// Proteção para administradores
if (!isset($_SESSION['logado']) || $_SESSION['usuario_nivel'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$id_partida = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$msg_sucesso = "";
$msg_erro = "";

// Puxa a partida
$stmt = $pdo->prepare("SELECT * FROM partidas WHERE id = ?");
$stmt->execute([$id_partida]);
$partida = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$partida) {
    header("Location: cadastro.php");
    exit;
}

// Puxa os detalhes dos sets cadastrados
$stmt_sets = $pdo->prepare("SELECT * FROM detalhes_sets WHERE id_partida = ? ORDER BY numero_set ASC");
$stmt_sets->execute([$id_partida]);
$sets_db = $stmt_sets->fetchAll(PDO::FETCH_ASSOC);

$sets_map = [];
foreach ($sets_db as $s) {
    $sets_map[$s['numero_set']] = $s;
}

// ATUALIZAÇÃO DA PARTIDA E SEUS SETS
if (isset($_POST['atualizar_partida'])) {
    $id_casa = (int)$_POST['id_casa'];
    $id_fora = (int)$_POST['id_fora'];
    $genero = $_POST['genero'];
    $fase = $_POST['fase'];
    $data_partida = $_POST['data_partida'];

    $set1_casa = isset($_POST['set1_casa']) ? (int)$_POST['set1_casa'] : 0;
    $set1_fora = isset($_POST['set1_fora']) ? (int)$_POST['set1_fora'] : 0;
    $set2_casa = isset($_POST['set2_casa']) ? (int)$_POST['set2_casa'] : 0;
    $set2_fora = isset($_POST['set2_fora']) ? (int)$_POST['set2_fora'] : 0;
    $set3_casa = isset($_POST['set3_casa']) ? (int)$_POST['set3_casa'] : 0;
    $set3_fora = isset($_POST['set3_fora']) ? (int)$_POST['set3_fora'] : 0;
    $set4_casa = isset($_POST['set4_casa']) ? (int)$_POST['set4_casa'] : 0;
    $set4_fora = isset($_POST['set4_fora']) ? (int)$_POST['set4_fora'] : 0;
    $set5_casa = isset($_POST['set5_casa']) ? (int)$_POST['set5_casa'] : 0;
    $set5_fora = isset($_POST['set5_fora']) ? (int)$_POST['set5_fora'] : 0;

    if ($id_casa !== $id_fora) {
        $sets = [
            1 => ['casa' => $set1_casa, 'fora' => $set1_fora],
            2 => ['casa' => $set2_casa, 'fora' => $set2_fora],
            3 => ['casa' => $set3_casa, 'fora' => $set3_fora],
            4 => ['casa' => $set4_casa, 'fora' => $set4_fora],
            5 => ['casa' => $set5_casa, 'fora' => $set5_fora]
        ];

        $sets_vencidos_casa = 0;
        $sets_vencidos_fora = 0;
        $sets_validos = [];

        foreach ($sets as $num => $p) {
            if ($p['casa'] > 0 || $p['fora'] > 0) {
                if ($p['casa'] > $p['fora']) {
                    $sets_vencidos_casa++;
                } else if ($p['fora'] > $p['casa']) {
                    $sets_vencidos_fora++;
                }
                $sets_validos[$num] = $p;
            }
        }

        if ($sets_vencidos_casa == 3 || $sets_vencidos_fora == 3) {
            try {
                $pdo->beginTransaction();

                // Atualiza partida
                $stmt_up = $pdo->prepare("UPDATE partidas SET id_casa = ?, id_fora = ?, pontos_casa = ?, pontos_fora = ?, genero = ?, fase = ?, data_partida = ? WHERE id = ?");
                $stmt_up->execute([$id_casa, $id_fora, $sets_vencidos_casa, $sets_vencidos_fora, $genero, $fase, $data_partida, $id_partida]);

                // Limpa e reinsere os sets
                $stmt_del = $pdo->prepare("DELETE FROM detalhes_sets WHERE id_partida = ?");
                $stmt_del->execute([$id_partida]);

                $stmt_set = $pdo->prepare("INSERT INTO detalhes_sets (id_partida, numero_set, pontos_casa, pontos_fora) VALUES (?, ?, ?, ?)");
                foreach ($sets_validos as $num => $p) {
                    $stmt_set->execute([$id_partida, $num, $p['casa'], $p['fora']]);
                }

                $pdo->commit();
                $msg_sucesso = "Partida e sets atualizados com sucesso!";
                
                // Recarrega dados
                $stmt->execute([$id_partida]);
                $partida = $stmt->fetch(PDO::FETCH_ASSOC);
                $stmt_sets->execute([$id_partida]);
                $sets_db = $stmt_sets->fetchAll(PDO::FETCH_ASSOC);
                $sets_map = [];
                foreach ($sets_db as $s) { $sets_map[$s['numero_set']] = $s; }

            } catch (PDOException $e) {
                $pdo->rollBack();
                $msg_erro = "Erro ao atualizar partida: " . $e->getMessage();
            }
        } else {
            $msg_erro = "Erro: Uma equipe precisa vencer exatamente 3 sets para encerrar a partida!";
        }
    } else {
        $msg_erro = "Erro: Selecione seleções diferentes!";
    }
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
            --btn-txt: #0b132b; --input-bg: #0b132b;
        }
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: var(--bg-body); color: var(--txt-main); }
        .container { max-width: 800px; margin: 0 auto; }
        .card { background: var(--bg-card); border: 1px solid var(--border-line); border-radius: 8px; padding: 20px; }
        h1, h3 { color: var(--txt-heading); }
        label { display: block; margin-top: 10px; font-weight: bold; font-size: 13px; }
        input, select { width: 100%; padding: 10px; margin-top: 5px; background: var(--input-bg); border: 1px solid var(--border-line); color: var(--txt-main); border-radius: 4px; box-sizing: border-box; }
        button { background: var(--btn-bg); color: var(--btn-txt); border: none; padding: 12px 20px; font-weight: bold; border-radius: 4px; cursor: pointer; margin-top: 15px; width: 100%; }
        .flex-row { display: flex; gap: 15px; flex-wrap: wrap; }
        .flex-col { flex: 1; min-width: 180px; }
        .alert-success { background: #10b981; color: white; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .alert-error { background: #ef4444; color: white; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .btn-top { display: inline-block; background: var(--border-line); color: var(--txt-main); padding: 8px 16px; border-radius: 20px; text-decoration: none; margin-bottom: 20px; font-weight: bold; font-size: 13px; }
        .sets-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 10px; margin-top: 15px; background: var(--bg-body); padding: 15px; border-radius: 6px; border: 1px solid var(--border-line); }
        .set-box { text-align: center; }
        .set-box input { text-align: center; font-weight: bold; }
    </style>
</head>
<body>

<div class="container">
    <a href="cadastro.php" class="btn-top">← Voltar para o Painel</a>
    <h1>✏️ Editar Partida #<?=$partida['id']?></h1>

    <?php if(!empty($msg_sucesso)): ?><div class="alert-success"><?=$msg_sucesso?></div><?php endif; ?>
    <?php if(!empty($msg_erro)): ?><div class="alert-error"><?=$msg_erro?></div><?php endif; ?>

    <div class="card">
        <form method="POST">
            <div class="flex-row">
                <div class="flex-col">
                    <label>Gênero:</label>
                    <select name="genero" required>
                        <option value="F" <?=$partida['genero'] == 'F' ? 'selected' : ''?>>Feminino</option>
                        <option value="M" <?=$partida['genero'] == 'M' ? 'selected' : ''?>>Masculino</option>
                    </select>
                </div>
                <div class="flex-col">
                    <label>Fase do Torneio:</label>
                    <select name="fase" required>
                        <option value="Fase de Grupos" <?=$partida['fase'] == 'Fase de Grupos' ? 'selected' : ''?>>Fase de Grupos</option>
                        <option value="Quartas de Final" <?=$partida['fase'] == 'Quartas de Final' ? 'selected' : ''?>>Quartas de Final</option>
                        <option value="Semifinal" <?=$partida['fase'] == 'Semifinal' ? 'selected' : ''?>>Semifinal</option>
                        <option value="Final" <?=$partida['fase'] == 'Final' ? 'selected' : ''?>>Grande Final 🏆</option>
                    </select>
                </div>
                <div class="flex-col">
                    <label>Data da Partida:</label>
                    <input type="date" name="data_partida" value="<?=$partida['data_partida']?>" required>
                </div>
            </div>

            <div class="flex-row" style="margin-top: 15px;">
                <div class="flex-col">
                    <label>Mandante / Casa:</label>
                    <select name="id_casa" required>
                        <?php foreach($paises as $p): ?>
                            <option value="<?=$p['id']?>" <?=$p['id'] == $partida['id_casa'] ? 'selected' : ''?>><?=$p['nome']?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex-col">
                    <label>Visitante / Fora:</label>
                    <select name="id_fora" required>
                        <?php foreach($paises as $p): ?>
                            <option value="<?=$p['id']?>" <?=$p['id'] == $partida['id_fora'] ? 'selected' : ''?>><?=$p['nome']?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <h3>📊 Pontuação dos Sets</h3>
            <div class="sets-grid">
                <?php for($i = 1; $i <= 5; $i++): ?>
                    <div class="set-box">
                        <label><?=$i?>º Set <?=$i == 5 ? '(Tie)' : ''?></label>
                        <input type="number" name="set<?=$i?>_casa" placeholder="Casa" min="0" value="<?=$sets_map[$i]['pontos_casa'] ?? ''?>">
                        <input type="number" name="set<?=$i?>_fora" placeholder="Fora" min="0" style="margin-top:5px;" value="<?=$sets_map[$i]['pontos_fora'] ?? ''?>">
                    </div>
                <?php endfor; ?>
            </div>

            <button type="submit" name="atualizar_partida">Salvar Alterações</button>
        </form>
    </div>
</div>

</body>
</html>
