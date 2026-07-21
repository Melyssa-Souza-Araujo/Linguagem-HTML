<?php
session_start();
include 'conexao.php';
/** @var PDO $pdo */

if (!isset($_SESSION['logado'])) {
    header("Location: login.php");
    exit;
}

$genero_filtro = isset($_GET['genero_filtro']) ? $_GET['genero_filtro'] : 'todos';
$pais_filtro = isset($_GET['pais_filtro']) ? $_GET['pais_filtro'] : 'todos';
$data_filtro = isset($_GET['data_filtro']) ? $_GET['data_filtro'] : '';

$paises = $pdo->query("SELECT * FROM paises ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

$tabela_F = []; $tabela_M = [];
foreach ($paises as $p) {
    $base = ['nome' => $p['nome'], 'sigla' => strtolower($p['sigla']), 'vitorias' => 0, 'derrotas' => 0, 'pontos' => 0, 'sets_pro' => 0, 'sets_contra' => 0, 'jogos_disputados' => 0, 'jogou' => false, 'historico_resultados' => []];
    $tabela_F[$p['id']] = $base; $tabela_M[$p['id']] = $base;
}

$todas_partidas = $pdo->query("SELECT * FROM partidas ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

foreach ($todas_partidas as $partida) {
    $casa = $partida['id_casa']; $fora = $partida['id_fora'];
    $p_casa = $partida['pontos_casa']; $p_fora = $partida['pontos_fora'];
    $genero = $partida['genero'];

    if ($genero == 'F') { $ref = &$tabela_F; } else { $ref = &$tabela_M; }
    if (!isset($ref[$casa])) continue;

    $ref[$casa]['jogou'] = true; $ref[$fora]['jogou'] = true;
    $ref[$casa]['jogos_disputados']++; $ref[$fora]['jogos_disputados']++;
    $ref[$casa]['sets_pro'] += $p_casa; $ref[$casa]['sets_contra'] += $p_fora;
    $ref[$fora]['sets_pro'] += $p_fora; $ref[$fora]['sets_contra'] += $p_casa;

    if ($p_casa > $p_fora) {
        $ref[$casa]['vitorias']++; $ref[$fora]['derrotas']++;
        if ($p_casa == 3 && ($p_fora == 0 || $p_fora == 1)) $ref[$casa]['pontos'] += 3;
        elseif ($p_casa == 3 && $p_fora == 2) { $ref[$casa]['pontos'] += 2; $ref[$fora]['pontos'] += 1; }
        else { $ref[$casa]['pontos'] += 3; }
        $ref[$casa]['historico_resultados'][] = 'V'; $ref[$fora]['historico_resultados'][] = 'D';
    } else {
        $ref[$fora]['vitorias']++; $ref[$casa]['derrotas']++;
        if ($p_fora == 3 && ($p_casa == 0 || $p_casa == 1)) $ref[$fora]['pontos'] += 3;
        elseif ($p_fora == 3 && $p_casa == 2) { $ref[$fora]['pontos'] += 2; $ref[$casa]['pontos'] += 1; }
        else { $ref[$fora]['pontos'] += 3; }
        $ref[$fora]['historico_resultados'][] = 'V'; $ref[$casa]['historico_resultados'][] = 'D';
    }
}

$tabela_F = array_filter($tabela_F, function($item) { return $item['jogou'] === true; });
$tabela_M = array_filter($tabela_M, function($item) { return $item['jogou'] === true; });

$ordenar_fivb = function($a, $b) {
    if ($a['vitorias'] != $b['vitorias']) return $b['vitorias'] <=> $a['vitorias'];
    if ($a['pontos'] != $b['pontos']) return $b['pontos'] <=> $a['pontos'];
    $ratio_a = $a['sets_contra'] > 0 ? ($a['sets_pro'] / $a['sets_contra']) : $a['sets_pro'];
    $ratio_b = $b['sets_contra'] > 0 ? ($b['sets_pro'] / $b['sets_contra']) : $b['sets_pro'];
    return $ratio_b <=> $ratio_a;
};
uasort($tabela_F, $ordenar_fivb); uasort($tabela_M, $ordenar_fivb);

$sql_historico = "SELECT p.*, t1.nome AS casa_nome, t1.sigla AS casa_sigla, t2.nome AS fora_nome, t2.sigla AS fora_sigla 
                  FROM partidas p 
                  JOIN paises t1 ON p.id_casa = t1.id 
                  JOIN paises t2 ON p.id_fora = t2.id 
                  WHERE 1=1";
$params_historico = [];

if ($genero_filtro !== 'todos') {
    $sql_historico .= " AND p.genero = ?";
    $params_historico[] = $genero_filtro;
}
if ($pais_filtro !== 'todos') {
    $sql_historico .= " AND (p.id_casa = ? OR p.id_fora = ?)";
    $params_historico[] = $pais_filtro;
    $params_historico[] = $pais_filtro;
}
if (!empty($data_filtro)) {
    $sql_historico .= " AND p.data_partida = ?";
    $params_historico[] = $data_filtro;
}

$sql_historico .= " ORDER BY p.data_partida DESC, p.id DESC";
$stmt_hist = $pdo->prepare($sql_historico);
$stmt_hist->execute($params_historico);
$historico_partidas = $stmt_hist->fetchAll(PDO::FETCH_ASSOC);

function calcularBadgeStreak($historico) {
    if (empty($historico)) return "<span class='streak-badge empty'>-</span>";
    $ultimos = array_slice($historico, -5);
    $html = "";
    foreach($ultimos as $res) {
        $classe = ($res == 'V') ? 'vitoria' : 'derrota';
        $html .= "<span class='streak-dot $classe'>$res</span>";
    }
    return $html;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VNL - Classificação e Histórico</title>
    <style>
        :root {
            --bg-body: #0b132b; 
            --txt-main: #f1f5f9; 
            --txt-heading: #48cae4;
            --bg-card: #1c2541; 
            --border-line: #3a506b; 
            --bg-g8: #14213d;
            --btn-bg: #48cae4; 
            --btn-txt: #0b132b;
            --accent-blue: #00b4d8;
        }
        [data-theme="light"] {
            --bg-body: #e0f2fe; 
            --txt-main: #0f172a; 
            --txt-heading: #0369a1;
            --bg-card: #ffffff; 
            --border-line: #bae6fd; 
            --bg-g8: #f0f9ff;
            --btn-bg: #0284c7; 
            --btn-txt: #ffffff;
            --accent-blue: #0284c7;
        }

        body, .box, table, tr, td, th, input, select, .partida-card {
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
        }

        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: var(--bg-body); color: var(--txt-main); }
        h1, h2, h3 { text-align: center; color: var(--txt-heading); }
        h1 { font-weight: 800; letter-spacing: 1px; }
        h2 { margin-top: 40px; border-bottom: 2px solid var(--border-line); padding-bottom: 10px; }
        
        .header-top { display: flex; justify-content: space-between; align-items: center; max-width: 950px; margin: 0 auto; flex-wrap: wrap; gap: 10px; }
        .theme-toggle, .btn-logout { background: var(--btn-bg); color: var(--btn-txt); border: none; padding: 8px 18px; font-weight: bold; border-radius: 20px; cursor: pointer; text-decoration: none; font-size: 13px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .theme-toggle:hover { filter: brightness(1.1); }

        .secao-filtros { background: var(--bg-card); padding: 15px; border-radius: 8px; max-width: 950px; margin: 20px auto; text-align: center; border: 1px solid var(--border-line); display: flex; justify-content: center; gap: 12px; flex-wrap: wrap; align-items: center; }
        .secao-filtros select, .secao-filtros input { padding: 8px; border-radius: 4px; background: var(--bg-body); color: var(--txt-main); border: 1px solid var(--border-line); font-size: 13px; font-weight: bold; }
        .secao-filtros button { padding: 8px 20px; background: var(--accent-blue); color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; }
        .secao-filtros button:hover { filter: brightness(1.1); }
        .secao-filtros a { padding: 8px 12px; background: #ef4444; color: white; text-decoration: none; border-radius: 4px; font-size: 13px; font-weight: bold; }

        .tabelas-container { display: flex; flex-direction: column; gap: 40px; align-items: center; margin-top: 20px; width: 100%; }
        .tabela-bloco { width: 100%; max-width: 950px; }
        .table-responsive { width: 100%; overflow-x: auto; border-radius: 8px; border: 1px solid var(--border-line); }
        table { width: 100%; border-collapse: collapse; background: var(--bg-card); min-width: 750px; }
        th, td { padding: 12px 10px; text-align: center; font-size: 14px; }
        th { background-color: var(--border-line); color: var(--txt-main); font-weight: bold; }
        tr { border-bottom: 1px solid var(--border-line); }
        .g8-zona { background-color: var(--bg-g8); border-left: 5px solid var(--accent-blue); }

        .nome-pais { text-align: left; font-weight: bold; display: flex; align-items: center; }
        .flag { width: 22px; height: 15px; margin-right: 8px; border-radius: 2px; object-fit: cover; vertical-align: middle; }

        .streak-dot { display: inline-block; width: 18px; height: 18px; line-height: 18px; text-align: center; border-radius: 50%; font-size: 10px; font-weight: bold; margin: 0 2px; color: #fff; }
        .streak-dot.vitoria { background-color: #10b981; }
        .streak-dot.derrota { background-color: #ef4444; }

        .btn-gerenciar { display: block; width: 250px; margin: 20px auto; text-align: center; background: var(--accent-blue); color: white; padding: 12px; border-radius: 6px; text-decoration: none; font-weight: bold; box-shadow: 0 3px 6px rgba(0,0,0,0.1); }
        .btn-gerenciar:hover { filter: brightness(1.1); }
        
        .historico-container { max-width: 950px; margin: 20px auto; display: flex; flex-direction: column; gap: 12px; }
        .link-card-jogo { text-decoration: none; color: inherit; display: block; }
        .partida-card { background: var(--bg-card); border: 1px solid var(--border-line); padding: 15px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .partida-card:hover { border-color: var(--accent-blue); box-shadow: 0 2px 12px rgba(3, 105, 161, 0.25); }
        
        .partida-info { font-size: 12px; color: var(--txt-main); opacity: 0.8; display: flex; flex-direction: column; gap: 4px; }
        .partida-confronto { display: flex; align-items: center; gap: 20px; font-size: 16px; font-weight: bold; }
        .time-box { display: flex; align-items: center; gap: 8px; }
        .placar-box { background: var(--bg-body); padding: 5px 15px; border-radius: 20px; border: 1px solid var(--border-line); font-size: 15px; letter-spacing: 2px; font-weight: bold; color: var(--txt-heading); }
        .genero-badge { display: inline-block; padding: 2px 7px; border-radius: 4px; font-size: 11px; font-weight: bold; color: #fff; text-transform: uppercase; }
        .badge-f { background-color: #ec4899; }
        .badge-m { background-color: #3b82f6; }
        
        .texto-grafico { font-size: 12px; color: var(--accent-blue); font-weight: bold; display: flex; align-items: center; gap: 4px; }
    </style>
</head>
<body>

    <div class="header-top">
        <div>Olá, <strong><?=htmlspecialchars($_SESSION['usuario_login'])?></strong> (<span style="text-transform: uppercase; font-size: 11px; color: var(--accent-blue); font-weight: bold;"><?=$_SESSION['usuario_nivel']?></span>)</div>
        <div style="display: flex; gap: 8px; align-items: center;">
            <a href="relatorio.php" class="btn-logout" style="background:var(--accent-blue); color:#fff;">📊 Ver Relatórios</a>
            <button class="theme-toggle" onclick="toggleTheme()" id="btnTema">☀️ Modo Claro</button>
            <a href="logout.php" class="btn-logout" style="background:#ef4444; color:#fff;">🚪 Sair</a>
        </div>
    </div>

    <h1>PANORAMA DE CLASSIFICAÇÃO VNL</h1>

    <?php if ($_SESSION['usuario_nivel'] === 'admin'): ?>
        <a href="cadastro.php" class="btn-gerenciar">⚙️ Painel de Cadastro (Admin) →</a>
    <?php endif; ?>

    <div class="tabelas-container">
        <!-- CLASSIFICAÇÃO FEMININA -->
        <div class="tabela-bloco">
            <h2>Classificação Geral Feminina</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr><th>Pos</th><th style="text-align:left;">País</th><th>J</th><th>V</th><th>D</th><th>Pts</th><th>Sets P</th><th>Sets C</th><th>Forma</th></tr>
                    </thead>
                    <tbody>
                        <?php $pos = 1; foreach ($tabela_F as $id_p => $item): ?>
                        <tr class="<?=($pos <= 8)?'g8-zona':''?>">
                            <td><strong><?=$pos?></strong></td>
                            <td class="nome-pais"><img class="flag" src="https://flagcdn.com/w40/<?=$item['sigla']?>.png"> <?=$item['nome']?></td>
                            <td><?=$item['jogos_disputados']?></td>
                            <td style="color:#10b981; font-weight:bold;"><?=$item['vitorias']?></td>
                            <td style="color:#ef4444;"><?=$item['derrotas']?></td>
                            <td><strong><?=$item['pontos']?></strong></td>
                            <td><?=$item['sets_pro']?></td>
                            <td><?=$item['sets_contra']?></td>
                            <td><?=calcularBadgeStreak($item['historico_resultados'])?></td>
                        </tr>
                        <?php $pos++; endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- CLASSIFICAÇÃO MASCULINA -->
        <div class="tabela-bloco">
            <h2>Classificação Geral Masculina</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr><th>Pos</th><th style="text-align:left;">País</th><th>J</th><th>V</th><th>D</th><th>Pts</th><th>Sets P</th><th>Sets C</th><th>Forma</th></tr>
                    </thead>
                    <tbody>
                        <?php $pos = 1; foreach ($tabela_M as $id_p => $item): ?>
                        <tr class="<?=($pos <= 8)?'g8-zona':''?>">
                            <td><strong><?=$pos?></strong></td>
                            <td class="nome-pais"><img class="flag" src="https://flagcdn.com/w40/<?=$item['sigla']?>.png"> <?=$item['nome']?></td>
                            <td><?=$item['jogos_disputados']?></td>
                            <td style="color:#10b981; font-weight:bold;"><?=$item['vitorias']?></td>
                            <td style="color:#ef4444;"><?=$item['derrotas']?></td>
                            <td><strong><?=$item['pontos']?></strong></td>
                            <td><?=$item['sets_pro']?></td>
                            <td><?=$item['sets_contra']?></td>
                            <td><?=calcularBadgeStreak($item['historico_resultados'])?></td>
                        </tr>
                        <?php $pos++; endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <h2>🔎 Histórico de Partidas Registradas</h2>

    <div class="secao-filtros">
        <form method="GET" action="index.php" style="display: flex; gap: 10px; flex-wrap: wrap; justify-content: center; align-items: center; margin: 0;">
            <select name="genero_filtro">
                <option value="todos" <?=$genero_filtro == 'todos'?'selected':''?>>Todos os Gêneros</option>
                <option value="M" <?=$genero_filtro == 'M'?'selected':''?>>Masculino</option>
                <option value="F" <?=$genero_filtro == 'F'?'selected':''?>>Feminino</option>
            </select>

            <select name="pais_filtro">
                <option value="todos" <?=$pais_filtro == 'todos'?'selected':''?>>Todos os Países</option>
                <?php foreach($paises as $p): ?>
                    <option value="<?=$p['id']?>" <?=$pais_filtro == $p['id']?'selected':''?>><?=$p['nome']?></option>
                <?php endforeach; ?>
            </select>

            <input type="date" name="data_filtro" value="<?=$data_filtro?>">

            <button type="submit">Filtrar Histórico</button>
            <a href="index.php">Limpar</a>
        </form>
    </div>

    <div class="historico-container">
        <?php if(empty($historico_partidas)): ?>
            <p style="text-align:center; color:var(--txt-main); opacity:0.6; padding:20px;">Nenhuma partida encontrada para os filtros selecionados.</p>
        <?php endif; ?>

        <?php foreach($historico_partidas as $partida): ?>
            <a href="detalhe_jogo.php?id=<?=$partida['id']?>" class="link-card-jogo" title="Clique para ver a evolução gráfica dos sets">
                <div class="partida-card">
                    <div class="partida-info">
                        <span>📅 <?=date('d/m/Y', strtotime($partida['data_partida']))?></span>
                        <span>📌 <?=$partida['fase']?></span>
                        <span>
                            <span class="genero-badge <?=($partida['genero']=='F')?'badge-f':'badge-m'?>">
                                <?=($partida['genero']=='F')?'Feminino':'Masculino'?>
                            </span>
                        </span>
                    </div>

                    <div class="partida-confronto">
                        <div class="time-box">
                            <img class="flag" src="https://flagcdn.com/w40/<?=strtolower($partida['casa_sigla'])?>.png">
                            <span><?=$partida['casa_nome']?></span>
                        </div>
                        
                        <div class="placar-box">
                            <?=$partida['pontos_casa']?>x<?=$partida['pontos_fora']?>
                        </div>

                        <div class="time-box">
                            <span><?=$partida['fora_nome']?></span>
                            <img class="flag" src="https://flagcdn.com/w40/<?=strtolower($partida['fora_sigla'])?>.png">
                        </div>
                    </div>

                    <div class="texto-grafico">
                        <span>📊 Gráfico →</span>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
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
        const savedTheme = localStorage.getItem('vnl-theme') || 'dark';
        applyTheme(savedTheme);
    </script>
</body>
</html>
