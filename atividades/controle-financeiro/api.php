<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

require_once "conexao.php";

$metodo = $_SERVER['REQUEST_METHOD'];

// ----------------------------------------------------
// READ: Listar despesas, orçamento e cálculos agregados
// ----------------------------------------------------
if ($metodo === 'GET') {
    // 1. Buscar Orçamento Total
    $resOrcamento = $conn->query("SELECT valor_total FROM orcamento WHERE id = 1");
    $orcamentoObj = $resOrcamento->fetch_assoc();
    $orcamentoTotal = $orcamentoObj ? (float)$orcamentoObj['valor_total'] : 0.00;

    // 2. Buscar Lista de Despesas e calcular o total acumulado
    $resDespesas = $conn->query("SELECT * FROM despesas ORDER BY data_gasto DESC, id DESC");
    $despesas = [];
    $totalGasto = 0.00;
    
    while ($row = $resDespesas->fetch_assoc()) {
        $row['id'] = (int)$row['id'];
        $row['valor'] = (float)$row['valor'];
        $totalGasto += $row['valor'];
        $despesas[] = $row;
    }

    // 3. Calcular Saldo Restante
    $saldoRestante = $orcamentoTotal - $totalGasto;

    // 4. Agrupar Gastos por Categoria para alimentar o Gráfico
    $resCategorias = $conn->query("SELECT categoria, SUM(valor) as total FROM despesas GROUP BY categoria");
    $gastosPorCategoria = [];
    while ($row = $resCategorias->fetch_assoc()) {
        $gastosPorCategoria[$row['categoria']] = (float)$row['total'];
    }

    // Retornar o JSON estruturado para o frontend
    echo json_encode([
        "orcamento_total" => $orcamentoTotal,
        "total_gasto" => $totalGasto,
        "saldo_restante" => $saldoRestante,
        "despesas" => $despesas,
        "grafico_categorias" => $gastosPorCategoria
    ]);
}

// ----------------------------------------------------
// CREATE / UPDATE: Salvar nova despesa ou atualizar orçamento
// ----------------------------------------------------
if ($metodo === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    // Rota interna para atualizar o orçamento limite global
    if (isset($data['acao']) && $data['acao'] === 'atualizar_orcamento') {
        $novoOrcamento = filter_var($data['valor_total'], FILTER_VALIDATE_FLOAT);
        
        if ($novoOrcamento === false || $novoOrcamento < 0) {
            echo json_encode(["status" => "error", "message" => "Valor de orçamento inválido!"]);
            exit;
        }

        $stmt = $conn->prepare("UPDATE orcamento SET valor_total = ? WHERE id = 1");
        $stmt->bind_param("d", $novoOrcamento);
        
        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Orçamento global atualizado!"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Erro ao atualizar orçamento."]);
        }
        $stmt->close();
        exit;
    }

    // Rota para inserção de uma nova despesa
    $descricao = trim($data['descricao'] ?? '');
    $valor = filter_var($data['valor'] ?? 0, FILTER_VALIDATE_FLOAT);
    $categoria = trim($data['categoria'] ?? '');
    $data_gasto = trim($data['data_gasto'] ?? '');

    if (empty($descricao) || $valor === false || $valor <= 0 || empty($categoria) || empty($data_gasto)) {
        echo json_encode(["status" => "error", "message" => "Preencha todos os campos corretamente com valores maiores que zero!"]);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO despesas (descricao, valor, categoria, data_gasto) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sdss", $descricao, $valor, $categoria, $data_gasto);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Despesa adicionada com sucesso!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Erro ao salvar a despesa no banco de dados."]);
    }
    $stmt->close();
}

// ----------------------------------------------------
// DELETE: Excluir uma despesa específica
// ----------------------------------------------------
if ($metodo === 'DELETE') {
    $data = json_decode(file_get_contents("php://input"), true);
    $id = isset($data['id']) ? intval($data['id']) : 0;

    if ($id <= 0) {
        echo json_encode(["status" => "error", "message" => "ID inválido para exclusão."]);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM despesas WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Despesa removida com sucesso!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Erro ao remover a despesa."]);
    }
    $stmt->close();
}

$conn->close();
?>
