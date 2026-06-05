<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE");

require_once "conexao.php";

$metodo = $_SERVER['REQUEST_METHOD'];

// FUNÇÃO PARA VALIDAR CPF
function validarCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($cpf) != 11 || preg_match('/(\d)\1{10}/', $cpf)) return false;
    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $c = 0; $c < $t; $c++) { $d += $cpf[$c] * (($t + 1) - $c); }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) return false;
    }
    return true;
}

// FUNÇÃO MATEMÁTICA PARA VALIDAR CNPJ
function validarCNPJ($cnpj) {
    $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
    if (strlen($cnpj) != 14 || preg_match('/(\d)\1{13}/', $cnpj)) return false;
    for ($t = 12; $t < 14; $t++) {
        for ($d = 0, $p = ($t - 7), $c = 0; $c < $t; $c++) {
            $d += $cnpj[$c] * $p;
            $p = ($p == 2) ? 9 : --$p;
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cnpj[$c] != $d) return false;
    }
    return true;
}

// 1. LISTAR CLIENTES
if ($metodo === 'GET') {
    $result = $conn->query("SELECT * FROM clientes ORDER BY id DESC");
    $clientes = [];
    while ($row = $result->fetch_assoc()) {
        $clientes[] = $row;
    }
    echo json_encode($clientes);
}

// 2. ADICIONAR OU ALTERAR CLIENTE
if ($metodo === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    $id = isset($data['id']) ? intval($data['id']) : null;
    $nome = trim($data['nome']);
    $email = trim($data['email']);
    $cpf = preg_replace('/[^0-9]/', '', $data['cpf']);
    
    // Tratamento do CNPJ Opcional
    $cnpj = preg_replace('/[^0-9]/', '', $data['cnpj']);
    $cnpj = empty($cnpj) ? null : $cnpj; // Se tiver vazio, vira NULL para o banco

    $telefone = trim($data['telefone']);
    $cep = trim($data['cep']);
    $logradouro = trim($data['logradouro']);
    $bairro = trim($data['bairro']);
    $cidade = trim($data['cidade']);
    $estado = trim($data['estado']);

    // Validação de obrigatórios
    if (empty($nome) || empty($email) || empty($cpf) || empty($telefone) || empty($cep) || empty($logradouro) || empty($bairro) || empty($cidade) || empty($estado)) {
        echo json_encode(["status" => "error", "message" => "Todos os campos obrigatórios devem ser preenchidos!"]);
        exit;
    }

    // Valida CPF (Obrigatório)
    if (!validarCPF($cpf)) {
        echo json_encode(["status" => "error", "message" => "O CPF digitado é inválido!"]);
        exit;
    }

    // Valida CNPJ (Apenas se não for nulo)
    if ($cnpj !== null && !validarCNPJ($cnpj)) {
        echo json_encode(["status" => "error", "message" => "O CNPJ digitado é inválido!"]);
        exit;
    }

    if ($id) {
        // --- ALTERAÇÃO ---
        // Verifica se e-mail, cpf ou cnpj já existem em OUTRO id
        $sqlCheck = "SELECT id FROM clientes WHERE (email = ? OR cpf = ?" . ($cnpj ? " OR cnpj = ?" : "") . ") AND id != ?";
        $stmtCheck = $conn->prepare($sqlCheck);
        if ($cnpj) { $stmtCheck->bind_param("sssi", $email, $cpf, $cnpj, $id); } 
        else { $stmtCheck->bind_param("ssi", $email, $cpf, $id); }
        
        $stmtCheck->execute();
        if ($stmtCheck->get_result()->num_rows > 0) {
            echo json_encode(["status" => "error", "message" => "E-mail, CPF ou CNPJ já estão em uso por outro cliente!"]);
            exit;
        }

        $stmt = $conn->prepare("UPDATE clientes SET nome=?, email=?, cpf=?, cnpj=?, telefone=?, cep=?, logradouro=?, bairro=?, city=?, estado=? WHERE id=?");
        // Correção para o nome da coluna caso no seu banco esteja cidade: altere 'city' para 'cidade' se necessário
        $stmt = $conn->prepare("UPDATE clientes SET nome=?, email=?, cpf=?, cnpj=?, telefone=?, cep=?, logradouro=?, bairro=?, cidade=?, estado=? WHERE id=?");
        $stmt->bind_param("ssssssssssi", $nome, $email, $cpf, $cnpj, $telefone, $cep, $logradouro, $bairro, $cidade, $estado, $id);
        $msg = "Cliente atualizado com sucesso!";
    } else {
        // --- INSERÇÃO ---
        $sqlCheck = "SELECT id FROM clientes WHERE email = ? OR cpf = ?" . ($cnpj ? " OR cnpj = ?" : "");
        $stmtCheck = $conn->prepare($sqlCheck);
        if ($cnpj) { $stmtCheck->bind_param("sss", $email, $cpf, $cnpj); } 
        else { $stmtCheck->bind_param("ss", $email, $cpf); }
        
        $stmtCheck->execute();
        if ($stmtCheck->get_result()->num_rows > 0) {
            echo json_encode(["status" => "error", "message" => "E-mail, CPF ou CNPJ já cadastrado!"]);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO clientes (nome, email, cpf, cnpj, telefone, cep, logradouro, bairro, cidade, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssss", $nome, $email, $cpf, $cnpj, $telefone, $cep, $logradouro, $bairro, $cidade, $estado);
        $msg = "Cliente cadastrado com sucesso!";
    }

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => $msg]);
    } else {
        echo json_encode(["status" => "error", "message" => "Erro ao salvar no banco. Verifique duplicidade de dados."]);
    }
    $stmt->close();
}

// 3. EXCLUIR CLIENTE
if ($metodo === 'DELETE') {
    $data = json_decode(file_get_contents("php://input"), true);
    $id = intval($data['id']);
    $stmt = $conn->prepare("DELETE FROM clientes WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) { echo json_encode(["status" => "success", "message" => "Cliente excluído!"]); }
    $stmt->close();
}
$conn->close();
?>
