<?php
session_start();
require 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $senha = $_POST['senha'];

    if (!$email || empty($nome) || empty($senha)) {
        die("Por favor, preencha todos os campos corretamente.");
    }

    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() > 0) {
        die("Este e-mail já está cadastrado no sistema.");
    }

    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)");
        $stmt->execute([$nome, $email, $senha_hash]);

        $_SESSION['usuario_id'] = $pdo->lastInsertId();
        $_SESSION['usuario_nome'] = $nome;

        header("Location: index.php");
        exit;
    } catch (PDOException $e) {
        die("Erro ao cadastrar usuário: " . $e->getMessage());
    }
} else {
    header("Location: login.html");
    exit;
}
?>
