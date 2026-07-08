<?php
require_once 'conexao.php';

// Definimos a senha limpa que você vai digitar no login
$senha_nova = 'admin123'; 

// Geramos o hash nativo correto para o seu servidor atual
$novo_hash = password_hash($senha_nova, PASSWORD_DEFAULT);

try {
    // Atualiza a senha especificamente do e-mail do admin
    $stmt = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE email = 'admin@pobreflix.com'");
    $stmt->execute([$novo_hash]);

    echo "<h2 style='color: #2ecc71; font-family: sans-serif;'>Senha do Administrador redefinida com sucesso!</h2>";
    echo "<p style='font-family: sans-serif;'>Agora volte à tela de login e tente entrar.</p>";
    echo "<a href='login.php' style='font-family: sans-serif; background: #0084ff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>Ir para o Login</a>";
} catch (PDOException $e) {
    echo "Erro ao atualizar: " . $e->getMessage();
}
?>
