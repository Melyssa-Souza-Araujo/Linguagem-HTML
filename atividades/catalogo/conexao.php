<?php
$host = "localhost";
$user = "root";
$pass = ""; // Senha do seu MySQL se houver
$db   = "sistema_catalogo";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    header("Content-Type: application/json");
    echo json_encode(["status" => "error", "message" => "Conexão falhou: " . $conn->connect_error]);
    exit;
}
