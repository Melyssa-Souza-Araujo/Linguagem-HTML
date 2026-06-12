<?php
include 'conexao.php';
/** @var PDO $pdo */

$id_partida = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Busca dados gerais do confronto
$stmt = $pdo->prepare("SELECT p.*, t1.nome AS casa_nome, t2.nome AS fora_nome 
                       FROM partidas p 
                       JOIN paises t1 ON p.id_casa = t1.id 
                       JOIN paises t2 ON p.id_fora = t2.id 
                       WHERE p.id = ?");
$stmt->execute([$id_partida]);
$jogo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$jogo) {
    die("Partida não encontrada!");
}

// Busca a pontuação detalhada dos sets
$stmt_sets = $pdo->prepare("SELECT * FROM detalhes_sets WHERE id_partida = ? ORDER BY numero_set ASC");
$stmt_sets->execute([$id_partida]);
$dados_sets = $stmt_sets->fetchAll(PDO::FETCH_ASSOC);

// Prepara as variáveis para enviar ao JavaScript/Chart.js
$labels = [];
$pontos_casa = [];
$pontos_fora = [];

foreach ($dados_sets as $s) {
    $labels[] = "Set " . $s['numero_set'];
    $pontos_casa[] = $s['pontos_casa'];
    $pontos_fora[] = $s['pontos_fora'];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Evolução da Partida - VNL</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: Arial, sans-serif; background-color: #0d1b2a; color: #f4f4f9; text-align: center; padding: 20px; }
        .chart-container { background: #1b263b; border: 1px solid #415a77; border-radius: 8px; padding: 20px; max-width: 700px; margin: 20px auto; }
        .btn-voltar { display: inline-block; background: #e76f51; color: white; padding: 8px 15px; border-radius: 4px; text-decoration: none; font-weight: bold; margin-bottom: 20px; }
    </style>
</head>
<body>

    <a href="index.php" class="btn-voltar">← Voltar para Classificação</a>
    
    <h2>Evolução do Confronto</h2>
    <h3><?=$jogo['casa_nome']?> (<?=$jogo['pontos_casa']?>) vs (<?=$jogo['pontos_fora']?>) <?=$jogo['fora_nome']?></h3>

    <div class="chart-container">
        <canvas id="graficoPartida"></canvas>
    </div>

    <script>
        // Captura os dados processados pelo PHP
        const labelsSets = <?= json_encode($labels) ?>;
        const dadosCasa = <?= json_encode($pontos_casa) ?>;
        const dadosFora = <?= json_encode($pontos_fora) ?>;
        
        const nomeCasa = "<?= $jogo['casa_nome'] ?>";
        const nomeFora = "<?= $jogo['fora_nome'] ?>";

        const ctx = document.getElementById('graficoPartida').getContext('2d');
        
        new Chart(ctx, {
            type: 'line', // Tipo do gráfico: Linha (igual ao modelo enviado)
            data: {
                labels: labelsSets, // Eixo X: "Set 1", "Set 2", etc.
                datasets: [
                    {
                        label: nomeCasa,
                        data: dadosCasa,
                        borderColor: '#2a9d8f', // Cor da linha do Time da Casa
                        backgroundColor: 'rgba(42, 157, 143, 0.1)',
                        borderWidth: 3,
                        tension: 0.2, // Deixa as curvas mais suaves
                        pointRadius: 5,
                        pointBackgroundColor: '#2a9d8f'
                    },
                    {
                        label: nomeFora,
                        data: dadosFora,
                        borderColor: '#e76f51', // Cor da linha do Time Visitante
                        backgroundColor: 'rgba(231, 111, 81, 0.1)',
                        borderWidth: 3,
                        tension: 0.2,
                        pointRadius: 5,
                        pointBackgroundColor: '#e76f51'
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        labels: { color: '#f4f4f9', font: { size: 14 } }
                    }
                },
                scales: {
                    y: {
                        title: { display: true, text: 'Pontos Feitos no Set', color: '#888' },
                        ticks: { color: '#f4f4f9' },
                        grid: { color: 'rgba(255, 255, 255, 0.1)' },
                        min: 0 // Começa a contagem de pontos do zero no eixo Y
                    },
                    x: {
                        ticks: { color: '#f4f4f9' },
                        grid: { color: 'rgba(255, 255, 255, 0.05)' }
                    }
                }
            }
        });
    </script>
</body>
</html>
