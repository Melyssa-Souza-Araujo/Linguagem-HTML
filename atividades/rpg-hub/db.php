<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$host = 'localhost';
$dbname = 'rpg_hub';
$username = 'root';
$password = '';

try {
    $pdo = new PDO('mysql:host=$host;dbname=$dbname', $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Erro ao se conectar ao banco de dados: " . $e->getMessage());
}

?>