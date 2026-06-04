<?php
session_start();
header("Content-Type: application/json");

require_once "conexao.php";

// ---- AJUSTE DE SEGURANÇA NO BANCO (ADICIONANDO PRIORIDADE SE NÃO EXISTIR) ----
$conn->query("SHOW COLUMNS FROM chamados LIKE 'prioridade'");
if ($conn->affected_rows === 0) {
    $conn->query("ALTER TABLE chamados ADD COLUMN prioridade ENUM('baixa', 'media', 'alta') DEFAULT 'baixa' AFTER descricao");
}

$metodo = $_SERVER['REQUEST_METHOD'];

// ---- PROCESSAMENTO DE REQUISIÇÕES GET ----
if ($metodo === 'GET') {
    $acao = isset($_GET['acao']) ? $_GET['acao'] : '';

    // 1. Verifica se a sessão está ativa e entrega os dados do usuário
    if ($acao === 'verificar_sessao') {
        if (!isset($_SESSION['usuario_id'])) {
            echo json_encode(["status" => "error", "message" => "Não autorizado"]);
            exit;
        }
        echo json_encode([
            "status" => "success",
            "nome" => $_SESSION['usuario_nome'],
            "perfil" => $_SESSION['usuario_perfil']
        ]);
        exit;
    }

    // 2. Destrói a sessão ao fazer logout
    if ($acao === 'logout') {
        session_destroy();
        echo json_encode(["status" => "success"]);
        exit;
    }

    // 3. Listar apenas os chamados do cliente logado
    if ($acao === 'listar_cliente') {
        if (!isset($_SESSION['usuario_id'])) {
            echo json_encode(["status" => "error", "message" => "Não autorizado"]);
            exit;
        }
        
        $cliente_id = $_SESSION['usuario_id'];
        $stmt = $conn->prepare("SELECT id, titulo, descricao, prioridade, status, data_criacao FROM chamados WHERE cliente_id = ? ORDER BY id DESC");
        $stmt->bind_param("i", $cliente_id);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $chamados = [];
        while ($row = $result->fetch_assoc()) {
            $chamados[] = $row;
        }
        echo json_encode($chamados);
        $stmt->close();
        exit;
    }
}

// ---- PROCESSAMENTO DE REQUISIÇÕES POST ----
if ($metodo === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $acao = isset($data['acao']) ? $data['acao'] : '';

    // 1. Criar novo chamado vindo do formulário do cliente
    if ($acao === 'criar_chamado') {
        if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_perfil'] !== 'cliente') {
            echo json_encode(["status" => "error", "message" => "Operação não autorizada."]);
            exit;
        }

        $cliente_id = $_SESSION['usuario_id'];
        $titulo = trim($data['titulo']);
        $prioridade = $data['prioridade'];
        $descricao = trim($data['descricao']);

        if (empty($titulo) || empty($descricao) || empty($prioridade)) {
            echo json_encode(["status" => "error", "message" => "Preencha todos os campos do chamado!"]);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO chamados (cliente_id, titulo, descricao, prioridade) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $cliente_id, $titulo, $descricao, $prioridade);

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Chamado aberto com sucesso! Nossa equipe técnica já foi notificada."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Erro ao registrar o chamado de suporte."]);
        }
        $stmt->close();
        exit;
    }
}

$conn->close();
?>
