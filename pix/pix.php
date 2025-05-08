<?php 
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../api/apiConfig.php';
require_once __DIR__ . '/../api/config.php';

checkRateLimit();

// Processar o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        logSecurityEvent("Tentativa de CSRF detectada no PIX");
        die("Token de segurança inválido.");
    }

    // Validar entrada
    $requiredFields = [
        'first_name', 'last_name', 'email', 'doc_type', 'doc_number',
        'zip_code', 'street_name', 'street_number', 'neighborhood', 'city', 'federal_unit'
    ];
    
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            die("O campo $field é obrigatório.");
        }
    }
    
    if (!validateEmail($_POST['email'])) {
        die("Email inválido.");
    }
    
    if ($_POST['doc_type'] === 'CPF' && !validateCPF($_POST['doc_number'])) {
        die("CPF inválido.");
    }
    
    if ($_POST['doc_type'] === 'CNPJ' && !validateCNPJ($_POST['doc_number'])) {
        die("CNPJ inválido.");
    }

    $curl = curl_init();

    // Dados da Compra
    $dados["transaction_amount"] = 29.90;
    $dados["description"] = "PetCard";
    $dados["external_reference"] = "PetCard";
    $dados["payment_method_id"] = "pix";
    $dados["notification_url"] = "https://petcard.uno";

    // Dados Pessoais
    $payer_email = sanitizeInput($_POST['email']);
    $dados["payer"]["email"] = $payer_email;
    $dados["payer"]["first_name"] = sanitizeInput($_POST['first_name']);
    $dados["payer"]["last_name"] = sanitizeInput($_POST['last_name']);
    $dados["payer"]["identification"]["type"] = sanitizeInput($_POST['doc_type']);
    $dados["payer"]["identification"]["number"] = sanitizeInput($_POST['doc_number']);

    // Dados de Endereço
    $dados["payer"]["address"]["zip_code"] = sanitizeInput($_POST['zip_code']);
    $dados["payer"]["address"]["street_name"] = sanitizeInput($_POST['street_name']);
    $dados["payer"]["address"]["street_number"] = sanitizeInput($_POST['street_number']);
    $dados["payer"]["address"]["neighborhood"] = sanitizeInput($_POST['neighborhood']);
    $dados["payer"]["address"]["city"] = sanitizeInput($_POST['city']);
    $dados["payer"]["address"]["federal_unit"] = sanitizeInput($_POST['federal_unit']);

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.mercadopago.com/v1/payments',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($dados),
        CURLOPT_HTTPHEADER => array(
            'accept: application/json',
            'content-type: application/json',
            'X-Idempotency-Key: ' . date('Y-m-d-H:i:s-') . rand(0, 1500),
            'Authorization: Bearer ' . $access_token
        ),
    ));

    $response = curl_exec($curl);
    
    if (curl_errno($curl)) {
        error_log("Erro na requisição PIX ao Mercado Pago: " . curl_error($curl));
        die("Erro ao processar o pagamento PIX. Por favor, tente novamente.");
    }
    
    $resultado = json_decode($response);
    $id = $dados["external_reference"];
    curl_close($curl);

    // Inserir no banco de dados - status
    $stmt = $conexao->prepare("INSERT INTO status(
            id_reg,
            email, 
            pag_metodo, 
            status, 
            description, 
            id_venda
        ) VALUES (?, ?, ?, ?, ?, ?)");
    
    $stmt->bind_param(
        "ssssss",
        $id,
        $payer_email,
        $resultado->payment_method_id,
        $resultado->status,
        $resultado->description,
        $resultado->id
    );

    if ($stmt->execute()) {
        // Inserir endereço
        $stmt_endereco = $conexao->prepare("INSERT INTO endereco(
                id_venda,
                id_registro,
                cep,
                logradouro,
                numero,
                bairro,
                cidade,
                estado
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt_endereco->bind_param(
            "ssssssss",
            $resultado->id,
            $id,
            $_POST['zip_code'],
            $_POST['street_name'],
            $_POST['street_number'],
            $_POST['neighborhood'],
            $_POST['city'],
            $_POST['federal_unit']
        );
        
        if (!$stmt_endereco->execute()) {
            error_log("Erro ao inserir endereço PIX: " . $stmt_endereco->error);
        }
    } else {
        error_log("Erro ao inserir status PIX: " . $stmt->error);
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento via PIX</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-lg w-full max-w-md overflow-hidden">
        <div class="bg-blue-600 p-6 text-white">
            <h1 class="text-2xl font-bold text-center">Pagamento via PIX</h1>
        </div>

        <div class="p-6">
            <?php if (!isset($resultado)): ?>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">Nome</label>
                        <input type="text" id="first_name" name="first_name" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Sobrenome</label>
                        <input type="text" id="last_name" name="last_name" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                        <input type="email" id="email" name="email" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label for="doc_type" class="block text-sm font-medium text-gray-700 mb-1">Tipo de Documento</label>
                        <select id="doc_type" name="doc_type" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            <option value="CPF">CPF</option>
                            <option value="CNPJ">CNPJ</option>
                        </select>
                    </div>

                    <div>
                        <label for="doc_number" class="block text-sm font-medium text-gray-700 mb-1">Número do Documento</label>
                        <input type="text" id="doc_number" name="doc_number" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <!-- Campos de Endereço -->
                    <div class="border-t border-gray-200 pt-4 mt-4">
                        <h3 class="text-lg font-medium text-gray-900 mb-3">Endereço</h3>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="col-span-2 sm:col-span-1">
                                <label for="zip_code" class="block text-sm font-medium text-gray-700 mb-1">CEP</label>
                                <input type="text" id="zip_code" name="zip_code" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div class="col-span-2">
                                <label for="street_name" class="block text-sm font-medium text-gray-700 mb-1">Logradouro</label>
                                <input type="text" id="street_name" name="street_name" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div class="col-span-2 sm:col-span-1">
                                <label for="street_number" class="block text-sm font-medium text-gray-700 mb-1">Número</label>
                                <input type="text" id="street_number" name="street_number" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div class="col-span-2 sm:col-span-1">
                                <label for="neighborhood" class="block text-sm font-medium text-gray-700 mb-1">Bairro</label>
                                <input type="text" id="neighborhood" name="neighborhood" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div class="col-span-2 sm:col-span-1">
                                <label for="city" class="block text-sm font-medium text-gray-700 mb-1">Cidade</label>
                                <input type="text" id="city" name="city" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div class="col-span-2 sm:col-span-1">
                                <label for="federal_unit" class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                                <select id="federal_unit" name="federal_unit" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Selecione</option>
                                    <option value="AC">Acre</option>
                                    <option value="AL">Alagoas</option>
                                    <option value="AP">Amapá</option>
                                    <option value="AM">Amazonas</option>
                                    <option value="BA">Bahia</option>
                                    <option value="CE">Ceará</option>
                                    <option value="DF">Distrito Federal</option>
                                    <option value="ES">Espírito Santo</option>
                                    <option value="GO">Goiás</option>
                                    <option value="MA">Maranhão</option>
                                    <option value="MT">Mato Grosso</option>
                                    <option value="MS">Mato Grosso do Sul</option>
                                    <option value="MG">Minas Gerais</option>
                                    <option value="PA">Pará</option>
                                    <option value="PB">Paraíba</option>
                                    <option value="PR">Paraná</option>
                                    <option value="PE">Pernambuco</option>
                                    <option value="PI">Piauí</option>
                                    <option value="RJ">Rio de Janeiro</option>
                                    <option value="RN">Rio Grande do Norte</option>
                                    <option value="RS">Rio Grande do Sul</option>
                                    <option value="RO">Rondônia</option>
                                    <option value="RR">Roraima</option>
                                    <option value="SC">Santa Catarina</option>
                                    <option value="SP">São Paulo</option>
                                    <option value="SE">Sergipe</option>
                                    <option value="TO">Tocantins</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <button type="submit"
                        class="w-full mt-4 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition duration-200">
                        Gerar QR Code PIX
                    </button>
                </form>
            <?php else: ?>
                <div class="text-center space-y-6">
                    <h2 class="text-xl font-bold text-gray-800">PetCard - Pagamento via PIX</h2>
                    <p class="text-gray-600">Escaneie o QR Code abaixo para realizar o pagamento</p>

                    <div class="flex justify-center">
                        <img class="border border-gray-200 p-4 bg-white rounded-lg" id="base64image"
                            src="data:image/jpeg;base64, <?php echo $resultado->point_of_interaction->transaction_data->qr_code_base64; ?>" />
                    </div>

                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <p class="font-medium text-green-800 mb-2">Copie o código PIX:</p>
                        <div class="flex items-center bg-white p-3 rounded border border-green-200">
                            <span id="pix-code" class="break-all flex-1"><?php echo $resultado->point_of_interaction->transaction_data->qr_code; ?></span>
                            <button onclick="copyPixCode()" class="ml-2 bg-green-600 hover:bg-green-700 text-white p-2 rounded-lg transition duration-200">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                        <p id="copy-success" class="hidden text-green-600 text-sm mt-1"><i class="fas fa-check-circle"></i> Código copiado!</p>
                    </div>

                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 text-left rounded-r-lg">
                        <p class="font-medium text-blue-800"><i class="fas fa-exclamation-circle mr-2"></i> Atenção!</p>
                        <p class="text-sm text-gray-700 mt-1">A confirmação do pagamento poderá demorar até 24h.</p>
                        <p class="text-sm text-gray-700 mt-1">Após a confirmação será enviado seu LOGIN e SENHA para o e-mail informado.</p>
                        <p class="text-sm text-gray-700 mt-1">Dúvidas? Acesse nosso contato no WhatsApp.</p>
                    </div>

                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>"
                        class="inline-block w-full mt-4 bg-gray-600 hover:bg-gray-700 text-white font-bold py-3 px-4 rounded-lg transition duration-200 text-center">
                        Voltar ao Formulário
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
     <script>
    function copyPixCode() {
        const pixCode = document.getElementById('pix-code').innerText;
        navigator.clipboard.writeText(pixCode).then(() => {
            const copySuccess = document.getElementById('copy-success');
            copySuccess.classList.remove('hidden');
            setTimeout(() => copySuccess.classList.add('hidden'), 2000);
        });
    }
    </script>
</body>

</html>