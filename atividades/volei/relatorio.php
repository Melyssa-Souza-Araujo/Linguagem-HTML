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

    $ref[$casa]['jogou'] = true; $ref[$fora]['jogou'] = true;
    $ref[$casa]['jogos']++; $ref[$fora]['jogos']++;
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

// Função auxiliar para gerar texto descritivo por time
function gerarTextoExplicativo($dados) {
    if (empty($dados)) return "<p>Sem dados suficientes cadastrados para este gênero.</p>";
    
    $html = "<div class='analise-texto'>";
    $pos = 1;
    foreach ($dados as $t) {
        $aprov = $t['jogos'] > 0 ? number_format(($t['vitorias'] / $t['jogos']) * 100, 1) : 0;
        $saldo = $t['sets_pro'] - $t['sets_contra'];
        
        $html .= "<p><strong>{$pos}º {$t['nome']}:</strong> ";
        if ($pos == 1) {
            $html .= "Lidera a competição com <strong>{$t['pontos']} pontos</strong> e aproveitamento de <strong>{$aprov}%</strong>. Apresenta o melhor desempenho geral com {$t['vitorias']} vitória(s) em {$t['jogos']} jogo(s).";
        } elseif ($aprov >= 60) {
            $html .= "Desempenho sólido na parte de cima da tabela. Possui {$t['vitorias']} vitória(s) e saldo de sets positivo de " . ($saldo > 0 ? "+$saldo" : $saldo) . ".";
        } elseif ($aprov >= 40) {
            $html .= "Campanha mediana com aproveitamento de {$aprov}%. Mantém oscilação entre vitórias ({$t['vitorias']}) e derrotas ({$t['derrotas']}).";
        } else {
            $html .= "Apresenta dificuldades no torneio. Registra aproveitamento de apenas {$aprov}% e saldo de sets em " . ($saldo > 0 ? "+$saldo" : $saldo) . ". Necessita de ajustes táticos.";
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
    <!-- Bibliotecas Necessárias -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

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
        .btn-excel { background: #10b981; color: white; }
        .btn-pdf { background: #ef4444; color: white; }

        .container-relatorio { max-width: 1000px; margin: 0 auto; background: var(--bg-body); padding: 10px; }
        
        .grid-graficos { display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 30px; }
        .card-grafico { flex: 1; min-width: 300px; background: var(--bg-card); padding: 20px; border-radius: 8px; border: 1px solid var(--border-line); }

        .table-responsive { border-radius: 8px; border: 1px solid var(--border-line); overflow: hidden; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; background: var(--bg-card); text-align: center; }
        th, td { padding: 10px; border-bottom: 1px solid var(--border-line); font-size: 13px; }
        th { background: var(--border-line); color: var(--txt-main); font-weight: bold; }

        .flag { width: 20px; height: 14px; object-fit: cover; vertical-align: middle; margin-right: 6px; border-radius: 2px; }

        .box-explicativa { background: var(--bg-card); padding: 15px 20px; border-radius: 8px; border-left: 4px solid var(--accent-blue); margin-bottom: 35px; border-top: 1px solid var(--border-line); border-right: 1px solid var(--border-line); border-bottom: 1px solid var(--border-line); }
        .analise-texto p { margin: 8px 0; font-size: 14px; line-height: 1.5; }

        @media print {
            .header-top, .btn-acao { display: none !important; }
            body { background: white !important; color: black !important; }
            .card-grafico, table, .box-explicativa { border: 1px solid #ccc !important; background: white !important; color: black !important; }
            th { background: #eee !important; color: black !important; }
            h1, h2, h3 { color: black !important; }
        }
    </style>
</head>
<body>

    <div class="header-top">
        <a href="index.php" class="btn-acao">← Voltar para Inicio</a>
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <button class="btn-acao btn-excel" onclick="exportarExcel()">📊 Exportar Gráficos (Excel)</button>
            <button class="btn-acao btn-pdf" onclick="exportarPDF()">📄 Exportar Relatório (PDF)</button>
            <button class="btn-acao" onclick="toggleTheme()" id="btnTema">☀️ Modo Claro</button>
        </div>
    </div>

    <!-- ÁREA CAPTURADA PELO PDF -->
    <div class="container-relatorio" id="conteudo-relatorio">
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

        <!-- FEMININO: TABELA + ANÁLISE ESCRITA -->
        <h2>Desempenho Detalhado - Feminino</h2>
        <div class="table-responsive">
            <table id="tabela-feminino">
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

        <h3>📝 Análise Técnica do Desempenho Feminino</h3>
        <div class="box-explicativa">
            <?=gerarTextoExplicativo($stats_F)?>
        </div>

        <!-- MASCULINO: TABELA + ANÁLISE ESCRITA -->
        <h2>Desempenho Detalhado - Masculino</h2>
        <div class="table-responsive">
            <table id="tabela-masculino">
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

        <h3>📝 Análise Técnica do Desempenho Masculino</h3>
        <div class="box-explicativa">
            <?=gerarTextoExplicativo($stats_M)?>
        </div>
    </div>

    <script>
    // Gerenciamento de Temas
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

    // EXPORTAÇÃO EXCEL (Apenas Gráficos / Dados Numéricos)
    function exportarExcel() {
        const wb = XLSX.utils.book_new();

        // Dados Gráfico Feminino
        const dadosF = [["País", "Pontuação"]];
        labelsF.forEach((nome, i) => dadosF.push([nome, dataF[i]]));
        const wsF = XLSX.utils.aoa_to_sheet(dadosF);
        XLSX.utils.book_append_sheet(wb, wsF, "Grafico_Feminino");

        // Dados Gráfico Masculino
        const dadosM = [["País", "Pontuação"]];
        labelsM.forEach((nome, i) => dadosM.push([nome, dataM[i]]));
        const wsM = XLSX.utils.aoa_to_sheet(dadosM);
        XLSX.utils.book_append_sheet(wb, wsM, "Grafico_Masculino");

        XLSX.writeFile(wb, "Graficos_Desempenho_VNL.xlsx");
    }

    // EXPORTAÇÃO PDF (Completo: Gráficos, Tabelas e Textos)
    function exportarPDF() {
        const element = document.getElementById('conteudo-relatorio');
        
        html2canvas(element, { scale: 2 }).then(canvas => {
            const imgData = canvas.toDataURL('image/png');
            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF('p', 'mm', 'a4');
            
            const imgWidth = 210; 
            const pageHeight = 295;  
            const imgHeight = (canvas.height * imgWidth) / canvas.width;
            let heightLeft = imgHeight;
            let position = 0;

            pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
            heightLeft -= pageHeight;

            while (heightLeft >= 0) {
                position = heightLeft - imgHeight;
                pdf.addPage();
                pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                heightLeft -= pageHeight;
            }
            
            pdf.save("Relatorio_Completo_VNL.pdf");
        });
    }
    </script>
</body>
</html>
