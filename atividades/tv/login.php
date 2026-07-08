<?php
// Ativa a exibição de todos os erros do PHP para descobrirmos o problema
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'conexao.php';
$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];

    try {
        // 1. Procura o utilizador pelo e-mail
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            // 2. Utilizador existe, vamos testar a senha
            if (password_verify($senha, $usuario['senha'])) {
                
                // 3. Senha correta! Guarda os dados na sessão
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nome'] = $usuario['nome'];
                $_SESSION['usuario_nivel'] = $usuario['nivel_acesso'];

                // 4. Redireciona baseado no nível de acesso
                if ($usuario['nivel_acesso'] === 'admin') {
                    header("Location: admin.php");
                } else {
                    header("Location: index.php");
                }
                exit;
            } else {
                $mensagem = "Erro: A senha inserida está incorreta para este e-mail.";
            }
        } else {
            $mensagem = "Erro: Não existe nenhum utilizador cadastrado com o e-mail '$email'.";
        }
    } catch (PDOException $e) {
        $mensagem = "Erro no Banco de Dados: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Login Debug - Pobreflix</title>
    <style>
        body { background-color: #060913; color: white; font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .box { background-color: #0d1222; padding: 40px; border-radius: 8px; width: 100%; max-width: 400px; box-shadow: 0 4px 15px rgba(0,0,0,0.5); }
        h2 { margin-top: 0; text-align: center; color: #0084ff; }
        input { width: 100%; padding: 12px; margin: 10px 0; border: none; border-radius: 4px; background: #161f38; color: white; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background-color: #0084ff; color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; }
        .alerta { background-color: rgba(231, 76, 60, 0.2); border: 1px solid #e74c3c; padding: 10px; color: #ff4d4d; font-size: 14px; text-align: center; margin-bottom: 15px; border-radius: 4px; }
        a { color: #0084ff; text-decoration: none; display: block; text-align: center; margin-top: 15px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="box">
        <h2>Entrar (Modo Debug)</h2>
        
        <?php if($mensagem): ?> 
            <div class="alerta"><?= $mensagem ?></div> 
        <?php endif; ?>

        <form method="POST">
            <input type="email" name="email" placeholder="E-mail" required>
            <input type="password" name="senha" placeholder="Senha" required>
            <button type="submit">Entrar</button>
        </form>
        <a href="cadastro.php">Criar nova conta de usuário</a>
    </div>
</body>
</html>
