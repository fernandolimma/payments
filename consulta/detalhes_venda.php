<?php
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/api/apiConfig.php';
require_once __DIR__ . '/api/config.php';

checkRateLimit();

// Validar e sanitizar o ID
$saleId = isset($_GET['id']) ? sanitizeInput($_GET['id']) : null;

if (!$saleId || !preg_match('/^\d+$/', $saleId)) {
    logSecurityEvent("Tentativa de acesso com ID inválido: " . ($_GET['id'] ?? ''));
    header('Location: consulta.php');
    exit;
}

// Buscar detalhes com prepared statement
$query = "SELECT * FROM status WHERE id_venda = ? AND status = 'rejected'";
$stmt = $conexao->prepare($query);
$stmt->bind_param("s", $saleId);
$stmt->execute();
$result = $stmt->get_result();
$saleDetails = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes da Venda Rejeitada</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>

<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-2xl font-bold text-gray-800">Detalhes da Venda Rejeitada</h1>
            <a href="consulta.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">
                Voltar
            </a>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6">
            <?php if ($saleDetails): ?>
                <h2 class="text-xl font-semibold mb-6 text-gray-800">Venda #<?php echo htmlspecialchars($saleDetails['id_venda']); ?></h2>

                <div class="space-y-4">
                    <div class="flex border-b pb-2">
                        <span class="w-48 font-medium text-gray-500">Status:</span>
                        <span class="font-bold text-red-600">Rejeitado</span>
                    </div>

                    <?php foreach ($saleDetails as $key => $value): ?>
                        <?php if ($key !== 'id_venda' && $key !== 'status'): ?>
                            <div class="flex border-b pb-2">
                                <span class="w-48 font-medium text-gray-500">
                                    <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $key))); ?>:
                                </span>
                                <span class="flex-1"><?php echo htmlspecialchars($value); ?></span>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-600">Não foram encontrados detalhes para esta venda rejeitada.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>