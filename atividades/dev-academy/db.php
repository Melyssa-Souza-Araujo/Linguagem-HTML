<?php
// Configura os parâmetros do cookie de sessão antes de iniciá-la para máxima segurança
session_set_cookie_params([
    'lifetime' => 0,          // Expira quando o navegador fechar
    'path' => '/',
    'domain' => '',
    'secure' => isset($_SERVER['HTTPS']), // Ativa apenas se estiver em ambiente HTTPS legítimo
    'httponly' => true,       // Impede que scripts JavaScript acessem o cookie (Mitiga roubo via XSS)
    'samesite' => 'Strict'    // Impede o envio do cookie em requisições cruzadas (Mitiga CSRF)
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $host = 'localhost';
    $db   = 'dev_academy';
    $user = 'root';
    $pass = '';
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false, // Desativa a emulação e usa preparados reais do MySQL (Segurança Máxima)
    ];

    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     // Jamais exiba o $e->getMessage() na tela do usuário em produção, pois expõe caminhos de arquivos e credenciais.
     error_log($e->getMessage());
     die("Erro interno no servidor. Por favor, tente mais tarde.");
}
?>
