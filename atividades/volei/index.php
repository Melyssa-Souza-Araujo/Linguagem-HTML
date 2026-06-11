<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'conexao.php';

// Captura o país selecionado para o filtro do histórico (padrão: todos)
$pais_filtro = isset($_GET['pais_filtro']) ? $_GET['pais_filtro'] : 'todos';

// 1. Puxar todos os países cadastrados
$paises = $pdo->query("SELECT * FROM paises ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

// Criar estruturas separadas para as tabelas Feminina e Masculina
$tabela_F = [];
$tabela_M = [];

foreach ($paises as $p) {
    // Inicializa a estrutura para o Feminino
    $tabela_F[$p['id']] = [
        'nome' => $p['nome'],
        'vitorias' => 0,
        'derrotas' => 0,
        'pontos' => 0
    ];
    // Inicializa a estrutura para o Masculino
    $tabela_M[$p['id']] = [
        'nome' => $p['nome'],
        'vitorias' => 0,
        'derrotas' => 0,
        'pontos' => 0
    ];
}

// 2. Puxar todas as partidas gravadas para processar as duas classificações
$partidas_todas = $pdo->query("SELECT p.*, t1.nome AS casa_nome, t2.nome AS fora_nome FROM partidas p JOIN paises t1 ON p.id_casa = t1.id JOIN paises t2 ON p.id_fora = t2.id ORDER BY p.id DESC")->fetchAll(PDO::FETCH_ASSOC);

foreach ($partidas_todas as $partida) {
    $casa = $partida['id_casa'];
    $fora = $partida['id_fora'];
    $p_casa = $partida['pontos_casa'];
    $p_fora = $partida['pontos_fora'];
    $genero = $partida['genero'];

    // Define qual tabela de classificação vai pontuar com base no gênero da partida
    if ($genero == 'F') {
        if (!isset($tabela_F[$casa]) || !isset($tabela_F[$fora])) continue;
        $ref_tabela = &$tabela_F;
    } else {
        if (!isset($tabela_M[$casa]) || !isset($tabela_M[$fora])) continue;
        $ref_tabela = &$tabela_M;
    }

    // Processamento de pontos e resultados
    if ($p_casa > $p_fora) {
        $ref_tabela[$casa]['vitorias'] += 1;
        $ref_tabela[$fora]['derrotas'] += 1;
        
        if ($p_casa == 3 && ($p_fora == 0 || $p_fora == 1)) {
            $ref_tabela[$casa]['pontos'] += 3;
        } elseif ($p_casa == 3 && $p_fora == 2) {
            $ref_tabela[$casa]['pontos'] += 2;
            $ref_tabela[$fora]['pontos'] += 1;
        }
    } else {
        $ref_tabela[$fora]['vitorias'] += 1;
        $ref_tabela[$casa]['derrotas'] += 1;

        if ($p_fora == 3 && ($p_casa == 0 || $p_casa == 1)) {
            $ref_tabela[$fora]['pontos'] += 3;
        } elseif ($p_fora == 3 && $p_casa == 2) {
            $ref_tabela[$fora]['pontos'] += 2;
            $ref_tabela[$casa]['pontos'] += 1;
        }
    }
}

// Ordenar as duas classificações de forma independente (Mais Vitórias -> Mais Pontos)
$ordenar_tabela = function($a, $b) {
    if ($a['vitorias'] != $b['vitorias']) {
        return $b['vitorias'] <=> $a['vitorias'];
    }
    return $b['pontos'] <=> $a['pontos'];
};
uasort($tabela_F, $ordenar_tabela);
uasort($tabela_M, $ordenar_tabela);

// 3. Filtrar o histórico de partidas por país (se selecionado)
$historico_filtrado = [];
foreach ($partidas_todas as $part) {
    if ($pais_filtro == 'todos' || $part['id_casa'] == $pais_filtro || $part['id_fora'] == $pais_filtro) {
        $historico_filtrado[] = $part;
    }
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
        h2 { margin-top: 40px; border-bottom: 2px solid #415a77; padding-bottom: 10px; }
        
        .tabelas-container { display: flex; flex-wrap: wrap; gap: 20px; justify-content: center; margin-top: 20px; }
        .tabela-bloco { flex: 1; min-width: 380px; max-width: 600px; }

        table { width: 100%; border-collapse: collapse; background: #1b263b; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.3); }
        th, td { padding: 12px 15px; text-align: center; }
        th { background-color: #415a77; font-weight: bold; text-transform: uppercase; font-size: 13px; }
        tr { border-bottom: 1px solid #415a77; }
        tr:last-child { border-bottom: none; }
        
        .zona-classificacao { border-left: 5px solid #2a9d8f; }
        .posicao-numero { font-weight: bold; color: #e0e1dd; }
        .nome-pais { text-align: left; font-weight: bold; }
        
        .btn-gerenciar { display: block; width: 200px; margin: 20px auto; text-align: center; background: #e0e1dd; color: #0d1b2a; padding: 10px; border-radius: 4px; text-decoration: none; font-weight: bold; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
        .btn-gerenciar:hover { background: #cbd5e1; }

        /* Estilos do Histórico e Filtro */
        .filtro-historico { background: #1b263b; padding: 15px; border-radius: 8px; max-width: 500px; margin: 20px auto; text-align: center; border: 1px solid #415a77; }
        .filtro-historico select { padding: 8px 15px; border-radius: 4px; border: 1px solid #415a77; background: #0d1b2a; color: white; font-weight: bold; font-size: 14px; width: 60%; }
        .filtro-historico button { padding: 8px 15px; background: #2a9d8f; color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; margin-left: 5px; }
        
        .historico-container { max-width: 800px; margin: 20px auto; }
        .partida-card { background: #1b263b; padding: 15px; margin-bottom: 12px; border-radius: 6px; border-left: 5px solid #415a77; display: flex; justify-content: space-between; align-items: center; }
        .partida-info { font-size: 16px; font-weight: bold; }
        .partida-placar { background: #0d1b2a; padding: 6px 15px; border-radius: 4px; color: #2a9d8f; font-size: 18px; font-weight: bold; letter-spacing: 2px; }
        .tag-genero { font-size: 11px; font-weight: bold; padding: 3px 8px; border-radius: 3px; background: #415a77; color: #e0e1dd; margin-left: 10px; }
    </style>
</head>
<body>

    <h1>PAINEL DE CLASSIFICAÇÃO VNL</h1>
    <a href="cadastro.php" class="btn-gerenciar">Painel de Cadastro →</a>

    <div class="tabelas-container">
        
        <div class="tabela-bloco">
            <h2>Classificação Feminina</h2>
            <table>
                <thead>
                    <tr>
                        <th width="50">Pos</th>
                        <th style="text-align: left;">País</th>
                        <th width="60">V</th>
                        <th width="60">D</th>
                        <th width="60">Pts</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $pos = 1;
                    foreach ($tabela_F as $item): 
                        $classeZona = ($pos <= 8) ? "zona-classificacao" : "";
                    ?>
                    <tr class="<?=$classeZona?>">
                        <td class="posicao-numero"><?=$pos?></td>
                        <td class="nome-pais"><?=$item['nome']?></td>
                        <td style="color: #2a9d8f; font-weight: bold;"><?=$item['vitorias']?></td>
                        <td style="color: #e63946;"><?=$item['derrotas']?></td>
                        <td style="font-weight: bold;"><?=$item['pontos']?></td>
                    </tr>
                    <?php $pos++; endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="tabela-bloco">
            <h2>Classificação Masculina</h2>
            <table>
                <thead>
                    <tr>
                        <th width="50">Pos</th>
                        <th style="text-align: left;">País</th>
                        <th width="60">V</th>
                        <th width="60">D</th>
                        <th width="60">Pts</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $pos = 1;
                    foreach ($tabela_M as $item): 
                        $classeZona = ($pos <= 8) ? "zona-classificacao" : "";
                    ?>
                    <tr class="<?=$classeZona?>">
                        <td class="posicao-numero"><?=$pos?></td>
                        <td class="nome-pais"><?=$item['nome']?></td>
                        <td style="color: #2a9d8f; font-weight: bold;"><?=$item['vitorias']?></td>
                        <td style="color: #e63946;"><?=$item['derrotas']?></td>
                        <td style="font-weight: bold;"><?=$item['pontos']?></td>
                    </tr>
                    <?php $pos++; endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>

    <h2>Histórico de Partidas</h2>

    <div class="filtro-historico">
        <form method="GET" action="index.php">
            <select name="pais_filtro">
                <option value="todos" <?=$pais_filtro == 'todos'?'selected':''?>>-- Visualizar Todos os Países --</option>
                <?php foreach ($paises as $p): ?>
                    <option value="<?=$p['id']?>" <?=$pais_filtro == $p['id']?'selected':''?>><?=$p['nome']?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Filtrar</button>
        </form>
    </div>

    <div class="historico-container">
        <?php if (empty($historico_filtrado)): ?>
            <p style="text-align: center; color: #888; font-style: italic;">Nenhuma partida encontrada para este filtro.</p>
        <?php else: ?>
            <?php foreach ($historico_filtrado as $part): 
                $cat = ($part['genero'] == 'M') ? 'MASCULINO' : 'FEMININO';
            ?>
                <div class="partida-card">
                    <div class="partida-info">
                        <span><?=$part['casa_nome']?></span> 
                        <span style="color: #888; font-weight: normal; margin: 0 10px;">vs</span> 
                        <span><?=$part['fora_nome']?></span>
                        <span class="tag-genero"><?=$cat?></span>
                    </div>
                    <div class="partida-placar">
                        <?=$part['pontos_casa']?> x <?=$part['pontos_fora']?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</body>
</html>
