<?php
require_once 'conexao.php';
verificarAdmin(); // Só entra se for Administrador

$sucesso = '';

// Buscar todos os gêneros para listar no formulário
$generos = $pdo->query("SELECT * FROM generos ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo']);
    $sinopse = trim($_POST['sinopse']);
    $tipo = $_POST['tipo'];
    $ano = (int)$_POST['ano'];
    $avaliacao = (float)$_POST['avaliacao'];
    
    // Dados do Episódio/Vídeo
    $temporada = !empty($_POST['temporada']) ? (int)$_POST['temporada'] : null;
    $numero_episodio = !empty($_POST['numero_episodio']) ? (int)$_POST['numero_episodio'] : null;
    $titulo_episodio = trim($_POST['titulo_episodio']);
    $tipo_midia = $_POST['tipo_midia']; // 'link' ou 'arquivo'
    
    // 1. Upload da Imagem de Capa
    $imagem_capa = 'https://via.placeholder.com/300x450';
    if (isset($_FILES['capa']) && $_FILES['capa']['error'] === UPLOAD_ERR_OK) {
        $extensao = pathinfo($_FILES['capa']['name'], PATHINFO_EXTENSION);
        $nome_capa = uniqid() . '.' . $extensao;
        if (!is_dir('uploads')) { mkdir('uploads', 0777, true); }
        move_uploaded_file($_FILES['capa']['tmp_temp_name'] ?? $_FILES['capa']['tmp_name'], 'uploads/' . $nome_capa);
        $imagem_capa = 'uploads/' . $nome_capa;
    }

    // 2. Processar arquivo ou link de mídia
    $origem_midia = '';
    if ($tipo_midia === 'link') {
        $origem_midia = trim($_POST['midia_link']);
    } else if ($tipo_midia === 'arquivo' && isset($_FILES['midia_arquivo']) && $_FILES['midia_arquivo']['error'] === UPLOAD_ERR_OK) {
        $extensao_video = pathinfo($_FILES['midia_arquivo']['name'], PATHINFO_EXTENSION);
        $nome_video = uniqid() . '.' . $extensao_video;
        if (!is_dir('uploads')) { mkdir('uploads', 0777, true); }
        move_uploaded_file($_FILES['midia_arquivo']['tmp_name'], 'uploads/' . $nome_video);
        $origem_midia = 'uploads/' . $nome_video;
    }

    // 3. Salvar Mídia no Banco
    $stmt = $pdo->prepare("INSERT INTO midias (titulo, sinopse, tipo, imagem_capa, ano, avaliacao) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$titulo, $sinopse, $tipo, $imagem_capa, $ano, $avaliacao]);
    $midia_id = $pdo->lastInsertId();

    // 4. Salvar múltiplos Gêneros associados
    if (isset($_POST['generos_selecionados']) && is_array($_POST['generos_selecionados'])) {
        foreach ($_POST['generos_selecionados'] as $genero_id) {
            $stmt_gen = $pdo->prepare("INSERT INTO midia_genero (midia_id, genero_id) VALUES (?, ?)");
            $stmt_gen->execute([$midia_id, (int)$genero_id]);
        }
    }

    // 5. Salvar o arquivo de vídeo ou link na tabela de conteúdos
    $stmt_content = $pdo->prepare("INSERT INTO conteudos_midia (midia_id, temporada, numero_episodio, titulo_episodio, tipo_midia, origem_midia) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt_content->execute([$midia_id, $temporada, $numero_episodio, $titulo_episodio, $tipo_midia, $origem_midia]);

    $sucesso = "Conteúdo cadastrado com sucesso!";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Painel Admin - Pobreflix</title>
    <style>
        body { background-color: #060913; color: white; font-family: sans-serif; padding: 30px; }
        .container { max-width: 700px; margin: 0 auto; background-color: #0d1222; padding: 30px; border-radius: 8px; }
        h1, h3 { color: #0084ff; }
        input, select, textarea { width: 100%; padding: 10px; margin: 10px 0; border: none; border-radius: 4px; background: #161f38; color: white; box-sizing: border-box; }
        .generos-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin: 10px 0; }
        .genero-item { display: flex; align-items: center; gap: 5px; }
        .genero-item input { width: auto; margin: 0; }
        .row { display: flex; gap: 15px; }
        .row > div { flex: 1; }
        button { background-color: #27ae60; color: white; padding: 12px 20px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; width: 100%; margin-top: 15px;}
        .sucesso { color: #2ecc71; text-align: center; font-weight: bold; margin-bottom: 15px; }
        .logout-btn { background: #e74c3c; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; float: right; }
        .condicional-serie { display: none; background: #111729; padding: 15px; border-radius: 6px; margin-top: 10px; }
    </style>
</head>
<body>

<div class="container">
    <a href="logout.php" class="logout-btn">Sair</a>
    <a href="index.php" style="color: #0084ff; text-decoration: none; font-size: 14px;">← Ver Catálogo</a>
    <h1>Cadastrar Filme ou Série</h1>
    
    <?php if($sucesso): ?> <div class="sucesso"><?= $sucesso ?></div> <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <label>Título do Conteúdo</label>
        <input type="text" name="titulo" required placeholder="Ex: A Casa do Dragão">

        <label>Sinopse</label>
        <textarea name="sinopse" rows="4" required placeholder="Escreva a sinopse aqui..."></textarea>

        <div class="row">
            <div>
                <label>Tipo</label>
                <select name="tipo" id="tipo" onchange="toggleSerieCampos()" required>
                    <option value="filme">Filme</option>
                    <option value="serie">Série</option>
                </select>
            </div>
            <div>
                <label>Ano de Lançamento</label>
                <input type="number" name="ano" required value="2026">
            </div>
            <div>
                <label>Nota de Avaliação</label>
                <input type="number" step="0.1" max="10" name="avaliacao" value="8.5">
            </div>
        </div>

        <label>Selecione os Gêneros (Pode marcar vários)</label>
        <div class="generos-grid">
            <?php foreach($generos as $g): ?>
                <div class="genero-item">
                    <input type="checkbox" name="generos_selecionados[]" value="<?= $g['id'] ?>" id="gen_<?= $g['id'] ?>">
                    <label Kakao for="gen_<?= $g['id'] ?>"><?= $g['nome'] ?></label>
                </div>
            <?php endforeach; ?>
        </div>

        <label>Imagem de Capa (Poster)</label>
        <input type="file" name="capa" accept="image/*" required>

        <div id="campos-serie" class="condicional-serie">
            <h3>Informações do Episódio</h3>
            <div class="row">
                <div>
                    <label>Temporada</label>
                    <input type="number" name="temporada" placeholder="Ex: 1">
                </div>
                <div>
                    <label>Nº do Episódio</label>
                    <input type="number" name="numero_episodio" placeholder="Ex: 1">
                </div>
            </div>
            <label>Título do Episódio</label>
            <input type="text" name="titulo_episodio" placeholder="Ex: Os Herdeiros do Dragão">
        </div>

        <h3>Origem da Mídia (Vídeo)</h3>
        <label>Como deseja adicionar o vídeo?</label>
        <select name="tipo_midia" id="tipo_midia" onchange="toggleMidiaCampos()" required>
            <option value="link">Link Externo / URL Embed (YouTube, Vimeo, Streamtape)</option>
            <option value="arquivo">Fazer Upload de arquivo MP4</option>
        </select>

        <div id="campo-link">
            <label>Link do Vídeo ou Iframe URL</label>
            <input type="text" name="midia_link" placeholder="https://exemplo.com/player/video.mp4">
        </div>

        <div id="campo-arquivo" style="display: none;">
            <label>Selecione o arquivo MP4</label>
            <input type="file" name="midia_arquivo" accept="video/mp4">
        </div>

        <button type="submit">SALVAR NO CATÁLOGO</button>
    </form>
</div>

<script>
function toggleSerieCampos() {
    const tipo = document.getElementById('tipo').value;
    const camposSerie = document.getElementById('campos-serie');
    camposSerie.style.display = (tipo === 'serie') ? 'block' : 'none';
}

function toggleMidiaCampos() {
    const tipoMidia = document.getElementById('tipo_midia').value;
    document.getElementById('campo-link').style.display = (tipoMidia === 'link') ? 'block' : 'none';
    document.getElementById('campo-arquivo').style.display = (tipoMidia === 'arquivo') ? 'block' : 'none';
}
</script>
</body>
</html>
