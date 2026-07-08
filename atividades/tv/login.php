<?php
require_once 'conexao.php';
$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario && password_verify($senha, $usuario['senha'])) {
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['usuario_nivel'] = $usuario['nivel_acesso'];

        if ($usuario['nivel_acesso'] === 'admin') {
            header("Location: admin.php");
        } else {
            header("Location: index.php");
        }
        exit;
    } else {
        $mensagem = "E-mail ou senha incorretos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Login - Pobreflix</title>
    <style>
        body { background-color: #060913; color: white; font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .box { background-color: #0d1222; padding: 40px; border-radius: 8px; width: 100%; max-width: 360px; box-shadow: 0 4px 15px rgba(0,0,0,0.5); }
        h2 { margin-top: 0; text-align: center; color: #0084ff; }
        input { width: 100%; padding: 12px; margin: 10px 0; border: none; border-radius: 4px; background: #161f38; color: white; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background-color: #0084ff; color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; }
        .erro { color: #ff4d4d; font-size: 14px; text-align: center; margin-bottom: 10px; }
        a { color: #0084ff; text-decoration: none; display: block; text-align: center; margin-top: 15px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="box">
        <h2>Entrar no Pobreflix</h2>
        <?php if($mensagem): ?> <div class="erro"><?= $mensagem ?></div> <?php endif; ?>
        <form method="POST">
            <input type="email" name="email" placeholder="E-mail" required>
            <input type="password" name="senha" placeholder="Senha" required>
            <button type="submit">Entrar</button>
        </form>
        <a href="cadastro.php">Não tem uma conta? Cadastre-se</a>
    </div>
</body>
</html>
