<?php
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/api/apiConfig.php';
require_once __DIR__ . '/api/config.php';

checkRateLimit();

// Configurar o timezone
date_default_timezone_set('America/Sao_Paulo');

// Funções de atualização
function updateTransactionStatus($conexao, $transaction_id, $new_status) {
    $sql = "UPDATE status SET status = ? WHERE id_venda = ?";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("ss", $new_status, $transaction_id);
    return $stmt->execute();
}

function getRejectedSales($conexao) {
    $rejectedSales = [];
    $query = "SELECT id_venda FROM status WHERE status = 'rejected' ORDER BY id DESC LIMIT 5";
    $result = $conexao->query($query);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $rejectedSales[] = $row['id_venda'];
        }
    }
    return $rejectedSales;
}

// Buscar últimos registros
$latestTransactions = [];
$query = "SELECT id_venda, status, description FROM status ORDER BY id DESC LIMIT 5";
$result = $conexao->query($query);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $latestTransactions[] = $row;
    }
}

// Consultar API para cada transação
$apiResults = [];
foreach ($latestTransactions as $transaction) {
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.mercadopago.com/v1/payments/' . $transaction['id_venda'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'accept: application/json',
            'content-type: application/json',
            'X-Idempotency-Key: ' . date('Y-m-d-H:i:s-') . rand(0, 1500),
            'Authorization: Bearer ' . $access_token
        ),
    ));
    
    $response = curl_exec($curl);
    
    if (curl_errno($curl)) {
        error_log("Erro ao consultar pagamento " . $transaction['id_venda'] . ": " . curl_error($curl));
        continue;
    }
    
    $apiResult = json_decode($response);
    $apiResults[] = $apiResult;
    
    if (isset($apiResult->status) && $apiResult->status != $transaction['status']) {
        updateTransactionStatus($conexao, $transaction['id_venda'], $apiResult->status);
    }
    
    curl_close($curl);
}

$rejectedSales = getRejectedSales($conexao);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Pagamentos</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <meta http-equiv="refresh" content="300">
</head>

<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-xl p-6 mb-8 shadow-lg">
            <h1 class="text-2xl font-bold">Painel de Pagamentos</h1>
            <p class="text-blue-100">Status atualizado em: <?php echo date('d/m/Y H:i:s'); ?></p>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4 text-gray-800">Últimas 5 Transações</h2>
            
            <?php if (!empty($apiResults)): ?>
                <div class="flex space-x-4 overflow-x-auto pb-4">
                    <?php foreach ($apiResults as $index => $resultado): ?>
                        <div class="min-w-[300px] border border-gray-200 rounded-lg p-4 flex-shrink-0 shadow-sm hover:shadow-md transition-shadow">
                            <div class="flex flex-col h-full">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center space-x-2">
                                        <div class="h-3 w-3 rounded-full 
                                            <?php echo $resultado->status === 'approved' ? 'bg-green-500' : ''; ?>
                                            <?php echo $resultado->status === 'pending' ? 'bg-yellow-500' : ''; ?>
                                            <?php echo $resultado->status === 'rejected' ? 'bg-red-500' : ''; ?>">
                                        </div>
                                        <span class="text-sm font-medium">
                                            <?php echo ucfirst($resultado->status); ?>
                                        </span>
                                    </div>
                                    <span class="text-xs text-gray-500">#<?php echo $index + 1; ?></span>
                                </div>
                                
                                <div class="space-y-2 flex-grow">
                                    <p><span class="text-gray-600">ID:</span> <?php echo substr($resultado->id, 0, 8) . '...'; ?></p>
                                    <p><span class="text-gray-600">Valor:</span> R$ <?php echo number_format($resultado->transaction_amount, 2, ',', '.'); ?></p>
                                    <p class="text-sm"><span class="text-gray-600">Método:</span> <?php echo ucfirst($resultado->payment_method_id); ?></p>
                                    <?php if (isset($resultado->status_detail)): ?>
                                        <p class="text-xs"><span class="text-gray-600">Detalhe:</span> <?php echo $resultado->status_detail; ?></p>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mt-4 pt-2 border-t border-gray-100">
                                    <p class="text-xs text-gray-500 truncate" title="<?php echo $latestTransactions[$index]['description']; ?>">
                                        <?php echo $latestTransactions[$index]['description']; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-600">Nenhuma transação encontrada.</p>
            <?php endif; ?>

            <div class="mt-8 border-t pt-6">
                <h3 class="text-lg font-semibold text-red-600 mb-4">Últimas 5 Vendas Rejeitadas</h3>
                <?php if (!empty($rejectedSales)): ?>
                    <ul class="divide-y divide-gray-200">
                        <?php foreach ($rejectedSales as $saleId): ?>
                            <li class="py-3 flex justify-between items-center">
                                <span class="font-mono bg-red-50 px-3 py-1 rounded text-red-800"><?php echo htmlspecialchars($saleId); ?></span>
                                <a href="detalhes_venda.php?id=<?php echo $saleId; ?>"
                                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm transition">
                                    Ver detalhes
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-gray-500 italic">Nenhuma venda rejeitada encontrada.</p>
                <?php endif; ?>
            </div>
        </div>

        <?php 
        // Incluir a tabela de status com proteção
        include('status_table.php'); 
        ?>

        <footer class="mt-12 text-center text-gray-500 text-sm">
            <p>Sistema de pagamentos API - Mercado Pago &copy; <?php echo date('Y'); ?></p>
        </footer>
    </div>

    <script>
        setTimeout(function(){
            window.location.reload();
        }, 300000);
    </script>
</body>
</html>