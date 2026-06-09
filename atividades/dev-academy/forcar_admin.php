<?php
include 'db.php';

// Segurança de Vetor de Ataque: Este script deve ser deletado após a primeira execução!
try {
    $email = 'admin@devacademy.com';
    $senha_plana = 'admin123';
    
    // Idempotência Operacional por Destruição Segura
    $stmtDelete = $pdo->prepare("DELETE FROM usuarios WHERE email = ?");
    $stmtDelete->execute([$email]);
    
    // Consistência Baseada no Ambiente de Execução (Runtime)
    $senha_hash = password_hash($senha_plana, PASSWORD_DEFAULT);
    
    $stmtInsert = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, ?)");
    $stmtInsert->execute(['Administrador Central', $email, $senha_hash, 'admin']);
    
    echo "<h2>Ambiente Semeado com Sucesso!</h2>";
    echo "<p>Usuário Admin criado/resetado com segurança:</p>";
    echo "<ul><li><strong>E-mail:</strong> admin@devacademy.com</li><li><strong>Senha:</strong> admin123</li></ul>";
    echo "<p style='color:red;'><strong>AVISO DE SEGURANÇA:</strong> Exclua este arquivo (forcar_admin.php) do seu servidor imediatamente.</p>";
} catch (\Exception $e) {
    error_log($e->getMessage());
    die("Falha na semeadura do banco de dados.");
}
?>
