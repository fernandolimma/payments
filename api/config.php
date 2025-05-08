<?php
if (!defined('PROTECT_CONFIG')) {
    die('Acesso direto não permitido');
}
define('PROTECT_CONFIG', true);

require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Conexão com banco de dados
$servername = $_ENV['DB_HOST'] ?? 'localhost';
$username = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASS'] ?? '';
$db_name = $_ENV['DB_NAME'] ?? 'pgto';

$conexao = mysqli_connect($servername, $username, $password, $db_name);

if (!$conexao) {
    error_log("Falha na conexão com o banco de dados: " . mysqli_connect_error());
    die("Erro de conexão com o banco de dados. Por favor, tente novamente mais tarde.");
}
?>