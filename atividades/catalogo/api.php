<?php
header("Access-Control-Allow-Origin: *");
require_once "conexao.php";

$acao = isset($_GET['acao']) ? $_GET['acao'] : '';

// 1. LISTAR ITENS (GET)
if ($acao === 'listar') {
    header("Content-Type: application/json");
    $result = $conn->query("SELECT * FROM itens ORDER BY id DESC");
    $itens = [];
    while ($row = $result->fetch_assoc()) {
        $itens[] = $row;
    }
    echo json_encode($itens);
    exit;
}

// 2. ADICIONAR OU ALTERAR ITEM (POST)
if ($acao === 'salvar') {
    header("Content-Type: application/json");
    
    $id = isset($_POST['id']) ? intval($_POST['id']) : null;
    $titulo = trim($_POST['titulo']);
    $tipo = $_POST['tipo'];
    $genero = trim($_POST['genero']);
    $descricao = trim($_POST['descricao']);
    $nome_foto = null;

    if (empty($titulo) || empty($tipo) || empty($genero) || empty($descricao)) {
        echo json_encode(["status" => "error", "message" => "Preencha todos os campos obrigatórios!"]);
        exit;
    }

    // Processamento do Upload da Foto
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $extensao = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array(strtolower($extensao), $extensoes_permitidas)) {
            // Cria um nome único para a foto não sobrescrever outra com mesmo nome
            $nome_foto = uniqid() . "." . $extensao;
            $destino = "uploads/" . $nome_foto;
            move_uploaded_file($_FILES['foto']['tmp_name'], $destino);
        }
    }

    if ($id) {
        // --- ATUALIZAÇÃO (UPDATE) ---
        if ($nome_foto) {
            // Se o usuário enviou uma foto nova, atualiza o campo da foto também
            $stmt = $conn->prepare("UPDATE itens SET titulo=?, tipo=?, genero=?, descricao=?, foto=? WHERE id=?");
            $stmt->bind_param("sssssi", $titulo, $tipo, $genero, $descricao, $nome_foto, $id);
        } else {
            // Se não enviou foto nova, mantém a antiga
            $stmt = $conn->prepare("UPDATE itens SET titulo=?, tipo=?, genero=?, descricao=? WHERE id=?");
            $stmt->bind_param("ssssi", $titulo, $tipo, $genero, $descricao, $id);
        }
        $msg = "Item atualizado com sucesso!";
    } else {
        // --- INSERÇÃO (INSERT) ---
        $foto_final = $nome_foto ? $nome_foto : 'padrao.png'; // Foto padrão caso não envie nenhuma
        $stmt = $conn->prepare("INSERT INTO itens (titulo, tipo, genero, descricao, foto) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $titulo, $tipo, $genero, $descricao, $foto_final);
        $msg = "Item adicionado ao catálogo!";
    }

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => $msg]);
    } else {
        echo json_encode(["status" => "error", "message" => "Erro ao salvar no banco de dados."]);
    }
    $stmt->close();
    exit;
}

// 3. EXCLUIR ITEM (POST/GET)
if ($acao === 'excluir') {
    header("Content-Type: application/json");
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($id > 0) {
        $stmt = $conn->prepare("DELETE FROM itens WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Item removido com sucesso!"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Erro ao deletar item."]);
        }
        $stmt->close();
    }
    exit;
}

$conn->close();
?>
