<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'conexao.php';

if (!isset($_GET['id'])) { header("Location: cadastro.php"); exit; }
$id = $_GET['id'];

// Buscar dados do país atual (incluindo a sigla)
$stmt = $pdo->prepare("SELECT * FROM paises WHERE id = ?");
$stmt->execute([$id]);
$pais = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$pais) { header("Location: cadastro.php"); exit; }

// Processar edição integrada
if (isset($_POST['atualizar_pais'])) {
    $nome = trim($_POST['nome_pais']);
    $sigla = strtoupper(trim($_POST['sigla_pais']));
    
    if (!empty($nome) && strlen($sigla) === 2) {
        // Atualiza tanto o nome quanto a sigla da bandeira
        $stmt = $pdo->prepare("UPDATE paises SET nome = ?, sigla = ? WHERE id = ?");
        $stmt->execute([$nome, $sigla, $id]);
        header("Location: cadastro.php?sucesso=pais");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar País</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 50px; background-color: #f4f4f9; color: #333; }
        .box { background: white; padding: 20px; border-radius: 8px; max-width: 500px; margin: 0 auto; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        label { display: block; margin-top: 15px; font-weight: bold; }
        input { width: 100%; padding: 10px; margin: 5px 0 15px 0; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        button { background: #0056b3; color: white; border: none; padding: 12px; width: 100%; border-radius: 4px; font-weight: bold; cursor: pointer; }
        button:hover { background: #004085; }
        a { display: block; text-align: center; margin-top: 15px; color: #555; text-decoration: none; font-weight: bold; }
        a:hover { color: #000; }
        .preview-bandeira { text-align: center; margin-bottom: 15px; }
        .flag-edit { width: 60px; height: 40px; border: 1px solid #ccc; border-radius: 4px; object-fit: cover; }
    </style>
</head>
<body>
    <div class="box">
        <h2>Editar País</h2>
        
        <div class="preview-bandeira">
            <p style="margin: 0 0 5px 0; font-size: 12px; color: #777;">Bandeira Atual:</p>
            <img class="flag-edit" id="img_preview" src="https://flagcdn.com/w80/<?=strtolower($pais['sigla'])?>.png" alt="Bandeira">
        </div>

        <form method="POST">
            <label>Nome do País:</label>
            <input type="text" id="nome_pais" name="nome_pais" value="<?=$pais['nome']?>" required oninput="autodetectarSigla()">
            
            <label>Sigla do País (2 letras):</label>
            <input type="text" id="sigla_pais" name="sigla_pais" value="<?=$pais['sigla']?>" maxlength="2" required oninput="atualizarPreview()">
            
            <button type="submit" name="atualizar_pais">Salvar Alterações</button>
        </form>
        <a href="cadastro.php">Cancelar e Voltar</a>
    </div>

    <script>
    // Dicionário inteligente para manter o comportamento automatizado na edição
    const dicionarioPaises = {
        "brasil": "BR", "brazil": "BR",
        "italia": "IT", "itália": "IT", "italy": "IT",
        "japao": "JP", "japão": "JP", "japan": "JP",
        "estados unidos": "US", "usa": "US", "eua": "US",
        "franca": "FR", "frança": "FR", "france": "FR",
        "polonia": "PL", "polônia": "PL", "poland": "PL",
        "servia": "RS", "sérvia": "RS", "serbia": "RS",
        "turquia": "TR", "turkey": "TR",
        "china": "CN",
        "alemanha": "DE", "germany": "DE",
        "argentina": "AR",
        "canada": "CA", "canadá": "CA",
        "holanda": "NL", "netherlands": "NL",
        "eslovenia": "SI", "eslovênia": "SI", "slovenia": "SI",
        "iran": "IR", "irã": "IR",
        "cuba": "CU",
        "bulgaria": "BG", "bulgária": "BG",
        "republica dominicana": "DO", "rep dominicana": "DO", "dominican republic": "DO",
        "tailandia": "TH", "tailândia": "TH", "thailand": "TH",
        "coreia": "KR", "coreia do sul": "KR", "korea": "KR"
    };

    function autodetectarSigla() {
        const nomeDigitado = document.getElementById('nome_pais').value.toLowerCase().trim();
        const campoSigla = document.getElementById('sigla_pais');
        
        const nomeLimpo = nomeDigitado.normalize("NFD").replace(/[\u0300-\u036f]/g, "");

        if (dicionarioPaises[nomeDigitado]) {
            campoSigla.value = dicionarioPaises[nomeDigitado];
        } else if (dicionarioPaises[nomeLimpo]) {
            campoSigla.value = dicionarioPaises[nomeLimpo];
        }
        atualizarPreview();
    }

    // Atualiza o desenho da bandeira na tela em tempo real caso a sigla mude
    function atualizarPreview() {
        const sigla = document.getElementById('sigla_pais').value.toLowerCase().trim();
        const img = document.getElementById('img_preview');
        if (sigla.length === 2) {
            img.src = "https://flagcdn.com/w80/" + sigla + ".png";
        }
    }
    </script>
</body>
</html>
