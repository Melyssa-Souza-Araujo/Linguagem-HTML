<?php
$host = "localhost";
$user = "root";
$pass = ""; // Coloque a senha do seu MySQL se houver
$db   = "sistema_clientes";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    header("Content-Type: application/json");
    echo json_encode(["status" => "error", "message" => "Falha na conexão: " . $conn->connect_error]);
    exit;
}
