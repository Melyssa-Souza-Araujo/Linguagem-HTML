<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE");

require_once "conexao.php";

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
    $nome = trim($data['nome']);
    $email = trim($data['email']);
    $telefone = trim($data['telefone']);

    if (empty($nome) || empty($email) || empty($telefone)) {
        echo json_encode(["status" => "error", "message" => "Preencha todos os campos!"]);
        exit;
    }

    if ($id) {
        // --- ALTERAÇÃO COM PREPARED STATEMENT ---
        // Verifica se o e-mail já está sendo usado por OUTRO usuário
        $stmtCheck = $conn->prepare("SELECT id FROM clientes WHERE email = ? AND id != ?");
        $stmtCheck->bind_param("si", $email, $id);
        $stmtCheck->execute();
        if ($stmtCheck->get_result()->num_rows > 0) {
            echo json_encode(["status" => "error", "message" => "Este e-mail já está sendo utilizado por outro cliente!"]);
            exit;
        }

        $stmt = $conn->prepare("UPDATE clientes SET nome = ?, email = ?, telefone = ? WHERE id = ?");
        $stmt->bind_param("sssi", $nome, $email, $telefone, $id);
        $msg = "Cliente atualizado com sucesso!";
    } else {
        // --- INSERÇÃO COM PREPARED STATEMENT ---
        // Verifica se o e-mail já existe
        $stmtCheck = $conn->prepare("SELECT id FROM clientes WHERE email = ?");
        $stmtCheck->bind_param("s", $email);
        $stmtCheck->execute();
        if ($stmtCheck->get_result()->num_rows > 0) {
            echo json_encode(["status" => "error", "message" => "Este e-mail já está cadastrado!"]);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO clientes (nome, email, telefone) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nome, $email, $telefone);
        $msg = "Cliente cadastrado com sucesso!";
    }

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => $msg]);
    } else {
        echo json_encode(["status" => "error", "message" => "Erro ao salvar os dados."]);
    }
    $stmt->close();
}

// 3. EXCLUIR CLIENTE (DELETE)
if ($metodo === 'DELETE') {
    $data = json_decode(file_get_contents("php://input"), true);
    $id = intval($data['id']);

    $stmt = $conn->prepare("DELETE FROM clientes WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Cliente excluído com sucesso!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Erro ao excluir o cliente."]);
    }
    $stmt->close();
}

$conn->close();
?>
