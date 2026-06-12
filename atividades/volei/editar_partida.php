<?php
session_start();
include 'conexao.php';
/** @var PDO $pdo */

// BLOQUEIO DE SESSÃO: Garante que apenas o admin consiga acessar este arquivo de edição
if (!isset($_SESSION['logado']) || $_SESSION['usuario_nivel'] !== 'admin') {
    header("Location: login.php");
    exit;
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$mensagem_erro = "";

// Verifica se o ID da partida foi passado na URL
if (!isset($_GET['id'])) {
    header("Location: cadastro.php");
    exit;
}

$id_partida = intval($_GET['id']);

// 1. Processar a Atualização dos Dados (Salvar as alterações)
if (isset($_POST['atualizar_partida'])) {
    $id_casa = $_POST['id_casa'];
    $id_fora = $_POST['id_fora'];
    $p_casa = intval($_POST['pontos_casa']);
    $p_fora = intval($_POST['pontos_fora']);
    $genero = $_POST['genero'];
    $fase = $_POST['fase'];
    $data_partida = $_POST['data_partida'];

    // Validações de vôlei para o placar geral
    $placar_valido = false;
    if (($p_casa == 3 && ($p_fora >= 0 && $p_fora <= 2)) || ($p_fora == 3 && ($p_casa >= 0 && $p_casa <= 2))) {
        $placar_valido = true;
    }

    if ($id_casa == $id_fora) {
        $mensagem_erro = "Um país não pode jogar contra ele mesmo.";
    } elseif (!$placar_valido) {
        $mensagem_erro = "Placar inválido! Um dos times deve fazer exatamente 3 sets, e o outro no máximo 2.";
    } else {
        try {
            $pdo->beginTransaction();

            // Atualiza a tabela principal 'partidas'
            $stmt = $pdo->prepare("UPDATE partidas SET id_casa = ?, id_fora = ?, pontos_casa = ?, pontos_fora = ?, genero = ?, fase = ?, data_partida = ? WHERE id = ?");
            $stmt->execute([$id_casa, $id_fora, $p_casa, $p_fora, $genero, $fase, $data_partida, $id_partida]);
            
            // Limpa as parciais antigas deste jogo para evitar duplicados ou conflitos
            $pdo->prepare("DELETE FROM detalhes_sets WHERE id_partida = ?")->execute([$id_partida]);

            // Insere as novas parciais digitadas nos inputs dos sets
            if (isset($_POST['pontos_set_casa'])) {
                foreach ($_POST['pontos_set_casa'] as $index => $pts_c) {
                    $pts_f = $_POST['pontos_set_fora'][$index];
                    $numero_set = $index + 1;

                    if (($pts_c !== '' && $pts_f !== '') && ($pts_c > 0 || $pts_f > 0)) {
                        $stmt_set = $pdo->prepare("INSERT INTO detalhes_sets (id_partida, numero_set, pontos_casa, pontos_fora) VALUES (?, ?, ?, ?)");
                        $stmt_set->execute([$id_partida, $numero_set, intval($pts_c), intval($pts_f)]);
                    }
                }
            }

            $pdo->commit();
            header("Location: cadastro.php?sucesso=partida");
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $mensagem_erro = "Erro ao atualizar partida: " . $e->getMessage();
        }
    }
}

// 2. Buscar os dados atuais desta partida para preencher o formulário
$stmt = $pdo->prepare("SELECT * FROM partidas WHERE id = ?");
$stmt->execute([$id_partida]);
$partida = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$partida) {
    header("Location: cadastro.php");
    exit;
}

// 3. Buscar as parciais de sets já existentes para esta partida (se houver)
$stmt_sets = $pdo->prepare("SELECT * FROM detalhes_sets WHERE id_partida = ? ORDER BY numero_set ASC");
$stmt_sets->execute([$id_partida]);
$sets_carregados = $stmt_sets->fetchAll(PDO::FETCH_ASSOC);

// Indexa as parciais por número do set para facilitar o preenchimento automático no HTML
$valores_sets = [];
foreach ($sets_carregados as $s) {
    $valores_sets[$s['numero_set']] = [
        'casa' => $s['pontos_casa'],
        'fora' => $s['pontos_fora']
    ];
}

// Buscar todos os países para alimentar os seletores de times
$paises = $pdo->query("SELECT * FROM paises ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Partida - VNL</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; background-color: #f4f4f9; color: #333; }
        h1, h2 { text-align: center; color: #0d1b2a; }
        .box { background: white; padding: 25px; border-radius: 8px; width: 100%; max-width: 500px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); box-sizing: border-box; margin: 20px auto; }
        label { display: block; margin-top: 12px; font-weight: bold; font-size: 14px; }
        input, select { width: 100%; padding: 8px; margin: 4px 0 12px 0; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        button { background: #ffb703; color: #000; border: none; padding: 12px; width: 100%; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 15px; margin-top: 15px; }
        button:hover { background: #e5a300; }
        .alert-error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px; text-align: center; font-weight: bold; }
        .btn-voltar { display: block; width: 200px; margin: 20px auto; text-align: center; background: #0d1b2a; color: white; padding: 10px; border-radius: 4px; text-decoration: none; font-weight: bold; }
        
        /* Estilo para a grade de pontos por set */
        .grid-sets { display: flex; flex-direction: column; gap: 8px; background: #f8f9fa; padding: 12px; border-radius: 6px; border: 1px dashed #cbd5e1; margin-bottom: 12px; }
        .linha-set { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
        .linha-set span { font-weight: bold; font-size: 13px; min-width: 45px; color: #475569; }
        .linha-set input { margin: 0; text-align: center; padding: 6px; }
    </style>
</head>
<body>

    <h1>✏️ Editar Resultado de Partida</h1>
    <a href="cadastro.php" class="btn-voltar">← Cancelar e Voltar</a>

    <div class="box">
        <?php if(!empty($mensagem_erro)): ?><div class="alert-error"><?=$mensagem_erro?></div><?php endif; ?>

        <form method="POST">
            <label>Categoria/Gênero:</label>
            <select name="genero" required>
                <option value="F" <?=$partida['genero'] == 'F' ? 'selected' : ''?>>Feminino (F)</option>
                <option value="M" <?=$partida['genero'] == 'M' ? 'selected' : ''?>>Masculino (M)</option>
            </select>

            <label>Fase do Campeonato:</label>
            <select name="fase" required>
                <option value="Fase de Grupos" <?=$partida['fase'] == 'Fase de Grupos' ? 'selected' : ''?>>Fase de Grupos</option>
                <option value="Quartas de Final" <?=$partida['fase'] == 'Quartas de Final' ? 'selected' : ''?>>Quartas de Final</option>
                <option value="Semifinal" <?=$partida['fase'] == 'Semifinal' ? 'selected' : ''?>>Semifinal</option>
                <option value="Final" <?=$partida['fase'] == 'Final' ? 'selected' : ''?>>Final</option>
            </select>

            <label>Data do Confronto:</label>
            <input type="date" name="data_partida" required value="<?=htmlspecialchars($partida['data_partida'])?>">

            <div style="display: flex; gap: 10px;">
                <div style="flex: 1;">
                    <label>Time da Casa:</label>
                    <select name="id_casa" required>
                        <?php foreach($paises as $p): ?>
                            <option value="<?=$p['id']?>" <?=$partida['id_casa'] == $p['id'] ? 'selected' : ''?>>
                                <?=$p['nome']?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <label>Placar Geral (Sets):</label>
                    <input type="number" name="pontos_casa" min="0" max="3" value="<?=$partida['pontos_casa']?>" required>
                </div>
                
                <div style="flex: 1;">
                    <label>Time Visitante:</label>
                    <select name="id_fora" required>
                        <?php foreach($paises as $p): ?>
                            <option value="<?=$p['id']?>" <?=$partida['id_fora'] == $p['id'] ? 'selected' : ''?>>
                                <?=$p['nome']?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <label>Placar Geral (Sets):</label>
                    <input type="number" name="pontos_fora" min="0" max="3" value="<?=$partida['pontos_fora']?>" required>
                </div>
            </div>

            <label>Pontuação por Set (Para alimentar o gráfico):</label>
            <div class="grid-sets">
                <?php for($i = 0; $i < 5; $i++): 
                    $num_set = $i + 1;
                    // Resgata o valor antigo salvo se existir, caso contrário deixa vazio
                    $val_casa = isset($valores_sets[$num_set]) ? $valores_sets[$num_set]['casa'] : '';
                    $val_fora = isset($valores_sets[$num_set]) ? $valores_sets[$num_set]['fora'] : '';
                ?>
                    <div class="linha-set">
                        <span>Set <?=$num_set?>:</span>
                        <input type="number" name="pontos_set_casa[<?=$i?>]" value="<?=$val_casa?>" placeholder="Casa" min="0">
                        x
                        <input type="number" name="pontos_set_fora[<?=$i?>]" value="<?=$val_fora?>" placeholder="Fora" min="0">
                    </div>
                <?php endfor; ?>
            </div>

            <button type="submit" name="atualizar_partida">Salvar Alterações</button>
        </form>
    </div>

    <script>
    // Mantém a inversão automática de seleção ativa também na tela de edição
    const selectCasa = document.getElementsByName('id_casa')[0];
    const selectFora = document.getElementsByName('id_fora')[0];

    function ajustarOpcoesConfronto() {
        const valorCasa = selectCasa.value;
        const valorFora = selectFora.value;

        Array.from(selectFora.options).forEach(opcao => {
            opcao.style.display = (opcao.value === valorCasa && opcao.value !== "") ? "none" : "block";
        });

        Array.from(selectCasa.options).forEach(opcao => {
            opcao.style.display = (opcao.value === valorFora && opcao.value !== "") ? "none" : "block";
        });
    }

    // Executa no carregamento para travar as opções iniciais
    ajustarOpcoesConfronto();

    selectCasa.addEventListener('change', ajustarOpcoesConfronto);
    selectFora.addEventListener('change', ajustarOpcoesConfronto);
    </script>
</body>
</html>
