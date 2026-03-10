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
        case 'get_alias':
            // Retorna la lista única de loterías registradas
            $data = $db->fetch('lottery_results', ['select' => 'loteria']);
            $loterias = array_unique(array_column($data, 'loteria'));
            sort($loterias);
            $alias = [];
            foreach ($loterias as $l) {
                $alias[$l] = $l;
            }
            echo json_encode($alias);
            break;

        case 'get_available_data':
            // Retorna años y meses disponibles
            $data = $db->fetch('lottery_results', ['select' => 'fecha']);
            $available = [];
            foreach ($data as $row) {
                $dt = new DateTime($row['fecha']);
                $year = "Año " . $dt->format('Y');
                $month = strtolower(getSpanishMonth($dt->format('n')));
                if (!isset($available[$year]))
                    $available[$year] = [];
                if (!in_array($month, $available[$year]))
                    $available[$year][] = $month;
            }
            echo json_encode($available);
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

function getSpanishMonth($n)
{
    $meses = [1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril', 5 => 'mayo', 6 => 'junio',
        7 => 'julio', 8 => 'agosto', 9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'];
    return $meses[$n] ?? '';
}