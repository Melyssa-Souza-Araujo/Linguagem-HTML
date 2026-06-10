<?php
// index.php
require_once 'db.php';

$erro = '';

// Lógica de Cadastro
if (isset($_POST['action']) && $_POST['action'] == 'register') {
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $senha = password_hash($_POST['senha'], PASSWORD_BCRYPT);
    $tipo = $_POST['tipo']; 

    if ($email && $nome && ($tipo == 'mestre' || $tipo == 'jogador')) {
        try {
            $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nome, $email, $senha, $tipo]);
            echo "<script>alert('Cadastro realizado! Faça login.');</script>";
        } catch (PDOException $e) {
            $erro = "E-mail já cadastrado.";
        }
    } else {
        $erro = "Dados inválidos preenchidos.";
    }
}

// Lógica de Login
if (isset($_POST['action']) && $_POST['action'] == 'login') {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $senha = $_POST['senha'];

    if ($email) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($senha, $user['senha'])) {
                // Guarda as informações do usuário na Sessão do servidor
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_nome'] = $user['nome'];
                $_SESSION['user_tipo'] = $user['tipo'];

                // CONECTADO: Envia o usuário direto para a ficha dele
                header("Location: ficha.php");
                exit;
            } else {
                $erro = "E-mail ou senha incorretos.";
            }
        } catch (PDOException $e) {
            $erro = "Erro no banco de dados. Tente novamente mais tarde.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>RPG Hub - Entrar na Taverna</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container" style="max-width: 500px; margin-top: 50px;">
    <h1 style="text-align: center; margin-bottom: 20px; color: var(--accent);">🎲 RPG HUB</h1>
    
    <?php if($erro): ?>
        <div class="card" style="border-color: var(--danger); color: var(--danger);"><?= $erro ?></div>
    <?php endif; ?>

    <!-- Formulário de Login -->
    <div class="card">
        <h2>Entrar</h2>
        <form method="POST">
            <input type="hidden" name="action" value="login">
            <div class="form-group">
                <label>E-mail</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Senha</label>
                <input type="password" name="senha" required>
            </div>
            <button type="submit" class="btn">Acessar Painel</button>
        </form>
    </div>

    <!-- Formulário de Cadastro -->
    <div class="card">
        <h2>Criar Nova Conta</h2>
        <form method="POST">
            <input type="hidden" name="action" value="register">
            <div class="form-group">
                <label>Nome do Aventureiro</label>
                <input type="text" name="nome" required>
            </div>
            <div class="form-group">
                <label>E-mail</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Senha</label>
                <input type="password" name="senha" required>
            </div>
            <div class="form-group">
                <label>Função Principal</label>
                <select name="tipo" required>
                    <option value="jogador">Jogador (Cria personagens, entra em mesas)</option>
                    <option value="mestre">Mestre (Cria e comanda mesas, gerencia PDFs)</option>
                </select>
            </div>
            <button type="submit" class="btn" style="background-color: var(--primary);">Registrar</button>
        </form>
    </div>
</div>
</body>
</html>
