<?php
session_start();
include 'conexao.php';
/** @var PDO $pdo */

if (!isset($_SESSION['logado']) || $_SESSION['usuario_nivel'] !== 'admin') {
    header("Location: login.php");
    exit;
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$mensagem_sucesso = "";
$mensagem_erro = "";

if (isset($_POST['cadastrar_pais'])) {
    $nome = trim($_POST['nome_pais']);
    $sigla = strtoupper(trim($_POST['sigla_pais']));

    if (!empty($nome) && strlen($sigla) === 2) {
        try {
            $stmt = $pdo->prepare("INSERT INTO paises (nome, sigla) VALUES (?, ?)");
            $stmt->execute([$nome, $sigla]);
            header("Location: cadastro.php?sucesso=pais");
            exit;
        } catch (PDOException $e) {
            $mensagem_erro = "Erro ao cadastrar país: " . $e->getMessage();
        }
    } else {
        $mensagem_erro = "Preencha o nome e a sigla corretamente (2 letras).";
    }
}

if (isset($_POST['cadastrar_partida'])) {
    $id_casa = $_POST['id_casa'];
    $id_fora = $_POST['id_fora'];
    $p_casa = intval($_POST['pontos_casa']);
    $p_fora = intval($_POST['pontos_fora']);
    $genero = $_POST['genero'];
    $fase = $_POST['fase'];
    $data_partida = !empty($_POST['data_partida']) ? $_POST['data_partida'] : date('Y-m-d');

    $placar_valido = false;
    if (($p_casa == 3 && ($p_fora >= 0 && $p_fora <= 2)) || ($p_fora == 3 && ($p_casa >= 0 && $p_casa <= 2))) {
        $placar_valido = true;
    }

    if ($id_casa == $id_fora) {
        $mensagem_erro = "Um país não pode jogar contra ele mesmo.";
    } elseif (!$placar_valido) {
        $mensagem_erro = "Placar inválido para o vôlei! Um dos times deve fazer exatamente 3 sets, e o outro no máximo 2.";
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO partidas (id_casa, id_fora, pontos_casa, pontos_fora, genero, fase, data_partida) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$id_casa, $id_fora, $p_casa, $p_fora, $genero, $fase, $data_partida]);
            $id_partida_criada = $pdo->lastInsertId();

            if (isset($_POST['pontos_set_casa'])) {
                foreach ($_POST['pontos_set_casa'] as $index => $pts_c) {
                    $pts_f = $_POST['pontos_set_fora'][$index];
                    $numero_set = $index + 1;

                    if (($pts_c !== '' && $pts_f !== '') && ($pts_c > 0 || $pts_f > 0)) {
                        $stmt_set = $pdo->prepare("INSERT INTO detalhes_sets (id_partida, numero_set, pontos_casa, pontos_fora) VALUES (?, ?, ?, ?)");
                        $stmt_set->execute([$id_partida_criada, $numero_set, intval($pts_c), intval($pts_f)]);
                    }
                }
            }

            $pdo->commit();
            header("Location: cadastro.php?sucesso=partida");
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $mensagem_erro = "Erro ao cadastrar partida: " . $e->getMessage();
        }
    }
}

if (isset($_GET['excluir_pais'])) {
    $id = $_GET['excluir_pais'];
    $pdo->prepare("DELETE FROM paises WHERE id = ?")->execute([$id]);
    header("Location: cadastro.php?sucesso=deletado");
    exit;
}
if (isset($_GET['excluir_partida'])) {
    $id = $_GET['excluir_partida'];
    $pdo->prepare("DELETE FROM partidas WHERE id = ?")->execute([$id]);
    header("Location: cadastro.php?sucesso=deletado");
    exit;
}

if (isset($_GET['sucesso'])) {
    if ($_GET['sucesso'] == 'pais') $mensagem_sucesso = "País cadastrado com sucesso!";
    if ($_GET['sucesso'] == 'partida') $mensagem_sucesso = "Resultado lançado com sucesso!";
    if ($_GET['sucesso'] == 'deletado') $mensagem_sucesso = "Registro excluído com sucesso!";
}

$paises = $pdo->query("SELECT * FROM paises ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
$partidas = $pdo->query("SELECT p.*, t1.nome AS casa_nome, t2.nome AS fora_nome FROM partidas p JOIN paises t1 ON p.id_casa = t1.id JOIN paises t2 ON p.id_fora = t2.id ORDER BY p.data_partida DESC, p.id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - VNL</title>
    <style>
        /* SINCRO DE CORES COM A INDEX */
        :root {
            --bg-body: #0b132b; 
            --txt-main: #f1f5f9; 
            --txt-heading: #48cae4;
            --bg-card: #1c2541; 
            --border-line: #3a506b;
            --btn-bg: #48cae4; 
            --btn-txt: #0b132b;
            --accent-blue: #00b4d8;
            --input-bg: #0b132b;
        }
        [data-theme="light"] {
            --bg-body: #e0f2fe; 
            --txt-main: #0f172a; 
            --txt-heading: #0369a1;
            --bg-card: #ffffff; 
            --border-line: #bae6fd;
            --btn-bg: #0284c7; 
            --btn-txt: #ffffff;
            --accent-blue: #0284c7;
            --input-bg: #f8fafc;
        }

        body, .box, table, tr, td, th, input, select {
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }

        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: var(--bg-body); color: var(--txt-main); }
        h1, h2 { text-align: center; color: var(--txt-heading); }
        
        .header-top { display: flex; justify-content: space-between; align-items: center; max-width: 980px; margin: 0 auto 20px auto; }
        .theme-toggle { background: var(--btn-bg); color: var(--btn-txt); border: none; padding: 8px 18px; font-weight: bold; border-radius: 20px; cursor: pointer; font-size: 13px; }

        .container { display: flex; gap: 20px; justify-content: center; flex-wrap: wrap; margin-bottom: 30px; align-items: flex-start; }
        .box { background: var(--bg-card); padding: 20px; border-radius: 8px; width: 100%; max-width: 480px; border: 1px solid var(--border-line); box-sizing: border-box; }
        
        label { display: block; margin-top: 12px; font-weight: bold; font-size: 14px; color: var(--txt-main); }
        input, select { width: 100%; padding: 8px; margin: 4px 0 12px 0; box-sizing: border-box; border: 1px solid var(--border-line); background: var(--input-bg); color: var(--txt-main); border-radius: 4px; font-weight: bold; }
        
        button { background: var(--accent-blue); color: white; border: none; padding: 12px; width: 100%; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 15px; margin-top: 10px; }
        button:hover { filter: brightness(1.1); }
        
        .alert-success { background: #10b981; color: white; padding: 10px; border-radius: 4px; margin-bottom: 15px; text-align: center; font-weight: bold; }
        .alert-error { background: #ef4444; color: white; padding: 10px; border-radius: 4px; margin-bottom: 15px; text-align: center; font-weight: bold; }
        
        .table-responsive { border-radius: 8px; border: 1px solid var(--border-line); overflow: hidden; margin-top: 10px; }
        table { width: 100%; border-collapse: collapse; background: var(--bg-card); }
        th, td { padding: 10px; text-align: center; border-bottom: 1px solid var(--border-line); font-size: 14px; }
        th { background: var(--border-line); color: var(--txt-main); }
        
        .btn-action { text-decoration: none; padding: 4px 10px; border-radius: 3px; font-size: 12px; font-weight: bold; margin: 0 2px; display: inline-block; }
        .btn-edit { background: #ffb703; color: #000; }
        .btn-del { background: #ef4444; color: #fff; }
        .btn-voltar { display: block; width: 200px; margin: 20px auto; text-align: center; background: var(--border-line); color: var(--txt-main); padding: 10px; border-radius: 4px; text-decoration: none; font-weight: bold; border: 1px solid var(--border-line); }
        
        .grid-sets { display: flex; flex-direction: column; gap: 8px; background: var(--bg-body); padding: 12px; border-radius: 6px; border: 1px dashed var(--border-line); margin-bottom: 12px; }
        .linha-set { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
        .linha-set span { font-weight: bold; font-size: 13px; min-width: 45px; }
        .linha-set input { margin: 0; text-align: center; padding: 6px; }
    </style>
</head>
<body>

    <div class="header-top">
        <a href="index.php" style="text-decoration: none; font-weight: bold; color: var(--accent-blue);">← Voltar</a>
        <button class="theme-toggle" onclick="toggleTheme()" id="btnTema">☀️ Modo Claro</button>
    </div>

    <h1>⚙️ Painel de Controle (Modo Administrador)</h1>

    <div style="max-width: 980px; margin: 0 auto;">
        <?php if(!empty($mensagem_sucesso)): ?><div class="alert-success"><?=$mensagem_sucesso?></div><?php endif; ?>
        <?php if(!empty($mensagem_erro)): ?><div class="alert-error"><?=$mensagem_erro?></div><?php endif; ?>
    </div>

    <div class="container">
        <div class="box">
            <h2>Adicionar Novo País</h2>
            <form method="POST">
                <label>Nome do País:</label>
                <input type="text" id="nome_pais" name="nome_pais" placeholder="Ex: Brasil" required oninput="autodetectarSigla()">
                
                <label>Sigla da Bandeira (2 letras):</label>
                <input type="text" id="sigla_pais" name="sigla_pais" placeholder="Ex: BR" maxlength="2" required oninput="atualizarPreview()">
                
                <div style="text-align: center; margin: 5px 0 15px 0;">
                    <img id="img_preview" src="https://flagcdn.com/w80/br.png" style="width:50px; height:33px; border:1px solid var(--border-line); object-fit:cover; border-radius:3px;">
                </div>

                <button type="submit" name="cadastrar_pais">Salvar País</button>
            </form>
        </div>

        <div class="box">
            <h2>Lançar Resultado de Partida</h2>
            <form method="POST">
                <label>Categoria/Gênero:</label>
                <select name="genero" required>
                    <option value="F">Feminino (F)</option>
                    <option value="M">Masculino (M)</option>
                </select>

                <label>Fase do Campeonato:</label>
                <select name="fase" required>
                    <option value="Fase de Grupos">Fase de Grupos</option>
                    <option value="Quartas de Final">Quartas de Final</option>
                    <option value="Semifinal">Semifinal</option>
                    <option value="Final">Final</option>
                </select>

                <label>Data do Confronto:</label>
                <input type="date" name="data_partida" required value="<?=date('Y-m-d')?>">

                <div style="display: flex; gap: 15px;">
                    <div style="flex: 1;">
                        <label>Time da Casa:</label>
                        <select name="id_casa" required>
                            <option value="">-- Selecione --</option>
                            <?php foreach($paises as $p): ?><option value="<?=$p['id']?>"><?=$p['nome']?></option><?php endforeach; ?>
                        </select>
                        <label>Placar (Sets):</label>
                        <input type="number" name="pontos_casa" min="0" max="3" value="0" required>
                    </div>
                    
                    <div style="flex: 1;">
                        <label>Time Visitante:</label>
                        <select name="id_fora" required>
                            <option value="">-- Selecione --</option>
                            <?php foreach($paises as $p): ?><option value="<?=$p['id']?>"><?=$p['nome']?></option><?php endforeach; ?>
                        </select>
                        <label>Placar (Sets):</label>
                        <input type="number" name="pontos_fora" min="0" max="3" value="0" required>
                    </div>
                </div>

                <label>Pontuação por Set (Gráfico):</label>
                <div class="grid-sets">
                    <?php for($i = 0; $i < 5; $i++): ?>
                        <div class="linha-set">
                            <span>Set <?=$i+1?>:</span>
                            <input type="number" name="pontos_set_casa[<?=$i?>]" placeholder="Casa" min="0">
                            x
                            <input type="number" name="pontos_set_fora[<?=$i?>]" placeholder="Fora" min="0">
                        </div>
                    <?php endfor; ?>
                </div>

                <button type="submit" name="cadastrar_partida">Registrar Partida Completa</button>
            </form>
        </div>
    </div>

    <div style="max-width: 980px; margin: 30px auto;">
        <h2>Países Cadastrados</h2>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr><th>ID</th><th>Bandeira</th><th>Nome</th><th>Sigla</th><th>Ações</th></tr>
                </thead>
                <tbody>
                    <?php foreach($paises as $p): ?>
                    <tr>
                        <td><?=$p['id']?></td>
                        <td><img src="https://flagcdn.com/w40/<?=strtolower($p['sigla'])?>.png" style="width:24px; border-radius:2px;"></td>
                        <td style="text-align:left; font-weight:bold;"><?=$p['nome']?></td>
                        <td><?=$p['sigla']?></td>
                        <td>
                            <a href="editar_pais.php?id=<?=$p['id']?>" class="btn-action btn-edit">Editar</a>
                            <a href="cadastro.php?excluir_pais=<?=$p['id']?>" class="btn-action btn-del" onclick="return confirm('Deseja apagar este país?')">Excluir</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <h2 style="margin-top: 40px;">Partidas Lançadas</h2>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr><th>Data</th><th>Fase</th><th>Gênero</th><th>Confronto</th><th>Placar Geral</th><th>Ações</th></tr>
                </thead>
                <tbody>
                    <?php foreach($partidas as $part): ?>
                    <tr>
                        <td><?=date('d/m/Y', strtotime($part['data_partida']))?></td>
                        <td><?=$part['fase']?></td>
                        <td><strong><?=$part['genero']=='F'?'Feminino':'Masculino'?></strong></td>
                        <td style="text-align:right; font-weight:bold;"><?=$part['casa_nome']?> vs <?=$part['fora_nome']?></td>
                        <td><span style="background:var(--accent-blue); color:#fff; padding:3px 8px; border-radius:12px; font-weight:bold;"><?=$part['pontos_casa']?> x <?=$part['pontos_fora']?></span></td>
                        <td>
                            <a href="editar_partida.php?id=<?=$part['id']?>" class="btn-action btn-edit">Editar</a>
                            <a href="cadastro.php?excluir_partida=<?=$part['id']?>" class="btn-action btn-del" onclick="return confirm('Deseja apagar esta partida?')">Excluir</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
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
    const savedTheme = localStorage.getItem('vnl-theme') || 'dark';
    applyTheme(savedTheme);

    // Scripts de detecção de país originais mantidos abaixo...
    const dicionarioPaises = { "brasil": "BR", "italia": "IT", "japao": "JP", "estados unidos": "US", "franca": "FR", "polonia": "PL", "servia": "RS", "turquia": "TR", "china": "CN", "alemanha": "DE", "canada": "CA", "holanda": "NL" };
    function autodetectarSigla() {
        const nomeDigitado = document.getElementById('nome_pais').value.toLowerCase().trim();
        const campoSigla = document.getElementById('sigla_pais');
        if (dicionarioPaises[nomeDigitado]) { campoSigla.value = dicionarioPaises[nomeDigitado]; }
        atualizarPreview();
    }
    function atualizarPreview() {
        const sigla = document.getElementById('sigla_pais').value.toLowerCase().trim();
        const img = document.getElementById('img_preview');
        if (sigla.length === 2) { img.src = "https://flagcdn.com/w80/" + sigla + ".png"; }
    }
    </script>
</body>
</html>
