<?php
session_start();
include 'conexao.php';
/** @var PDO $pdo */

// Verifica permissão de admin
if (!isset($_SESSION['logado']) || $_SESSION['usuario_nivel'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$msg_sucesso = "";
$msg_erro = "";

// ====================================================================
// ITEM 4: LÓGICA DE AVANÇO AUTOMÁTICO (QUARTAS -> SEMI -> FINAL)
// ====================================================================
function checarEAvancarMataMata($pdo, $genero) {
    // 1. AVANÇO DAS QUARTAS PARA AS SEMIFINAIS
    $stmt_q = $pdo->prepare("SELECT * FROM partidas WHERE fase = 'Quartas de Final' AND genero = ? ORDER BY id ASC");
    $stmt_q->execute([$genero]);
    $quartas = $stmt_q->fetchAll(PDO::FETCH_ASSOC);

    if (count($quartas) == 4) {
        $vencedores_q = [];
        foreach ($quartas as $q) {
            if ($q['pontos_casa'] == 3) { $vencedores_q[] = $q['id_casa']; }
            elseif ($q['pontos_fora'] == 3) { $vencedores_q[] = $q['id_fora']; }
        }

        // Se todos os 4 jogos das Quartas foram concluídos
        if (count($vencedores_q) == 4) {
            $stmt_check_s = $pdo->prepare("SELECT COUNT(*) FROM partidas WHERE fase = 'Semifinal' AND genero = ?");
            $stmt_check_s->execute([$genero]);
            if ($stmt_check_s->fetchColumn() == 0) {
                // Cria as duas Semifinais automaticamente
                $data_hoje = date('Y-m-d');
                $stmt_ins = $pdo->prepare("INSERT INTO partidas (id_casa, id_fora, pontos_casa, pontos_fora, genero, fase, data_partida) VALUES (?, ?, 0, 0, ?, 'Semifinal', ?)");
                
                // Semifinal 1: Vencedor Q1 (1x8) vs Vencedor Q2 (4x5)
                $stmt_ins->execute([$vencedores_q[0], $vencedores_q[1], $genero, $data_hoje]);
                
                // Semifinal 2: Vencedor Q3 (2x7) vs Vencedor Q4 (3x6)
                $stmt_ins->execute([$vencedores_q[2], $vencedores_q[3], $genero, $data_hoje]);
            }
        }
    }

    // 2. AVANÇO DAS SEMIFINAIS PARA A GRANDE FINAL
    $stmt_s = $pdo->prepare("SELECT * FROM partidas WHERE fase = 'Semifinal' AND genero = ? ORDER BY id ASC");
    $stmt_s->execute([$genero]);
    $semis = $stmt_s->fetchAll(PDO::FETCH_ASSOC);

    if (count($semis) == 2) {
        $vencedores_s = [];
        foreach ($semis as $s) {
            if ($s['pontos_casa'] == 3) { $vencedores_s[] = $s['id_casa']; }
            elseif ($s['pontos_fora'] == 3) { $vencedores_s[] = $s['id_fora']; }
        }

        // Se os 2 jogos de Semifinal foram concluídos
        if (count($vencedores_s) == 2) {
            $stmt_check_f = $pdo->prepare("SELECT COUNT(*) FROM partidas WHERE fase = 'Final' AND genero = ?");
            $stmt_check_f->execute([$genero]);
            if ($stmt_check_f->fetchColumn() == 0) {
                // Cria a Grande Final automaticamente
                $data_hoje = date('Y-m-d');
                $stmt_ins = $pdo->prepare("INSERT INTO partidas (id_casa, id_fora, pontos_casa, pontos_fora, genero, fase, data_partida) VALUES (?, ?, 0, 0, ?, 'Final', ?)");
                $stmt_ins->execute([$vencedores_s[0], $vencedores_s[1], $genero, $data_hoje]);
            }
        }
    }
}

// Obter ID da partida via GET
$id_partida = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Atualização da Partida
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_edicao'])) {
    $pontos_casa = (int)$_POST['pontos_casa'];
    $pontos_fora = (int)$_POST['pontos_fora'];
    $genero = $_POST['genero'];

    try {
        $pdo->beginTransaction();

        $stmt_up = $pdo->prepare("UPDATE partidas SET pontos_casa = ?, pontos_fora = ? WHERE id = ?");
        $stmt_up->execute([$pontos_casa, $pontos_fora, $id_partida]);

        $pdo->commit();

        // ⚡ EXECUTAR CHECAGEM DO MATA-MATA
        checarEAvancarMataMata($pdo, $genero);

        $msg_sucesso = "Partida atualizada com sucesso!";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $msg_erro = "Erro ao atualizar partida: " . $e->getMessage();
    }
}

// Carregar dados da partida atual
$stmt = $pdo->prepare("
    SELECT p.*, 
           c.nome AS nome_casa, 
           f.nome AS nome_fora 
    FROM partidas p
    JOIN paises c ON p.id_casa = c.id
    JOIN paises f ON p.id_fora = f.id
    WHERE p.id = ?
");
$stmt->execute([$id_partida]);
$partida = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$partida) {
    die("Partida não encontrada.");
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Partida #<?=$partida['id']?></title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #0b132b; color: #f1f5f9; padding: 20px; }
        .card { background: #1c2541; padding: 20px; border-radius: 8px; max-width: 500px; margin: 0 auto; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input { width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #3a506b; background: #0b132b; color: #fff; box-sizing: border-box; }
        .btn { background: #48cae4; color: #0b132b; border: none; padding: 10px 15px; font-weight: bold; border-radius: 4px; cursor: pointer; width: 100%; }
        .alert-success { background: #10b981; color: white; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .alert-error { background: #ef4444; color: white; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
    </style>
</head>
<body>

<div class="card">
    <h2>✏️ Editar Partida #<?=$partida['id']?></h2>
    <p><strong>Fase:</strong> <?=$partida['fase']?> (<?=$partida['genero'] == 'F' ? 'Feminino' : 'Masculino'?>)</p>
    <p><a href="mata_mata.php" style="color: #48cae4;">← Voltar para Árvore Mata-Mata</a></p>

    <?php if ($msg_sucesso): ?><div class="alert-success"><?=$msg_sucesso?></div><?php endif; ?>
    <?php if ($msg_erro): ?><div class="alert-error"><?=$msg_erro?></div><?php endif; ?>

    <form method="POST">
        <input type="hidden" name="genero" value="<?=$partida['genero']?>">

        <div class="form-group">
            <label><?=$partida['nome_casa']?> (Sets):</label>
            <input type="number" name="pontos_casa" min="0" max="3" value="<?=$partida['pontos_casa']?>" required>
        </div>

        <div class="form-group">
            <label><?=$partida['nome_fora']?> (Sets):</label>
            <input type="number" name="pontos_fora" min="0" max="3" value="<?=$partida['pontos_fora']?>" required>
        </div>

        <button type="submit" name="salvar_edicao" class="btn">Salvar Placar</button>
    </form>
</div>

</body>
</html>
