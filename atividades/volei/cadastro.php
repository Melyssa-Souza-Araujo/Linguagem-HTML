<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'conexao.php';
session_start();
/** @var PDO $pdo */

// BLOQUEIO DE SESSÃO: Se não estiver logado ou se não for administrador, joga para o login
if (!isset($_SESSION['logado']) || $_SESSION['usuario_nivel'] !== 'admin') {
    header("Location: login.php");
    exit;
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$mensagem_sucesso = "";
$mensagem_erro = "";
// PROCESSAR CADASTRO DE PAÍS
if (isset($_POST['cadastrar_pais'])) {
    $nome = trim($_POST['nome_pais']);
    $sigla = strtoupper(trim($_POST['sigla_pais']));
    
    if (!empty($nome) && strlen($sigla) === 2) {
        try {
            $verificar = $pdo->prepare("SELECT COUNT(*) FROM paises WHERE LOWER(nome) = LOWER(?)");
            $verificar->execute([$nome]);
            
            if ($verificar->fetchColumn() > 0) {
                $mensagem_erro = "Erro: O país '$nome' já está cadastrado!";
            } else {
                $stmt = $pdo->prepare("INSERT INTO paises (nome, sigla) VALUES (?, ?)");
                $stmt->execute([$nome, $sigla]);
                header("Location: cadastro.php?sucesso=pais");
                exit;
            }
        } catch (PDOException $e) { $mensagem_erro = "Erro: " . $e->getMessage(); }
    } else {
        $mensagem_erro = "Erro: A sigla deve conter exatamente 2 letras (Ex: BR, US, JP).";
    }
}

// PROCESSAR CADASTRO DE PARTIDA
if (isset($_POST['cadastrar_partida'])) {
    $id_casa = $_POST['id_casa'];
    $id_fora = $_POST['id_fora'];
    $p_casa = intval($_POST['pontos_casa']);
    $p_fora = intval($_POST['pontos_fora']);
    $genero = $_POST['genero'];
    $fase = $_POST['fase'];

    if ($id_casa == $id_fora) {
        $mensagem_erro = "Erro: Um país não pode jogar contra ele mesmo!";
    } else {
        $placar_valido = false;
        if (($p_casa === 3 && in_array($p_fora, [0, 1, 2])) || ($p_fora === 3 && in_array($p_casa, [0, 1, 2]))) {
            $placar_valido = true;
        }

        if (!$placar_valido) {
            $mensagem_erro = "Erro: Placar inválido para o voleibol! Sets aceitos: 3x0, 3x1, 3x2, 0x3, 1x3 ou 2x3.";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO partidas (id_casa, id_fora, pontos_casa, pontos_fora, genero, fase) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$id_casa, $id_fora, $p_casa, $p_fora, $genero, $fase]);
                header("Location: cadastro.php?sucesso=partida");
                exit;
            } catch (PDOException $e) { $mensagem_erro = "Erro: " . $e->getMessage(); }
        }
    }
}

// EXCLUSÕES
if (isset($_GET['excluir_pais'])) {
    $id = $_GET['excluir_pais'];
    try {
        $pdo->prepare("DELETE FROM photos WHERE id_pais = ?")->execute([$id]); // Opcional se houver fotos vinculadas
        $pdo->prepare("DELETE FROM paises WHERE id = ?")->execute([$id]);
        header("Location: cadastro.php?sucesso=del_pais");
        exit;
    } catch (PDOException $e) { 
        $mensagem_erro = "Erro: Este país possui partidas vinculadas e não pode ser deletado!"; 
    }
}

if (isset($_GET['excluir_partida'])) {
    $id = $_GET['excluir_partida'];
    $pdo->prepare("DELETE FROM partidas WHERE id = ?")->execute([$id]);
    header("Location: cadastro.php?sucesso=del_partida");
    exit;
}

if (isset($_GET['sucesso'])) {
    if ($_GET['sucesso'] == 'pais') $mensagem_sucesso = "Operação com país realizada com sucesso!";
    if ($_GET['sucesso'] == 'partida') $mensagem_sucesso = "Partida registrada com sucesso!";
    if ($_GET['sucesso'] == 'del_pais') $mensagem_sucesso = "País removido com sucesso!";
    if ($_GET['sucesso'] == 'del_partida') $mensagem_sucesso = "Partida excluída com sucesso!";
}

$paises_select = $pdo->query("SELECT * FROM paises ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC); 
$partidas = $pdo->query("SELECT p.*, t1.nome AS casa, t2.nome AS fora FROM partidas p JOIN paises t1 ON p.id_casa = t1.id JOIN paises t2 ON p.id_fora = t2.id ORDER BY p.id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciador VNL</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f4f9; color: #333; }
        .box { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); max-width: 700px; margin: 0 auto 20px auto; }
        label { display: block; margin-top: 15px; font-weight: bold; }
        input, select { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { background: #0056b3; color: white; border: none; padding: 12px 20px; margin-top: 20px; border-radius: 4px; cursor: pointer; font-weight: bold; width: 100%; }
        .voltar { display: block; text-align: center; margin-bottom: 25px; color: #0056b3; text-decoration: none; font-weight: bold; }
        h2 { margin-top: 0; color: #0056b3; border-bottom: 2px solid #0056b3; padding-bottom: 8px; }
        .radio-group { margin-top: 8px; display: flex; gap: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #f2f2f2; }
        .btn-edit { color: #0056b3; text-decoration: none; font-weight: bold; margin-right: 12px; }
        .btn-del { color: #e63946; text-decoration: none; font-weight: bold; }
        .barra-pesquisa { background: #fff; border: 2px solid #0056b3; padding: 8px; margin-top: 10px; border-radius: 4px; width: 100%; box-sizing: border-box; }
        .flag { width: 24px; height: 16px; margin-right: 8px; vertical-align: middle; border: 1px solid #ddd; object-fit: cover; }
    </style>
</head>
<body>

    <a href="index.php" class="voltar">← Ver Tabela de Classificação Geral</a>

    <?php if (!empty($mensagem_sucesso)): ?><div style="background-color: #d4edda; color: #155724; padding: 15px; margin: 20px auto; max-width: 700px; border-radius: 4px; font-weight: bold; text-align: center;"><?= $mensagem_sucesso ?></div><?php endif; ?>
    <?php if (!empty($mensagem_erro)): ?><div style="background-color: #f8d7da; color: #721c24; padding: 15px; margin: 20px auto; max-width: 700px; border-radius: 4px; font-weight: bold; text-align: center;"><?= $mensagem_erro ?></div><?php endif; ?>

    <div class="box">
        <h2>Cadastrar Novo País</h2>
        <form method="POST" action="cadastro.php">
            <label>Nome do País:</label>
            <input type="text" id="nome_pais" name="nome_pais" required placeholder="Ex: Brasil, Japão, Estados Unidos" oninput="autodetectarSigla()">
            
            <label>Sigla do País (Preenchida Automaticamente):</label>
            <input type="text" id="sigla_pais" name="sigla_pais" required maxlength="2" placeholder="Ex: BR">
            
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
            
            <label>Fase do Torneio:</label>
            <select name="fase" required>
                <option value="Fase de Grupos">Fase de Grupos</option>
                <option value="Quartas de Final">Quartas de Final</option>
                <option value="Semifinal">Semifinal</option>
                <option value="Final">Final</option>
            </select>

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
            
            <label>Sets Casa:</label> <input type="number" id="pontos_casa" name="pontos_casa" min="0" max="3" required>
            <label>Sets Visita:</label> <input type="number" id="pontos_fora" name="pontos_fora" min="0" max="3" required>
            <button type="submit" name="cadastrar_partida">Registrar Partida</button>
        </form>
    </div>

    <div class="box">
        <h2>Gerenciar Países Cadastrados</h2>
        <input type="text" id="buscaPaises" class="barra-pesquisa" onkeyup="filtrarPaises()" placeholder="🔎 Buscar país...">
        <table id="tabelaPaises">
            <thead><tr><th>Bandeira</th><th>País</th><th>Sigla</th><th>Ações</th></tr></thead>
            <tbody>
                <?php foreach($paises_select as $p): ?>
                <tr>
                    <td><img class="flag" src="https://flagcdn.com/w40/<?=strtolower($p['sigla'])?>.png" alt="Bandeira"></td>
                    <td class="nome-alvo" style="font-weight: bold;"><?=$p['nome']?></td>
                    <td><?=strtoupper($p['sigla'])?></td>
                    <td>
                        <a href="editar_pais.php?id=<?=$p['id']?>" class="btn-edit">Editar</a>
                        <a href="cadastro.php?excluir_pais=<?=$p['id']?>" class="btn-del" onclick="return confirm('Confirmar exclusão?')">Excluir</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
        // Captura os seletores de times
const selectCasa = document.getElementsByName('id_casa')[0];
const selectFora = document.getElementsByName('id_fora')[0];

function ajustarOpcoesConfronto() {
    const valorSelecionadoCasa = selectCasa.value;
    const valorSelecionadoFora = selectFora.value;

    // Restaura a visibilidade de todas as opções do time de fora
    Array.from(selectFora.options).forEach(opcao => {
        if (opcao.value === valorSelecionadoCasa && opcao.value !== "") {
            opcao.style.display = "none"; // Esconde o time selecionado na casa
        } else {
            opcao.style.display = "block";
        }
    });

    // Restaura a visibilidade de todas as opções do time de casa
    Array.from(selectCasa.options).forEach(opcao => {
        if (opcao.value === valorSelecionadoFora && opcao.value !== "") {
            opcao.style.display = "none"; // Esconde o time selecionado fora
        } else {
            opcao.style.display = "block";
        }
    });
}

// Vincula o evento de mudança aos seletores
selectCasa.addEventListener('change', ajustarOpcoesConfronto);
selectFora.addEventListener('change', ajustarOpcoesConfronto);
        
    // Banco de dados em JS para autodetectar as principais siglas da VNL de forma instantânea
    const dicionarioPaises = {
        "brasil": "BR", "brazil": "BR",
        "italia": "IT", "itália": "IT", "italy": "IT",
        "japao": "JP", "japão": "JP", "japan": "JP",
        "estados unidos": "US", "usa": "US", "eua": "US",
        "franca": "FR", "frança": "FR", "france": "FR",
        "polonia": "PL", "polônia": "PL", "poland": "PL",
        "servia": "RS", "sérvia": "RS", "serbia": "RS",
        "turquia": "TR", "turkey": "TR",
        "china": "CN",
        "alemanha": "DE", "germany": "DE",
        "argentina": "AR",
        "canada": "CA", "canadá": "CA",
        "holanda": "NL", "netherlands": "NL",
        "eslovenia": "SI", "eslovênia": "SI", "slovenia": "SI",
        "iran": "IR", "irã": "IR",
        "cuba": "CU",
        "bulgaria": "BG", "bulgária": "BG",
        "republica dominicana": "DO", "rep dominicana": "DO", "dominican republic": "DO",
        "tailandia": "TH", "tailândia": "TH", "thailand": "TH",
        "coreia": "KR", "coreia do sul": "KR", "korea": "KR"
    };

    function autodetectarSigla() {
        const nomeDigitado = document.getElementById('nome_pais').value.toLowerCase().trim();
        const campoSigla = document.getElementById('sigla_pais');
        
        // Remove acentos comuns para melhorar a busca
        const nomeLimpo = nomeDigitado.normalize("NFD").replace(/[\u0300-\u036f]/g, "");

        if (dicionarioPaises[nomeDigitado]) {
            campoSigla.value = dicionarioPaises[nomeDigitado];
        } else if (dicionarioPaises[nomeLimpo]) {
            campoSigla.value = dicionarioPaises[nomeLimpo];
        }
    }

    // Travas do placar do vôlei
    const campoCasa = document.getElementById('pontos_casa');
    const campoFora = document.getElementById('pontos_fora');
    campoCasa.addEventListener('input', function() {
        let v = parseInt(campoCasa.value);
        if (v === 3) campoFora.max = 2;
        if (v < 3 && v >= 0) campoFora.value = 3;
    });
    campoFora.addEventListener('input', function() {
        let v = parseInt(campoFora.value);
        if (v === 3) campoCasa.max = 2;
        if (v < 3 && v >= 0) campoCasa.value = 3;
    });

    function filtrarPaises() {
        var input = document.getElementById("buscaPaises");
        var filter = input.value.toUpperCase();
        var table = document.getElementById("tabelaPaises");
        var tr = table.getElementsByTagName("tr");
        for (var i = 1; i < tr.length; i++) {
            var td = tr[i].getElementsByClassName("nome-alvo")[0];
            if (td) {
                var txtValue = td.textContent || td.innerText;
                tr[i].style.display = (txtValue.toUpperCase().indexOf(filter) > -1) ? "" : "none";
            }
        }
    }
    </script>
</body>
</html>
