<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'conexao.php';

$pais_filtro = isset($_GET['pais_filtro']) ? $_GET['pais_filtro'] : 'todos';
$fase_filtro = isset($_GET['fase_filtro']) ? $_GET['fase_filtro'] : 'todos';
$h2h_1 = isset($_GET['h2h_1']) ? $_GET['h2h_1'] : '';
$h2h_2 = isset($_GET['h2h_2']) ? $_GET['h2h_2'] : '';

// 1. Puxar países (com suas respectivas siglas de bandeira)
$paises = $pdo->query("SELECT * FROM paises ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

// Cria mapeamento rápido de ID para Sigla/Nome para otimizar os cards e H2H
$mapa_paises = [];
foreach($paises as $p) {
    $mapa_paises[$p['id']] = ['nome' => $p['nome'], 'sigla' => strtolower($p['sigla'])];
}

$tabela_F = []; $tabela_M = [];
foreach ($paises as $p) {
    $base = ['nome' => $p['nome'], 'sigla' => strtolower($p['sigla']), 'vitorias' => 0, 'derrotas' => 0, 'pontos' => 0, 'sets_pro' => 0, 'sets_contra' => 0, 'jogos_disputados' => 0, 'jogou' => false];
    $tabela_F[$p['id']] = $base; $tabela_M[$p['id']] = $base;
}

// 2. Puxar todas as partidas gravadas
$partidas_todas = $pdo->query("SELECT p.*, t1.nome AS casa_nome, t2.nome AS fora_nome FROM partidas p JOIN paises t1 ON p.id_casa = t1.id JOIN paises t2 ON p.id_fora = t2.id ORDER BY p.id DESC")->fetchAll(PDO::FETCH_ASSOC);

$h2h_vitorias_1 = 0; $h2h_vitorias_2 = 0; $h2h_jogos = [];

foreach ($partidas_todas as $partida) {
    $casa = $partida['id_casa']; $fora = $partida['id_fora'];
    $p_casa = $partida['pontos_casa']; $p_fora = $partida['pontos_fora'];
    $genero = $partida['genero']; $fase_atual = $partida['fase'] ?? 'Fase de Grupos';

    if (!empty($h2h_1) && !empty($h2h_2)) {
        if (($casa == $h2h_1 && $fora == $h2h_2) || ($casa == $h2h_2 && $fora == $h2h_1)) {
            $h2h_jogos[] = $partida;
            if ($p_casa > $p_fora) {
                if ($casa == $h2h_1) $h2h_vitorias_1++; else $h2h_vitorias_2++;
            } else {
                if ($fora == $h2h_1) $h2h_vitorias_1++; else $h2h_vitorias_2++;
            }
        }
    }

    if ($fase_filtro != 'todos' && $fase_atual != $fase_filtro) continue;

    if ($genero == 'F') {
        if (!isset($tabela_F[$casa])) continue;
        $tabela_F[$casa]['jogou'] = true; $tabela_F[$fora]['jogou'] = true; $ref = &$tabela_F;
    } else {
        if (!isset($tabela_M[$casa])) continue;
        $tabela_M[$casa]['jogou'] = true; $tabela_M[$fora]['jogou'] = true; $ref = &$tabela_M;
    }

    $ref[$casa]['jogos_disputados']++; $ref[$fora]['jogos_disputados']++;
    $ref[$casa]['sets_pro'] += $p_casa; $ref[$casa]['sets_contra'] += $p_fora;
    $ref[$fora]['sets_pro'] += $p_fora; $ref[$fora]['sets_contra'] += $p_casa;

    if ($p_casa > $p_fora) {
        $ref[$casa]['vitorias']++; $ref[$fora]['derrotas']++;
        if ($p_casa == 3 && ($p_fora == 0 || $p_fora == 1)) $ref[$casa]['pontos'] += 3;
        elseif ($p_casa == 3 && $p_fora == 2) { $ref[$casa]['pontos'] += 2; $ref[$fora]['pontos'] += 1; }
    } else {
        $ref[$fora]['vitorias']++; $ref[$casa]['derrotas']++;
        if ($p_fora == 3 && ($p_casa == 0 || $p_casa == 1)) $ref[$fora]['pontos'] += 3;
        elseif ($p_fora == 3 && $p_casa == 2) { $ref[$fora]['pontos'] += 2; $ref[$casa]['pontos'] += 1; }
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

$historico_filtrado = [];
foreach ($partidas_todas as $part) {
    if (($pais_filtro == 'todos' || $part['id_casa'] == $pais_filtro || $part['id_fora'] == $pais_filtro) && ($fase_filtro == 'todos' || ($part['fase'] ?? 'Fase de Grupos') == $fase_filtro)) {
        $historico_filtrado[] = $part;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classificação VNL Premium</title>
    <style>
        /* Variáveis de Cores (Suporte Nativo a Temas) */
        :root {
            --bg-body: #0d1b2a; --txt-main: #f4f4f9; --txt-heading: #e0e1dd;
            --bg-card: #1b263b; --border-line: #415a77; --bg-g8: #1f3147;
            --btn-bg: #e0e1dd; --btn-txt: #0d1b2a; --btn-hover: #cbd5e1;
        }
        [data-theme="light"] {
            --bg-body: #f4f6f9; --txt-main: #1e293b; --txt-heading: #0f172a;
            --bg-card: #ffffff; --border-line: #cbd5e1; --bg-g8: #f1f5f9;
            --btn-bg: #0f172a; --btn-txt: #ffffff; --btn-hover: #1e293b;
        }

        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: var(--bg-body); color: var(--txt-main); transition: background 0.3s, color 0.3s; }
        h1, h2, h3 { text-align: center; color: var(--txt-heading); }
        h2 { margin-top: 40px; border-bottom: 2px solid var(--border-line); padding-bottom: 10px; }
        
        /* Botão Alternador de Tema */
        .theme-toggle { position: fixed; top: 15px; right: 15px; background: var(--btn-bg); color: var(--btn-txt); border: none; padding: 8px 15px; font-weight: bold; border-radius: 20px; cursor: pointer; box-shadow: 0 4px 6px rgba(0,0,0,0.15); z-index: 1000; }

        /* Central de Filtros Responsiva */
        .secao-filtros { background: var(--bg-card); padding: 15px; border-radius: 8px; max-width: 950px; margin: 20px auto; text-align: center; border: 1px solid var(--border-line); display: flex; justify-content: center; gap: 10px; flex-wrap: wrap; align-items: center; }
        .secao-filtros select { padding: 8px; border-radius: 4px; background: var(--bg-body); color: var(--txt-main); font-weight: bold; border: 1px solid var(--border-line); }
        .secao-filtros button { padding: 8px 20px; background: #2a9d8f; color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; }

        /* Estrutura Flexbox das Tabelas (Responsividade Nativa) */
        .tabelas-container { display: flex; flex-direction: column; gap: 40px; align-items: center; margin-top: 20px; width: 100%; }
        .tabela-bloco { width: 100%; max-width: 950px; }
        
        /* Container de rolagem para telas muito pequenas de celular */
        .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.15); }
        table { width: 100%; border-collapse: collapse; background: var(--bg-card); min-width: 650px; }
        th, td { padding: 12px 10px; text-align: center; font-size: 14px; }
        th { background-color: var(--border-line); color: var(--txt-heading); font-size: 11px; text-transform: uppercase; }
        tr { border-bottom: 1px solid var(--border-line); }
        .g8-zona { background-color: var(--bg-g8); border-left: 5px solid #2a9d8f; }

        /* Ajuste do nome do país com a bandeira */
        .nome-pais { text-align: left; font-weight: bold; display: flex; align-items: center; }
        .flag { width: 22px; height: 15px; margin-right: 8px; border-radius: 2px; border: 1px solid var(--border-line); object-fit: cover; }

        .historico-container { max-width: 850px; margin: 20px auto; }
        .partida-card { background: var(--bg-card); padding: 15px; margin-bottom: 12px; border-radius: 6px; display: flex; justify-content: space-between; align-items: center; border-left: 5px solid var(--border-line); border: 1px solid var(--border-line); }
        .partida-placar { background: var(--bg-body); padding: 6px 15px; border-radius: 4px; color: #2a9d8f; font-size: 18px; font-weight: bold; min-width: 60px; text-align: center; }
        
        .btn-gerenciar { display: block; width: 200px; margin: 20px auto; text-align: center; background: var(--btn-bg); color: var(--btn-txt); padding: 10px; border-radius: 4px; text-decoration: none; font-weight: bold; }
        .h2h-box { background: var(--bg-card); border: 2px dashed #2a9d8f; padding: 20px; border-radius: 8px; max-width: 850px; margin: 30px auto; text-align: center; }
        .h2h-placar-geral { font-size: 24px; font-weight: bold; margin: 15px 0; color: #e76f51; display: flex; justify-content: center; align-items: center; gap: 10px; }

        /* Regras adicionais de responsividade para telas pequenas (Mobile First UI) */
        @media (max-width: 600px) {
            body { padding: 10px; }
            .secao-filtros form { display: flex; flex-direction: column; width: 100%; gap: 8px; }
            .secao-filtros select, .secao-filtros button { width: 100%; }
            .partida-card { flex-direction: column; text-align: center; gap: 10px; }
            .theme-toggle { padding: 5px 10px; font-size: 12px; }
        }
    </style>
</head>
<body>

    <button class="theme-toggle" onclick="toggleTheme()" id="btnTema">☀️ Modo Claro</button>

    <h1>PAINEL DE CLASSIFICAÇÃO VNL</h1>
    <a href="cadastro.php" class="btn-gerenciar">Painel de Cadastro →</a>

    <div class="secao-filtros">
        <form method="GET" action="index.php">
            <label>Filtrar Competição:</label>
            <select name="fase_filtro">
                <option value="todos" <?=$fase_filtro == 'todos'?'selected':''?>>-- Todas as Fases --</option>
                <option value="Fase de Grupos" <?=$fase_filtro == 'Fase de Grupos'?'selected':''?>>Fase de Grupos</option>
                <option value="Quartas de Final" <?=$fase_filtro == 'Quartas de Final'?'selected':''?>>Quartas de Final</option>
                <option value="Semifinal" <?=$fase_filtro == 'Semifinal'?'selected':''?>>Semifinal</option>
                <option value="Final" <?=$fase_filtro == 'Final'?'selected':''?>>Final</option>
            </select>

            <select name="pais_filtro">
                <option value="todos" <?=$pais_filtro == 'todos'?'selected':''?>>-- Todos os Países (Histórico) --</option>
                <?php foreach ($paises as $p): ?>
                    <option value="<?=$p['id']?>" <?=$pais_filtro == $p['id']?'selected':''?>><?=$p['nome']?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Filtrar</button>
        </form>
    </div>

    <div class="tabelas-container">
        <div class="tabela-bloco">
            <h2>Classificação Feminina (<?=$fase_filtro == 'todos' ? 'Geral' : $fase_filtro?>)</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr><th>Pos</th><th style="text-align:left;">País</th><th>J</th><th>V</th><th>D</th><th>Pts</th><th>Sets P</th><th>Sets C</th><th>Ratio S</th></tr>
                    </thead>
                    <tbody>
                        <?php $pos = 1; foreach ($tabela_F as $item): 
                            $ratio = $item['sets_contra'] > 0 ? round($item['sets_pro'] / $item['sets_contra'], 3) : $item['sets_pro'];
                        ?>
                        <tr class="<?=($pos <= 8)?'g8-zona':''?>">
                            <td><strong><?=$pos?></strong></td>
                            <td class="nome-pais"><img class="flag" src="https://flagcdn.com/w40/<?=$item['sigla']?>.png" alt="Flag"> <?=$item['nome']?></td>
                            <td><?=$item['jogos_disputados']?></td>
                            <td style="color:#2a9d8f; font-weight:bold;"><?=$item['vitorias']?></td>
                            <td style="color:#e63946;"><?=$item['derrotas']?></td>
                            <td><strong><?=$item['pontos']?></strong></td>
                            <td><?=$item['sets_pro']?></td>
                            <td><?=$item['sets_contra']?></td>
                            <td><strong><?=$ratio?></strong></td>
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
                        <tr><th>Pos</th><th style="text-align:left;">País</th><th>J</th><th>V</th><th>D</th><th>Pts</th><th>Sets P</th><th>Sets C</th><th>Ratio S</th></tr>
                    </thead>
                    <tbody>
                        <?php $pos = 1; foreach ($tabela_M as $item): 
                            $ratio = $item['sets_contra'] > 0 ? round($item['sets_pro'] / $item['sets_contra'], 3) : $item['sets_pro'];
                        ?>
                        <tr class="<?=($pos <= 8)?'g8-zona':''?>">
                            <td><strong><?=$pos?></strong></td>
                            <td class="nome-pais"><img class="flag" src="https://flagcdn.com/w40/<?=$item['sigla']?>.png" alt="Flag"> <?=$item['nome']?></td>
                            <td><?=$item['jogos_disputados']?></td>
                            <td style="color:#2a9d8f; font-weight:bold;"><?=$item['vitorias']?></td>
                            <td style="color:#e63946;"><?=$item['derrotas']?></td>
                            <td><strong><?=$item['pontos']?></strong></td>
                            <td><?=$item['sets_pro']?></td>
                            <td><?=$item['sets_contra']?></td>
                            <td><strong><?=$ratio?></strong></td>
                        </tr>
                        <?php $pos++; endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="h2h-box">
        <h3>📊 Comparador Confronto Direto (Head-to-Head)</h3>
        <form method="GET" action="index.php">
            <select name="h2h_1" required>
                <option value="">-- Selecione o País A --</option>
                <?php foreach ($paises as $p): ?> <option value="<?=$p['id']?>" <?=$h2h_1 == $p['id']?'selected':''?>><?=$p['nome']?></option> <?php endforeach; ?>
            </select>
            <span style="font-weight: bold; margin: 0 5px; color:#2a9d8f;">VS</span>
            <select name="h2h_2" required>
                <option value="">-- Selecione o País B --</option>
                <?php foreach ($paises as $p): ?> <option value="<?=$p['id']?>" <?=$h2h_2 == $p['id']?'selected':''?>><?=$p['nome']?></option> <?php endforeach; ?>
            </select>
            <button type="submit" style="background:#e76f51; color:white; border:none; padding:8px 15px; border-radius:4px; font-weight:bold; cursor:pointer;">Comparar</button>
        </form>

        <?php if (!empty($h2h_1) && !empty($h2h_2)): ?>
            <div class="h2h-placar-geral">
                <img class="flag" style="width:30px; height:20px;" src="https://flagcdn.com/w40/<?=$mapa_paises[$h2h_1]['sigla']?>.png"> 
                <?=$mapa_paises[$h2h_1]['nome']?> <?=$h2h_vitorias_1?> x <?=$h2h_vitorias_2?> <?=$mapa_paises[$h2h_2]['nome']?>
                <img class="flag" style="width:30px; height:20px;" src="https://flagcdn.com/w40/<?=$mapa_paises[$h2h_2]['sigla']?>.png">
            </div>
        <?php endif; ?>
    </div>

    <h2>Histórico de Partidas Filtradas</h2>
    <div class="historico-container">
        <?php if (empty($historico_filtrado)): ?>
            <p style="text-align: center; color: #888; font-style: italic;">Nenhuma partida encontrada.</p>
        <?php else: ?>
            <?php foreach ($historico_filtrado as $part): ?>
                <div class="partida-card">
                    <div style="font-weight: bold; display:flex; align-items:center; gap:5px; flex-wrap:wrap; justify-content:center;">
                        <img class="flag" src="https://flagcdn.com/w40/<?=$mapa_paises[$part['id_casa']]['sigla']?>.png">
                        <span><?=$part['casa_nome']?></span> 
                        <span style="color:#888; font-weight:normal; margin:0 5px;">vs</span> 
                        <img class="flag" src="https://flagcdn.com/w40/<?=$mapa_paises[$part['id_fora']]['sigla']?>.png">
                        <span><?=$part['fora_nome']?></span>
                        <span style="font-size:10px; background:#e76f51; color:white; padding:2px 6px; border-radius:3px; margin-left:5px;"><?=$part['fase'] ?? 'Fase de Grupos'?></span>
                        <span style="font-size:10px; background:#415a77; padding:2px 6px; border-radius:3px; color:white;"><?=$part['genero']=='M'?'MASC':'FEM'?></span>
                    </div>
                    <div class="partida-placar"><?=$part['pontos_casa']?> x <?=$part['pontos_fora']?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        function applyTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            const btn = document.getElementById('btnTema');
            if (theme === 'light') {
                btn.innerHTML = '🌙 Modo Escuro';
            } else {
                btn.innerHTML = '☀️ Modo Claro';
            }
        }

        function toggleTheme() {
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            localStorage.setItem('vnl-theme', newTheme);
            applyTheme(newTheme);
        }

        // Recupera a escolha anterior do usuário assim que a página carrega
        const savedTheme = localStorage.getItem('vnl-theme') || 'dark';
        applyTheme(savedTheme);
    </script>
</body>
</html>
