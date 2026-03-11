<?php
require_once __DIR__ . '/includes/Database.php';
$db = new Database();
try {
    // Vamos a ver EXACTAMENTE qué fechas hay y cuántos registros por fecha
    $results = $db->fetch('lottery_results', ['select' => 'fecha,loteria', 'order' => 'fecha.desc', 'limit' => 1000]);
    
    $counts = [];
    foreach ($results as $r) {
        $f = substr($r['fecha'], 0, 10);
        if (!isset($counts[$f])) $counts[$f] = 0;
        $counts[$f]++;
    }
    
    echo "--- CONTEO DE REGISTROS POR FECHA ---\n";
    foreach ($counts as $fecha => $qty) {
        echo "Fecha: $fecha -> $qty registros\n";
    }
    
    echo "\n--- MUESTRA DEL ÚLTIMO DÍA ---\n";
    $lastDay = array_key_first($counts);
    foreach ($results as $r) {
        if (substr($r['fecha'], 0, 10) === $lastDay) {
            echo " - " . $r['loteria'] . " (" . $r['fecha'] . ")\n";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
