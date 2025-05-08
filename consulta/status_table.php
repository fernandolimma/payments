<?php
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/api/apiConfig.php';
require_once __DIR__ . '/api/config.php';

checkRateLimit();

// Consulta segura usando prepared statement (se houver parâmetros)
$query_status = "SELECT * FROM status";
$stmt = $conexao->prepare($query_status);
$stmt->execute();
$result_status = $stmt->get_result();
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

            <?php if ($result_status->num_rows > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gradient-to-r from-blue-600 to-purple-600 text-white">
                            <tr>
                                <?php
                                $fields = $result_status->fetch_fields();
                                foreach ($fields as $field) {
                                    echo '<th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">' .
                                        htmlspecialchars(ucfirst(str_replace('_', ' ', $field->name))) . '</th>';
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
$stmt->close();
// Não fechamos a conexão principal para ser reutilizada
?>