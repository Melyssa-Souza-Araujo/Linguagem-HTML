<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "controle_gastos";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Falha na conexão: " . $conn->connect_error]));
}

$conn->set_charset("utf8mb4");
?>
