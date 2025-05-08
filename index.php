<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_method'])) {
    $method = $_POST['payment_method'];
    switch ($method) {
        case 'pix':
            header('Location: pix/pix.php');
            exit();
        case 'boleto':
            header('Location: boleto/boleto.php');
            exit();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mercado Pago - Método de Pagamento</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-lg w-full max-w-md overflow-hidden">
        <div class="bg-blue-600 p-6 text-white">
            <h1 class="text-2xl font-bold text-center">Escolha o Método de Pagamento</h1>
            <p class="text-center text-blue-100 mt-2">API - Mercado Pago</p>
        </div>

        <form id="paymentForm" method="post" action="" class="p-6">
            <div class="space-y-4">
                <div class="flex items-center p-4 border border-gray-200 rounded-lg hover:border-blue-500 transition cursor-pointer">
                    <input type="radio" id="method-pix" name="payment_method" value="pix" required class="h-5 w-5 text-blue-600">
                    <label for="method-pix" class="ml-3 flex-1 cursor-pointer">
                        <div class="flex items-center">
                            <div class="bg-blue-100 p-3 rounded-full mr-4">
                                <i class="fas fa-qrcode text-blue-600 text-xl"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-800">PIX</h3>
                                <p class="text-sm text-gray-600">Pagamento instantâneo via QR Code ou código PIX</p>
                            </div>
                        </div>
                    </label>
                </div>

                <div class="flex items-center p-4 border border-gray-200 rounded-lg hover:border-blue-500 transition cursor-pointer">
                    <input type="radio" id="method-boleto" name="payment_method" value="boleto" class="h-5 w-5 text-blue-600">
                    <label for="method-boleto" class="ml-3 flex-1 cursor-pointer">
                        <div class="flex items-center">
                            <div class="bg-blue-100 p-3 rounded-full mr-4">
                                <i class="fas fa-barcode text-blue-600 text-xl"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-800">Boleto Bancário</h3>
                                <p class="text-sm text-gray-600">Pague em qualquer agência bancária ou internet banking</p>
                            </div>
                        </div>
                    </label>
                </div>
            </div>

            <button type="submit" class="w-full mt-6 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition duration-200">
                Continuar
            </button>
        </form>
    </div>
</body>

</html>