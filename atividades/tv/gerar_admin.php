<?php
require_once 'conexao.php';

$nome = 'Administrador';
$email = 'admin@pobreflix.com';
$senha_pura = 'admin123'; // Esta será a sua senha de acesso

// Gera o hash correto que o PHP entende
$senha_hash = password_hash($senha_pura, PASSWORD_DEFAULT);

try {
    // Apaga se já existir um admin antigo para não dar erro de duplicado
    $pdo->prepare("DELETE FROM usuarios WHERE email = ?")->execute([$email]);

    // Insere o administrador com o hash perfeito
    $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, nivel_acesso) VALUES (?, ?, ?, 'admin')");
    $stmt->execute([$nome, $email, $senha_hash]);

    echo "<h2>Administrador configurado com sucesso!</h2>";
    echo "<b>E-mail:</b> admin@pobreflix.com<br>";
    echo "<b>Senha:</b> admin123<br><br>";
    echo "<a href='login.php'>Ir para o Login</a>";
} catch (PDOException $e) {
    echo "Erro ao criar administrador: " . $e->getMessage();
}
?>
