1. Estrutura de Arquivos

/payments/
├── api/
│   ├── apiConfig.php           (Chaves restritas)
│   └── config.php              (Arquivo de configuração)
├── boleto/
│   └── boleto.php              (Função boleto)
├── consulta/
│   ├── consulta.php            (Consulta vendas)
│   ├── detalhes_venda.php      (Detalhes das vendas)
│   └── status_table.php        (Status das vendas)
├── includes/
│   ├── functions.php           (Funções de validação)
│   └── security.php            (Funções de segurança)
├── pix/
│   └── pix.php                 (Função PIX)
├── index.php                   (Arquivo inicial)
├── .env                        (...)
├── .htaccess                   (...)
├── .gitignore                  (...)
└── readme.md                   (Leia-me)

1. Proteção das Chaves de API com .env
    // apiConfig.php
    require_once __DIR__ . '/vendor/autoload.php';
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();

$access_token = $_ENV['MERCADOPAGO_ACCESS_TOKEN'];
$public_key = $_ENV['MERCADOPAGO_PUBLIC_KEY'];

2. Melhorias no Banco de Dados
    Substitua mysqli_real_escape_string por prepared statements:

    // Exemplo em boleto.php
    $stmt = $conexao->prepare("INSERT INTO status(id_reg, email, pag_metodo, status, description, id_venda) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $id, $payer_email, $resultado->payment_method_id, $resultado->status, $resultado->description, $resultado->id);
    $stmt->execute();

3. Validação de Entrada Rigorosa
    Adicione validação antes de processar os formulários:

    // Validar email
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        die("Email inválido");
    }

    // Validar CPF/CNPJ
    if ($_POST['doc_type'] === 'CPF' && !validaCPF($_POST['doc_number'])) {
        die("CPF inválido");
    }

    function validaCPF($cpf) {
        // Implementação da validação de CPF
    }

4. Proteção de Arquivos de Configuração
    Adicione no início de apiConfig.php e config.php:

    if (!defined('PROTECT_CONFIG')) {
    die('Acesso direto não permitido');
    }
    define('PROTECT_CONFIG', true);

5. HTTPS Obrigatório
    Force HTTPS em produção:

    if ($_SERVER['HTTPS'] != "on" && $_SERVER['HTTP_HOST'] != 'localhost') {
    header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit();
    }

6. Headers de Segurança
    Adicione no início dos scripts PHP ou no servidor:
    //includes/security.php

    header("X-Frame-Options: DENY");
    header("X-Content-Type-Options: nosniff");
    header("X-XSS-Protection: 1; mode=block");
    header("Referrer-Policy: strict-origin-when-cross-origin");

7. Proteção contra CSRF
    Adicione tokens CSRF nos formulários:

    // No topo do script
    session_start();
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    // No formulário
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

    // Na validação
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Token CSRF inválido');
    }

8. Logs de Segurança
    Implemente um sistema básico de logs:

    function logSecurityEvent($message) {
    $log = date('Y-m-d H:i:s') . " - " . $_SERVER['REMOTE_ADDR'] . " - " . $message . PHP_EOL;
    file_put_contents(__DIR__ . '/security.log', $log, FILE_APPEND);
    }

9. Rate Limiting
    Proteja contra ataques de força bruta:

   session_start();
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
    }

    $_SESSION['login_attempts']++;
    if ($_SESSION['login_attempts'] > 5) {
        logSecurityEvent("Tentativas excessivas de pagamento do IP: " . $_SERVER['REMOTE_ADDR']);
        die("Muitas tentativas. Tente novamente mais tarde.");
    } 

10. Implementação do dotenv via composer
    bash - composer require vlucas/phpdotenv

    - Crie o arquivo .env na raiz do projeto com suas chaves
    - Adicione .env ao seu .gitignore
    - Atualize apiConfig.php para ler do .env como mostrado acima

11. Instruções de Implementação
    - Execute composer install para instalar as dependências (principalmente o dotenv)
    - Crie o arquivo .env com as configurações adequadas
    - Certifique-se de que o diretório e arquivo security.log tenham permissões de escrita
    - Adicione .env e security.log ao seu .gitignore
    - Configure o servidor para usar HTTPS

12. Configuração do Ambiente Local
    Usando XAMPP/WAMP (Windows)
    - Configure o Virtual Host:
    Edite C:\xampp\apache\conf\extra\httpd-vhosts.conf (Windows)

    - <VirtualHost *:80>
    ServerAdmin webmaster@emissor.local
    DocumentRoot "C:/xampp/htdocs/payments.local"
    ServerName payments.local
    ErrorLog "logs/payments.local-error.log"
    CustomLog "logs/payments.local-access.log" common
    </VirtualHost>
    <VirtualHost *:443>
        ServerName payments.local
        DocumentRoot "C:/xampp/htdocs/payments.local"
        SSLEngine on
        SSLCertificateFile "C:/xampp/apache/conf/ssl.crt/server.crt"
        SSLCertificateKeyFile "C:/xampp/apache/conf/ssl.key/server.key"
        <Directory "C:/xampp/htdocs/payments.local">
            Options Indexes FollowSymLinks
            AllowOverride All
            Require all granted
        </Directory>
    </VirtualHost>

    Edite o arquivo hosts:
    Windows: C:\Windows\System32\drivers\etc\hosts
    Adicione: 127.0.0.1 payments.local

    - Solução de Problemas Comuns
        Problema: SSL não funciona
        Solução:
                # No XAMPP (Terminal bash):
                openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
                -keyout C:/xampp/apache/conf/ssl.key/server.key \
                -out C:/xampp/apache/conf/ssl.crt/server.crt


