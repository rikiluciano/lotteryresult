<?php
require_once __DIR__ . '/includes/Database.php';
$db = new Database();
$fecha = '2026-03-08';
$nextDay = '2026-03-09';
try {
    // Prueba 1: Filtro simple de coincidencia exacta (si es dato exacto)
    echo "--- PROBANDO IGUALDAD EXACTA ---\n";
    $p1 = ['fecha' => 'eq.' . $fecha . 'T00:00:00+00:00'];
    $r1 = $db->fetch('lottery_results', $p1);
    echo "Encontrados eq: " . count($r1) . "\n";

    // Prueba 2: El filtro que usé antes
    echo "--- PROBANDO RANGO AND ---\n";
    $p2 = ['fecha' => 'and(fecha.gte.' . $fecha . 'T00:00:00,fecha.lt.' . $nextDay . 'T00:00:00)'];
    $r2 = $db->fetch('lottery_results', $p2);
    echo "Encontrados range: " . count($r2) . "\n";
    
    // Prueba 3: Sin filtros pero limit 100
    echo "--- PROBANDO SIN FILTRO ---\n";
    $r3 = $db->fetch('lottery_results', ['limit' => 100]);
    echo "Encontrados total: " . count($r3) . "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
