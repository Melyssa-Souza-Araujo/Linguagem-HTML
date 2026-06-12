<?php
include 'conexao.php';
/** @var PDO $pdo */

$nova_senha = password_hash('123', PASSWORD_DEFAULT);

try {
    // Tenta atualizar se o admin já existir
    $stmt = $pdo->prepare("UPDATE usuarios SET senha = ?, nivel = 'admin' WHERE login = 'admin'");
    $stmt->execute([$nova_senha]);
    
    // Se não atualizou nenhuma linha (porque o admin não existia), cria ele do zero
    if ($stmt->rowCount() === 0) {
        $stmt = $pdo->prepare("INSERT INTO usuarios (login, senha, nivel) VALUES ('admin', ?, 'admin')");
        $stmt->execute([$nova_senha]);
    }
    
    echo "<h2>Sucesso! O utilizador 'admin' foi atualizado com a senha '123'.</h2>";
    echo "<p>Pode apagar este ficheiro e tentar fazer o login novamente.</p>";
} catch (PDOException $e) {
    echo "Erro ao corrigir: " . $e->getMessage();
}
?>
