<?php
session_start();
require_once 'conexao.php';
require_once 'helpers.php';

$stmt = $pdo->query('SELECT id, nome, preco, desconto_pct, imagem FROM produtos ORDER BY id DESC');
$produtos = $stmt->fetchAll();

$clienteLogado = $_SESSION['cliente'] ?? null;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SuperMercado - Início</title>
    <style>
        * {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    font-family: Arial, sans-serif;
}

body {
    background-color: #f4f4f9;
    color: #333;
}

header {
    background-color: #2e7d32;
    color: white;
    padding: 15px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
}

.logo {
    color: white;
    text-decoration: none;
    font-size: 22px;
    font-weight: bold;
}

.nav-acoes {
    display: flex;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
}

.link-header {
    color: white;
    text-decoration: none;
    font-weight: bold;
}

.link-header:hover {
    text-decoration: underline;
}

.boas-vindas {
    font-size: 14px;
    background: rgba(255, 255, 255, 0.15);
    padding: 6px 10px;
    border-radius: 4px;
}

header button {
    background-color: #ff9800;
    color: white;
    border: none;
    padding: 10px 15px;
    font-size: 15px;
    font-weight: bold;
    border-radius: 5px;
    cursor: pointer;
}

main {
    max-width: 1100px;
    margin: 30px auto;
    padding: 0 20px;
}

.container-estreito {
    max-width: 960px;
}

h2 {
    margin-bottom: 20px;
}

h3 {
    margin-bottom: 12px;
}

.grade-produtos {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 20px;
}

.card-produto {
    position: relative;
    background: white;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    text-align: center;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.selo-promocao {
    position: absolute;
    top: 10px;
    left: 10px;
    background-color: #d32f2f;
    color: white;
    font-size: 12px;
    font-weight: bold;
    padding: 4px 8px;
    border-radius: 4px;
}

.card-produto img {
    max-width: 100%;
    height: 150px;
    object-fit: cover;
    border-radius: 5px;
    margin-bottom: 10px;
}

.bloco-preco {
    margin: 10px 0;
}

.preco-antigo {
    display: block;
    font-size: 13px;
    color: #888;
    text-decoration: line-through;
}

.card-produto .preco {
    font-size: 18px;
    font-weight: bold;
    color: #2e7d32;
}

.card-produto button,
.btn-primario {
    background-color: #2e7d32;
    color: white;
    border: none;
    padding: 10px 14px;
    border-radius: 5px;
    cursor: pointer;
    font-weight: bold;
    text-decoration: none;
    display: inline-block;
}

.card-produto button:hover,
.btn-primario:hover {
    background-color: #1b5e20;
}

.btn-secundario {
    background-color: #757575;
    color: white;
    padding: 10px 14px;
    border-radius: 5px;
    text-decoration: none;
    font-weight: bold;
}

/* Formulários e Área Cliente / Admin */
.grid-auth {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
}

.card-form {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.08);
    margin-bottom: 24px;
}

.card-form label {
    display: block;
    margin-top: 12px;
    margin-bottom: 5px;
    font-weight: bold;
    font-size: 14px;
}

.card-form input[type="text"],
.card-form input[type="email"],
.card-form input[type="password"],
.card-form input[type="number"] {
    width: 100%;
    padding: 9px 10px;
    border: 1px solid #ccc;
    border-radius: 5px;
    font-size: 15px;
}

.box-promocao {
    margin-top: 16px;
    padding: 14px;
    background-color: #fff8e1;
    border: 1px solid #ffe082;
    border-radius: 6px;
}

.checkbox-linha {
    display: flex !important;
    align-items: center;
    gap: 8px;
    margin-top: 0 !important;
    cursor: pointer;
}

.checkbox-linha input {
    width: auto;
}

.texto-apoio {
    display: block;
    margin-top: 6px;
    color: #666;
    font-size: 13px;
}

.acoes-form {
    margin-top: 18px;
    display: flex;
    gap: 10px;
    align-items: center;
}

.alerta {
    padding: 12px 16px;
    border-radius: 6px;
    margin-bottom: 18px;
    font-weight: bold;
}

.alerta.sucesso {
    background-color: #e8f5e9;
    color: #1b5e20;
    border: 1px solid #a5d6a7;
}

.alerta.erro {
    background-color: #ffebee;
    color: #b71c1c;
    border: 1px solid #ef9a9a;
}

.tabela-responsiva {
    overflow-x: auto;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.08);
}

.tabela-dados {
    width: 100%;
    border-collapse: collapse;
}

.tabela-dados th,
.tabela-dados td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.badge-promo {
    background-color: #d32f2f;
    color: white;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
}

.celula-acoes {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.form-inline {
    display: inline;
}

.btn-acao-editar {
    background-color: #1976d2;
    color: white;
    text-decoration: none;
    padding: 5px 9px;
    border-radius: 4px;
    font-size: 13px;
}

.btn-acao-alerta {
    background-color: #f57c00;
    color: white;
    border: none;
    padding: 5px 9px;
    border-radius: 4px;
    font-size: 13px;
    cursor: pointer;
}

.btn-acao-excluir {
    background-color: #d32f2f;
    color: white;
    border: none;
    padding: 6px 10px;
    border-radius: 4px;
    font-size: 13px;
    cursor: pointer;
}

.espaco-topo {
    margin-top: 15px;
}

/* Gaveta Lateral do Carrinho */
.carrinho-container {
    position: fixed;
    top: 0;
    right: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: flex-end;
    z-index: 1000;
}

.oculto {
    display: none;
}

.carrinho-conteudo {
    background: white;
    width: 400px;
    max-width: 100%;
    height: 100%;
    padding: 20px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.carrinho-topo {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #ddd;
    padding-bottom: 10px;
}

.btn-fechar {
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
}

#lista-itens-carrinho {
    flex-grow: 1;
    overflow-y: auto;
    margin: 15px 0;
}

.item-carrinho {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #eee;
    padding: 10px 0;
}

.controles-qtd button {
    padding: 3px 8px;
    margin: 0 4px;
    cursor: pointer;
}

.btn-remover {
    background-color: #d32f2f;
    color: white;
    border: none;
    padding: 5px 8px;
    border-radius: 3px;
    cursor: pointer;
}

.carrinho-rodape {
    border-top: 2px solid #ddd;
    padding-top: 15px;
    text-align: right;
}
    </style>
</head>
<body>

    <header>
        <a href="index.php" class="logo">🛒 SuperMercado</a>

        <nav class="nav-acoes">
            <?php if ($clienteLogado): ?>
                <span class="boas-vindas">Olá, <?= htmlspecialchars($clienteLogado['nome']) ?></span>
                <a href="cliente.php" class="link-header">Minha Conta</a>
            <?php else: ?>
                <a href="cliente.php" class="link-header">Entrar / Cadastrar</a>
            <?php endif; ?>

            <a href="admin.php" class="link-header">Admin</a>

            <button id="btn-abrir-carrinho" onclick="toggleCarrinho()">
                Carrinho (<span id="contador-carrinho">0</span>)
            </button>
        </nav>
    </header>

    <main>
        <h2>Produtos</h2>

        <?php if (empty($produtos)): ?>
            <div class="card-form">
                <p>Nenhum produto cadastrado ainda.</p>
                <p class="texto-apoio">Acesse o <a href="admin.php">Painel Admin</a> para cadastrar os primeiros produtos.</p>
            </div>
        <?php else: ?>
            <div class="grade-produtos">
                <?php foreach ($produtos as $produto):
                    $id = (int)$produto['id'];
                    $precoOriginal = (float)$produto['preco'];
                    $desconto = (float)$produto['desconto_pct'];
                    $temPromocao = $desconto > 0;
                    $precoFinal = precoComDesconto($precoOriginal, $desconto);
                    $img = !empty($produto['imagem'])
                        ? $produto['imagem']
                        : 'https://placehold.co/200x150?text=' . urlencode($produto['nome']);
                ?>
                    <div class="card-produto">
                        <?php if ($temPromocao): ?>
                            <span class="selo-promocao">-<?= rtrim(rtrim(number_format($desconto, 2, '.', ''), '0'), '.') ?>% OFF</span>
                        <?php endif; ?>

                        <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($produto['nome']) ?>">
                        <h3><?= htmlspecialchars($produto['nome']) ?></h3>

                        <div class="bloco-preco">
                            <?php if ($temPromocao): ?>
                                <span class="preco-antigo"><?= formatarMoeda($precoOriginal) ?></span>
                            <?php endif; ?>
                            <p class="preco"><?= formatarMoeda($precoFinal) ?></p>
                        </div>

                        <button onclick="gerenciarCarrinho('adicionar', <?= $id ?>)">
                            Adicionar ao Carrinho
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <!-- Gaveta Lateral do Carrinho -->
    <div id="modal-carrinho" class="carrinho-container oculto">
        <div class="carrinho-conteudo">
            <div class="carrinho-topo">
                <h2>Seu Carrinho</h2>
                <button class="btn-fechar" onclick="toggleCarrinho()">✖</button>
            </div>

            <div id="lista-itens-carrinho"></div>

            <div class="carrinho-rodape">
                <h3>Total: R$ <span id="valor-total">0,00</span></h3>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
    gerenciarCarrinho('listar');
});

function toggleCarrinho() {
    const modal = document.getElementById('modal-carrinho');
    modal.classList.toggle('oculto');
}

function gerenciarCarrinho(acao, id = 0) {
    const formData = new FormData();
    formData.append('acao', acao);
    formData.append('id', id);

    fetch('api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(dados => {
        atualizarInterface(dados);
    })
    .catch(erro => console.error('Erro ao atualizar carrinho:', erro));
}

function atualizarInterface(dados) {
    const lista = document.getElementById('lista-itens-carrinho');
    const contador = document.getElementById('contador-carrinho');
    const valorTotal = document.getElementById('valor-total');

    contador.innerText = dados.totalItens;
    valorTotal.innerText = dados.totalGeral.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    lista.innerHTML = '';

    if (dados.itens.length === 0) {
        lista.innerHTML = '<p style="text-align:center; color:#777; margin-top:20px;">Seu carrinho está vazio.</p>';
        return;
    }

    dados.itens.forEach(item => {
        const subtotal = (item.preco * item.quantidade).toLocaleString('pt-BR', { minimumFractionDigits: 2 });

        lista.innerHTML += `
            <div class="item-carrinho">
                <div>
                    <strong>${item.nome}</strong><br>
                    <small>R$ ${item.preco.toFixed(2).replace('.', ',')} un. | Sub: R$ ${subtotal}</small>
                </div>
                <div class="controles-qtd">
                    <button onclick="gerenciarCarrinho('diminuir', ${item.id})">-</button>
                    <span>${item.quantidade}</span>
                    <button onclick="gerenciarCarrinho('adicionar', ${item.id})">+</button>
                    <button class="btn-remover" onclick="gerenciarCarrinho('remover', ${item.id})">🗑️</button>
                </div>
            </div>
        `;
    });
}
    </script>
</body>
</html>
