<?php
session_start();
require 'conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.html");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$parceiro_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($parceiro_id && $parceiro_id != $usuario_id) {
    try {
        // Inicia uma transação no banco de dados para garantir que ambos fiquem conectados juntos
        $pdo->beginTransaction();

        // Atualiza a minha conta com o ID do meu parceiro
        $stmt1 = $pdo->prepare("UPDATE usuarios SET parceiro_id = ? WHERE id = ?");
        $stmt1->execute([$parceiro_id, $usuario_id]);

        // Atualiza a conta do meu parceiro com o meu ID
        $stmt2 = $pdo->prepare("UPDATE usuarios SET parceiro_id = ? WHERE id = ?");
        $stmt2->execute([$usuario_id, $parceiro_id]);

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Erro ao conectar os perfis: " . $e->getMessage());
    }
}

// Redireciona de volta para o index, que agora carregará o mural unificado!
header("Location: index.php");
exit;
?>
