<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'conexao.php';

$mensagem_sucesso = "";
$mensagem_erro = "";

// PROCESSAR CADASTRO DE PAÍS (COM VERIFICAÇÃO DE DUPLICIDADE)
if (isset($_POST['cadastrar_pais'])) {
    $nome = trim($_POST['nome_pais']);
    if (!empty($nome)) {
        try {
            $verificar = $pdo->prepare("SELECT COUNT(*) FROM paises WHERE LOWER(nome) = LOWER(?)");
            $verificar->execute([$nome]);
            
            if ($verificar->fetchColumn() > 0) {
                $mensagem_erro = "Erro: O país '$nome' já está cadastrado!";
            } else {
                $stmt = $pdo->prepare("INSERT INTO paises (nome) VALUES (?)");
                $stmt->execute([$nome]);
                header("Location: cadastro.php?sucesso=pais");
                exit;
            }
        } catch (PDOException $e) { $mensagem_erro = "Erro: " . $e->getMessage(); }
    }
}

// PROCESSAR CADASTRO DE PARTIDA
if (isset($_POST['cadastrar_partida'])) {
    $id_casa = $_POST['id_casa'];
    $id_fora = $_POST['id_fora'];
    $p_casa = $_POST['pontos_casa'];
    $p_fora = $_POST['pontos_fora'];
    $genero = $_POST['genero'];

    if ($id_casa == $id_fora) {
        $mensagem_erro = "Erro: Um país não pode jogar contra ele mesmo!";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO partidas (id_casa, id_fora, pontos_casa, pontos_fora, genero) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$id_casa, $id_fora, $p_casa, $p_fora, $genero]);
            header("Location: cadastro.php?sucesso=partida");
            exit;
        } catch (PDOException $e) { $mensagem_erro = "Erro: " . $e->getMessage(); }
    }
}

// DELETAR PAÍS OU PARTIDA
if (isset($_GET['excluir_pais'])) {
    $id = $_GET['excluir_pais'];
    try {
        $pdo->prepare("DELETE FROM paises WHERE id = ?")->execute([$id]);
        
        // MÁGICA: Se a tabela ficou vazia, reseta o auto_increment para 1 automaticamente
        $total = $pdo->query("SELECT COUNT(*) FROM paises")->fetchColumn();
        if ($total == 0) {
            $pdo->query("ALTER TABLE paises AUTO_INCREMENT = 1");
        }

        header("Location: cadastro.php?sucesso=del_pais");
        exit;
    } catch (PDOException $e) { 
        $mensagem_erro = "Erro: Não é possível deletar este país porque ele já possui partidas registradas!"; 
    }
}

if (isset($_GET['excluir_partida'])) {
    $id = $_GET['excluir_partida'];
    $pdo->prepare("DELETE FROM partidas WHERE id = ?")->execute([$id]);
    header("Location: cadastro.php?sucesso=del_partida");
    exit;
}

// MENSAGENS DE RETORNO
if (isset($_GET['sucesso'])) {
    if ($_GET['sucesso'] == 'pais') $mensagem_sucesso = "País cadastrado com sucesso!";
    if ($_GET['sucesso'] == 'partida') $mensagem_sucesso = "Partida registrada com sucesso!";
    if ($_GET['sucesso'] == 'del_pais') $mensagem_sucesso = "País removido com sucesso!";
    if ($_GET['sucesso'] == 'del_partida') $mensagem_sucesso = "Partida excluída com sucesso!";
    if ($_GET['sucesso'] == 'editado') $mensagem_sucesso = "Dados updated com sucesso!";
}

// BUSCAS
$paises_select = $pdo->query("SELECT * FROM paises ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC); 
$paises_historico = $pdo->query("SELECT * FROM paises ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC); 
$partidas = $pdo->query("SELECT p.*, t1.nome AS casa, t2.nome AS fora FROM partidas p JOIN paises t1 ON p.id_casa = t1.id JOIN paises t2 ON p.id_fora = t2.id ORDER BY p.id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciador VNL</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; background-color: #f4f4f9; color: #333; }
        .box { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); max-width: 700px; margin-left: auto; margin-right: auto; }
        label { display: block; margin-top: 15px; font-weight: bold; }
        input, select { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { background: #0056b3; color: white; border: none; padding: 12px 20px; margin-top: 20px; border-radius: 4px; cursor: pointer; font-weight: bold; width: 100%; }
        button:hover { background: #004085; }
        .voltar { display: block; text-align: center; margin-bottom: 25px; color: #0056b3; text-decoration: none; font-weight: bold; }
        h2 { margin-top: 0; color: #0056b3; border-bottom: 2px solid #0056b3; padding-bottom: 8px; }
        .radio-group { margin-top: 8px; display: flex; gap: 20px; }
        .radio-group label { display: inline; font-weight: normal; margin-top: 0; cursor: pointer; }
        .radio-group input { width: auto; margin-top: 0; margin-right: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #f2f2f2; }
        .btn-edit { color: #0056b3; text-decoration: none; font-weight: bold; margin-right: 10px; }
        .btn-del { color: #e63946; text-decoration: none; font-weight: bold; }
        .barra-pesquisa { background: #fff; border: 2px solid #0056b3; padding: 8px; margin-top: 10px; border-radius: 4px; font-size: 14px; width: 100%; box-sizing: border-box; }
    </style>
</head>
<body>

    <a href="index.php" class="voltar">← Ver Tabela de Classificação Geral</a>

    <?php if (!empty($mensagem_sucesso)): ?>
        <div style="background-color: #d4edda; color: #155724; padding: 15px; margin: 20px auto; max-width: 700px; border-radius: 4px; font-weight: bold; text-align: center; border: 1px solid #c3e6cb;"><?php echo $mensagem_sucesso; ?></div>
    <?php endif; ?>
    <?php if (!empty($mensagem_erro)): ?>
        <div style="background-color: #f8d7da; color: #721c24; padding: 15px; margin: 20px auto; max-width: 700px; border-radius: 4px; font-weight: bold; text-align: center; border: 1px solid #f5c6cb;"><?php echo $mensagem_erro; ?></div>
    <?php endif; ?>

    <div class="box">
        <h2>Cadastrar Novo País</h2>
        <form method="POST" action="cadastro.php">
            <label>Nome do País:</label>
            <input type="text" name="nome_pais" required placeholder="Ex: Brasil">
            <button type="submit" name="cadastrar_pais">Salvar País</button>
        </form>
    </div>

    <div class="box">
        <h2>Registrar Nova Partida</h2>
        <form method="POST" action="cadastro.php">
            <label>Categoria:</label>
            <div class="radio-group">
                <label><input type="radio" name="genero" value="F" checked> Feminina</label>
                <label><input type="radio" name="genero" value="M"> Masculina</label>
            </div>
            <label>Casa:</label>
            <select name="id_casa" required>
                <option value="">Selecione...</option>
                <?php foreach($paises_select as $p): ?> <option value="<?=$p['id']?>"><?=$p['nome']?></option> <?php endforeach; ?>
            </select>
            <label>Visita:</label>
            <select name="id_fora" required>
                <option value="">Selecione...</option>
                <?php foreach($paises_select as $p): ?> <option value="<?=$p['id']?>"><?=$p['nome']?></option> <?php endforeach; ?>
            </select>
            <label>Sets Casa:</label> <input type="number" name="pontos_casa" min="0" max="3" required>
            <label>Sets Visita:</label> <input type="number" name="pontos_fora" min="0" max="3" required>
            <button type="submit" name="cadastrar_partida">Registrar Partida</button>
        </form>
    </div>

    <div class="box">
        <h2>Gerenciar Países Cadastrados</h2>
        <input type="text" id="buscaPaises" class="barra-pesquisa" onkeyup="filtrarPaises()" placeholder="🔎 Digite o nome do país para buscar...">
        <table id="tabelaPaises">
            <thead><tr><th width="60">Nº</th><th>País</th><th>Ações</th></tr></thead>
            <tbody>
                <?php 
                $numero_visual = 1; // Contador sequencial perfeito para a tela
                foreach($paises_historico as $p): 
                ?>
                <tr>
                    <td style="font-weight: bold; text-align: center; color: #777;"><?=$numero_visual?></td>
                    <td class="nome-alvo" style="font-weight: bold;"><?=$p['nome']?></td>
                    <td>
                        <a href="editar_pais.php?id=<?=$p['id']?>" class="btn-edit">Editar</a>
                        <a href="cadastro.php?excluir_pais=<?=$p['id']?>" class="btn-del" onclick="return confirm('Atenção: Excluir um país apagará seu histórico de forma permanente se ele não tiver partidas vinculadas. Confirmar exclusão?')">Excluir</a>
                    </td>
                </tr>
                <?php 
                $numero_visual++; 
                endforeach; 
                ?>
            </tbody>
        </table>
    </div>

    <div class="box">
        <h2>Gerenciar Partidas Registradas</h2>
        <input type="text" id="buscaPartidas" class="barra-pesquisa" onkeyup="filtrarPartidas()" placeholder="🔎 Digite o nome de um país para buscar confrontos...">
        <table id="tabelaPartidas">
            <thead><tr><th>Confronto</th><th>Placar</th><th>Cat.</th><th>Ações</th></tr></thead>
            <tbody>
                <?php foreach($partidas as $part): ?>
                <tr>
                    <td class="confronto-alvo"><?=$part['casa']?> x <?=$part['fora']?></td>
                    <td><?=$part['pontos_casa']?> x <?=$part['pontos_fora']?></td>
                    <td><?=$part['genero'] == 'M' ? 'Masc' : 'Fem'?></td>
                    <td>
                        <a href="editar_partida.php?id=<?=$part['id']?>" class="btn-edit">Editar</a>
                        <a href="cadastro.php?excluir_partida=<?=$part['id']?>" class="btn-del" onclick="return confirm('Deseja mesmo excluir esta partida?')">Excluir</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
    function filtrarPaises() {
        var input = document.getElementById("buscaPaises");
        var filter = input.value.toUpperCase();
        var table = document.getElementById("tabelaPaises");
        var tr = table.getElementsByTagName("tr");

        for (var i = 1; i < tr.length; i++) {
            var td = tr[i].getElementsByClassName("nome-alvo")[0];
            if (td) {
                var txtValue = td.textContent || td.innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                }
            }
        }
    }

    function filtrarPartidas() {
        var input = document.getElementById("buscaPartidas");
        var filter = input.value.toUpperCase();
        var table = document.getElementById("tabelaPartidas");
        var tr = table.getElementsByTagName("tr");

        for (var i = 1; i < tr.length; i++) {
            var td = tr[i].getElementsByClassName("confronto-alvo")[0];
            if (td) {
                var txtValue = td.textContent || td.innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                }
            }
        }
    }
    </script>

</body>
</html>
