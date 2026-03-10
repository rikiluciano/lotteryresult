<?php
/**
 * Migration Script: JSON -> Supabase
 * Parses the folder structure in /json and uploads results to Supabase
 */

require_once __DIR__ . '/../includes/Database.php';

$db = new Database();
$jsonDir = __DIR__ . '/../json';

if (!is_dir($jsonDir)) {
    die("Error: Directorio /json no encontrado.\n");
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
            if (!$content)
                continue;

            procesarLoterias($db, $content);
        }
    }
}

echo "✅ Migración completada.\n";

function procesarLoterias($db, $data)
{
    foreach ($data as $loteriaName => $info) {
        // En el formato actual, ConteoAll tiene los resultados individuales por fecha
        if (!isset($info['ConteoAll']))
            continue;

        foreach ($info['ConteoAll'] as $fechaStr => $sorteo) {
            // Ignorar el campo 'estadisticas' que a veces viene en el mismo nivel
            if ($fechaStr === 'estadisticas')
                continue;

            $dataToInsert = [
                'loteria' => $loteriaName,
                'fecha' => $sorteo['fecha_completa'], // Supabase manejará el formato ISO
                'primera' => $sorteo['numeros'][0] ?? null,
                'segunda' => $sorteo['numeros'][1] ?? null,
                'tercera' => $sorteo['numeros'][2] ?? null
            ];

            try {
                $db->insert('lottery_results', $dataToInsert);
                echo "   ✓ [{$loteriaName}] {$fechaStr} insertado.\n";
            }
            catch (Exception $e) {
                echo "   ✗ Error en [{$loteriaName}] {$fechaStr}: " . $e->getMessage() . "\n";
            }
        }
    }
}
