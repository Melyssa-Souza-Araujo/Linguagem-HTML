<?php
session_start();
require_once 'conexao.php';
require_once 'helpers.php';

$SENHA_ADMIN = 'admin123';
$mensagem = '';
$erro = '';

if (isset($_POST['login_admin'])) {
    if (($_POST['senha_admin'] ?? '') === $SENHA_ADMIN) {
        $_SESSION['admin_logado'] = true;
    } else {
        $erro = 'Senha de administrador incorreta.';
    }
}

if (isset($_GET['logout_admin'])) {
    unset($_SESSION['admin_logado']);
    header('Location: admin.php');
    exit;
}

$adminLogado = !empty($_SESSION['admin_logado']);
$produtoParaEditar = null;

if ($adminLogado && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $acaoAdmin = $_POST['acao_admin'] ?? '';

    if ($acaoAdmin === 'salvar_produto') {
        $idEdit = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : 0;
        $nome = trim($_POST['nome'] ?? '');
        $precoFloat = (float)str_replace(',', '.', trim($_POST['preco'] ?? '0'));
        $emPromocao = isset($_POST['em_promocao']);
        $descontoRaw = (float)str_replace(',', '.', trim($_POST['desconto_pct'] ?? '0'));
        $descontoFloat = $emPromocao ? max(0, min(99, $descontoRaw)) : 0;
        $imagem = trim($_POST['imagem'] ?? '');

        if ($nome === '' || $precoFloat <= 0) {
            $erro = 'Informe um nome válido e um preço maior que zero.';
        } else {
            $imagemFinal = $imagem !== '' ? $imagem : null;

            if ($idEdit > 0) {
                $stmt = $pdo->prepare(
                    'UPDATE produtos SET nome = :nome, preco = :preco, desconto_pct = :desconto_pct, imagem = :imagem WHERE id = :id'
                );
                $stmt->execute([
                    ':nome' => $nome,
                    ':preco' => round($precoFloat, 2),
                    ':desconto_pct' => round($descontoFloat, 2),
                    ':imagem' => $imagemFinal,
                    ':id' => $idEdit,
                ]);
                $mensagem = 'Produto atualizado no banco de dados!';
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO produtos (nome, preco, desconto_pct, imagem) VALUES (:nome, :preco, :desconto_pct, :imagem)'
                );
                $stmt->execute([
                    ':nome' => $nome,
                    ':preco' => round($precoFloat, 2),
                    ':desconto_pct' => round($descontoFloat, 2),
                    ':imagem' => $imagemFinal,
                ]);
                $mensagem = 'Produto cadastrado no banco de dados!';
            }
        }
    } elseif ($acaoAdmin === 'excluir_produto') {
        $idExcluir = (int)($_POST['id'] ?? 0);
        if ($idExcluir > 0) {
            $stmt = $pdo->prepare('DELETE FROM produtos WHERE id = :id');
            $stmt->execute([':id' => $idExcluir]);
            unset($_SESSION['carrinho'][$idExcluir]);
            $mensagem = 'Produto excluído do banco de dados.';
        }
    } elseif ($acaoAdmin === 'remover_desconto') {
        $idDesc = (int)($_POST['id'] ?? 0);
        if ($idDesc > 0) {
            $stmt = $pdo->prepare('UPDATE produtos SET desconto_pct = 0 WHERE id = :id');
            $stmt->execute([':id' => $idDesc]);
            $mensagem = 'Promoção removida do produto.';
        }
    }
}

if ($adminLogado && isset($_GET['editar'])) {
    $idEd = (int)$_GET['editar'];
    $stmt = $pdo->prepare('SELECT id, nome, preco, desconto_pct, imagem FROM produtos WHERE id = :id');
    $stmt->execute([':id' => $idEd]);
    $produtoParaEditar = $stmt->fetch() ?: null;
}

$produtos = [];
if ($adminLogado) {
    $stmt = $pdo->query('SELECT id, nome, preco, desconto_pct, imagem FROM produtos ORDER BY id DESC');
    $produtos = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Admin - SuperMercado</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <header>
        <a href="index.php" class="logo">🛒 SuperMercado</a>
        <nav class="nav-acoes">
            <a href="index.php" class="link-header">Ver Loja</a>
            <a href="cliente.php" class="link-header">Área do Cliente</a>
            <?php if ($adminLogado): ?>
                <a href="admin.php?logout_admin=1" class="link-header">Sair do Admin</a>
            <?php endif; ?>
        </nav>
    </header>

    <main class="container-estreito">
        <?php if (!$adminLogado): ?>
            <div class="card-form">
                <h2>Acesso do Administrador</h2>
                <p class="texto-apoio">Senha padrão para teste: <strong>admin123</strong></p>

                <?php if ($erro): ?>
                    <div class="alerta erro"><?= htmlspecialchars($erro) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <label for="senha_admin">Senha</label>
                    <input type="password" id="senha_admin" name="senha_admin" required>
                    <button type="submit" name="login_admin" class="btn-primario">Entrar</button>
                </form>
            </div>
        <?php else: ?>
            <h2>Gerenciar Produtos e Promoções</h2>

            <?php if ($mensagem): ?>
                <div class="alerta sucesso"><?= htmlspecialchars($mensagem) ?></div>
            <?php endif; ?>
            <?php if ($erro): ?>
                <div class="alerta erro"><?= htmlspecialchars($erro) ?></div>
            <?php endif; ?>

            <div class="card-form">
                <h3><?= $produtoParaEditar ? 'Editar Produto #' . (int)$produtoParaEditar['id'] : 'Adicionar Novo Produto' ?></h3>
                <form method="POST">
                    <input type="hidden" name="acao_admin" value="salvar_produto">
                    <input type="hidden" name="id" value="<?= $produtoParaEditar ? (int)$produtoParaEditar['id'] : '' ?>">

                    <label for="nome">Nome do Produto</label>
                    <input type="text" id="nome" name="nome" required value="<?= htmlspecialchars($produtoParaEditar['nome'] ?? '') ?>">

                    <label for="preco">Preço Original (R$)</label>
                    <input type="number" step="0.01" min="0.01" id="preco" name="preco" required value="<?= htmlspecialchars((string)($produtoParaEditar['preco'] ?? '')) ?>">

                    <label for="imagem">URL da Imagem (opcional)</label>
                    <input type="text" id="imagem" name="imagem" placeholder="https://..." value="<?= htmlspecialchars($produtoParaEditar['imagem'] ?? '') ?>">

                    <div class="box-promocao">
                        <?php $descAtual = (float)($produtoParaEditar['desconto_pct'] ?? 0); ?>
                        <label class="checkbox-linha">
                            <input type="checkbox" id="em_promocao" name="em_promocao" <?= $descAtual > 0 ? 'checked' : '' ?>>
                            Colocar este produto em promoção
                        </label>

                        <label for="desconto_pct">Porcentagem de Desconto (%)</label>
                        <input type="number" step="0.5" min="0" max="99" id="desconto_pct" name="desconto_pct" value="<?= htmlspecialchars((string)$descAtual) ?>">
                        <small class="texto-apoio">Para retirar o desconto, desmarque a caixa acima ou deixe em 0.</small>
                    </div>

                    <div class="acoes-form">
                        <button type="submit" class="btn-primario">
                            <?= $produtoParaEditar ? 'Salvar Alterações' : 'Cadastrar Produto' ?>
                        </button>
                        <?php if ($produtoParaEditar): ?>
                            <a href="admin.php" class="btn-secundario">Cancelar Edição</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <h3>Produtos Cadastrados</h3>
            <?php if (empty($produtos)): ?>
                <div class="card-form">
                    <p class="texto-apoio">Nenhum produto cadastrado no banco ainda. Use o formulário acima para adicionar.</p>
                </div>
            <?php else: ?>
                <div class="tabela-responsiva">
                    <table class="tabela-dados">
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th>Preço Base</th>
                                <th>Desconto</th>
                                <th>Preço Final</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($produtos as $prod):
                                $base = (float)$prod['preco'];
                                $pct = (float)$prod['desconto_pct'];
                                $final = precoComDesconto($base, $pct);
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($prod['nome']) ?></td>
                                    <td><?= formatarMoeda($base) ?></td>
                                    <td>
                                        <?php if ($pct > 0): ?>
                                            <span class="badge-promo"><?= rtrim(rtrim(number_format($pct, 2, '.', ''), '0'), '.') ?>%</span>
                                        <?php else: ?>
                                            <span>Sem desconto</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?= formatarMoeda($final) ?></strong></td>
                                    <td class="celula-acoes">
                                        <a href="admin.php?editar=<?= (int)$prod['id'] ?>" class="btn-acao-editar">Editar</a>

                                        <?php if ($pct > 0): ?>
                                            <form method="POST" class="form-inline">
                                                <input type="hidden" name="acao_admin" value="remover_desconto">
                                                <input type="hidden" name="id" value="<?= (int)$prod['id'] ?>">
                                                <button type="submit" class="btn-acao-alerta">Tirar Promoção</button>
                                            </form>
                                        <?php endif; ?>

                                        <form method="POST" class="form-inline" onsubmit="return confirm('Excluir este produto?');">
                                            <input type="hidden" name="acao_admin" value="excluir_produto">
                                            <input type="hidden" name="id" value="<?= (int)$prod['id'] ?>">
                                            <button type="submit" class="btn-acao-excluir">Excluir</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>

</body>
</html>