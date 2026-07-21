<?php
session_start();
include 'conexao.php';
/** @var PDO $pdo */

if (!isset($_SESSION['logado'])) {
    header("Location: login.php");
    exit;
}

// 1. Carrega todos os países
$paises = $pdo->query("SELECT * FROM paises ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

// Estrutura inicial para estatísticas
$stats_F = []; $stats_M = [];
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
        'vit_mandante' => 0,
        'der_mandante' => 0,
        'vit_visitante' => 0,
        'der_visitante' => 0,
        'jogou' => false
    ];
    $stats_F[$p['id']] = $base;
    $stats_M[$p['id']] = $base;
}

// 2. Processa todas as partidas lançadas
$partidas = $pdo->query("SELECT * FROM partidas ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

foreach ($partidas as $partida) {
    $casa = $partida['id_casa'];
    $fora = $partida['id_fora'];
    $p_casa = (int)$partida['pontos_casa'];
    $p_fora = (int)$partida['pontos_fora'];
    $genero = $partida['genero'];

    $ref = ($genero == 'F') ? $stats_F : $stats_M;

    if (!isset($ref[$casa])) continue;

    $ref[$casa]['jogou'] = true; $ref[$fora]['jogou'] = true;
    $ref[$casa]['jogos']++; $ref[$fora]['jogos']++;
    $ref[$casa]['sets_pro'] += $p_casa; $ref[$casa]['sets_contra'] += $p_fora;
    $ref[$fora]['sets_pro'] += $p_fora; $ref[$fora]['sets_contra'] += $p_casa;

    if ($p_casa > $p_fora) {
        $ref[$casa]['vitorias']++; $ref[$fora]['derrotas']++;
        $ref[$casa]['vit_mandante']++; $ref[$fora]['der_visitante']++;
        
        if ($p_casa == 3 && ($p_fora == 0 || $p_fora == 1)) $ref[$casa]['pontos'] += 3;
        elseif ($p_casa == 3 && $p_fora == 2) { $ref[$casa]['pontos'] += 2; $ref[$fora]['pontos'] += 1; }
    } else {
        $ref[$fora]['vitorias']++; $ref[$casa]['derrotas']++;
        $ref[$fora]['vit_visitante']++; $ref[$casa]['der_mandante']++;

        if ($p_fora == 3 && ($p_casa == 0 || $p_casa == 1)) $ref[$fora]['pontos'] += 3;
        elseif ($p_fora == 3 && $p_casa == 2) { $ref[$fora]['pontos'] += 2; $ref[$casa]['pontos'] += 1; }
    }

    if ($genero == 'F') { $stats_F = $ref; } else { $stats_M = $ref; }
}

$stats_F = array_filter($stats_F, function($item) { return $item['jogou']; });
$stats_M = array_filter($stats_M, function($item) { return $item['jogou']; });

$ordenar = function($a, $b) {
    if ($a['pontos'] != $b['pontos']) return $b['pontos'] <=> $a['pontos'];
    if ($a['vitorias'] != $b['vitorias']) return $b['vitorias'] <=> $a['vitorias'];
    $saldo_a = $a['sets_pro'] - $a['sets_contra'];
    $saldo_b = $b['sets_pro'] - $b['sets_contra'];
    return $saldo_b <=> $saldo_a;
};

uasort($stats_F, $ordenar);
uasort($stats_M, $ordenar);

// Dados Top 5 para gráficos
$top5_F_nomes = []; $top5_F_pontos = [];
foreach (array_slice($stats_F, 0, 5) as $t) {
    $top5_F_nomes[] = $t['nome'];
    $top5_F_pontos[] = $t['pontos'];
}

$top5_M_nomes = []; $top5_M_pontos = [];
foreach (array_slice($stats_M, 0, 5) as $t) {
    $top5_M_nomes[] = $t['nome'];
    $top5_M_pontos[] = $t['pontos'];
}

function gerarTextoExplicativo($dados) {
    if (empty($dados)) return "<p>Sem dados suficientes cadastrados para este gênero.</p>";
    
    $html = "<div class='analise-texto'>";
    $pos = 1;
    foreach ($dados as $t) {
        $aprov = $t['jogos'] > 0 ? number_format(($t['vitorias'] / $t['jogos']) * 100, 1) : 0;
        $saldo = $t['sets_pro'] - $t['sets_contra'];
        
        $html .= "<p><strong>{$pos}º {$t['nome']}:</strong> ";
        if ($pos == 1) {
            $html .= "Lidera a competição com <strong>{$t['pontos']} pontos</strong> e aproveitamento de <strong>{$aprov}%</strong>. Apresenta {$t['vitorias']} vitória(s) e saldo de sets em " . ($saldo > 0 ? "+$saldo" : $saldo) . ".";
        } elseif ($aprov >= 60) {
            $html .= "Desempenho sólido na parte de cima da tabela. Possui {$t['vitorias']} vitória(s) e saldo de sets positivo de " . ($saldo > 0 ? "+$saldo" : $saldo) . ".";
        } elseif ($aprov >= 40) {
            $html .= "Campanha mediana com aproveitamento de {$aprov}%. Mantém oscilação entre vitórias ({$t['vitorias']}) e derrotas ({$t['derrotas']}).";
        } else {
            $html .= "Apresenta dificuldades no torneio. Registra aproveitamento de apenas {$aprov}% e saldo de sets em " . ($saldo > 0 ? "+$saldo" : $saldo) . ".";
        }
        $html .= "</p>";
        $pos++;
    }
    $html .= "</div>";
    return $html;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório Completo - VNL</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --bg-body: #0b132b; --txt-main: #f1f5f9; --txt-heading: #48cae4;
            --bg-card: #1c2541; --border-line: #3a506b; --btn-bg: #48cae4; 
            --btn-txt: #0b132b; --accent-blue: #00b4d8;
        }
        [data-theme="light"] {
            --bg-body: #e0f2fe; --txt-main: #0f172a; --txt-heading: #0369a1;
            --bg-card: #ffffff; --border-line: #bae6fd; --btn-bg: #0284c7; 
            --btn-txt: #ffffff; --accent-blue: #0284c7;
        }

        body, .card, table, th, td { transition: background-color 0.3s ease, color 0.3s ease; }
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: var(--bg-body); color: var(--txt-main); }
        h1, h2, h3 { text-align: center; color: var(--txt-heading); }
        
        .header-top { display: flex; justify-content: space-between; align-items: center; max-width: 1000px; margin: 0 auto 20px auto; flex-wrap: wrap; gap: 10px; }
        .btn-acao { background: var(--btn-bg); color: var(--btn-txt); border: none; padding: 8px 16px; font-weight: bold; border-radius: 20px; cursor: pointer; text-decoration: none; font-size: 13px; }

        .container-relatorio { max-width: 1000px; margin: 0 auto; background: var(--bg-body); padding: 10px; }
        
        .grid-graficos { display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 30px; }
        .card-grafico { flex: 1; min-width: 300px; background: var(--bg-card); padding: 20px; border-radius: 8px; border: 1px solid var(--border-line); }

        .table-responsive { border-radius: 8px; border: 1px solid var(--border-line); overflow-x: auto; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; background: var(--bg-card); text-align: center; min-width: 800px; }
        th, td { padding: 10px; border-bottom: 1px solid var(--border-line); font-size: 13px; }
        th { background: var(--border-line); color: var(--txt-main); font-weight: bold; }

        .flag { width: 20px; height: 14px; object-fit: cover; vertical-align: middle; margin-right: 6px; border-radius: 2px; }

        .box-explicativa, .box-funcional { background: var(--bg-card); padding: 20px; border-radius: 8px; border: 1px solid var(--border-line); margin-bottom: 35px; }
        .box-explicativa { border-left: 4px solid var(--accent-blue); }
        .analise-texto p { margin: 8px 0; font-size: 14px; line-height: 1.5; }

        .form-select, .input-score { background: var(--bg-body); color: var(--txt-main); border: 1px solid var(--border-line); padding: 8px; border-radius: 5px; }
        .input-score { width: 50px; text-align: center; font-weight: bold; }
        .flex-center { display: flex; gap: 15px; align-items: center; justify-content: center; flex-wrap: wrap; }
        .h2h-result { margin-top: 15px; padding: 15px; background: var(--bg-body); border-radius: 8px; text-align: center; }
    </style>
</head>
<body>

    <div class="header-top">
        <a href="index.php" class="btn-acao">← Voltar para Início</a>
        <button class="btn-acao" onclick="toggleTheme()" id="btnTema">☀️ Modo Claro</button>
    </div>

    <div class="container-relatorio">
        <h1>📊 Relatório Geral de Desempenho VNL</h1>

        <!-- SEÇÃO DE GRÁFICOS -->
        <h2>Top 5 Pontuadores por Gênero</h2>
        <div class="grid-graficos">
            <div class="card-grafico">
                <h3>Feminino (Pontos)</h3>
                <canvas id="chartFeminino"></canvas>
            </div>
            <div class="card-grafico">
                <h3>Masculino (Pontos)</h3>
                <canvas id="chartMasculino"></canvas>
            </div>
        </div>

        <!-- NOVO: COMPARATIVO DIRETO (HEAD-TO-HEAD) -->
        <h2>⚔️ Comparativo Direto (Head-to-Head)</h2>
        <div class="box-funcional">
            <div class="flex-center">
                <select id="h2h_time1" class="form-select">
                    <option value="">Selecione o Time 1</option>
                    <?php foreach ($paises as $p): ?>
                        <option value="<?=$p['id']?>"><?=$p['nome']?></option>
                    <?php endforeach; ?>
                </select>
                <strong>VS</strong>
                <select id="h2h_time2" class="form-select">
                    <option value="">Selecione o Time 2</option>
                    <?php foreach ($paises as $p): ?>
                        <option value="<?=$p['id']?>"><?=$p['nome']?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn-acao" onclick="calcularH2H()">Comparar</button>
            </div>
            <div id="h2h_resultado" class="h2h-result" style="display: none;"></div>
        </div>

        <!-- NOVO: SIMULADOR DE CLASSIFICAÇÃO -->
        <h2>🔮 Simulador de Classificação</h2>
        <div class="box-funcional">
            <p style="text-align: center; font-size: 14px;">Simule o placar de uma partida hipotética para ver o impacto na tabela:</p>
            <div class="flex-center" style="margin-bottom: 15px;">
                <select id="sim_genero" class="form-select" onchange="atualizarSelectsSimulador()">
                    <option value="F">Feminino</option>
                    <option value="M">Masculino</option>
                </select>
                <select id="sim_casa" class="form-select"></select>
                <input type="number" id="sim_pts_casa" class="input-score" min="0" max="3" value="3">
                <span>x</span>
                <input type="number" id="sim_pts_fora" class="input-score" min="0" max="3" value="0">
                <select id="sim_fora" class="form-select"></select>
                <button class="btn-acao" onclick="simularPartida()">Simular</button>
            </div>
            <div id="sim_resultado"></div>
        </div>

        <!-- FEMININO: TABELA + ANÁLISE ESCRITA + ESTATÍSTICAS DE SET -->
        <h2>Desempenho Detalhado - Feminino</h2>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Pos</th>
                        <th style="text-align: left;">País</th>
                        <th>J</th>
                        <th>V</th>
                        <th>D</th>
                        <th>Sets P/C</th>
                        <th>Média S. P/C</th>
                        <th>% Sets Venc.</th>
                        <th>Mandante (V/D)</th>
                        <th>Visitante (V/D)</th>
                        <th>Pts</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $pos = 1; foreach ($stats_F as $time): 
                        $total_sets = $time['sets_pro'] + $time['sets_contra'];
                        $pct_sets = $total_sets > 0 ? number_format(($time['sets_pro'] / $total_sets) * 100, 1) : 0;
                        $media_pro = $time['jogos'] > 0 ? number_format($time['sets_pro'] / $time['jogos'], 1) : 0;
                        $media_contra = $time['jogos'] > 0 ? number_format($time['sets_contra'] / $time['jogos'], 1) : 0;
                    ?>
                    <tr>
                        <td><strong><?=$pos++?>º</strong></td>
                        <td style="text-align: left;"><img src="https://flagcdn.com/w40/<?=$time['sigla']?>.png" class="flag"> <strong><?=$time['nome']?></strong></td>
                        <td><?=$time['jogos']?></td>
                        <td style="color:#10b981; font-weight:bold;"><?=$time['vitorias']?></td>
                        <td style="color:#ef4444;"><?=$time['derrotas']?></td>
                        <td><?=$time['sets_pro']?>/<?=$time['sets_contra']?></td>
                        <td><?=$media_pro?> / <?=$media_contra?></td>
                        <td><strong><?=$pct_sets?>%</strong></td>
                        <td><?=$time['vit_mandante']?> / <?=$time['der_mandante']?></td>
                        <td><?=$time['vit_visitante']?> / <?=$time['der_visitante']?></td>
                        <td style="background: var(--border-line); font-weight:bold;"><?=$time['pontos']?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <h3>📝 Análise Técnica do Desempenho Feminino</h3>
        <div class="box-explicativa">
            <?=gerarTextoExplicativo($stats_F)?>
        </div>

        <!-- MASCULINO: TABELA + ANÁLISE ESCRITA + ESTATÍSTICAS DE SET -->
        <h2>Desempenho Detalhado - Masculino</h2>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Pos</th>
                        <th style="text-align: left;">País</th>
                        <th>J</th>
                        <th>V</th>
                        <th>D</th>
                        <th>Sets P/C</th>
                        <th>Média S. P/C</th>
                        <th>% Sets Venc.</th>
                        <th>Mandante (V/D)</th>
                        <th>Visitante (V/D)</th>
                        <th>Pts</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $pos = 1; foreach ($stats_M as $time): 
                        $total_sets = $time['sets_pro'] + $time['sets_contra'];
                        $pct_sets = $total_sets > 0 ? number_format(($time['sets_pro'] / $total_sets) * 100, 1) : 0;
                        $media_pro = $time['jogos'] > 0 ? number_format($time['sets_pro'] / $time['jogos'], 1) : 0;
                        $media_contra = $time['jogos'] > 0 ? number_format($time['sets_contra'] / $time['jogos'], 1) : 0;
                    ?>
                    <tr>
                        <td><strong><?=$pos++?>º</strong></td>
                        <td style="text-align: left;"><img src="https://flagcdn.com/w40/<?=$time['sigla']?>.png" class="flag"> <strong><?=$time['nome']?></strong></td>
                        <td><?=$time['jogos']?></td>
                        <td style="color:#10b981; font-weight:bold;"><?=$time['vitorias']?></td>
                        <td style="color:#ef4444;"><?=$time['derrotas']?></td>
                        <td><?=$time['sets_pro']?>/<?=$time['sets_contra']?></td>
                        <td><?=$media_pro?> / <?=$media_contra?></td>
                        <td><strong><?=$pct_sets?>%</strong></td>
                        <td><?=$time['vit_mandante']?> / <?=$time['der_mandante']?></td>
                        <td><?=$time['vit_visitante']?> / <?=$time['der_visitante']?></td>
                        <td style="background: var(--border-line); font-weight:bold;"><?=$time['pontos']?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <h3>📝 Análise Técnica do Desempenho Masculino</h3>
        <div class="box-explicativa">
            <?=gerarTextoExplicativo($stats_M)?>
        </div>
    </div>

    <script>
    // Configurações do Banco enviadas para JS
    const todasPartidas = <?=json_encode($partidas)?>;
    const listaPaises = <?=json_encode($paises)?>;
    const statsFeminino = <?=json_encode(array_values($stats_F))?>;
    const statsMasculino = <?=json_encode(array_values($stats_M))?>;

    // Theme Manager
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

    // Chart.js
    const labelsF = <?=json_encode($top5_F_nomes)?>;
    const dataF = <?=json_encode($top5_F_pontos)?>;
    const labelsM = <?=json_encode($top5_M_nomes)?>;
    const dataM = <?=json_encode($top5_M_pontos)?>;

    new Chart(document.getElementById('chartFeminino'), {
        type: 'bar',
        data: { labels: labelsF, datasets: [{ label: 'Pontos', data: dataF, backgroundColor: '#ec4899' }] },
        options: { responsive: true, plugins: { legend: { display: false } } }
    });

    new Chart(document.getElementById('chartMasculino'), {
        type: 'bar',
        data: { labels: labelsM, datasets: [{ label: 'Pontos', data: dataM, backgroundColor: '#3b82f6' }] },
        options: { responsive: true, plugins: { legend: { display: false } } }
    });

    // LÓGICA COMPARATIVO DIRETO (HEAD-TO-HEAD)
    function calcularH2H() {
        const t1 = document.getElementById('h2h_time1').value;
        const t2 = document.getElementById('h2h_time2').value;
        const resDiv = document.getElementById('h2h_resultado');

        if (!t1 || !t2 || t1 === t2) {
            alert('Por favor, selecione dois times diferentes.');
            return;
        }

        const confrontos = todasPartidas.filter(p => 
            (p.id_casa == t1 && p.id_fora == t2) || (p.id_casa == t2 && p.id_fora == t1)
        );

        const time1Obj = listaPaises.find(p => p.id == t1);
        const time2Obj = listaPaises.find(p => p.id == t2);

        if (confrontos.length === 0) {
            resDiv.style.display = 'block';
            resDiv.innerHTML = `<p>Nenhum confronto direto registrado entre <strong>${time1Obj.nome}</strong> e <strong>${time2Obj.nome}</strong>.</p>`;
            return;
        }

        let vitT1 = 0, vitT2 = 0, setsT1 = 0, setsT2 = 0;
        let historicoHTML = '<h4>Histórico de Partidas:</h4><ul style="list-style:none; padding:0;">';

        confrontos.forEach(p => {
            const eCasaT1 = p.id_casa == t1;
            const ptsT1 = eCasaT1 ? parseInt(p.pontos_casa) : parseInt(p.pontos_fora);
            const ptsT2 = eCasaT1 ? parseInt(p.pontos_fora) : parseInt(p.pontos_casa);
            
            setsT1 += ptsT1; setsT2 += ptsT2;
            if (ptsT1 > ptsT2) vitT1++; else vitT2++;

            historicoHTML += `<li>[Gênero ${p.genero}] ${time1Obj.nome} ${ptsT1} x ${ptsT2} ${time2Obj.nome}</li>`;
        });
        historicoHTML += '</ul>';

        resDiv.style.display = 'block';
        resDiv.innerHTML = `
            <h3>Placar Geral de Confrontos</h3>
            <p style="font-size:18px;"><strong>${time1Obj.nome}</strong> ${vitT1} Vitórias (${setsT1} Sets) vs ${vitT2} Vitórias (${setsT2} Sets) <strong>${time2Obj.nome}</strong></p>
            ${historicoHTML}
        `;
    }

    // LÓGICA DO SIMULADOR DE CLASSIFICAÇÃO
    function atualizarSelectsSimulador() {
        const genero = document.getElementById('sim_genero').value;
        const base = genero === 'F' ? statsFeminino : statsMasculino;
        const selectCasa = document.getElementById('sim_casa');
        const selectFora = document.getElementById('sim_fora');

        selectCasa.innerHTML = ''; selectFora.innerHTML = '';
        base.forEach(t => {
            selectCasa.innerHTML += `<option value="${t.id}">${t.nome}</option>`;
            selectFora.innerHTML += `<option value="${t.id}">${t.nome}</option>`;
        });
        if (selectFora.options.length > 1) selectFora.selectedIndex = 1;
    }

    function simularPartida() {
        const genero = document.getElementById('sim_genero').value;
        const idCasa = document.getElementById('sim_casa').value;
        const idFora = document.getElementById('sim_fora').value;
        const pCasa = parseInt(document.getElementById('sim_pts_casa').value);
        const pFora = parseInt(document.getElementById('sim_pts_fora').value);

        if (idCasa === idFora) { alert('Selecione times diferentes.'); return; }

        // Copia profunda dos dados para simular sem alterar o original
        let dadosSim = JSON.parse(JSON.stringify(genero === 'F' ? statsFeminino : statsMasculino));
        let casa = dadosSim.find(t => t.id == idCasa);
        let fora = dadosSim.find(t => t.id == idFora);

        casa.jogos++; fora.jogos++;
        casa.sets_pro += pCasa; casa.sets_contra += pFora;
        fora.sets_pro += pFora; fora.sets_contra += pCasa;

 if (pCasa > pFora) {
            casa.vitorias++; fora.derrotas++;
            if (pCasa === 3 && (pFora === 0 || pFora === 1)) casa.pontos += 3;
            else if (pCasa === 3 && pFora === 2) { casa.pontos += 2; fora.pontos += 1; }
        } else {
            fora.vitorias++; casa.derrotas++;
            if (pFora === 3 && (pCasa === 0 || pCasa === 1)) fora.pontos += 3;
            else if (pFora === 3 && pCasa === 2) { fora.pontos += 2; casa.pontos += 1; }
        }

        dadosSim.sort((a,b) => b.pontos - a.pontos || b.vitorias - a.vitorias || (b.sets_pro - b.sets_contra) - (a.sets_pro - a.sets_contra));

        let html = `<h4>Tabela Simulada (${genero === 'F' ? 'Feminino' : 'Masculino'}):</h4><ol style="text-align:left; max-width:400px; margin:0 auto;">`;
        dadosSim.forEach(t => {
            html += `<li><strong>${t.nome}</strong> - ${t.pontos} pts (${t.vitorias}V / ${t.derrotas}D)</li>`;
        });
        html += '</ol>';
        document.getElementById('sim_resultado').innerHTML = html;
    }

    // Inicializa selects do simulador
    atualizarSelectsSimulador();
    </script>
</body>
</html>
