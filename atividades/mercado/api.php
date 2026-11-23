<?php
session_start();
require_once 'conexao.php';
require_once 'helpers.php';
header('Content-Type: application/json');

if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}

$acao = $_POST['acao'] ?? 'listar';
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id > 0 && in_array($acao, ['adicionar', 'diminuir', 'remover'], true)) {
    if ($acao === 'remover') {
        unset($_SESSION['carrinho'][$id]);
    } else {
        $stmt = $pdo->prepare('SELECT id, nome, preco, desconto_pct FROM produtos WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $prod = $stmt->fetch();

        if ($prod) {
            $precoEfetivo = precoComDesconto((float)$prod['preco'], (float)$prod['desconto_pct']);

            if ($acao === 'adicionar') {
                if (isset($_SESSION['carrinho'][$id])) {
                    $_SESSION['carrinho'][$id]['quantidade']++;
                } else {
                    $_SESSION['carrinho'][$id] = [
                        'id' => (int)$prod['id'],
                        'nome' => $prod['nome'],
                        'preco' => $precoEfetivo,
                        'quantidade' => 1,
                    ];
                }
            } elseif ($acao === 'diminuir' && isset($_SESSION['carrinho'][$id])) {
                $_SESSION['carrinho'][$id]['quantidade']--;
                if ($_SESSION['carrinho'][$id]['quantidade'] <= 0) {
                    unset($_SESSION['carrinho'][$id]);
                }
            }
        }
    }
}

// Revalida os itens do carrinho contra o banco atual
$totalGeral = 0.0;
$totalItens = 0;
$itensAtuais = [];

if (!empty($_SESSION['carrinho'])) {
    $ids = array_map('intval', array_keys($_SESSION['carrinho']));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT id, nome, preco, desconto_pct FROM produtos WHERE id IN ({$placeholders})");
    $stmt->execute($ids);

    $produtosBanco = [];
    while ($row = $stmt->fetch()) {
        $produtosBanco[(int)$row['id']] = $row;
    }

    foreach ($_SESSION['carrinho'] as $itemId => $item) {
        if (!isset($produtosBanco[$itemId])) {
            unset($_SESSION['carrinho'][$itemId]);
            continue;
        }

        $p = $produtosBanco[$itemId];
        $precoAtual = precoComDesconto((float)$p['preco'], (float)$p['desconto_pct']);
        $qtd = (int)$item['quantidade'];

        $_SESSION['carrinho'][$itemId]['nome'] = $p['nome'];
        $_SESSION['carrinho'][$itemId]['preco'] = $precoAtual;

        $itensAtuais[] = [
            'id' => (int)$itemId,
            'nome' => $p['nome'],
            'preco' => $precoAtual,
            'quantidade' => $qtd,
        ];

        $totalGeral += $precoAtual * $qtd;
        $totalItens += $qtd;
    }
}

echo json_encode([
    'itens' => $itensAtuais,
    'totalGeral' => round($totalGeral, 2),
    'totalItens' => $totalItens,
]);