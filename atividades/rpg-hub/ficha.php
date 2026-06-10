<?php
// ficha.php
require_once 'db.php';

// Segurança: Se não houver uma sessão ativa, manda de volta para o login
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$usuario_logado = $_SESSION['user_id'];

// Lógica de Salvamento via POST (Atualiza a ficha do usuário logado)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_ficha'])) {
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS);
    $vida_max = filter_input(INPUT_POST, 'vida_max', FILTER_SANITIZE_NUMBER_INT);
    $vida_atual = filter_input(INPUT_POST, 'vida_atual', FILTER_SANITIZE_NUMBER_INT);
    $forca = filter_input(INPUT_POST, 'forca', FILTER_SANITIZE_NUMBER_INT);
    $destreza = filter_input(INPUT_POST, 'destreza', FILTER_SANITIZE_NUMBER_INT);
    $constituicao = filter_input(INPUT_POST, 'constituicao', FILTER_SANITIZE_NUMBER_INT);

    // Verifica se o usuário já tem uma ficha criada
    $check = $pdo->prepare("SELECT id FROM personagens WHERE usuario_id = ?");
    $check->execute([$usuario_logado]);
    
    if ($check->fetch()) {
        // Se já existe, atualiza
        $stmt = $pdo->prepare("UPDATE personagens SET nome = ?, vida_max = ?, vida_atual = ?, forca = ?, destreza = ?, constituicao = ? WHERE usuario_id = ?");
        $stmt->execute([$nome, $vida_max, $vida_atual, $forca, $destreza, $constituicao, $usuario_logado]);
    } else {
        // Se for a primeira vez salvando, cria o registro atrelando ao usuário
        $stmt = $pdo->prepare("INSERT INTO personagens (usuario_id, nome, vida_max, vida_atual, forca, destreza, constituicao) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$usuario_logado, $nome, $vida_max, $vida_atual, $forca, $destreza, $constituicao]);
    }
    echo "<script>alert('Ficha atualizada e salva no banco de dados!');</script>";
}

// CONECTADO: Busca os dados do personagem do usuário que está logado na sessão
$stmt = $pdo->prepare("SELECT * FROM personagens WHERE usuario_id = ?");
$stmt->execute([$usuario_logado]);
$p = $stmt->fetch();

// Se o usuário acabou de criar a conta e não salvou nada ainda, gera valores temporários na tela
if (!$p) {
    $p = [
        'nome' => 'Novo Herói de ' . $_SESSION['user_nome'],
        'vida_max' => 20,
        'vida_atual' => 20,
        'forca' => 10,
        'destreza' => 10,
        'constituicao' => 10
    ];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Ficha de Personagem - RPG Hub</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .status-bar {
            background-color: #334155;
            border-radius: 20px;
            overflow: hidden;
            margin: 10px 0;
        }
        .hp-fill {
            background: linear-gradient(90deg, #ef4444, #b91c1c);
            height: 20px;
            width: 100%;
            transition: width 0.3s ease;
        }
        .flex-row {
            display: flex;
            gap: 15px;
            align-items: center;
            margin-top: 10px;
        }
        .modificador {
            font-weight: bold;
            color: var(--accent);
            margin-left: 10px;
        }
        .dice-log {
            background-color: var(--bg-dark);
            border: 1px solid var(--primary);
            padding: 15px;
            border-radius: 4px;
            min-height: 80px;
            margin-top: 15px;
            font-family: monospace;
        }
    </style>
</head>
<body>

<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <span style="color: var(--text-muted);">Logado como: <strong><?= htmlspecialchars($_SESSION['user_nome']) ?></strong> (<?= ucfirst($_SESSION['user_tipo']) ?>)</span>
        <!-- Botão para destruir a sessão e sair do app de forma segura -->
        <a href="logout.php" class="btn btn-danger" style="padding: 5px 15px;">Sair da Conta</a>
    </div>

    <form method="POST">
        <input type="hidden" name="salvar_ficha" value="1">
        
        <div class="grid">
            <!-- COLUNA 1: DADOS E ATRIBUTOS -->
            <div class="card">
                <h2>📜 Identidade do Herói</h2>
                <div class="form-group">
                    <label>Nome do Personagem</label>
                    <input type="text" name="nome" value="<?= htmlspecialchars($p['nome']) ?>" required>
                </div>

                <h3 style="margin-top: 25px; margin-bottom: 10px;">⚔️ Atributos</h3>
                
                <div class="form-group">
                    <label>Força (FOR)</label>
                    <div class="flex-row">
                        <input type="number" name="forca" id="forca" value="<?= $p['forca'] ?>" oninput="calcularModificadores()">
                        <span class="modificador" id="mod_forca">+0</span>
                    </div>
                </div>

                <div class="form-group">
                    <label>Destreza (DES)</label>
                    <div class="flex-row">
                        <input type="number" name="destreza" id="destreza" value="<?= $p['destreza'] ?>" oninput="calcularModificadores()">
                        <span class="modificador" id="mod_destreza">+0</span>
                    </div>
                </div>

                <div class="form-group">
                    <label>Constituição (CON)</label>
                    <div class="flex-row">
                        <input type="number" name="constituicao" id="constituicao" value="<?= $p['constituicao'] ?>" oninput="calcularModificadores()">
                        <span class="modificador" id="mod_constituicao">+0</span>
                    </div>
                </div>

                <button type="submit" class="btn" style="margin-top: 20px; width: 100%;">💾 Salvar Alterações no Banco</button>
            </div>

            <!-- COLUNA 2: GERENCIAMENTO DE VIDA & DADOS -->
            <div>
                <div class="card">
                    <h2>❤️ Pontos de Vida (HP)</h2>
                    <div class="flex-row">
                        <div>
                            <label>Atual</label>
                            <input type="number" name="vida_atual" id="vida_atual" value="<?= $p['vida_atual'] ?>" oninput="atualizarBarraHP()">
                        </div>
                        <div style="font-size: 24px; align-self: flex-end; padding-bottom: 5px;">/</div>
                        <div>
                            <label>Máximo</label>
                            <input type="number" name="vida_max" id="vida_max" value="<?= $p['vida_max'] ?>" oninput="atualizarBarraHP()">
                        </div>
                    </div>

                    <div class="status-bar">
                        <div class="hp-fill" id="hp_barra"></div>
                    </div>

                    <div style="margin-top: 20px; border-top: 1px solid var(--primary); padding-top: 15px;">
                        <label>Modificador Rápido de HP</label>
                        <div class="flex-row">
                            <input type="number" id="valor_modificador_hp" placeholder="Ex: 10 ou -5">
                            <button type="button" class="btn" onclick="aplicarHP(true)" style="background-color: #22c55e;">Curar</button>
                            <button type="button" class="btn btn-danger" onclick="aplicarHP(false)">Dano</button>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <h2>🎲 Torre de Dados Interativa</h2>
                    <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div class="form-group">
                            <label>Escolha o Dado</label>
                            <select id="tipo_dado">
                                <option value="20">D20</option>
                                <option value="6">D6</option>
                                <option value="8">D8</option>
                                <option value="10">D10</option>
                                <option value="12">D12</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Modificador Extra</label>
                            <input type="number" id="modificador_dado" value="0">
                        </div>
                    </div>
                    <button type="button" class="btn" onclick="rolarDado()" style="width: 100%;">Rolar Dados!</button>
                    
                    <div class="dice-log" id="resultado_dados">
                        O resultado da sua rolagem aparecerá aqui...
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function calcularModificadores() {
    const atributos = ['forca', 'destreza', 'constituicao'];
    atributos.forEach(attr => {
        const valor = parseInt(document.getElementById(attr).value) || 0;
        const mod = Math.floor((valor - 10) / 2);
        const span = document.getElementById('mod_' + attr);
        span.textContent = mod >= 0 ? '+' + mod : mod;
        span.style.color = mod >= 0 ? '#3b82f6' : '#ef4444';
    });
}

function atualizarBarraHP() {
    const atual = parseInt(document.getElementById('vida_atual').value) || 0;
    const max = parseInt(document.getElementById('vida_max').value) || 1;
    let porcentagem = (atual / max) * 100;
    if (porcentagem > 100) porcentagem = 100;
    if (porcentagem < 0) porous = 0;
    document.getElementById('hp_barra').style.width = porcentagem + '%';
}

function aplicarHP(isCura) {
    const inputMod = document.getElementById('valor_modificador_hp');
    const valor = Math.abs(parseInt(inputMod.value)) || 0;
    const inputAtual = document.getElementById('vida_atual');
    let atual = parseInt(inputAtual.value) || 0;
    const max = parseInt(document.getElementById('vida_max').value) || 0;
    
    if (isCura) {
        atual += valor;
        if (atual > max) atual = max;
    } else {
        atual -= valor;
        if (atual < 0) atual = 0;
    }
    inputAtual.value = atual;
    inputMod.value = '';
    atualizarBarraHP();
}

function rolarDado() {
    const faces = parseInt(document.getElementById('tipo_dado').value);
    const mod = parseInt(document.getElementById('modificador_dado').value) || 0;
    const resultadoDado = Math.floor(Math.random() * faces) + 1;
    const totalGeral = resultadoDado + mod;
    const sinalMod = mod >= 0 ? ' + ' + mod : ' - ' + Math.abs(mod);
    
    document.getElementById('resultado_dados').innerHTML = `
        <strong style="font-size: 18px;">Resultado: ${totalGeral}</strong><br>
        <span style="color: var(--text-muted);">Detalhes: D${faces} tirou [${resultadoDado}] ${sinalMod}</span>
    `;
}

window.onload = function() {
    calcularModificadores();
    atualizarBarraHP();
};
</script>
</body>
</html>
