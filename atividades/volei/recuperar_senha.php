<?php
session_start();
include 'conexao.php';
/** @var PDO $pdo */

$mensagem_erro = "";
$mensagem_sucesso = "";

if (isset($_POST['alterar_senha'])) {
    $usuario = trim($_POST['usuario']);
    $nova_senha = trim($_POST['nova_senha']);
    $confirma_senha = trim($_POST['confirma_senha']);

    if (!empty($usuario) && !empty($nova_senha) && !empty($confirma_senha)) {
        if ($nova_senha !== $confirma_senha) {
            $mensagem_erro = "As senhas digitadas não coincidem.";
        } else {
            // Verifica se o usuário de fato existe no banco
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE login = ?");
            $stmt->execute([$usuario]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Criptografa a nova senha antes de salvar
                $novaSenhaHash = password_hash($nova_senha, PASSWORD_DEFAULT);

                $stmt_update = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
                $stmt_update->execute([$novaSenhaHash, $user['id']]);
                
                $mensagem_sucesso = "Senha alterada com sucesso! Redirecionando...";
                header("Refresh: 2; url=login.php");
            } else {
                $mensagem_erro = "Usuário não encontrado no sistema.";
            }
        }
    } else {
        $mensagem_erro = "Preencha todos os campos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - VNL</title>
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
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: var(--bg-body); color: var(--txt-main); display: flex; flex-direction: column; justify-content: center; align-items: center; min-height: 100vh; }
        
        .header-top { position: absolute; top: 20px; right: 20px; }
        .theme-toggle { background: var(--btn-bg); color: var(--btn-txt); border: none; padding: 8px 18px; font-weight: bold; border-radius: 20px; cursor: pointer; font-size: 13px; }

        .box { background: var(--bg-card); padding: 30px; border-radius: 8px; width: 100%; max-width: 380px; border: 1px solid var(--border-line); box-sizing: border-box; box-shadow: 0 4px 15px rgba(0,0,0,0.15); }
        h2 { text-align: center; margin-top: 0; color: var(--txt-heading); font-size: 22px; }
        
        label { display: block; margin-top: 15px; font-weight: bold; font-size: 14px; }
        input { width: 100%; padding: 12px; margin: 6px 0 16px 0; box-sizing: border-box; border: 1px solid var(--border-line); background: var(--input-bg); color: var(--txt-main); border-radius: 4px; font-weight: bold; }
        
        button { background: #ffb703; color: #000; border: none; padding: 12px; width: 100%; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 16px; margin-top: 10px; }
        button:hover { filter: brightness(1.1); }
        
        .alert-error { background: #ef4444; color: white; padding: 10px; border-radius: 4px; margin-bottom: 15px; text-align: center; font-weight: bold; font-size: 14px; }
        .alert-success { background: #10b981; color: white; padding: 10px; border-radius: 4px; margin-bottom: 15px; text-align: center; font-weight: bold; font-size: 14px; }
        
        .voltar-link { display: block; text-align: center; margin-top: 15px; font-size: 13px; color: var(--accent-blue); text-decoration: none; font-weight: bold; }
        .voltar-link:hover { text-decoration: underline; }
    </style>
</head>
<body>

    <div class="header-top">
        <button class="theme-toggle" onclick="toggleTheme()" id="btnTema">☀️ Modo Claro</button>
    </div>

    <div class="box">
        <h2>Alterar Senha</h2>
        
        <?php if(!empty($mensagem_erro)): ?><div class="alert-error"><?=$mensagem_erro?></div><?php endif; ?>
        <?php if(!empty($mensagem_sucesso)): ?><div class="alert-success"><?=$mensagem_sucesso?></div><?php endif; ?>

        <form method="POST">
            <label>Confirmar seu Usuário:</label>
            <input type="text" name="usuario" placeholder="Seu usuário cadastrado" required>
            
            <label>Nova Senha:</label>
            <input type="password" name="nova_senha" placeholder="Nova senha" required>

            <label>Confirmar Nova Senha:</label>
            <input type="password" name="confirma_senha" placeholder="Repita a nova senha" required>

            <button type="submit" name="alterar_senha">Atualizar Senha</button>
            
            <a href="login.php" class="voltar-link">← Voltar para o Login</a>
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
    </script>
</body>
</html>
