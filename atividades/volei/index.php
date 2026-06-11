<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'conexao.php';

// 1. Puxar todos os países cadastrados para a estrutura base
$paises = $pdo->query("SELECT * FROM paises")->fetchAll(PDO::FETCH_ASSOC);

$tabela = [];
foreach ($paises as $p) {
    $tabela[$p['id']] = [
        'nome' => $p['nome'],
        'vitorias' => 0,
        'derrotas' => 0,
        'pontos' => 0
    ];
}

// 2. Puxar todas as partidas gravadas para processar a pontuação da tabela
$partidas_votos = $pdo->query("SELECT * FROM partidas")->fetchAll(PDO::FETCH_ASSOC);
foreach ($partidas_votos as $partida) {
    $casa = $partida['id_casa'];
    $fora = $partida['id_fora'];
    $p_casa = $partida['pontos_casa'];
    $p_fora = $partida['pontos_fora'];

    // Evita erros caso o país tenha sido excluído mas o histórico ficou pendente
    if (!isset($tabela[$casa]) || !isset($tabela[$fora])) {
        continue;
    }

    if ($p_casa > $p_fora) {
        $tabela[$casa]['vitorias'] += 1;
        $tabela[$fora]['derrotas'] += 1;
        
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

// 3. Ordenar a classificação (Mais Vitórias -> Mais Pontos)
uasort($tabela, function($a, $b) {
    if ($a['vitorias'] != $b['vitorias']) {
        return $b['vitorias'] <=> $a['vitorias'];
    }
    return $b['pontos'] <=> $a['pontos'];
});

// 4. BUSCA ISOLADA PARA OS VÍDEOS (Modificada para LEFT JOIN - Garante a exibição)
$query_videos = "SELECT p.*, t1.nome AS nome_casa, t2.nome AS nome_fora 
                 FROM partidas p 
                 LEFT JOIN paises t1 ON p.id_casa = t1.id 
                 LEFT JOIN paises t2 ON p.id_fora = t2.id 
                 ORDER BY p.id DESC";
$lista_videos = $pdo->query($query_videos)->fetchAll(PDO::FETCH_ASSOC);

// 5. FUNÇÃO DO YOUTUBE MELHORADA (Trata links normais, Shorts, Compartilhados e Celular)
function obterLinkEmbedYoutube($url) {
    if (empty($url)) return null;
    
    $video_id = "";
    
    // Testa formato encurtado: youtu.be/ID
    if (preg_match('/youtu\.be\/([^\?&#]+)/', $url, $matches)) {
        $video_id = $matches[1];
    }
    // Testa formato de Shorts: youtube.com/shorts/ID
    elseif (preg_match('/youtube\.com\/shorts\/([^\?&#]+)/', $url, $matches)) {
        $video_id = $matches[1];
    }
    // Testa formato padrão: youtube.com/watch?v=ID
    elseif (preg_match('/v=([^&#]+)/', $url, $matches)) {
        $video_id = $matches[1];
    }
    // Testa formato embed já pronto caso cole por engano
    elseif (preg_match('/embed\/([^\?&#]+)/', $url, $matches)) {
        $video_id = $matches[1];
    }
    
    if (!empty($video_id)) {
        return "https://www.youtube.com/embed/" . $video_id;
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
        
        .zona-classificacao { border-left: 5px solid #2a9d8f; }
        .posicao-numero { font-weight: bold; color: #e0e1dd; }
        .nome-pais { text-align: left; font-weight: bold; }
        
        .container-videos { max-width: 800px; margin: 40px auto; }
        .video-card { background: #1b263b; padding: 20px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.3); }
        .iframe-wrapper { position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; margin-top: 15px; border-radius: 6px; border: 1px solid #415a77; }
        .iframe-wrapper iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0; }
        .btn-gerenciar { display: block; width: 200px; margin: 20px auto; text-align: center; background: #e0e1dd; color: #0d1b2a; padding: 10px; border-radius: 4px; text-decoration: none; font-weight: bold; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
        .btn-gerenciar:hover { background: #cbd5e1; }
        .tag-genero { display: inline-block; padding: 3px 8px; font-size: 11px; font-weight: bold; border-radius: 3px; background: #415a77; margin-left: 10px; vertical-align: middle; }
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
        
        <?php if (empty($lista_videos)): ?>
            <p style="text-align:center; color:#888; margin-top: 30px;">Nenhuma partida registrada até o momento.</p>
        <?php endif; ?>

        <?php foreach ($lista_videos as $part): 
            $embedUrl = obterLinkEmbedYoutube($part['youtube_url']);
            $categoria = ($part['genero'] == 'M') ? 'Masculino' : 'Feminino';
            
            // Tratamento caso o país tenha sido deletado para não deixar o nome em branco
            $casaNome = !empty($part['nome_casa']) ? $part['nome_casa'] : "País Removido";
            $foraNome = !empty($part['nome_fora']) ? $part['nome_fora'] : "País Removido";
        ?>
            <div class="video-card">
                <h3 style="margin-top: 0; margin-bottom: 10px;">
                    <?=$casaNome?> <?=$part['pontos_casa']?> x <?=$part['pontos_fora']?> <?=$foraNome?>
                    <span class="tag-genero"><?=$categoria?></span>
                </h3>
                
                <?php if (!empty($embedUrl)): ?>
                    <div class="iframe-wrapper">
                        <iframe src="<?=$embedUrl?>" 
                                title="YouTube video player" 
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                                allowfullscreen>
                        </iframe>
                    </div>
                <?php else: ?>
                    <p style="color: #888; font-size: 14px; margin: 10px 0 0 0; font-style: italic;">Nenhum vídeo anexado para esta partida.</p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

</body>
</html>
