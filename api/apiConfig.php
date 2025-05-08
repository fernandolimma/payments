<?php

if (!defined('PROTECT_CONFIG')) {
    die('Acesso direto não permitido');
}
define('PROTECT_CONFIG', true);

require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$access_token = $_ENV['MERCADOPAGO_ACCESS_TOKEN'];
$public_key = $_ENV['MERCADOPAGO_PUBLIC_KEY'];

if (empty($access_token)) {
    error_log("Token de acesso do Mercado Pago não configurado");
    die("Erro de configuração do sistema. Por favor, contate o administrador.");
}
?>