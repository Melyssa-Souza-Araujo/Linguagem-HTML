<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'conexao.php';

$erro = "";

if (isset($_POST['executar_reset'])) {
    $palavra = trim($_POST['palavra_seguranca']);
    
    // Confirmação estrita por palavra-chave para evitar acidentes
    if ($palavra === "RESETAR") {
        try {
            // 1. Limpa todas as partidas registradas
            $pdo->query("DELETE FROM partidas");
            
            // 2. Reinicia o contador da tabela de partidas de volta para o ID 1
            $pdo->query("ALTER TABLE partidas AUTO_INCREMENT = 1");
            
            header("Location: cadastro.php?sucesso=reset");
            exit;
        } catch (PDOException $e) {
            $erro = "Erro crítico ao resetar: " . $e->getMessage();
        }
    } else {
        $erro = "A palavra de segurança digitada está incorreta! O sistema não foi alterado.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Segurança - Reset VNL</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 50px; background-color: #1a0f0f; color: #f4f4f9; text-align: center; }
        .danger-card { background: #2d1a1a; padding: 40px; border-radius: 8px; border: 2px solid #e63946; max-width: 550px; margin: 0 auto; box-shadow: 0 4px 15px rgba(0,0,0,0.5); }
        input[type="text"] { width: 80%; padding: 12px; margin: 20px 0; border: 2px solid #e63946; background: #1a0f0f; color: #fff; text-align: center; font-size: 16px; font-weight: bold; letter-spacing: 2px; border-radius: 4px; }
        button { background: #e63946; color: white; border: none; padding: 12px 30px; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 14px; }
        button:hover { background: #b32430; }
        .voltar { display: block; margin-top: 25px; color: #a5a5a5; text-decoration: none; font-size: 14px; }
        .voltar:hover { color: #fff; }
    </style>
</head>
<body>

    <div class="danger-card">
        <h1 style="color: #e63946; margin-top:0;">⚠️ AÇÃO IRREVERSÍVEL</h1>
        <p>Esta operação irá **apagar definitivamente** todas as partidas e sets registrados no histórico, zerando completamente a pontuação de todas as tabelas.</p>
        <p style="color: #e76f51; font-weight: bold;">Os países cadastrados NÃO serão deletados.</p>
        
        <?php if (!empty($erro)): ?>
            <div style="background: #e63946; color: white; padding: 10px; margin-bottom: 20px; border-radius: 4px; font-weight: bold;"><?=$erro?></div>
        <?php endif; ?>

        <form method="POST" action="reset.php">
            <label style="font-size:14px; display:block; margin-top:20px;">Para confirmar, digite a palavra <strong>RESETAR</strong> abaixo:</label>
            <input type="text" name="palavra_seguranca" placeholder="DIGITE AQUI" autocomplete="off" required>
            <br>
            <button type="submit" name="executar_reset" onclick="return confirm('Tem certeza absoluta que deseja limpar o histórico de jogos?')">Limpar Histórico e Resetar Pontuações</button>
        </form>

        <a href="cadastro.php" class="voltar">← Cancelar e Voltar ao Gerenciador</a>
    </div>

</body>
</html>
