<?php
// Cabeçalhos HTTP de Segurança (Security Headers)
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; frame-src 'self' https://www.youtube.com https://www.youtube-nocookie.com;");

include 'db.php';

// Gatekeeper de Segurança: Ejeção imediata de acesso não autenticado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

/**
 * ENGENHARIA DE STRINGS: CONVERSOR AUTOMÁTICO DE URLS DO YOUTUBE
 * Filtra a string contra injeções e força o player na rota segura /embed/ do nocookie
 */
function converterLinkYoutube($url) {
    $padrao = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/i';
    
    if (preg_match($padrao, $url, $matches)) {
        $idVideo = $matches[1];
        // Força o uso do domínio de privacidade estrito do YouTube
        return "https://www.youtube-nocookie.com/embed/" . $idVideo;
    }
    return false;
}

$aulas = $pdo->query("SELECT * FROM aulas ORDER BY id ASC")->fetchAll();
$aulaSelecionadaId = isset($_GET['aula']) ? intval($_GET['aula']) : null;
$aulaAtual = null;

if (!empty($aulas)) {
    if ($aulaSelecionadaId) {
        foreach ($aulas as $aula) {
            if ($aula['id'] === $aulaSelecionadaId) {
                $aulaAtual = $aula;
                break;
            }
        }
    }
    if (!$aulaAtual) {
        $aulaAtual = $aulas[0];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ambiente de Aprendizado Seguro - DevAcademy</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f3f4f6; color: #1f2937; margin: 0; padding: 0; display: flex; flex-direction: column; height: 100vh; }
        header { background-color: #6d28d9; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        header h1 { margin: 0; font-size: 22px; font-weight: 700; letter-spacing: -0.5px; }
        header .user-info { display: flex; align-items: center; gap: 15px; }
        header .btn-sair { background-color: rgba(255,255,255,0.2); color: white; text-decoration: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; font-size: 14px; transition: background 0.2s; }
        header .btn-sair:hover { background-color: rgba(255,255,255,0.3); }

        .workspace { display: flex; flex: 1; overflow: hidden; }
        .player-area { flex: 3; padding: 30px; display: flex; flex-direction: column; gap: 20px; overflow-y: auto; background: #fafafa; }
        .video-container { position: relative; width: 100%; padding-bottom: 56.25%; height: 0; background-color: #000; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        .video-container iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: none; }
        .aula-titulo-exibicao { font-size: 24px; color: #5b21b6; margin: 0; font-weight: bold; }

        .sidebar { flex: 1; min-width: 300px; max-width: 400px; background: white; border-left: 1px solid #e5e7eb; overflow-y: auto; display: flex; flex-direction: column; }
        .sidebar-header { padding: 20px; border-bottom: 1px solid #e5e7eb; background: #f8f6ff; }
        .sidebar-header h3 { margin: 0; color: #6d28d9; }
        
        .playlist { list-style: none; padding: 0; margin: 0; }
        .playlist-item a { display: flex; align-items: center; gap: 15px; padding: 18px 20px; text-decoration: none; color: #4b5563; font-weight: 500; border-bottom: 1px solid #f3f4f6; transition: all 0.2s; }
        .playlist-item a:hover { background-color: #f5f3ff; color: #6d28d9; }
        .playlist-item.active a { background-color: #ede9fe; color: #5b21b6; font-weight: 700; border-left: 5px solid #6d28d9; padding-left: 15px; }

        .no-content { display: flex; justify-content: center; align-items: center; height: 100%; color: #9ca3af; flex-direction: column; font-size: 18px; }
    </style>
</head>
<body>

<header>
    <h1>DevAcademy <span style="font-weight: 300; font-size: 16px;">| Aluno Protegido</span></h1>
    <div class="user-info">
        <span>Olá, <strong><?= htmlspecialchars($_SESSION['usuario_nome'] ?? 'Estudante') ?></strong></span>
        <a href="logout.php" class="btn-sair">Sair</a>
    </div>
</header>

<div class="workspace">
    <?php if ($aulaAtual): ?>
        <div class="player-area">
            <?php $urlIncorporada = converterLinkYoutube($aulaAtual['url_video']); ?>
            
            <?php if ($urlIncorporada): ?>
                <div class="video-container">
                    <iframe 
                        src="<?= htmlspecialchars($urlIncorporada) ?>?rel=0&modestbranding=1" 
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                        allowfullscreen>
                    </iframe>
                </div>
            <?php else: ?>
                <div class="video-container" style="background: #1f2937; display: flex; align-items: center; justify-content: center; color: #ef4444;">
                    <p style="padding: 20px; font-weight:bold;">⚠️ Link incompatível com as políticas estritas de frames do YouTube.</p>
                </div>
            <?php endif; ?>

            <h2 class="aula-titulo-exibicao"><?= htmlspecialchars($aulaAtual['titulo']) ?></h2>
        </div>

        <div class="sidebar">
            <div class="sidebar-header">
                <h3>Conteúdo Programático</h3>
            </div>
            <ul class="playlist">
                <?php foreach ($aulas as $index => $aula): ?>
                    <li class="playlist-item <?= $aula['id'] === $aulaAtual['id'] ? 'active' : '' ?>">
                        <a href="cursos.php?aula=<?= $aula['id'] ?>">
                            <span style="opacity: 0.5; font-size: 13px;">[<?= sprintf("%02d", $index + 1) ?>]</span>
                            <span><?= htmlspecialchars($aula['titulo']) ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php else: ?>
        <div class="no-content">
            <p>Nenhuma aula foi localizada no repositório.</p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
