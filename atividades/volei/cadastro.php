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

// EXCLUSÃO DE PARTIDA
if (isset($_GET['excluir_partida'])) {
    $id_excluir = (int)$_GET['excluir_partida'];
    try {
        $stmt = $pdo->prepare("DELETE FROM partidas WHERE id = ?");
        $stmt->execute([$id_excluir]);
        $msg_sucesso = "Partida excluída com sucesso!";
    } catch (PDOException $e) {
        $msg_erro = "Erro ao excluir partida: " . $e->getMessage();
    }
}

// 1. CADASTRAR PAÍS
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

// 2. CADASTRAR PARTIDA COM PONTOS DOS SETS
if (isset($_POST['cadastrar_partida'])) {
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

                $stmt = $pdo->prepare("INSERT INTO partidas (id_casa, id_fora, pontos_casa, pontos_fora, genero, fase, data_partida) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$id_casa, $id_fora, $sets_vencidos_casa, $sets_vencidos_fora, $genero, $fase, $data_partida]);
                $id_partida = $pdo->lastInsertId();

                $stmt_set = $pdo->prepare("INSERT INTO detalhes_sets (id_partida, numero_set, pontos_casa, pontos_fora) VALUES (?, ?, ?, ?)");
                foreach ($sets_validos as $num => $p) {
                    $stmt_set->execute([$id_partida, $num, $p['casa'], $p['fora']]);
                }

                $pdo->commit();
                checarEAvancarMataMata($pdo, $genero); // Executa verificação e criação automática das fases seguintes
                $msg_sucesso = "Partida e sets cadastrados com sucesso!";
            } catch (PDOException $e) {
                $pdo->rollBack();
                $msg_erro = "Erro ao cadastrar partida: " . $e->getMessage();
            }
        } else {
            $msg_erro = "Erro: Uma equipe precisa vencer exatamente 3 sets para encerrar a partida!";
        }
    } else {
        $msg_erro = "Erro: Selecione seleções diferentes!";
    }
}

// Carrega dados para os selects e tabela
$paises = $pdo->query("SELECT * FROM paises ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

$sql_partidas = "
    SELECT p.*, 
           c.nome AS nome_casa, c.sigla AS sigla_casa,
           f.nome AS nome_fora, f.sigla AS sigla_fora
    FROM partidas p
    JOIN paises c ON p.id_casa = c.id
    JOIN paises f ON p.id_fora = f.id
    ORDER BY p.id DESC
";
$partidas_cadastradas = $pdo->query($sql_partidas)->fetchAll(PDO::FETCH_ASSOC);
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
        .container { max-width: 900px; margin: 0 auto; }
        .card { background: var(--bg-card); border: 1px solid var(--border-line); border-radius: 8px; padding: 20px; margin-bottom: 25px; }
        h1, h2, h3 { color: var(--txt-heading); }
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
        
        table { width: 100%; border-collapse: collapse; font-size: 13px; margin-top: 15px; }
        th, td { padding: 10px; border-bottom: 1px solid var(--border-line); text-align: center; }
        th { background: var(--border-line); color: var(--txt-main); }
        .flag { width: 20px; height: 14px; object-fit: cover; vertical-align: middle; border-radius: 2px; }
        .btn-action { display: inline-block; padding: 4px 8px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: bold; color: white; margin: 2px; }
    </style>
</head>
<body>

<div class="container">
    <a href="index.php" class="btn-top">← Voltar para Classificação</a>
    <a href="mata_mata.php" class="btn-top">🏆 Ver Mata-Mata</a>
    <h1>⚙️ Painel de Administração - VNL</h1>

    <?php if(!empty($msg_sucesso)): ?><div class="alert-success"><?=$msg_sucesso?></div><?php endif; ?>
    <?php if(!empty($msg_erro)): ?><div class="alert-error"><?=$msg_erro?></div><?php endif; ?>

    <!-- 1. CADASTRAR PAÍS -->
    <div class="card">
        <h2>🏳️ Cadastrar Nova Seleção</h2>
        <form method="POST">
            <div class="flex-row">
                <div class="flex-col">
                    <label>Nome do País:</label>
                    <input type="text" name="nome_pais" placeholder="Ex: Brasil" required>
                </div>
                <div class="flex-col">
                    <label>Sigla (ISO 2 letras para bandeira):</label>
                    <input type="text" name="sigla_pais" placeholder="Ex: br" maxlength="2" required>
                </div>
            </div>
            <button type="submit" name="cadastrar_pais">Cadastrar País</button>
        </form>
    </div>

    <!-- 2. CADASTRAR PARTIDA COM PONTOS DOS SETS -->
    <div class="card">
        <h2>🏐 Cadastrar Partida e Sets</h2>
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
                    <label>Fase do Torneio:</label>
                    <select name="fase" required>
                        <option value="Fase de Grupos">Fase de Grupos (Conta Pontos)</option>
                        <option value="Quartas de Final">Quartas de Final</option>
                        <option value="Semifinal">Semifinal</option>
                        <option value="Final">Grande Final 🏆</option>
                    </select>
                </div>
                <div class="flex-col">
                    <label>Data da Partida:</label>
                    <input type="date" name="data_partida" value="<?=date('Y-m-d')?>" required>
                </div>
            </div>

            <div class="flex-row" style="margin-top:15px;">
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
                    <label>Visitante / Fora:</label>
                    <select name="id_fora" required>
                        <option value="">Selecione...</option>
                        <?php foreach($paises as $p): ?>
                            <option value="<?=$p['id']?>"><?=$p['nome']?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <h3>📊 Pontuação por Set</h3>
            <div class="sets-grid">
                <div class="set-box">
                    <label>1º Set</label>
                    <input type="number" name="set1_casa" placeholder="Casa" min="0">
                    <input type="number" name="set1_fora" placeholder="Fora" min="0" style="margin-top:5px;">
                </div>
                <div class="set-box">
                    <label>2º Set</label>
                    <input type="number" name="set2_casa" placeholder="Casa" min="0">
                    <input type="number" name="set2_fora" placeholder="Fora" min="0" style="margin-top:5px;">
                </div>
                <div class="set-box">
                    <label>3º Set</label>
                    <input type="number" name="set3_casa" placeholder="Casa" min="0">
                    <input type="number" name="set3_fora" placeholder="Fora" min="0" style="margin-top:5px;">
                </div>
                <div class="set-box">
                    <label>4º Set (Op.)</label>
                    <input type="number" name="set4_casa" placeholder="Casa" min="0">
                    <input type="number" name="set4_fora" placeholder="Fora" min="0" style="margin-top:5px;">
                </div>
                <div class="set-box">
                    <label>5º Set (Tie)</label>
                    <input type="number" name="set5_casa" placeholder="Casa" min="0">
                    <input type="number" name="set5_fora" placeholder="Fora" min="0" style="margin-top:5px;">
                </div>
            </div>

            <button type="submit" name="cadastrar_partida">Cadastrar Partida e Sets</button>
        </form>
    </div>

    <!-- 3. HISTÓRICO DE PARTIDAS COM OPÇÕES DE EDIÇÃO -->
    <div class="card">
        <h2>📋 Partidas Cadastradas</h2>
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Data</th>
                        <th>Fase</th>
                        <th>Gênero</th>
                        <th>Confronto</th>
                        <th>Placar</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($partidas_cadastradas)): ?>
                        <tr><td colspan="7">Nenhuma partida registrada.</td></tr>
                    <?php else: ?>
                        <?php foreach ($partidas_cadastradas as $partida): ?>
                            <tr>
                                <td>#<?=$partida['id']?></td>
                                <td><?=date('d/m/Y', strtotime($partida['data_partida']))?></td>
                                <td><?=$partida['fase']?></td>
                                <td><?=$partida['genero'] == 'F' ? 'Fem' : 'Masc'?></td>
                                <td style="text-align: left;">
                                    <img src="https://flagcdn.com/w40/<?=strtolower($partida['sigla_casa'])?>.png" class="flag"> <?=$partida['nome_casa']?>
                                    x
                                    <img src="https://flagcdn.com/w40/<?=strtolower($partida['sigla_fora'])?>.png" class="flag"> <?=$partida['nome_fora']?>
                                </td>
                                <td><strong><?=$partida['pontos_casa']?> - <?=$partida['pontos_fora']?></strong></td>
                                <td>
                                    <a href="editar_partida.php?id=<?=$partida['id']?>" class="btn-action" style="background:#0284c7;">✏️ Editar</a>
                                    <a href="cadastro.php?excluir_partida=<?=$partida['id']?>" class="btn-action" style="background:#ef4444;" onclick="return confirm('Deseja realmente excluir esta partida e todos os seus sets?');">🗑️ Excluir</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
