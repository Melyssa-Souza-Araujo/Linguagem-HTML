<?php
include 'conexao.php';
/** @var PDO $pdo */

$mensagem = "";

if (isset($_POST['registrar'])) {
    $login = trim($_POST['login']);
    $senha = trim($_POST['senha']);

    if (!empty($login) && !empty($senha)) {
        // Criptografa a senha com segurança profissional
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("INSERT INTO usuarios (login, senha, nivel) VALUES (?, ?, 'usuario')");
            $stmt->execute([$login, $senhaHash]);
            $mensagem = "<p style='color:#2a9d8f; font-weight:bold;'>Conta criada com sucesso! <a href='login.php' style='color:#fff;'>Ir para Login</a></p>";
        } catch (PDOException $e) {
            $mensagem = "<p style='color:#e63946; font-weight:bold;'>Erro: Este usuário já existe!</p>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - VNL</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #0d1b2a; color: #fff; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-box { background: #1b263b; padding: 30px; border-radius: 8px; width: 100%; max-width: 400px; box-shadow: 0 4px 10px rgba(0,0,0,0.3); text-align: center; }
        input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #415a77; background: #0d1b2a; color: #fff; border-radius: 4px; box-sizing: border-box; }
        button { background: #e76f51; color: white; border: none; padding: 12px; width: 100%; border-radius: 4px; font-weight: bold; cursor: pointer; }
        a { color: #2a9d8f; text-decoration: none; font-size: 14px; display: block; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Criar Conta de Torcedor</h2>
        <?=$mensagem?>
        <form method="POST">
            <input type="text" name="login" placeholder="Escolha um Usuário" required autocomplete="off">
            <input type="password" name="senha" placeholder="Escolha uma Senha" required>
            <button type="submit" name="registrar">Cadastrar</button>
        </form>
        <a href="login.php">← Voltar para o Login</a>
    </div>
</body>
</html>
