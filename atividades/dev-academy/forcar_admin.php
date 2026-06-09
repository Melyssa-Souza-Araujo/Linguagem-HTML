<?php
include 'db.php';

$email = 'admin@dev.com';
$senha_pura = 'admin123';
// O próprio PHP local gera a hash perfeita para o seu ambiente
$senha_hash = password_hash($senha_pura, PASSWORD_DEFAULT);

try {
    // 1. Remove qualquer registro antigo com esse e-mail para evitar duplicidade
    $stmtDelete = $pdo->prepare("DELETE FROM usuarios WHERE email = ?");
    $stmtDelete->execute([$email]);

    // 2. Insere o administrador do zero com a hash correta
    $stmtInsert = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, tipo) VALUES ('Administrador', ?, ?, 'admin')");
    $stmtInsert->execute([$email, $senha_hash]);

    echo "<div style='font-family: sans-serif; max-width: 500px; margin: 50px auto; padding: 20px; border: 2px solid #6d28d9; border-radius: 8px; background: #faf5ff;'>";
    echo "<h2 style='color: #5b21b6; margin-top: 0;'>✓ Conta de Admin Resetada!</h2>";
    echo "<p>O usuário foi reinserido diretamente pelo PHP.</p>";
    echo "<p><strong>E-mail:</strong> <span style='color: #6d28d9;'>admin@dev.com</span></p>";
    echo "<p><strong>Senha:</strong> <span style='color: #6d28d9;'>admin123</span></p>";
    echo "<hr style='border: 0; border-top: 1px solid #e9d5ff; margin: 20px 0;'>";
    echo "<p style='color: #b91c1c; font-size: 14px; font-weight: bold;'>⚠️ Importante: Delete o arquivo 'forcar_admin.php' da sua pasta agora por motivos de segurança.</p>";
    echo "</div>";

} catch (Exception $e) {
    echo "<h2 style='color: red; font-family: sans-serif;'>Erro ao resetar: " . $e->getMessage() . "</h2>";
}
