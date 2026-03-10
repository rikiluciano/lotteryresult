<?php
/**
 * Modernized API REST - LotteryProyect
 * Fetches data from Supabase instead of local JSON/MySQL files
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try {
    require_once __DIR__ . '/includes/Database.php';

    $db = new Database();
    $action = $_GET['action'] ?? 'get_recent_results';
    switch ($action) {
        case 'get_alias':
            // Retorna la lista única de loterías registradas
            // Solicitamos un límite alto para asegurar que vemos todas las loterías en la DB
            $data = $db->fetch('lottery_results', ['select' => 'loteria', 'limit' => 5000]);
            $loterias = array_unique(array_column($data, 'loteria'));
            sort($loterias);
            $alias = [];
            foreach ($loterias as $l) {
                $alias[$l] = $l;
            }
            echo json_encode($alias);
            break;

        case 'get_available_data':
            // Dado que Supabase tiene un límite de 1000 filas (o postgREST max rows),
            // usar select fecha iterará sólo sobre las primeras 1000 filas perdiendo años.
            // Solución: Generar la estructura de años/meses desde 2012 dinámicamente.
            $available = [];
            $currentYear = (int)date('Y');
            $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

            for ($v = $currentYear; $v >= 2012; $v--) {
                $yearLabel = "Año $v";
                // En el año actual, mostramos hasta el mes actual. Para el resto, todos.
                if ($v === $currentYear) {
                    $currentMonth = (int)date('n');
                    $available[$yearLabel] = array_slice($meses, 0, $currentMonth);
                }
                else {
                    $available[$yearLabel] = $meses;
                }
            }

            echo json_encode($available);
            break;

        case 'get_results':
            // Retorna resultados filtrados por fecha, año o mes
            $params = ['select' => '*', 'order' => 'fecha.desc'];

            if (isset($_GET['startDate']) && isset($_GET['endDate'])) {
                $params['and'] = '(fecha.gte.' . $_GET['startDate'] . ',fecha.lte.' . $_GET['endDate'] . ')';
            }
            elseif (isset($_GET['year']) && isset($_GET['month'])) {
                // Filtro por mes (ej: 2026-03)
                $monthNum = array_search($_GET['month'], [
                    'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
                    'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'
                ]) + 1;
                $year = $_GET['year'];
                $month = str_pad($monthNum, 2, '0', STR_PAD_LEFT);
                $lastDay = date('t', strtotime("$year-$month-01"));
                $params['and'] = "(fecha.gte.$year-$month-01,fecha.lte.$year-$month-$lastDay)";
            }
            elseif (isset($_GET['year'])) {
                $year = $_GET['year'];
                $params['and'] = "(fecha.gte.$year-01-01,fecha.lte.$year-12-31)";
            }

            echo json_encode($db->fetch('lottery_results', $params));
            break;

        default:
            echo json_encode(['error' => 'Acción no reconocida']);
            break;
    }
}
catch (Throwable $t) {
    http_response_code(500);
    echo json_encode([
        'error' => $t->getMessage(),
        'file' => basename($t->getFile()),
        'line' => $t->getLine()
    ]);
}

function getSpanishMonth($n)
{
    $meses = [1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril', 5 => 'mayo', 6 => 'junio',
        7 => 'julio', 8 => 'agosto', 9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'];
    return $meses[$n] ?? '';
}