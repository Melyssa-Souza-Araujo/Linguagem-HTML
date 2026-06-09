<?php
include 'db.php';
session_start();

$erro = '';
$sucesso = '';

// Processar Cadastro
if (isset($_POST['cadastrar'])) {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)");
        $stmt->execute([$nome, $email, $senha]);
        $sucesso = "Cadastro realizado! Faça o login ao lado.";
    } catch (PDOException $e) {
        $erro = "Este e-mail já está cadastrado.";
    }
}

// Processar Login
if (isset($_POST['logar'])) {
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario && password_verify($senha, $usuario['senha'])) {
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['usuario_tipo'] = $usuario['tipo'];

        if ($usuario['tipo'] === 'admin') {
            header("Location: admin.php");
        } else {
            header("Location: cursos.php");
        }
        exit;
    } else {
        $erro = "E-mail ou senha incorretos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>DevAcademy - Entrar</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background: #f3f0ff; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .container { background: #ffffff; width: 100%; max-width: 900px; border-radius: 12px; box-shadow: 0 8px 24px rgba(109, 40, 217, 0.1); overflow: hidden; display: flex; flex-wrap: wrap; }
        .coluna { flex: 1; min-width: 300px; padding: 40px; }
        .coluna-login { border-right: 1px solid #e9d5ff; }
        .coluna-cadastro { background: #faf5ff; }
        h2 { color: #5b21b6; margin-bottom: 20px; font-size: 24px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; color: #4c1d95; font-size: 14px; font-weight: 600; }
        input { wIdth: 100%; padding: 10px; border: 2px solid #ddd6fe; border-radius: 6px; outline: none; transition: 0.3s; }
        input:focus { border-color: #7c3aed; }
        button { wIdth: 100%; padding: 12px; background: #7c3aed; border: none; color: white; font-weight: bold; border-radius: 6px; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        button:hover { background: #6d28d9; }
        .alerta { padding: 10px; border-radius: 6px; margin-bottom: 15px; text-align: center; font-size: 14px; wIdth: 100%; }
        .erro { background: #fee2e2; color: #991b1b; }
        .sucesso { background: #dcfce7; color: #166534; }
    </style>
</head>
<body>

<div style="display: flex; flex-direction: column; align-items: center; wIdth: 100%; max-width: 900px;">
    
    <?php if($erro): ?> <div class="alerta erro"><?= $erro ?></div> <?php endif; ?>
    <?php if($sucesso): ?> <div class="alerta sucesso"><?= $sucesso ?></div> <?php endif; ?>

    <div class="container">
        <!-- FORMULÁRIO DE LOGIN -->
        <div class="coluna coluna-login">
            <h2>Já tenho conta</h2>
            <form method="POST">
                <div class="form-group">
                    <label>E-mail</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Senha</label>
                    <input type="password" name="senha" required>
                </div>
                <button type="submit" name="logar">Entrar na Plataforma</button>
            </form>
        </div>

        <!-- FORMULÁRIO DE CADASTRO -->
        <div class="coluna coluna-cadastro">
            <h2>Criar Conta</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Nome Completo</label>
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
                <button type="submit" name="cadastrar" style="background: #6d28d9;">Cadastrar Grátis</button>
            </form>
        </div>
    </div>
</div>

</body>
</html>
