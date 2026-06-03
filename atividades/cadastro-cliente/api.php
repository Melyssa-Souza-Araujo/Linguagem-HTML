<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE, PUT");

$host = "localhost";
$user = "root";
$pass = ""; // Se tiver senha no seu MySQL (como no MAMP), coloque aqui
$db   = "sistema_clientes";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Falha na conexão: " . $conn->connect_error]);
    exit;
}

$metodo = $_SERVER['REQUEST_METHOD'];

// 1. LISTAR CLIENTES (GET)
if ($metodo === 'GET') {
    $result = $conn->query("SELECT * FROM clientes ORDER BY id DESC");
    $clientes = [];
    while ($row = $result->fetch_assoc()) {
        $clientes[] = $row;
    }
    echo json_encode($clientes);
}

// 2. ADICIONAR OU ALTERAR CLIENTE (POST)
if ($metodo === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    $id = isset($data['id']) ? intval($data['id']) : null;
    $nome = $conn->real_escape_string($data['nome']);
    $email = $conn->real_escape_string($data['email']);
    $telefone = $conn->real_escape_string($data['telefone']);

    if (empty($nome) || empty($email) || empty($telefone)) {
        echo json_encode(["status" => "error", "message" => "Preencha todos os campos!"]);
        exit;
    }

    if ($id) {
        // Se já tem ID, é uma ALTERAÇÃO (Update)
        $sql = "UPDATE clientes SET nome='$nome', email='$email', telefone='$telefone' WHERE id=$id";
        $msg = "Cliente atualizado com sucesso!";
    } else {
        // Se não tem ID, é um NOVO CADASTRO (Insert)
        $sql = "INSERT INTO clientes (nome, email, telefone) VALUES ('$nome', '$email', '$telefone')";
        $msg = "Cliente cadastrado com sucesso!";
    }

    if ($conn->query($sql)) {
        echo json_encode(["status" => "success", "message" => $msg]);
    } else {
        echo json_encode(["status" => "error", "message" => "Erro ao salvar: " . $conn->error]);
    }
}

// 3. EXCLUIR CLIENTE (DELETE)
if ($metodo === 'DELETE') {
    $data = json_decode(file_get_contents("php://input"), true);
    $id = intval($data['id']);

    $sql = "DELETE FROM clientes WHERE id = $id";
    if ($conn->query($sql)) {
        echo json_encode(["status" => "success", "message" => "Cliente excluído com sucesso!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Erro ao excluir."]);
    }
}

$conn->close();
?>
