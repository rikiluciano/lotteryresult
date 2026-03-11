<?php
require_once __DIR__ . '/includes/Database.php';
$db = new Database();
try {
    $results = $db->fetch('lottery_results', ['limit' => 50, 'order' => 'fecha.desc']);
    echo "Total: " . count($results) . "\n";
    foreach ($results as $r) {
        echo $r['fecha'] . " - " . $r['loteria'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
