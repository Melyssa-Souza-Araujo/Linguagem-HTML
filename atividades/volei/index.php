<?php
session_start();
include 'conexao.php';
/** @var PDO $pdo */

// Se o usuário não estiver logado em nenhuma conta, joga para a página de login
if (!isset($_SESSION['logado'])) {
    header("Location: login.php");
    exit;
}

$pais_filtro = isset($_GET['pais_filtro']) ? $_GET['pais_filtro'] : 'todos';
$fase_filtro = isset($_GET['fase_filtro']) ? $_GET['fase_filtro'] : 'todos';
$h2h_1 = isset($_GET['h2h_1']) ? $_GET['h2h_1'] : '';
$h2h_2 = isset($_GET['h2h_2']) ? $_GET['h2h_2'] : '';

$paises = $pdo->query("SELECT * FROM paises ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

$mapa_paises = [];
foreach($paises as $p) {
    $mapa_paises[$p['id']] = ['nome' => $p['nome'], 'sigla' => strtolower($p['sigla'])];
}

$tabela_F = []; $tabela_M = [];
foreach ($paises as $p) {
    $base = ['nome' => $p['nome'], 'sigla' => strtolower($p['sigla']), 'vitorias' => 0, 'derrotas' => 0, 'pontos' => 0, 'sets_pro' => 0, 'sets_contra' => 0, 'jogos_disputados' => 0, 'jogou' => false, 'historico_resultados' => []];
    $tabela_F[$p['id']] = $base; $tabela_M[$p['id']] = $base;
}

// Puxar todas as partidas organizadas por ID antigo para processar o histórico em ordem cronológica correta
$partidas_cronologicas = $pdo->query("SELECT p.*, t1.nome AS casa_nome, t2.nome AS fora_nome FROM partidas p JOIN paises t1 ON p.id_casa = t1.id JOIN paises t2 ON p.id_fora = t2.id ORDER BY p.id ASC")->fetchAll(PDO::FETCH_ASSOC);

$h2h_vitorias_1 = 0; $h2h_vitorias_2 = 0;

// Estrutura para o Chaveamento do Mata-Mata
$mata_mata = [
    'F' => ['Quartas' => [], 'Semifinal' => [], 'Final' => []],
    'M' => ['Quartas' => [], 'Semifinal' => [], 'Final' => []]
];

foreach ($partidas_cronologicas as $partida) {
    $casa = $partida['id_casa']; $fora = $partida['id_fora'];
    $p_casa = $partida['pontos_casa']; $p_fora = $partida['pontos_fora'];
    $genero = $partida['genero']; $fase_atual = $partida['fase'] ?? 'Fase de Grupos';

    // Organizar chaves de mata-mata
    if (in_array($fase_atual, ['Quartas de Final', 'Semifinal', 'Final'])) {
        $chave_fase = $fase_atual == 'Quartas de Final' ? 'Quartas' : ($fase_atual == 'Semifinal' ? 'Semifinal' : 'Final');
        $mata_mata[$genero][$chave_fase][] = $partida;
    }

    if (!empty($h2h_1) && !empty($h2h_2)) {
        if (($casa == $h2h_1 && $fora == $h2h_2) || ($casa == $h2h_2 && $fora == $h2h_1)) {
            if ($p_casa > $p_fora) {
                if ($casa == $h2h_1) $h2h_vitorias_1++; else $h2h_vitorias_2++;
            } else {
                if ($fora == $h2h_1) $h2h_vitorias_1++; else $h2h_vitorias_2++;
            }
        }
    }

    if ($genero == 'F') { $ref = &$tabela_F; } else { $ref = &$tabela_M; }

    if (!isset($ref[$casa])) continue;

    if ($fase_filtro == 'todos' || $fase_atual == $fase_filtro) {
        $ref[$casa]['jogou'] = true; $ref[$fora]['jogou'] = true;
        $ref[$casa]['jogos_disputados']++; $ref[$fora]['jogos_disputados']++;
        $ref[$casa]['sets_pro'] += $p_casa; $ref[$casa]['sets_contra'] += $p_fora;
        $ref[$fora]['sets_pro'] += $p_fora; $ref[$fora]['sets_contra'] += $p_casa;
    }

    if ($p_casa > $p_fora) {
        if ($fase_filtro == 'todos' || $fase_atual == $fase_filtro) {
            $ref[$casa]['vitorias']++; $ref[$fora]['derrotas']++;
            if ($p_casa == 3 && ($p_fora == 0 || $p_fora == 1)) $ref[$casa]['pontos'] += 3;
            elseif ($p_casa == 3 && $p_fora == 2) { $ref[$casa]['pontos'] += 2; $ref[$fora]['pontos'] += 1; }
            else { $ref[$casa]['pontos'] += 3; }
        }
        // Armazena no array de Streaks individuais (independente de filtros de visualização de fase)
        $tabela_F[$casa]['historico_resultados'][] = 'V'; $tabela_F[$fora]['historico_resultados'][] = 'D';
    } else {
        if ($fase_filtro == 'todos' || $fase_atual == $fase_filtro) {
            $ref[$fora]['vitorias']++; $ref[$casa]['derrotas']++;
            if ($p_fora == 3 && ($p_casa == 0 || $p_casa == 1)) $ref[$fora]['pontos'] += 3;
            elseif ($p_fora == 3 && $p_casa == 2) { $ref[$fora]['pontos'] += 2; $ref[$casa]['pontos'] += 1; }
            else { $ref[$fora]['pontos'] += 3; }
        }
        $tabela_F[$fora]['historico_resultados'][] = 'V'; $tabela_F[$casa]['historico_resultados'][] = 'D';
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

// Função auxiliar interna para gerar as Badges de Sequência (Streak)
function calcularBadgeStreak($historico) {
    if (empty($historico)) return "<span class='streak-badge empty'>-</span>";
    $ultimos = array_slice($historico, -5); // Pega os últimos 5 jogos disputados
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
    <title>Classificação Geral VNL</title>
    <style>
        :root {
            --bg-body: #0d1b2a; --txt-main: #f4f4f9; --txt-heading: #e0e1dd;
            --bg-card: #1b263b; --border-line: #415a77; --bg-g8: #1f3147;
            --btn-bg: #e0e1dd; --btn-txt: #0d1b2a;
        }
        [data-theme="light"] {
            --bg-body: #f4f6f9; --txt-main: #1e293b; --txt-heading: #0f172a;
            --bg-card: #ffffff; --border-line: #cbd5e1; --bg-g8: #f1f5f9;
            --btn-bg: #0f172a; --btn-txt: #ffffff;
        }

        /* ANIMAÇÃO NA TRANSIÇÃO DO TEMA (SUA SOLICITAÇÃO) */
        body, .box, table, tr, td, th, input, select, .partida-card, .bracket-game {
            transition: all 0.4s ease;
        }

        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: var(--bg-body); color: var(--txt-main); }
        h1, h2, h3 { text-align: center; color: var(--txt-heading); }
        h2 { margin-top: 40px; border-bottom: 2px solid var(--border-line); padding-bottom: 10px; }
        
        .header-top { display: flex; justify-content: space-between; align-items: center; max-width: 950px; margin: 0 auto; flex-wrap: wrap; gap: 10px; }
        .theme-toggle, .btn-logout { background: var(--btn-bg); color: var(--btn-txt); border: none; padding: 8px 15px; font-weight: bold; border-radius: 20px; cursor: pointer; text-decoration: none; font-size: 13px; }

        .secao-filtros { background: var(--bg-card); padding: 15px; border-radius: 8px; max-width: 950px; margin: 20px auto; text-align: center; border: 1px solid var(--border-line); display: flex; justify-content: center; gap: 10px; flex-wrap: wrap; align-items: center; }
        .secao-filtros select { padding: 8px; border-radius: 4px; background: var(--bg-body); color: var(--txt-main); border: 1px solid var(--border-line); }
        .secao-filtros button { padding: 8px 20px; background: #2a9d8f; color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; }

        .tabelas-container { display: flex; flex-direction: column; gap: 40px; align-items: center; margin-top: 20px; width: 100%; }
        .tabela-bloco { width: 100%; max-width: 950px; }
        .table-responsive { width: 100%; overflow-x: auto; border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; background: var(--bg-card); min-width: 750px; }
        th, td { padding: 12px 10px; text-align: center; font-size: 14px; }
        th { background-color: var(--border-line); color: var(--txt-heading); }
        tr { border-bottom: 1px solid var(--border-line); }
        .g8-zona { background-color: var(--bg-g8); border-left: 5px solid #2a9d8f; }

        .nome-pais { text-align: left; font-weight: bold; display: flex; align-items: center; }
        .flag { width: 22px; height: 15px; margin-right: 8px; border-radius: 2px; object-fit: cover; }

        /* ESTILO DO STREAK (SEQUÊNCIA) */
        .streak-dot { display: inline-block; width: 18px; height: 18px; line-height: 18px; text-align: center; border-radius: 50%; font-size: 10px; font-weight: bold; margin: 0 2px; color: #fff; }
        .streak-dot.vitoria { background-color: #2a9d8f; }
        .streak-dot.derrota { background-color: #e63946; }

        /* ESTILOS DA ÁRVORE GRÁFICA DO MATA-MATA */
        .bracket-container { display: flex; justify-content: space-around; align-items: center; background: var(--bg-card); padding: 20px; border-radius: 8px; margin: 20px auto; max-width: 950px; border: 1px solid var(--border-line); flex-wrap: wrap; gap: 20px; }
        .bracket-column { display: flex; flex-direction: column; gap: 15px; justify-content: center; }
        .bracket-game { background: var(--bg-body); padding: 10px; border-radius: 6px; border: 1px solid var(--border-line); min-width: 180px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
        .bracket-team { display: flex; justify-content: space-between; align-items: center; font-size: 12px; padding: 3px 0; font-weight: bold; }
        .bracket-score { background: var(--border-line); padding: 1px 6px; border-radius: 3px; font-size: 11px; }

        .btn-gerenciar { display: block; width: 220px; margin: 20px auto; text-align: center; background: #e76f51; color: white; padding: 10px; border-radius: 4px; text-decoration: none; font-weight: bold; }
        .h2h-box { background: var(--bg-card); border: 1px solid var(--border-line); padding: 20px; border-radius: 8px; max-width: 950px; margin: 30px auto; text-align: center; }
    </style>
</head>
<body>

    <div class="header-top">
        <div>Olá, <strong><?=htmlspecialchars($_SESSION['usuario_login'])?></strong> (<span style="text-transform: uppercase; font-size: 11px; color: #2a9d8f;"><?=$_SESSION['usuario_nivel']?></span>)</div>
        <div>
            <button class="theme-toggle" onclick="toggleTheme()" id="btnTema">☀️ Modo Claro</button>
            <a href="logout.php" class="btn-logout" style="background:#e63946; color:#fff; margin-left: 10px;">🚪 Sair</a>
        </div>
    </div>

    <h1>PAINEL DE CLASSIFICAÇÃO VNL</h1>

    <?php if ($_SESSION['usuario_nivel'] === 'admin'): ?>
        <a href="cadastro.php" class="btn-gerenciar">⚙️ Painel de Cadastro (Admin) →</a>
    <?php endif; ?>

    <h2>🌴 Árvore de Chaveamento do Mata-Mata (Feminino & Masculino)</h2>
    <?php foreach(['F' => 'Feminino', 'M' => 'Masculino'] as $g_key => $g_nome): ?>
        <h3 style="margin-top:20px; font-size:14px; color:#2a9d8f;">Fase Eliminatória - <?=$g_nome?></h3>
        <div class="bracket-container">
            <div class="bracket-column">
                <span style="font-size:10px; text-transform:uppercase; text-align:center; display:block; color:#888;">Quartas de Final</span>
                <?php if(empty($mata_mata[$g_key]['Quartas'])): ?><p style="font-size:11px; color:#666;">Aguardando definição</p><?php endif; ?>
                <?php foreach($mata_mata[$g_key]['Quartas'] as $jogo): ?>
                    <div class="bracket-game">
                        <div class="bracket-team"><span><?=$mapa_paises[$jogo['id_casa']]['nome']?></span> <span class="bracket-score"><?=$jogo['pontos_casa']?></span></div>
                        <div class="bracket-team"><span><?=$mapa_paises[$jogo['id_fora']]['nome']?></span> <span class="bracket-score"><?=$jogo['pontos_fora']?></span></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="bracket-column">
                <span style="font-size:10px; text-transform:uppercase; text-align:center; display:block; color:#888;">Semifinal</span>
                <?php if(empty($mata_mata[$g_key]['Semifinal'])): ?><p style="font-size:11px; color:#666;">Aguardando definição</p><?php endif; ?>
                <?php foreach($mata_mata[$g_key]['Semifinal'] as $jogo): ?>
                    <div class="bracket-game" style="border-color:#2a9d8f;">
                        <div class="bracket-team"><span><?=$mapa_paises[$jogo['id_casa']]['nome']?></span> <span class="bracket-score"><?=$jogo['pontos_casa']?></span></div>
                        <div class="bracket-team"><span><?=$mapa_paises[$jogo['id_fora']]['nome']?></span> <span class="bracket-score"><?=$jogo['pontos_fora']?></span></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="bracket-column">
                <span style="font-size:10px; text-transform:uppercase; text-align:center; display:block; color:#e76f51; font-weight:bold;">🏆 Grande Final</span>
                <?php if(empty($mata_mata[$g_key]['Final'])): ?><p style="font-size:11px; color:#666;">Aguardando definição</p><?php endif; ?>
                <?php foreach($mata_mata[$g_key]['Final'] as $jogo): ?>
                    <div class="bracket-game" style="border-color:#e76f51; background:#2d1a1a;">
                        <div class="bracket-team" style="color:#e76f51;"><span><?=$mapa_paises[$jogo['id_casa']]['nome']?></span> <span class="bracket-score"><?=$jogo['pontos_casa']?></span></div>
                        <div class="bracket-team" style="color:#e76f51;"><span><?=$mapa_paises[$jogo['id_fora']]['nome']?></span> <span class="bracket-score"><?=$jogo['pontos_fora']?></span></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="secao-filtros">
        <form method="GET" action="index.php">
            <select name="fase_filtro">
                <option value="todos" <?=$fase_filtro == 'todos'?'selected':''?>>-- Todas as Fases --</option>
                <option value="Fase de Grupos" <?=$fase_filtro == 'Fase de Grupos'?'selected':''?>>Fase de Grupos</option>
                <option value="Quartas de Final" <?=$fase_filtro == 'Quartas de Final'?'selected':''?>>Quartas de Final</option>
                <option value="Semifinal" <?=$fase_filtro == 'Semifinal'?'selected':''?>>Semifinal</option>
                <option value="Final" <?=$fase_filtro == 'Final'?'selected':''?>>Final</option>
            </select>
            <button type="submit">Filtrar Tabela</button>
        </form>
    </div>

    <div class="tabelas-container">
        <div class="tabela-bloco">
            <h2>Classificação Feminina (<?=$fase_filtro == 'todos' ? 'Geral' : $fase_filtro?>)</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr><th>Pos</th><th style="text-align:left;">País</th><th>J</th><th>V</th><th>D</th><th>Pts</th><th>Sets P</th><th>Sets C</th><th>Forma (Últimos 5)</th></tr>
                    </thead>
                    <tbody>
                        <?php $pos = 1; foreach ($tabela_F as $id_p => $item): ?>
                        <tr class="<?=($pos <= 8)?'g8-zona':''?>">
                            <td><strong><?=$pos?></strong></td>
                            <td class="nome-pais"><img class="flag" src="https://flagcdn.com/w40/<?=$item['sigla']?>.png"> <?=$item['nome']?></td>
                            <td><?=$item['jogos_disputados']?></td>
                            <td style="color:#2a9d8f; font-weight:bold;"><?=$item['vitorias']?></td>
                            <td style="color:#e63946;"><?=$item['derrotas']?></td>
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

        <div class="tabela-bloco">
            <h2>Classificação Masculina (<?=$fase_filtro == 'todos' ? 'Geral' : $fase_filtro?>)</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr><th>Pos</th><th style="text-align:left;">País</th><th>J</th><th>V</th><th>D</th><th>Pts</th><th>Sets P</th><th>Sets C</th><th>Forma (Últimos 5)</th></tr>
                    </thead>
                    <tbody>
                        <?php $pos = 1; foreach ($tabela_M as $id_p => $item): ?>
                        <tr class="<?=($pos <= 8)?'g8-zona':''?>">
                            <td><strong><?=$pos?></strong></td>
                            <td class="nome-pais"><img class="flag" src="https://flagcdn.com/w40/<?=$item['sigla']?>.png"> <?=$item['nome']?></td>
                            <td><?=$item['jogos_disputados']?></td>
                            <td style="color:#2a9d8f; font-weight:bold;"><?=$item['vitorias']?></td>
                            <td style="color:#e63946;"><?=$item['derrotas']?></td>
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
