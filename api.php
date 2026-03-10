<?php
/**
 * Modernized API REST - LotteryProyect
 * Fetches data from Supabase instead of local JSON/MySQL files
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/includes/Database.php';

$db = new Database();
$action = $_GET['action'] ?? 'get_recent_results';

try {
    switch ($action) {
        case 'get_results':
            $loteria = $_GET['loteria'] ?? null;
            $limit = $_GET['limit'] ?? 30;

            $params = ['order' => 'fecha.desc', 'limit' => $limit];
            if ($loteria) {
                $params['loteria'] = "eq.{$loteria}";
            }

            $data = $db->fetch('lottery_results', $params);
            echo json_encode($data);
            break;

        case 'get_statistics':
            // Esta lógica es más compleja en el frontend, 
            // pero podemos devolver los datos crudos para que el JS procese
            $data = $db->fetch('lottery_results', ['order' => 'fecha.desc']);
            echo json_encode($data);
            break;

        default:
            echo json_encode(['error' => 'Acción no reconocida']);
            break;
    }
}
catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}