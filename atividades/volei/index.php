<?php
include 'conexao.php';

// 1. Puxar todos os países cadastrados
$paises = $pdo->query("SELECT * FROM paises")->fetchAll(PDO::FETCH_ASSOC);

// 2. Criar uma estrutura de dados na memória para calcular a tabela
$tabela = [];
foreach ($paises as $p) {
    $tabela[$p['id']] = [
        'nome' => $p['nome'],
        'vitorias' => 0,
        'derrotas' => 0,
        'pontos' => 0
    ];
}

// 3. Puxar todas as partidas gravadas e somar as vitórias, derrotas e pontuação
$partidas = $pdo->query("SELECT * FROM partidas")->fetchAll(PDO::FETCH_ASSOC);
foreach ($partidas as $partida) {
    $casa = $partida['id_casa'];
    $fora = $partida['id_fora'];
    $p_casa = $partida['pontos_casa'];
    $p_fora = $partida['pontos_fora'];

    // No vôlei, quem ganha 3 sets ganha a partida
    if ($p_casa > $p_fora) {
        $tabela[$casa]['vitorias'] += 1;
        $tabela[$fora]['derrotas'] += 1;
        
        // Atribuição de pontos pelas regras do vôlei (3x0 ou 3x1 = 3pts, 3x2 = 2pts para vencedor e 1pt para perdedor)
        if ($p_casa == 3 && ($p_fora == 0 || $p_fora == 1)) {
            $tabela[$casa]['pontos'] += 3;
        } elseif ($p_casa == 3 && $p_fora == 2) {
            $tabela[$casa]['pontos'] += 2;
            $tabela[$fora]['pontos'] += 1;
        }
    } else {
        $tabela[$fora]['vitorias'] += 1;
        $tabela[$casa]['derrotas'] += 1;

        if ($p_fora == 3 && ($p_casa == 0 || $p_casa == 1)) {
            $tabela[$fora]['pontos'] += 3;
        } elseif ($p_fora == 3 && $p_casa == 2) {
            $tabela[$fora]['pontos'] += 2;
            $tabela[$casa]['pontos'] += 1;
        }
    }
}

// 4. Ordenar a tabela: Primeiro por número de Vitórias (V), depois por Pontos (Pts)
uasort($tabela, function($a, $b) {
    if ($a['vitorias'] != $b['vitorias']) {
        return $b['vitorias'] <=> $a['vitorias']; // Mais vitórias primeiro
    }
    return $b['pontos'] <=> $a['pontos']; // Mais pontos como critério de desempate
});

// Função auxiliar para transformar o link normal do YouTube em link incorporado (embed) seguro para o iframe
function obterLinkEmbedYoutube($url) {
    if (empty($url)) return null;
    
    // Filtros regex para detectar ID do vídeo padrão ou encurtado (youtu.be)
    if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match)) {
        return "https://www.youtube.com/embed/" . $match[1];
    }
    return null;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Classificação VNL</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; background-color: #0d1b2a; color: #f4f4f9; }
        h1, h2 { text-align: center; color: #e0e1dd; }
        table { width: 100%; max-width: 800px; margin: 20px auto; border-collapse: collapse; background: #1b263b; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.3); }
        th, td { padding: 12px 15px; text-align: center; }
        th { background-color: #415a77; font-weight: bold; text-transform: uppercase; font-size: 14px; }
        tr { border-bottom: 1px solid #415a77; }
        tr:last-child { border-bottom: none; }
        
        /* Destaque visual baseado nos prints enviados (G8 avança para o mata-mata) */
        .zona-classificacao { border-left: 5px solid #2a9d8f; }
        .posicao-numero { font-weight: bold; color: #e0e1dd; }
        .nome-pais { text-align: left; font-weight: bold; }
        
        .container-videos { max-width: 800px; margin: 40px auto; }
        .video-card { background: #1b263b; padding: 15px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.2); }
        .iframe-wrapper { position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; margin-top: 10px; border-radius: 4px; }
        .iframe-wrapper iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0; }
        .btn-gerenciar { display: block; width: 200px; margin: 20px auto; text-align: center; background: #e0e1dd; color: #0d1b2a; padding: 10px; border-radius: 4px; text-decoration: none; font-weight: bold; }
        .btn-gerenciar:hover { background: #cbd5e1; }
    </style>
</head>
<body>

    <h1>CLASSIFICAÇÃO VNL</h1>
    
    <a href="cadastro.php" class="btn-gerenciar">Painel de Cadastro →</a>

    <table>
        <thead>
            <tr>
                <th width="60">Pos</th>
                <th style="text-align: left;">País</th>
                <th width="80">V</th>
                <th width="80">D</th>
                <th width="80">Pts</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $pos = 1;
            foreach ($tabela as $item): 
                // Aplica a classe verde de mata-mata para os 8 primeiros colocados
                $classeZona = ($pos <= 8) ? "zona-classificacao" : "";
            ?>
            <tr class="<?=$classeZona?>">
                <td class="posicao-numero"><?=$pos?></td>
                <td class="nome-pais"><?=$item['nome']?></td>
                <td style="color: #2a9d8f; font-weight: bold;"><?=$item['vitorias']?></td>
                <td style="color: #e63946;"><?=$item['derrotas']?></td>
                <td style="font-weight: bold;"><?=$item['pontos']?></td>
            </tr>
            <?php 
            $pos++;
            endforeach; 
            ?>
        </tbody>
    </table>

    <div class="container-videos">
        <h2>Histórico de Partidas e Vídeos</h2>
        <?php if (empty($partidas)): ?>
            <p style="text-align:center; color:#888;">Nenhuma partida com vídeo cadastrada ainda.</p>
        <?php endif; ?>

        <?php foreach ($partidas as $partida): 
            $casaNome = $tabela[$partida['id_casa']]['nome'];
            $foraNome = $tabela[$partida['id_fora']]['nome'];
            $embedUrl = obterLinkEmbedYoutube($partida['youtube_url']);
        ?>
            <div class="video-card">
                <h3><?=$casaNome?> <?=$partida['pontos_casa']?> x <?=$partida['pontos_fora']?> <?=$foraNome?></h3>
                
                <?php if ($embedUrl): ?>
                    <div class="iframe-wrapper">
                        <iframe src="<?=$embedUrl?>" 
                                title="YouTube video player" 
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                                allowfullscreen>
                        </iframe>
                    </div>
                <?php else: ?>
                    <p style="color: #888; font-size: 14px;">Nenhum vídeo anexado para esta partida.</p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

</body>
</html>
