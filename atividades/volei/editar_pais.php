<?php
session_start();
include 'conexao.php';
/** @var PDO $pdo */

if (!isset($_SESSION['logado']) || $_SESSION['usuario_nivel'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$mensagem_erro = "";

if (!isset($_GET['id'])) {
    header("Location: cadastro.php");
    exit;
}

$id = intval($_GET['id']);

if (isset($_POST['editar_pais'])) {
    $nome = trim($_POST['nome_pais']);
    $sigla = strtoupper(trim($_POST['sigla_pais']));

    if (!empty($nome) && strlen($sigla) === 2) {
        try {
            $stmt = $pdo->prepare("UPDATE paises SET nome = ?, sigla = ? WHERE id = ?");
            $stmt->execute([$nome, $sigla, $id]);
            header("Location: cadastro.php?sucesso=pais");
            exit;
        } catch (PDOException $e) {
            $mensagem_erro = "Erro ao atualizar país: " . $e->getMessage();
        }
    } else {
        $mensagem_erro = "Preencha o nome e a sigla corretamente (2 letras).";
    }
}

$stmt = $pdo->prepare("SELECT * FROM paises WHERE id = ?");
$stmt->execute([$id]);
$pais = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pais) {
    header("Location: Dog.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar País - VNL</title>
    <style>
        :root {
            --bg-body: #0b132b; --txt-main: #f1f5f9; --txt-heading: #48cae4;
            --bg-card: #1c2541; --border-line: #3a506b; --btn-bg: #48cae4; 
            --btn-txt: #0b132b; --accent-blue: #00b4d8; --input-bg: #0b132b;
        }
        [data-theme="light"] {
            --bg-body: #e0f2fe; --txt-main: #0f172a; --txt-heading: #0369a1;
            --bg-card: #ffffff; --border-line: #bae6fd; --btn-bg: #0284c7; 
            --btn-txt: #ffffff; --accent-blue: #0284c7; --input-bg: #f8fafc;
        }

        body, .box, input { transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease; }
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: var(--bg-body); color: var(--txt-main); }
        h1, h2 { text-align: center; color: var(--txt-heading); }
        
        .header-top { display: flex; justify-content: space-between; align-items: center; max-width: 500px; margin: 0 auto 20px auto; }
        .theme-toggle { background: var(--btn-bg); color: var(--btn-txt); border: none; padding: 8px 18px; font-weight: bold; border-radius: 20px; cursor: pointer; font-size: 13px; }

        .box { background: var(--bg-card); padding: 25px; border-radius: 8px; width: 100%; max-width: 500px; border: 1px solid var(--border-line); box-sizing: border-box; margin: 20px auto; }
        label { display: block; margin-top: 12px; font-weight: bold; font-size: 14px; }
        input { width: 100%; padding: 10px; margin: 4px 0 12px 0; box-sizing: border-box; border: 1px solid var(--border-line); background: var(--input-bg); color: var(--txt-main); border-radius: 4px; font-weight: bold; }
        
        button { background: #ffb703; color: #000; border: none; padding: 12px; width: 100%; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 15px; margin-top: 15px; }
        button:hover { filter: brightness(1.1); }
        .alert-error { background: #ef4444; color: white; padding: 10px; border-radius: 4px; margin-bottom: 15px; text-align: center; font-weight: bold; }
        .btn-voltar { display: inline-block; text-decoration: none; font-weight: bold; color: var(--accent-blue); }
    </style>
</head>
<body>

    <div class="header-top">
        <a href="cadastro.php" class="btn-voltar">← Voltar</a>
        <button class="theme-toggle" onclick="toggleTheme()" id="btnTema">☀️ Modo Claro</button>
    </div>

    <h1>✏️ Editar País</h1>

    <div class="box">
        <?php if(!empty($mensagem_erro)): ?><div class="alert-error"><?=$mensagem_erro?></div><?php endif; ?>

        <form method="POST">
            <label>Nome do País:</label>
            <input type="text" id="nome_pais" name="nome_pais" value="<?=htmlspecialchars($pais['nome'])?>" required>
            
            <label>Sigla da Bandeira (2 letras):</label>
            <input type="text" id="sigla_pais" name="sigla_pais" value="<?=htmlspecialchars($pais['sigla'])?>" maxlength="2" required oninput="atualizarPreview()">
            
            <div style="text-align: center; margin: 15px 0;">
                <img id="img_preview" src="https://flagcdn.com/w80/<?=strtolower($pais['sigla'])?>.png" style="width:60px; height:40px; border:1px solid var(--border-line); object-fit:cover; border-radius:3px;">
            </div>

            <button type="submit" name="editar_pais">Salvar Alterações</button>
        </form>
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
    applyTheme(localStorage.getItem('vnl-theme') || 'dark');

    function atualizarPreview() {
        const sigla = document.getElementById('sigla_pais').value.toLowerCase().trim();
        const img = document.getElementById('img_preview');
        if (sigla.length === 2) { img.src = "https://flagcdn.com/w80/" + sigla + ".png"; }
    }
    </script>
</body>
</html>
