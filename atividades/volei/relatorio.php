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

// 2. Processa todas as partidas lançadas
$partidas = $pdo->query("SELECT * FROM partidas ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

foreach ($partidas as $partida) {
    $casa = $partida['id_casa'];
    $fora = $partida['id_fora'];
    $p_casa = $partida['pontos_casa'];
    $p_fora = $partida['pontos_fora'];
    $genero = $partida['genero'];

    $ref = ($genero == 'F') ? $stats_F : $stats_M;

    if (!isset($ref[$casa])) continue;

    // Registra que ambos os times jogaram
    $ref[$casa]['jogou'] = true; $ref[$fora]['jogou'] = true;
    $ref[$casa]['jogos']++; $ref[$fora]['jogos']++;
    $ref[$casa]['sets_pro'] += $p_casa; $ref[$casa]['sets_contra'] += $p_fora;
    $ref[$fora]['sets_pro'] += $p_fora; $ref[$fora]['sets_contra'] += $p_casa;

    // Distribuição de Pontos da Regra FIVB
    if ($p_casa > $p_fora) {
        $ref[$casa]['vitorias']++; $ref[$fora]['derrotas']++;
        if ($p_casa == 3 && ($p_fora == 0 || $p_fora == 1)) $ref[$casa]['pontos'] += 3;
        elseif ($p_casa == 3 && $p_fora == 2) { $ref[$casa]['pontos'] += 2; $ref[$fora]['pontos'] += 1; }
    } else {
        $ref[$fora]['vitorias']++; $ref[$casa]['derrotas']++;
        if ($p_fora == 3 && ($p_casa == 0 || $p_casa == 1)) $ref[$fora]['pontos'] += 3;
        elseif ($p_fora == 3 && $p_casa == 2) { $ref[$fora]['pontos'] += 2; $ref[$casa]['pontos'] += 1; }
    }

    if ($genero == 'F') { $stats_F = $ref; } else { $stats_M = $ref; }
}

// Filtra apenas times que jogaram
$stats_F = array_filter($stats_F, function($item) { return $item['jogou']; });
$stats_M = array_filter($stats_M, function($item) { return $item['jogou']; });

// Ordena por Pontos > Vitórias > Saldo de Sets
$ordenar = function($a, $b) {
    if ($a['pontos'] != $b['pontos']) return $b['pontos'] <=> $a['pontos'];
    if ($a['vitorias'] != $b['vitorias']) return $b['vitorias'] <=> $a['vitorias'];
    $saldo_a = $a['sets_pro'] - $a['sets_contra'];
    $saldo_b = $b['sets_pro'] - $b['sets_contra'];
    return $saldo_b <=> $saldo_a;
};

uasort($stats_F, $ordenar);
uasort($stats_M, $ordenar);

// Preparação de dados para o gráfico Chart.js (Top 5 de cada gênero)
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
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Desempenho - VNL</title>
    <!-- Chart.js para os gráficos -->
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
        
        .header-top { display: flex; justify-content: space-between; align-items: center; max-width: 1000px; margin: 0 auto 20px auto; }
        .btn-acao { background: var(--btn-bg); color: var(--btn-txt); border: none; padding: 8px 16px; font-weight: bold; border-radius: 20px; cursor: pointer; text-decoration: none; font-size: 13px; }

        .container-relatorio { max-width: 1000px; margin: 0 auto; }
        
        /* Gráficos em grade */
        .grid-graficos { display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 30px; }
        .card-grafico { flex: 1; min-width: 300px; background: var(--bg-card); padding: 20px; border-radius: 8px; border: 1px solid var(--border-line); }

        /* Tabelas */
        .table-responsive { border-radius: 8px; border: 1px solid var(--border-line); overflow: hidden; margin-bottom: 40px; }
        table { width: 100%; border-collapse: collapse; background: var(--bg-card); text-align: center; }
        th, td { padding: 10px; border-bottom: 1px solid var(--border-line); font-size: 13px; }
        th { background: var(--border-line); color: var(--txt-main); font-weight: bold; }

        .flag { width: 20px; height: 14px; object-fit: cover; vertical-align: middle; margin-right: 6px; border-radius: 2px; }

        /* Estilo exclusivo para Impressão / Salvar PDF */
        @media print {
            .header-top, .btn-acao { display: none !important; }
            body { background: white !important; color: black !important; }
            .card-grafico, table { border: 1px solid #ccc !important; background: white !important; color: black !important; }
            th { background: #eee !important; color: black !important; }
            h1, h2 { color: black !important; }
        }
    </style>
</head>
<body>

    <div class="header-top">
        <a href="index.php" class="btn-acao">← Voltar para Inicio</a>
        <div>
            <button class="btn-acao" onclick="window.print()">🖨️ Imprimir / Salvar PDF</button>
            <button class="btn-acao" onclick="toggleTheme()" id="btnTema">☀️ Modo Claro</button>
        </div>
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

        <!-- TABELA DESEMPENHO FEMININO -->
        <h2>Desempenho Detalhado - Feminino</h2>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Pos</th>
                        <th style="text-align: left;">País</th>
                        <th>Jogos</th>
                        <th>Vitórias</th>
                        <th>Derrotas</th>
                        <th>Sets Pró</th>
                        <th>Sets Contra</th>
                        <th>Saldo Sets</th>
                        <th>Aproveitamento</th>
                        <th>Pontos Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $pos = 1; foreach ($stats_F as $time): 
                        $saldo = $time['sets_pro'] - $time['sets_contra'];
                        $aproveitamento = $time['jogos'] > 0 ? number_format(($time['vitorias'] / $time['jogos']) * 100, 1) : 0;
                    ?>
                    <tr>
                        <td><strong><?=$pos++?>º</strong></td>
                        <td style="text-align: left;"><img src="https://flagcdn.com/w40/<?=$time['sigla']?>.png" class="flag"> <strong><?=$time['nome']?></strong></td>
                        <td><?=$time['jogos']?></td>
                        <td style="color:#10b981; font-weight:bold;"><?=$time['vitorias']?></td>
                        <td style="color:#ef4444;"><?=$time['derrotas']?></td>
                        <td><?=$time['sets_pro']?></td>
                        <td><?=$time['sets_contra']?></td>
                        <td><?=$saldo > 0 ? "+$saldo" : $saldo?></td>
                        <td><strong><?=$aproveitamento?>%</strong></td>
                        <td style="background: var(--border-line); font-weight:bold;"><?=$time['pontos']?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- TABELA DESEMPENHO MASCULINO -->
        <h2>Desempenho Detalhado - Masculino</h2>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Pos</th>
                        <th style="text-align: left;">País</th>
                        <th>Jogos</th>
                        <th>Vitórias</th>
                        <th>Derrotas</th>
                        <th>Sets Pró</th>
                        <th>Sets Contra</th>
                        <th>Saldo Sets</th>
                        <th>Aproveitamento</th>
                        <th>Pontos Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $pos = 1; foreach ($stats_M as $time): 
                        $saldo = $time['sets_pro'] - $time['sets_contra'];
                        $aproveitamento = $time['jogos'] > 0 ? number_format(($time['vitorias'] / $time['jogos']) * 100, 1) : 0;
                    ?>
                    <tr>
                        <td><strong><?=$pos++?>º</strong></td>
                        <td style="text-align: left;"><img src="https://flagcdn.com/w40/<?=$time['sigla']?>.png" class="flag"> <strong><?=$time['nome']?></strong></td>
                        <td><?=$time['jogos']?></td>
                        <td style="color:#10b981; font-weight:bold;"><?=$time['vitorias']?></td>
                        <td style="color:#ef4444;"><?=$time['derrotas']?></td>
                        <td><?=$time['sets_pro']?></td>
                        <td><?=$time['sets_contra']?></td>
                        <td><?=$saldo > 0 ? "+$saldo" : $saldo?></td>
                        <td><strong><?=$aproveitamento?>%</strong></td>
                        <td style="background: var(--border-line); font-weight:bold;"><?=$time['pontos']?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    // Configurações do Tema
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

    // Inicialização dos Gráficos com dados fornecidos pelo PHP
    const labelsF = <?=json_encode($top5_F_nomes)?>;
    const dataF = <?=json_encode($top5_F_pontos)?>;
    
    const labelsM = <?=json_encode($top5_M_nomes)?>;
    const dataM = <?=json_encode($top5_M_pontos)?>;

    // Gráfico Feminino
    new Chart(document.getElementById('chartFeminino'), {
        type: 'bar',
        data: {
            labels: labelsF,
            datasets: [{
                label: 'Pontos Total',
                data: dataF,
                backgroundColor: '#ec4899'
            }]
        },
        options: { responsive: true, plugins: { legend: { display: false } } }
    });

    // Gráfico Masculino
    new Chart(document.getElementById('chartMasculino'), {
        type: 'bar',
        data: {
            labels: labelsM,
            datasets: [{
                label: 'Pontos Total',
                data: dataM,
                backgroundColor: '#3b82f6'
            }]
        },
        options: { responsive: true, plugins: { legend: { display: false } } }
    });
    </script>
</body>
</html>
