<?php
require_once 'conexao.php';

$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];

    if (!empty($nome) && !empty($email) && !empty($senha)) {
        // Criptografar a senha por segurança
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, nivel_acesso) VALUES (?, ?, ?, 'usuario')");
            $stmt->execute([$nome, $email, $senha_hash]);
            header("Location: login.php?sucesso=cadastrado");
            exit;
        } catch (PDOException $e) {
            $mensagem = "Este e-mail já está cadastrado!";
        }
    } else {
        $mensagem = "Preencha todos os campos!";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cadastro - Pobreflix</title>
    <style>
        body { background-color: #060913; color: white; font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .box { background-color: #0d1222; padding: 40px; border-radius: 8px; width: 100%; max-width: 360px; box-shadow: 0 4px 15px rgba(0,0,0,0.5); }
        h2 { margin-top: 0; text-align: center; color: #0084ff; }
        input, select { width: 100%; padding: 12px; margin: 10px 0; border: none; border-radius: 4px; background: #161f38; color: white; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background-color: #0084ff; color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; }
        button:hover { opacity: 0.9; }
        .erro { color: #ff4d4d; font-size: 14px; text-align: center; margin-bottom: 10px; }
        a { color: #0084ff; text-decoration: none; display: block; text-align: center; margin-top: 15px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="box">
        <h2>Criar Conta</h2>
        <?php if($mensagem): ?> <div class="erro"><?= $mensagem ?></div> <?php endif; ?>
        <form method="POST">
            <input type="text" name="nome" placeholder="Nome Completo" required>
            <input type="email" name="email" placeholder="E-mail" required>
            <input type="password" name="senha" placeholder="Senha" required>
            <button type="submit">Cadastrar</button>
        </form>
        <a href="login.php">Já tem uma conta? Entrar</a>
    </div>
</body>
</html>
