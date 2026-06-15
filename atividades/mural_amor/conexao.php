<?php
$host = "localhost";
$banco = "mural_amor";
$usuario = "root";
$senha = ""; // Se usar XAMPP, por padrão a senha é vazia

try {
    $pdo = new PDO("mysql:host=$host;dbname=$banco;charset=utf8mb4", $usuario, $senha);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro ao conectar ao banco de dados: " . $e->getMessage());
}
?>
