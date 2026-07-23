<?php
session_start();
include 'conexao.php';
/** @var PDO $pdo */

$msg_sucesso = "";
$msg_erro = "";

// ====================================================================
// FUNÇÃO PARA CALCULAR O TOP 8 DA FASE DE GRUPOS POR GÊNERO
// ====================================================================
function getTop8FaseDeGrupos($pdo, $genero) {
    $paises = $pdo->query("SELECT * FROM paises ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
    $stats = [];

    foreach ($paises as $p) {
        $stats[$p['id']] = [
            'id' => $p['id'],
            'nome' => $p['nome'],
            'sigla' => strtolower($p['sigla']),
            'jogos' => 0, 'vitorias' => 0, 'derrotas' => 0,
            'pontos' => 0, 'sets_pro' => 0, 'sets_contra' => 0,
            'jogou' => false
        ];
    }

    $stmt = $pdo->prepare("SELECT * FROM partidas WHERE fase = 'Fase de Grupos' AND genero = ? ORDER BY id ASC");
    $stmt->execute([$genero]);
    $partidas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($partidas as $partida) {
        $casa = $partida['id_casa'];
        $fora = $partida['id_fora'];
        $p_casa = (int)$partida['pontos_casa'];
        $p_fora = (int)$partida['pontos_fora'];

        if (!isset($stats[$casa])) continue;

        $stats[$casa]['jogou'] = true;
        $stats[$fora]['jogou'] = true;
        $stats[$casa]['jogos']++;
        $stats[$fora]['jogos']++;
        $stats[$casa]['sets_pro'] += $p_casa;
        $stats[$casa]['sets_contra'] += $p_fora;
        $stats[$fora]['sets_pro'] += $p_fora;
        $stats[$fora]['sets_contra'] += $p_casa;

        if ($p_casa > $p_fora) {
            $stats[$casa]['vitorias']++;
            $stats[$fora]['derrotas']++;
            if ($p_casa == 3 && ($p_fora == 0 || $p_fora == 1)) { $stats[$casa]['pontos'] += 3; }
            elseif ($p_casa == 3 && $p_fora == 2) { $stats[$casa]['pontos'] += 2; $stats[$fora]['pontos'] += 1; }
        } else {
            $stats[$fora]['vitorias']++;
            $stats[$casa]['derrotas']++;
            if ($p_fora == 3 && ($p_casa == 0 || $p_casa == 1)) { $stats[$fora]['pontos'] += 3; }
            elseif ($p_fora == 3 && $p_casa == 2) { $stats[$fora]['pontos'] += 2; $stats[$casa]['pontos'] += 1; }
        }
    }

    // Filtra quem jogou
    $stats = array_filter($stats, function($item) { return $item['jogou']; });

    // Ordena: Pontos > Vitórias > Saldo de Sets
    uasort($stats, function($a, $b) {
        if ($a['pontos'] != $b['pontos']) return $b['pontos'] <=> $a['pontos'];
        if ($a['vitorias'] != $b['vitorias']) return $b['vitorias'] <=> $a['vitorias'];
        return ($b['sets_pro'] - $b['sets_contra']) <=> ($a['sets_pro'] - $a['sets_contra']);
    });

    return array_slice(array_values($stats), 0, 8);
}

// ====================================================================
// ITENS 1, 2 e 3: AÇÃO DO BOTÃO "GERAR CHAVEAMENTO" (ADMIN)
// ====================================================================
if (isset($_POST['gerar_chaveamento']) && isset($_SESSION['logado']) && $_SESSION['usuario_nivel'] === 'admin') {
    $genero_gerar = $_POST['genero_chaveamento'];

    // Item 1: Verifica se já existem jogos de Quartas para o gênero
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM partidas WHERE fase = 'Quartas de Final' AND genero = ?");
    $stmt_check->execute([$genero_gerar]);
    $existe_quartas = $stmt_check->fetchColumn();

    if ($existe_quartas > 0) {
        $msg_erro = "O chaveamento de Quartas de Final para este gênero já foi gerado anteriormente!";
    } else {
        $top8 = getTop8FaseDeGrupos($pdo, $genero_gerar);

        if (count($top8) < 8) {
            $msg_erro = "É necessário ter pelo menos 8 seleções com jogos registrados na Fase de Grupos para gerar o chaveamento!";
        } else {
            // Item 3: Cruzamento Olímpico (1ºx8º, 4ºx5º, 2ºx7º, 3ºx6º)
            $confrontos = [
                ['casa' => $top8[0]['id'], 'fora' => $top8[7]['id']], // Q1: 1º vs 8º
                ['casa' => $top8[3]['id'], 'fora' => $top8[4]['id']], // Q2: 4º vs 5º
                ['casa' => $top8[1]['id'], 'fora' => $top8[6]['id']], // Q3: 2º vs 7º
                ['casa' => $top8[2]['id'], 'fora' => $top8[5]['id']]  // Q4: 3º vs 6º
            ];

            try {
                $pdo->beginTransaction();
                $stmt_ins = $pdo->prepare("INSERT INTO partidas (id_casa, id_fora, pontos_casa, pontos_fora, genero, fase, data_partida) VALUES (?, ?, 0, 0, ?, 'Quartas de Final', ?)");
                $data_hoje = date('Y-m-d');

                foreach ($confrontos as $c) {
                    $stmt_ins->execute([$c['casa'], $c['fora'], $genero_gerar, $data_hoje]);
                }

                $pdo->commit();
                $msg_sucesso = "Chaveamento das Quartas de Final (" . ($genero_gerar == 'F' ? 'Feminino' : 'Masculino') . ") gerado com sucesso!";
            } catch (PDOException $e) {
                $pdo->rollBack();
                $msg_erro = "Erro ao gerar chaveamento: " . $e->getMessage();
            }
        }
    }
}

// ====================================================================
// ESTRUTURA E RECOMPOSIÇÃO DO MATA-MATA (QUARTAS, SEMIS E FINAL)
// ====================================================================
function carregarMataMata($pdo, $genero) {
    $sql = "
        SELECT p.*, 
               c.nome AS nome_casa, c.sigla AS sigla_casa,
               f.nome AS nome_fora, f.sigla AS sigla_fora
        FROM partidas p
        JOIN paises c ON p.id_casa = c.id
        JOIN paises f ON p.id_fora = f.id
        WHERE p.genero = ? AND p.fase IN ('Quartas de Final', 'Semifinal', 'Final')
        ORDER BY p.id ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$genero]);
    $jogos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $chaveamento = [
        'quartas' => [],
        'semis' => [],
        'final' => null
    ];

    foreach ($jogos as $j) {
        if ($j['fase'] === 'Quartas de Final') {
            $chaveamento['quartas'][] = $j;
        } elseif ($j['fase'] === 'Semifinal') {
            $chaveamento['semis'][] = $j;
        } elseif ($j['fase'] === 'Final') {
            $chaveamento['final'] = $j;
        }
    }

    return $chaveamento;
}

$mata_F = carregarMataMata($pdo, 'F');
$mata_M = carregarMataMata($pdo, 'M');

// Verifica status dos botões
$tem_quartas_F = count($mata_F['quartas']) > 0;
$tem_quartas_M = count($mata_M['quartas']) > 0;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chaveamento Mata-Mata - VNL</title>
    <style>
        :root {
            --bg-body: #0b132b; --txt-main: #f1f5f9; --txt-heading: #48cae4;
            --bg-card: #1c2541; --border-line: #3a506b; --btn-bg: #48cae4; 
            --btn-txt: #0b132b; --winner-bg: rgba(16, 185, 129, 0.15);
        }
        [data-theme="light"] {
            --bg-body: #e0f2fe; --txt-main: #0f172a; --txt-heading: #0369a1;
            --bg-card: #ffffff; --border-line: #bae6fd; --btn-bg: #0284c7; 
            --btn-txt: #ffffff; --winner-bg: rgba(16, 185, 129, 0.2);
        }

        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: var(--bg-body); color: var(--txt-main); }
        .container { max-width: 1200px; margin: 0 auto; }

        .header-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px; }
        h1, h2, h3 { color: var(--txt-heading); margin: 0; }
        .btn-top { background: var(--btn-bg); color: var(--btn-txt); border: none; padding: 8px 16px; font-weight: bold; border-radius: 20px; cursor: pointer; text-decoration: none; font-size: 13px; }

        .alert-success { background: #10b981; color: white; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: bold; }
        .alert-error { background: #ef4444; color: white; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: bold; }

        .admin-box { background: var(--bg-card); border: 2px dashed var(--btn-bg); padding: 15px; border-radius: 8px; margin-bottom: 25px; display: flex; gap: 15px; align-items: center; flex-wrap: wrap; }

        /* Estrutura da Árvore Bracket */
        .bracket-container { display: flex; gap: 20px; overflow-x: auto; padding-bottom: 15px; margin-top: 15px; }
        .bracket-column { flex: 1; min-width: 280px; display: flex; flex-direction: column; justify-content: space-around; }
        .column-title { text-align: center; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; padding: 8px; background: var(--border-line); border-radius: 4px; margin-bottom: 15px; font-weight: bold; }

        .match-card { background: var(--bg-card); border: 1px solid var(--border-line); border-radius: 8px; padding: 10px; margin-bottom: 15px; position: relative; }
        .match-team { display: flex; justify-content: space-between; align-items: center; padding: 6px 8px; border-radius: 4px; margin: 2px 0; }
        .match-team.winner { background: var(--winner-bg); font-weight: bold; border-left: 3px solid #10b981; }
        .flag { width: 22px; height: 15px; object-fit: cover; border-radius: 2px; vertical-align: middle; margin-right: 6px; }
        .score { font-weight: bold; font-size: 14px; background: var(--bg-body); padding: 2px 8px; border-radius: 4px; }
        
        .trophy-card { border: 2px solid #f59e0b; background: linear-gradient(135deg, var(--bg-card) 0%, rgba(245, 158, 11, 0.1) 100%); }
    </style>
</head>
<body>

<div class="container">
    <div class="header-top">
        <h1>🏆 Playoff & Chaveamento Final</h1>
        <div>
            <a href="index.php" class="btn-top">← Classificação</a>
            <?php if (isset($_SESSION['logado']) && $_SESSION['usuario_nivel'] === 'admin'): ?>
                <a href="cadastro.php" class="btn-top">⚙️ Painel Admin</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if(!empty($msg_sucesso)): ?><div class="alert-success"><?=$msg_sucesso?></div><?php endif; ?>
    <?php if(!empty($msg_erro)): ?><div class="alert-error"><?=$msg_erro?></div><?php endif; ?>

    <!-- PAINEL ADMIN: GERAR CHAVEAMENTOS -->
    <?php if (isset($_SESSION['logado']) && $_SESSION['usuario_nivel'] === 'admin'): ?>
        <div class="admin-box">
            <span style="font-weight: bold;">⚡ Painel do Administrador:</span>
            
            <form method="POST" style="display:inline;">
                <input type="hidden" name="genero_chaveamento" value="F">
                <button type="submit" name="gerar_chaveamento" class="btn-top" <?=$tem_quartas_F ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : ''?>>
                    🌸 Gerar Quartas de Final (Feminino)
                </button>
            </form>

            <form method="POST" style="display:inline;">
                <input type="hidden" name="genero_chaveamento" value="M">
                <button type="submit" name="gerar_chaveamento" class="btn-top" <?=$tem_quartas_M ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : ''?>>
                    🔷 Gerar Quartas de Final (Masculino)
                </button>
            </form>
        </div>
    <?php endif; ?>

    <!-- CHAVEAMENTO FEMININO -->
    <h2 style="color: #ec4899; margin-top: 20px;">🌸 Chaveamento Feminino</h2>
    <?php renderBracket($mata_F); ?>

    <hr style="border: 0; border-top: 1px solid var(--border-line); margin: 40px 0;">

    <!-- CHAVEAMENTO MASCULINO -->
    <h2 style="color: #3b82f6;">🔷 Chaveamento Masculino</h2>
    <?php renderBracket($mata_M); ?>

</div>

<?php
// Função Auxiliar para Renderizar a Árvore
function renderBracket($mata) {
    ?>
    <div class="bracket-container">
        <!-- COLUNA QUARTAS DE FINAL -->
        <div class="bracket-column">
            <div class="column-title">Quartas de Final</div>
            <?php if (empty($mata['quartas'])): ?>
                <div class="match-card" style="text-align:center; opacity:0.6;">Aguardando término da Fase de Grupos...</div>
            <?php else: ?>
                <?php foreach ($mata['quartas'] as $q): 
                    $venceu_casa = $q['pontos_casa'] == 3;
                    $venceu_fora = $q['pontos_fora'] == 3;
                ?>
                    <div class="match-card">
                        <small style="opacity:0.7; display:block; margin-bottom:4px;">Jogo #<?=$q['id']?></small>
                        <div class="match-team <?=$venceu_casa ? 'winner' : ''?>">
                            <span><img src="https://flagcdn.com/w40/<?=strtolower($q['sigla_casa'])?>.png" class="flag"> <?=$q['nome_casa']?></span>
                            <span class="score"><?=$q['pontos_casa']?></span>
                        </div>
                        <div class="match-team <?=$venceu_fora ? 'winner' : ''?>">
                            <span><img src="https://flagcdn.com/w40/<?=strtolower($q['sigla_fora'])?>.png" class="flag"> <?=$q['nome_fora']?></span>
                            <span class="score"><?=$q['pontos_fora']?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- COLUNA SEMIFINAIS -->
        <div class="bracket-column">
            <div class="column-title">Semifinais</div>
            <?php for ($i = 0; $i < 2; $i++): 
                $semi = $mata['semis'][$i] ?? null;
            ?>
                <div class="match-card">
                    <?php if ($semi): 
                        $venceu_casa = $semi['pontos_casa'] == 3;
                        $venceu_fora = $semi['pontos_fora'] == 3;
                    ?>
                        <small style="opacity:0.7; display:block; margin-bottom:4px;">Semifinal #<?=$semi['id']?></small>
                        <div class="match-team <?=$venceu_casa ? 'winner' : ''?>">
                            <span><img src="https://flagcdn.com/w40/<?=strtolower($semi['sigla_casa'])?>.png" class="flag"> <?=$semi['nome_casa']?></span>
                            <span class="score"><?=$semi['pontos_casa']?></span>
                        </div>
                        <div class="match-team <?=$venceu_fora ? 'winner' : ''?>">
                            <span><img src="https://flagcdn.com/w40/<?=strtolower($semi['sigla_fora'])?>.png" class="flag"> <?=$semi['nome_fora']?></span>
                            <span class="score"><?=$semi['pontos_fora']?></span>
                        </div>
                    <?php else: ?>
                        <div style="text-align:center; padding:15px; opacity:0.5; font-size:12px;">Aguardando definição das Quartas...</div>
                    <?php endif; ?>
                </div>
            <?php endfor; ?>
        </div>

        <!-- COLUNA FINAL -->
        <div class="bracket-column">
            <div class="column-title" style="background:#f59e0b; color:#000;">Grande Final 🏆</div>
            <div class="match-card trophy-card">
                <?php if ($mata['final']): 
                    $f = $mata['final'];
                    $venceu_casa = $f['pontos_casa'] == 3;
                    $venceu_fora = $f['pontos_fora'] == 3;
                ?>
                    <small style="opacity:0.7; display:block; margin-bottom:4px; text-align:center;">🏆 Disputa do Título</small>
                    <div class="match-team <?=$venceu_casa ? 'winner' : ''?>">
                        <span><img src="https://flagcdn.com/w40/<?=strtolower($f['sigla_casa'])?>.png" class="flag"> <?=$f['nome_casa']?></span>
                        <span class="score"><?=$f['pontos_casa']?></span>
                    </div>
                    <div class="match-team <?=$venceu_fora ? 'winner' : ''?>">
                        <span><img src="https://flagcdn.com/w40/<?=strtolower($f['sigla_fora'])?>.png" class="flag"> <?=$f['nome_fora']?></span>
                        <span class="score"><?=$f['pontos_fora']?></span>
                    </div>

                    <?php if ($venceu_casa || $venceu_fora): ?>
                        <div style="text-align:center; margin-top:10px; font-weight:bold; color:#10b981;">
                            🎉 CAMPEÃO: <?=$venceu_casa ? $f['nome_casa'] : $f['nome_fora']?>!
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div style="text-align:center; padding:20px; opacity:0.5; font-size:12px;">Aguardando os finalistas...</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}
?>
</body>
</html>
