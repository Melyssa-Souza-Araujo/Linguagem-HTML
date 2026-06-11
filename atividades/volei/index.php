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
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Classificação VNL</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; background-color: #0d1b2a; color: #f4f4f9; }
        h1 { text-align: center; color: #e0e1dd; }
        table { width: 100%; max-width: 800px; margin: 20px auto; border-collapse: collapse; background: #1b263b; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.3); }
        th, td { padding: 12px 15px; text-align: center; }
        th { background-color: #415a77; font-weight: bold; text-transform: uppercase; font-size: 14px; }
        tr { border-bottom: 1px solid #415a77; }
        tr:last-child { border-bottom: none; }
        
        .zona-classificacao { border-left: 5px solid #2a9d8f; }
        .posicao-numero { font-weight: bold; color: #e0e1dd; }
        .nome-pais { text-align: left; font-weight: bold; }
        
        .btn-gerenciar { display: block; width: 200px; margin: 20px auto; text-align: center; background: #e0e1dd; color: #0d1b2a; padding: 10px; border-radius: 4px; text-decoration: none; font-weight: bold; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
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

</body>
</html>
