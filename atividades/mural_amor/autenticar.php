<?php
// Garante que a sessão inicie limpa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $senha = $_POST['senha'];

    if (!$email || empty($senha)) {
        die("Por favor, preencha o e-mail e a senha corretamente.");
    }

    // Busca o usuário
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // Confere a senha criptografada
    if ($usuario && password_verify($senha, $usuario['senha'])) {
        // Grava as variáveis de sessão necessárias para o index.php
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];

        // Redireciona com sucesso para a página principal
        header("Location: index.php");
        exit;
    } else {
        echo "<script>alert('E-mail ou senha incorretos!'); window.location.href='login.html';</script>";
        exit;
    }
} else {
    header("Location: login.html");
    exit;
}
?>
