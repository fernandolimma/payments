<?php
include('../api/apiConfig.php');
include('../api/config.php');


// Chamada à API MercadoPago
$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://api.mercadopago.com/v1/payments/',
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
$resultado = json_decode($response);
curl_close($curl);

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Pagamentos</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>

<body>
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="p-6">
            <h2 class="text-xl font-semibold mb-4 text-gray-800">Registros de Status</h2>

            <?php
            $query_status = "SELECT * FROM status";
            $result_status = $conexao->query($query_status);

            if ($result_status->num_rows > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gradient-to-r from-blue-600 to-purple-600 text-white">
                            <tr>
                                <?php
                                $fields = $result_status->fetch_fields();
                                foreach ($fields as $field) {
                                    echo '<th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">' .
                                        ucfirst(str_replace('_', ' ', $field->name)) . '</th>';
                                }
                                ?>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            $result_status->data_seek(0);
                            while ($row = $result_status->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <?php foreach ($row as $value): ?>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                            <?php echo htmlspecialchars($value); ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-gray-500 italic">Nenhum dado encontrado na tabela status</p>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>

<?php
// $conexao->close();
?>