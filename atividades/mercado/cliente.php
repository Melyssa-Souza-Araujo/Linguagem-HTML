<?php
session_start();
require_once 'conexao.php';

$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao_cliente'] ?? '';

    if ($acao === 'cadastrar') {
        $nome = trim($_POST['nome'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $senha = $_POST['senha'] ?? '';

        if ($nome === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($senha) < 4) {
            $erro = 'Preencha nome, um e-mail válido e senha com pelo menos 4 caracteres.';
        } else {
            $stmtCheck = $pdo->prepare('SELECT id FROM clientes WHERE email = :email');
            $stmtCheck->execute([':email' => $email]);

            if ($stmtCheck->fetch()) {
                $erro = 'Já existe um cliente cadastrado com este e-mail.';
            } else {
                $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
                $stmtInsert = $pdo->prepare(
                    'INSERT INTO clientes (nome, email, senha_hash) VALUES (:nome, :email, :senha_hash)'
                );
                $stmtInsert->execute([
                    ':nome' => $nome,
                    ':email' => $email,
                    ':senha_hash' => $senhaHash,
                ]);

                $_SESSION['cliente'] = [
                    'id' => (int)$pdo->lastInsertId(),
                    'nome' => $nome,
                    'email' => $email,
                ];
                $mensagem = 'Cadastro concluído! Você já está conectado.';
            }
        }
    } elseif ($acao === 'login') {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $senha = $_POST['senha'] ?? '';

        $stmt = $pdo->prepare('SELECT id, nome, email, senha_hash FROM clientes WHERE email = :email');
        $stmt->execute([':email' => $email]);
        $cliente = $stmt->fetch();

        if (!$cliente || !password_verify($senha, $cliente['senha_hash'])) {
            $erro = 'E-mail ou senha inválidos.';
        } else {
            $_SESSION['cliente'] = [
                'id' => (int)$cliente['id'],
                'nome' => $cliente['nome'],
                'email' => $cliente['email'],
            ];
            $mensagem = 'Login realizado com sucesso!';
        }
    } elseif ($acao === 'logout') {
        unset($_SESSION['cliente']);
        $mensagem = 'Você saiu da sua conta.';
    }
}

$clienteAtual = $_SESSION['cliente'] ?? null;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Área do Cliente - SuperMercado</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <header>
        <a href="index.php" class="logo">🛒 SuperMercado</a>
        <nav class="nav-acoes">
            <a href="index.php" class="link-header">Voltar à Loja</a>
            <a href="admin.php" class="link-header">Admin</a>
        </nav>
    </header>

    <main class="container-estreito">
        <h2>Área do Cliente</h2>

        <?php if ($mensagem): ?>
            <div class="alerta sucesso"><?= htmlspecialchars($mensagem) ?></div>
        <?php endif; ?>
        <?php if ($erro): ?>
            <div class="alerta erro"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <?php if ($clienteAtual): ?>
            <div class="card-form">
                <h3>Dados da Conta</h3>
                <p><strong>Nome:</strong> <?= htmlspecialchars($clienteAtual['nome']) ?></p>
                <p><strong>E-mail:</strong> <?= htmlspecialchars($clienteAtual['email']) ?></p>

                <form method="POST" class="espaco-topo">
                    <input type="hidden" name="acao_cliente" value="logout">
                    <button type="submit" class="btn-acao-excluir">Sair da Conta</button>
                </form>
            </div>
        <?php else: ?>
            <div class="grid-auth">
                <div class="card-form">
                    <h3>Já tenho conta (Login)</h3>
                    <form method="POST">
                        <input type="hidden" name="acao_cliente" value="login">

                        <label for="login_email">E-mail</label>
                        <input type="email" id="login_email" name="email" required>

                        <label for="login_senha">Senha</label>
                        <input type="password" id="login_senha" name="senha" required>

                        <button type="submit" class="btn-primario">Entrar</button>
                    </form>
                </div>

                <div class="card-form">
                    <h3>Criar Conta (Cadastro)</h3>
                    <form method="POST">
                        <input type="hidden" name="acao_cliente" value="cadastrar">

                        <label for="cad_nome">Nome Completo</label>
                        <input type="text" id="cad_nome" name="nome" required>

                        <label for="cad_email">E-mail</label>
                        <input type="email" id="cad_email" name="email" required>

                        <label for="cad_senha">Senha</label>
                        <input type="password" id="cad_senha" name="senha" minlength="4" required>

                        <button type="submit" class="btn-primario">Cadastrar</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </main>

</body>
</html>