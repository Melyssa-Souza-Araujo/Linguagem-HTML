<?php
session_start();
require 'conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.html");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $texto = filter_input(INPUT_POST, 'texto', FILTER_SANITIZE_SPECIAL_CHARS);
    $usuario_id = $_SESSION['usuario_id'];
    $imagem_nome = null;

    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
        $extensao = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
        $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($extensao, $extensoes_permitidas)) {
            $imagem_nome = uniqid("love_") . "." . $extensao;
            $destino = "uploads/" . $imagem_nome;

            if (!is_dir('uploads')) {
                mkdir('uploads', 0755, true);
            }

            move_uploaded_file($_FILES['imagem']['tmp_name'], $destino);
        }
    }

    if (!empty($texto) || $imagem_nome !== null) {
        $stmt = $pdo->prepare("INSERT INTO posts (usuario_id, texto, imagem_url) VALUES (?, ?, ?)");
        $stmt->execute([$usuario_id, $texto, $imagem_nome]);
    }

    header("Location: index.php");
    exit;
}
?>
