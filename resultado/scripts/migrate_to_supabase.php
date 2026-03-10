<?php
/**
 * Migration Script: JSON -> Supabase
 * Parses the folder structure in /json and uploads results to Supabase
 */

require_once __DIR__ . '/../includes/Database.php';

$db = new Database();
$jsonDir = __DIR__ . '/../json';

if (!is_dir($jsonDir)) {
    die("Error: Directorio /json no encontrado en $jsonDir.\n");
}

echo "🚀 Iniciando migración...\n";

$years = array_diff(scandir($jsonDir), ['.', '..']);

foreach ($years as $yearDir) {
    if (!is_dir("$jsonDir/$yearDir"))
        continue;

    echo "📅 Procesando año: $yearDir\n";
    $months = array_diff(scandir("$jsonDir/$yearDir"), ['.', '..']);

    foreach ($months as $monthDir) {
        $path = "$jsonDir/$yearDir/$monthDir";
        if (!is_dir($path))
            continue;

        $files = array_diff(scandir($path), ['.', '..']);
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) !== 'json')
                continue;

            $content = json_decode(file_get_contents("$path/$file"), true);
            procesarLoterias($db, $content, $batch);
        }
    }
}

// Flush any remaining records in the batch
if (!empty($batch)) {
    try {
        $db->insert('lottery_results', $batch);
        echo "   ✓ Batch final insertado (" . count($batch) . " registros).\n";
    }
    catch (Exception $e) {
        echo "   ✗ Error en batch final: " . $e->getMessage() . "\n";
    }
}

echo "✅ Migración completada.\n";

$batch = [];
function procesarLoterias($db, $data, &$batch)
{
    foreach ($data as $loteriaName => $info) {
        if (!isset($info['ConteoAll']))
            continue;

        foreach ($info['ConteoAll'] as $fechaStr => $sorteo) {
            if ($fechaStr === 'estadisticas')
                continue;

            $batch[] = [
                'loteria' => $loteriaName,
                'fecha' => (new DateTime($sorteo['fecha_completa']))->format('Y-m-d'), // Format to strict ISO date
                'primera' => $sorteo['numeros'][0] ?? null,
                'segunda' => $sorteo['numeros'][1] ?? null,
                'tercera' => $sorteo['numeros'][2] ?? null
            ];

            // Subir de a 1000 registros para no sobrecargar el servidor
            if (count($batch) >= 1000) {
                try {
                    $db->insert('lottery_results', $batch);
                    echo "   ✓ Batch insertado.\n";
                }
                catch (Exception $e) {
                    echo "   ✗ Error en batch: " . $e->getMessage() . "\n";
                }
                $batch = []; // Limpiar batch
            }
        }
    }
}
