<?php
session_start();
include 'db.php';

class AulaModel {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function listarTodas(): array {
        return $this->pdo->query("SELECT * FROM aulas ORDER BY id DESC")->fetchAll();
    }

    public function inserir(string $titulo, string $url): int|false {
        $stmt = $this->pdo->prepare("INSERT INTO aulas (titulo, url_video) VALUES (?, ?)");
        if ($stmt->execute([$titulo, $url])) {
            return (int)$this->pdo->lastInsertId();
        }
        return false;
    }

    public function excluir(int $id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM aulas WHERE id = ?");
        return $stmt->execute([$id]);
    }
}

class AdminController {
    private AulaModel $model;

    public function __construct(AulaModel $model) {
        $this->model = $model;
    }

    public function verificarAutenticacao(): void {
        if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
            header("Location: index.php");
            exit;
        }
    }

 
    public function rotear(): array|null {
          if (isset($_GET['excluir_async'])) {
            $this->enviarJsonHeaders();
            $id = intval($_GET['excluir_async']);
            
            if ($this->model->excluir($id)) {
                echo json_encode(['success' => true, 'message' => 'Aula removida com sucesso via OOP!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Falha interna da Model ao excluir.']);
            }
            exit;
        }

        if (isset($_POST['adicionar_async'])) {
            $this->enviarJsonHeaders();
            $titulo = trim($_POST['titulo'] ?? '');
            $url = trim($_POST['url_video'] ?? '');

            if (empty($titulo) || empty($url)) {
                echo json_encode(['success' => false, 'message' => 'Dados inválidos interceptados pela Controller.']);
                exit;
            }

            $novoId = $this->model->inserir($titulo, $url);
            if ($novoId) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Aula cadastrada com sucesso via MVC!',
                    'aula' => ['id' => $novoId, 'titulo' => $titulo, 'url_video' => $url]
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro de persistência na Model.']);
            }
            exit;
        }

        return $this->model->listarTodas();
    }

    private function enviarJsonHeaders(): void {
        header('Content-Type: application/json; charset=utf-8');
    }
}

$aulaModel = new AulaModel($pdo);
$controller = new AdminController($aulaModel);
$controller->verificarAutenticacao();
$aulas = $controller->rotear();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo (MVC/POO) - DevAcademy</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f3f4f6; color: #1f2937; margin: 0; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        .card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border-top: 5px solid #6d28d9; }
        h2 { color: #5b21b6; margin-top: 0; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: 600; color: #4b5563; }
        input[type="text"] { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; box-sizing: border-box; }
        input[type="text"]:focus { border-color: #a78bfa; outline: none; box-shadow: 0 0 0 3px rgba(167, 139, 250, 0.3); }
        .btn { background-color: #6d28d9; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: bold; width: 100%; transition: background 0.2s; }
        .btn:hover { background-color: #5b21b6; }
        
        .lista-aulas { list-style: none; padding: 0; margin: 0; }
        .aula-item { display: flex; justify-content: space-between; align-items: center; padding: 12px; border-bottom: 1px solid #e5e7eb; transition: background 0.2s; }
        .aula-item:hover { background-color: #f9fafb; }
        .btn-excluir { background-color: #ef4444; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 13px; }
        .btn-excluir:hover { background-color: #dc2626; }

        #toast-container { position: fixed; bottom: 20px; right: 20px; z-index: 9999; display: flex; flex-direction: column; gap: 10px; }
        .toast { background-color: #6d28d9; color: white; padding: 14px 24px; border-radius: 8px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); font-weight: 500; min-width: 250px; opacity: 0; transform: translateY(20px); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .toast.show { opacity: 1; transform: translateY(0); }
        .toast.error { background-color: #b91c1c; }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <h2>Cadastrar Nova Aula <small style="font-size:12px; color:#7c3aed;">(Modo MVC)</small></h2>
        <form id="form-adicionar-aula">
            <div class="form-group">
                <label for="titulo">Título da Aula</label>
                <input type="text" id="titulo" name="titulo" placeholder="Ex: Arquitetura de Software" required>
            </div>
            <div class="form-group">
                <label for="url_video">URL do Vídeo (YouTube)</label>
                <input type="text" id="url_video" name="url_video" placeholder="Ex: https://www.youtube.com/watch?v=..." required>
            </div>
            <button type="submit" class="btn">Salvar Aula</button>
        </form>
    </div>

    <div class="card">
        <h2>Aulas Cadastradas</h2>
        <ul id="lista-aulas-container" class="lista-aulas">
            <?php if (!empty($aulas)): ?>
                <?php foreach ($aulas as $aula): ?>
                    <li class="aula-item" id="aula-<?= $aula['id'] ?>">
                        <span><?= htmlspecialchars($aula['titulo']) ?></span>
                        <button class="btn-excluir" data-id="<?= $aula['id'] ?>">Excluir</button>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </div>
</div>

<div id="toast-container"></div>

<script>
function dispararToast(mensagem, tipo = 'sucesso') {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.className = `toast ${tipo === 'erro' ? 'error' : ''}`;
    toast.innerText = message = mensagem;
    container.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 10);
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

document.getElementById('form-adicionar-aula').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('adicionar_async', 'true');

    fetch('admin.php', { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            dispararToast(data.message, 'sucesso');
            this.reset();
            const containerLista = document.getElementById('lista-aulas-container');
            const novoItem = document.createElement('li');
            novoItem.className = 'aula-item';
            novoItem.id = `aula-${data.aula.id}`;
            novoItem.innerHTML = `<span>${escapeHTML(data.aula.titulo)}</span><button class="btn-excluir" data-id="${data.aula.id}">Excluir</button>`;
            containerLista.insertBefore(novoItem, containerLista.firstChild);
        } else { dispararToast(data.message, 'erro'); }
    }).catch(() => dispararToast('Erro na comunicação com o controlador.', 'erro'));
});

document.getElementById('lista-aulas-container').addEventListener('click', function(e) {
    if (e.target.classList.contains('btn-excluir')) {
        const idAula = e.target.getAttribute('data-id');
        if (confirm('Tem certeza que deseja remover esta aula?')) {
            fetch(`admin.php?excluir_async=${idAula}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    dispararToast(data.message, 'sucesso');
                    const elementoAula = document.getElementById(`aula-${idAula}`);
                    elementoAula.style.opacity = '0';
                    setTimeout(() => elementoAula.remove(), 200);
                } else { dispararToast(data.message, 'erro'); }
            }).catch(() => dispararToast('Erro ao processar requisição.', 'erro'));
        }
    }
});

function escapeHTML(string) {
    return string.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}
</script>
</body>
</html>
