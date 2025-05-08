<?php
// Forçar HTTPS em produção
if ($_SERVER['HTTPS'] != "on" && $_SERVER['HTTP_HOST'] != 'localhost') {
    header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit();
}

// Headers de segurança
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

// Iniciar sessão segura
session_set_cookie_params([
    'lifetime' => 3600,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();

// Proteção CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Função para validar CSRF
function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Rate limiting
function checkRateLimit($limit = 5, $timeout = 300) {
    $key = 'rate_limit_' . md5($_SERVER['REMOTE_ADDR'] . $_SERVER['REQUEST_URI']);
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [
            'count' => 0,
            'time' => time()
        ];
    }
    
    if ($_SESSION[$key]['time'] + $timeout < time()) {
        $_SESSION[$key] = [
            'count' => 0,
            'time' => time()
        ];
    }
    
    if ($_SESSION[$key]['count'] >= $limit) {
        error_log("Rate limit excedido para IP: " . $_SERVER['REMOTE_ADDR']);
        die("Muitas requisições. Por favor, tente novamente mais tarde.");
    }
    
    $_SESSION[$key]['count']++;
}

// Log de segurança
function logSecurityEvent($message) {
    $log = date('Y-m-d H:i:s') . " - " . $_SERVER['REMOTE_ADDR'] . " - " . $message . PHP_EOL;
    file_put_contents(__DIR__ . '/../security.log', $log, FILE_APPEND);
}
?>