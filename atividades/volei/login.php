<?php
session_start();
include 'conexao.php';
/** @var PDO $pdo */

$erro = "";

if (isset($_POST['logar'])) {
    // trim() remove espaços em branco acidentais antes ou depois do texto
    $login = trim($_POST['login']);
    $senha = trim($_POST['senha']);

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE LOWER(login) = LOWER(?)");
    $stmt->execute([$login]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // Altere temporariamente a linha do IF para esta se quiser burlar o erro agora:
if ($usuario && (password_verify($senha, trim($usuario['senha'])) || ($login === 'admin' && $senha === '123'))) {
        $_SESSION['logado'] = true;
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_login'] = $usuario['login'];
        $_SESSION['usuario_nivel'] = trim($usuario['nivel']); // Evita espaços aqui também
        
        header("Location: index.php");
        exit;
    } else {
        $erro = "Usuário ou senha incorretos!";
    }
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - VNL</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #0d1b2a; color: #fff; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-box { background: #1b263b; padding: 30px; border-radius: 8px; width: 100%; max-width: 400px; box-shadow: 0 4px 10px rgba(0,0,0,0.3); text-align: center; }
        input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #415a77; background: #0d1b2a; color: #fff; border-radius: 4px; box-sizing: border-box; }
        button { background: #2a9d8f; color: white; border: none; padding: 12px; width: 100%; border-radius: 4px; font-weight: bold; cursor: pointer; }
        button:hover { background: #21867a; }
        a { color: #e76f51; text-decoration: none; font-size: 14px; display: block; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Entrar no Sistema VNL</h2>
        <?php if(!empty($erro)): ?><p style="color:#e63946; font-weight:bold;"><?=$erro?></p><?php endif; ?>
        <form method="POST">
            <input type="text" name="login" placeholder="Usuário" required autocomplete="off">
            <input type="password" name="senha" placeholder="Senha" required>
            <button type="submit" name="logar">Entrar</button>
        </form>
        <a href="registrar.php">Não tem conta? Cadastre-se aqui</a>
    </div>
</body>
</html>
