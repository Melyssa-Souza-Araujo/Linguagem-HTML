<?php
session_start();
include 'conexao.php';
/** @var PDO $pdo */

// 1. Carrega todos os países cadastrados
$paises = $pdo->query("SELECT * FROM paises ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

// Estrutura inicial para estatísticas da Fase de Grupos
$stats_F = []; 
$stats_M = [];

foreach ($paises as $p) {
    $base = [
        'id' => $p['id'],
        'nome' => $p['nome'],
        'sigla' => strtolower($p['sigla']),
        'jogos' => 0,
        'vitorias' => 0,
        'derrotas' => 0,
        'pontos' => 0,
        'sets_pro' => 0,
        'sets_contra' => 0,
        'jogou' => false
    ];
    $stats_F[$p['id']] = $base;
    $stats_M[$p['id']] = $base;
}

// ====================================================================
// ITEM 1: Busca APENAS partidas da Fase de Grupos para a Tabela Corrida
// ====================================================================
$partidas_tabela = $pdo->query("SELECT * FROM partidas WHERE fase = 'Fase de Grupos' ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

foreach ($partidas_tabela as $partida) {
    $casa = $partida['id_casa'];
    $fora = $partida['id_fora'];
    $p_casa = (int)$partida['pontos_casa'];
    $p_fora = (int)$partida['pontos_fora'];
    $genero = $partida['genero'];

    $ref = ($genero == 'F') ? $stats_F : $stats_M;

    if (!isset($ref[$casa])) continue;

    $ref[$casa]['jogou'] = true; 
    $ref[$fora]['jogou'] = true;
    $ref[$casa]['jogos']++; 
    $ref[$fora]['jogos']++;
    $ref[$casa]['sets_pro'] += $p_casa; 
    $ref[$casa]['sets_contra'] += $p_fora;
    $ref[$fora]['sets_pro'] += $p_fora; 
    $ref[$fora]['sets_contra'] += $p_casa;

    // Critérios Oficiais FIVB:
    // 3x0 ou 3x1 = 3 pts para o vencedor, 0 para o perdedor
    // 3x2 = 2 pts para o vencedor, 1 pt para o perdedor
    if ($p_casa > $p_fora) {
        $ref[$casa]['vitorias']++; 
        $ref[$fora]['derrotas']++;
        if ($p_casa == 3 && ($p_fora == 0 || $p_fora == 1)) {
            $ref[$casa]['pontos'] += 3;
        } elseif ($p_casa == 3 && $p_fora == 2) {
            $ref[$casa]['pontos'] += 2;
            $ref[$fora]['pontos'] += 1;
        }
    } else {
        $ref[$fora]['vitorias']++; 
        $ref[$casa]['derrotas']++;
        if ($p_fora == 3 && ($p_casa == 0 || $p_casa == 1)) {
            $ref[$fora]['pontos'] += 3;
        } elseif ($p_fora == 3 && $p_casa == 2) {
            $ref[$fora]['pontos'] += 2;
            $ref[$casa]['pontos'] += 1;
        }
    }

    if ($genero == 'F') { $stats_F = $ref; } else { $stats_M = $ref; }
}

// Remove times que ainda não jogaram na Fase de Grupos
$stats_F = array_filter($stats_F, function($item) { return $item['jogou']; });
$stats_M = array_filter($stats_M, function($item) { return $item['jogou']; });

// Ordenação oficial da FIVB: 1º Pontos, 2º Vitórias, 3º Saldo de Sets
$ordenar = function($a, $b) {
    if ($a['pontos'] != $b['pontos']) return $b['pontos'] <=> $a['pontos'];
    if ($a['vitorias'] != $b['vitorias']) return $b['vitorias'] <=> $a['vitorias'];
    $saldo_a = $a['sets_pro'] - $a['sets_contra'];
    $saldo_b = $b['sets_pro'] - $b['sets_contra'];
    return $saldo_b <=> $saldo_a;
};

uasort($stats_F, $ordenar);
uasort($stats_M, $ordenar);

// ====================================================================
// CONSULTA HISTÓRICO COMPLETO (Incluindo Mata-Mata e com Filtros)
// ====================================================================
$where_clauses = [];
$params = [];

if (!empty($_GET['filtro_genero'])) {
    $where_clauses[] = "p.genero = ?";
    $params[] = $_GET['filtro_genero'];
}
if (!empty($_GET['filtro_fase'])) {
    $where_clauses[] = "p.fase = ?";
    $params[] = $_GET['filtro_fase'];
}
if (!empty($_GET['filtro_pais'])) {
    $where_clauses[] = "(p.id_casa = ? OR p.id_fora = ?)";
    $params[] = $_GET['filtro_pais'];
    $params[] = $_GET['filtro_pais'];
}

$sql_historico = "
    SELECT p.*, 
           c.nome AS nome_casa, c.sigla AS sigla_casa,
           f.nome AS nome_fora, f.sigla AS sigla_fora
    FROM partidas p
    JOIN paises c ON p.id_casa = c.id
    JOIN paises f ON p.id_fora = f.id
";

if (count($where_clauses) > 0) {
    $sql_historico .= " WHERE " . implode(" AND ", $where_clauses);
}

$sql_historico .= " ORDER BY p.id DESC";

$stmt_hist = $pdo->prepare($sql_historico);
$stmt_hist->execute($params);
$historico_partidas = $stmt_hist->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classificação e Jogos - VNL</title>
    <style>
        :root {
            --bg-body: #0b132b; --txt-main: #f1f5f9; --txt-heading: #48cae4;
            --bg-card: #1c2541; --border-line: #3a506b; --btn-bg: #48cae4; 
            --btn-txt: #0b132b; --accent-blue: #00b4d8; --g8-border: #10b981;
        }
        [data-theme="light"] {
            --bg-body: #e0f2fe; --txt-main: #0f172a; --txt-heading: #0369a1;
            --bg-card: #ffffff; --border-line: #bae6fd; --btn-bg: #0284c7; 
            --btn-txt: #ffffff; --accent-blue: #0284c7; --g8-border: #059669;
        }

        body, .card, table, th, td, select { transition: background-color 0.3s ease, color 0.3s ease; }
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: var(--bg-body); color: var(--txt-main); }
        .container { max-width: 1000px; margin: 0 auto; }

        .header-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px; }
        h1, h2 { color: var(--txt-heading); margin: 0; }

        .nav-btns { display: flex; gap: 10px; flex-wrap: wrap; }
        .btn-top { background: var(--btn-bg); color: var(--btn-txt); border: none; padding: 8px 16px; font-weight: bold; border-radius: 20px; cursor: pointer; text-decoration: none; font-size: 13px; }

        .grid-tabelas { display: flex; gap: 20px; flex-wrap: wrap; margin-top: 20px; }
        .box-tabela { flex: 1; min-width: 320px; background: var(--bg-card); border-radius: 8px; border: 1px solid var(--border-line); padding: 15px; box-sizing: border-box; }

        .table-responsive { overflow-x: auto; margin-top: 10px; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; text-align: center; }
        th, td { padding: 8px 6px; border-bottom: 1px solid var(--border-line); }
        th { background: var(--border-line); color: var(--txt-main); }

        .flag { width: 20px; height: 14px; object-fit: cover; vertical-align: middle; margin-right: 5px; border-radius: 2px; }
        
        /* Destaque Zona G8 */
        .row-g8 { border-left: 4px solid var(--g8-border); }
        
        .badge-fase { display: inline-block; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; background: var(--border-line); color: var(--txt-main); }
        .badge-mata { background: #f59e0b; color: #000; }

        /* Filtros */
        .card-filtros { background: var(--bg-card); padding: 15px; border-radius: 8px; border: 1px solid var(--border-line); margin-top: 30px; }
        .form-inline { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
        .form-select { background: var(--bg-body); color: var(--txt-main); border: 1px solid var(--border-line); padding: 8px; border-radius: 4px; }

        .match-card { background: var(--bg-card); border: 1px solid var(--border-line); border-radius: 8px; padding: 12px; margin-top: 10px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        .match-link { color: var(--txt-heading); text-decoration: none; font-weight: bold; }
        .match-link:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="container">
    <div class="header-top">
        <h1>🏐 Liga das Nações (VNL)</h1>
        <div class="nav-btns">
            <a href="relatorio.php" class="btn-top">📊 Relatórios & Simulador</a>
            <?php if (isset($_SESSION['logado']) && $_SESSION['usuario_nivel'] === 'admin'): ?>
                <a href="cadastro.php" class="btn-top">⚙️ Painel Admin</a>
            <?php endif; ?>
            <?php if (isset($_SESSION['logado'])): ?>
                <a href="logout.php" class="btn-top" style="background: #ef4444; color: white;">Sair</a>
            <?php else: ?>
                <a href="login.php" class="btn-top">Login</a>
            <?php endif; ?>
            <button class="btn-top" onclick="toggleTheme()" id="btnTema">☀️ Modo Claro</button>
        </div>
    </div>

    <!-- TABELAS DA FASE DE GRUPOS -->
    <h2>🏆 Classificação Oficial (Fase de Grupos)</h2>
    <p style="font-size: 12px; opacity: 0.8; margin-top: 4px;">* Os 8 primeiros colocados (G8) avançam para as quartas de final.</p>

    <div class="grid-tabelas">
        <!-- TABELA FEMININA -->
        <div class="box-tabela">
            <h3 style="text-align: center; color: #ec4899;">Feminino</h3>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Pos</th>
                            <th style="text-align: left;">País</th>
                            <th>J</th>
                            <th>V</th>
                            <th>D</th>
                            <th>Sets</th>
                            <th>Pts</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($stats_F)): ?>
                            <tr><td colspan="7">Nenhum jogo registrado.</td></tr>
                        <?php else: ?>
                            <?php $pos = 1; foreach ($stats_F as $t): ?>
                            <tr class="<?=$pos <= 8 ? 'row-g8' : ''?>">
                                <td><strong><?=$pos++?>º</strong></td>
                                <td style="text-align: left;"><img src="https://flagcdn.com/w40/<?=$t['sigla']?>.png" class="flag"> <?=$t['nome']?></td>
                                <td><?=$t['jogos']?></td>
                                <td style="color:#10b981; font-weight:bold;"><?=$t['vitorias']?></td>
                                <td style="color:#ef4444;"><?=$t['derrotas']?></td>
                                <td><?=$t['sets_pro']?>:<?=$t['sets_contra']?></td>
                                <td style="font-weight:bold; background: var(--border-line);"><?=$t['pontos']?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TABELA MASCULINA -->
        <div class="box-tabela">
            <h3 style="text-align: center; color: #3b82f6;">Masculino</h3>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Pos</th>
                            <th style="text-align: left;">País</th>
                            <th>J</th>
                            <th>V</th>
                            <th>D</th>
                            <th>Sets</th>
                            <th>Pts</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($stats_M)): ?>
                            <tr><td colspan="7">Nenhum jogo registrado.</td></tr>
                        <?php else: ?>
                            <?php $pos = 1; foreach ($stats_M as $t): ?>
                            <tr class="<?=$pos <= 8 ? 'row-g8' : ''?>">
                                <td><strong><?=$pos++?>º</strong></td>
                                <td style="text-align: left;"><img src="https://flagcdn.com/w40/<?=$t['sigla']?>.png" class="flag"> <?=$t['nome']?></td>
                                <td><?=$t['jogos']?></td>
                                <td style="color:#10b981; font-weight:bold;"><?=$t['vitorias']?></td>
                                <td style="color:#ef4444;"><?=$t['derrotas']?></td>
                                <td><?=$t['sets_pro']?>:<?=$t['sets_contra']?></td>
                                <td style="font-weight:bold; background: var(--border-line);"><?=$t['pontos']?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- SEÇÃO DE HISTÓRICO DE JOGOS E FILTROS -->
    <div class="card-filtros">
        <h2>📅 Histórico de Partidas</h2>
        
        <form method="GET" class="form-inline" style="margin-top: 15px;">
            <select name="filtro_genero" class="form-select">
                <option value="">Todos os Gêneros</option>
                <option value="F" <?=($_GET['filtro_genero'] ?? '') == 'F' ? 'selected' : ''?>>Feminino</option>
                <option value="M" <?=($_GET['filtro_genero'] ?? '') == 'M' ? 'selected' : ''?>>Masculino</option>
            </select>

            <select name="filtro_fase" class="form-select">
                <option value="">Todas as Fases</option>
                <option value="Fase de Grupos" <?=($_GET['filtro_fase'] ?? '') == 'Fase de Grupos' ? 'selected' : ''?>>Fase de Grupos</option>
                <option value="Quartas de Final" <?=($_GET['filtro_fase'] ?? '') == 'Quartas de Final' ? 'selected' : ''?>>Quartas de Final</option>
                <option value="Semifinal" <?=($_GET['filtro_fase'] ?? '') == 'Semifinal' ? 'selected' : ''?>>Semifinal</option>
                <option value="3º Lugar / Bronze" <?=($_GET['filtro_fase'] ?? '') == '3º Lugar / Bronze' ? 'selected' : ''?>>3º Lugar / Bronze</option>
                <option value="Final" <?=($_GET['filtro_fase'] ?? '') == 'Final' ? 'selected' : ''?>>Final</option>
            </select>

            <select name="filtro_pais" class="form-select">
                <option value="">Todos os Países</option>
                <?php foreach($paises as $p): ?>
                    <option value="<?=$p['id']?>" <?=($_GET['filtro_pais'] ?? '') == $p['id'] ? 'selected' : ''?>><?=$p['nome']?></option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="btn-top">Filtrar</button>
            <a href="index.php" class="btn-top" style="background: var(--border-line);">Limpar</a>
        </form>

        <div style="margin-top: 20px;">
            <?php if (empty($historico_partidas)): ?>
                <p>Nenhuma partida encontrada com os filtros selecionados.</p>
            <?php else: ?>
                <?php foreach ($historico_partidas as $m): ?>
                    <div class="match-card">
                        <div>
                            <span class="badge-fase <?=$m['fase'] !== 'Fase de Grupos' ? 'badge-mata' : ''?>"><?=$m['fase']?></span>
                            <small style="opacity: 0.7; margin-left: 8px;"><?=date('d/m/Y', strtotime($m['data_partida']))?> (<?=$m['genero'] == 'F' ? 'Fem' : 'Masc'?>)</small>
                        </div>
                        
                        <div>
                            <img src="https://flagcdn.com/w40/<?=strtolower($m['sigla_casa'])?>.png" class="flag">
                            <strong><?=$m['nome_casa']?></strong>
                            <span style="font-size: 16px; font-weight: bold; margin: 0 10px; color: var(--txt-heading);">
                                <?=$m['pontos_casa']?> x <?=$m['pontos_fora']?>
                            </span>
                            <strong><?=$m['nome_fora']?></strong>
                            <img src="https://flagcdn.com/w40/<?=strtolower($m['sigla_fora'])?>.png" class="flag" style="margin-left: 5px;">
                        </div>

                        <div>
                            <a href="detalhe_jogo.php?id=<?=$m['id']?>" class="match-link">Detalhes do Jogo →</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
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
