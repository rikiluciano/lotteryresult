<?php
/**
 * NÚMEROS RD - Página Principal
 * Con logos oficiales de loterías
 */

require_once 'config_db.php';
require_once 'config_db_functions.php';
require_once 'normalizar_tabla.php';
require_once 'configuracion_loterias.php';

// Cargar configuración de loterías con horarios
require_once 'configuracion_loterias.php';

/**
 * Obtener horario correcto de una lotería desde configuración
 * Detecta si es domingo y usa horario especial si existe
 */
function obtener_horario_loteria($nombre_loteria, $fecha) {
    global $configuracion_urls;
    
    // Detectar si es domingo (1=Lunes ... 7=Domingo)
    $fecha_dt = DateTime::createFromFormat('d/m/Y', $fecha);
    $es_domingo = ($fecha_dt && $fecha_dt->format('N') == 7);
    
    // Buscar la lotería en la configuración
    foreach ($configuracion_urls as $config) {
        // Buscar por nombre o alias (case-insensitive)
        $nombre_config = strtolower($config['nombre']);
        $alias_config = strtolower($config['alias']);
        $nombre_buscar = strtolower($nombre_loteria);
        
        if (stripos($nombre_config, $nombre_buscar) !== false || 
            stripos($nombre_buscar, $nombre_config) !== false ||
            stripos($alias_config, $nombre_buscar) !== false ||
            stripos($nombre_buscar, $alias_config) !== false) {
            
            // Si es domingo Y tiene horario especial para domingo
            if ($es_domingo && 
                isset($config['horario_sorteo_domingo']) && 
                $config['horario_sorteo_domingo'] !== null) {
                return $config['horario_sorteo_domingo'];
            }
            
            // Sino, retornar horario normal
            return $config['horario_sorteo'] ?? 'N/A';
        }
    }
    
    // Si no se encuentra en la configuración, retornar N/A
    return 'N/A';
}

// Función para obtener URL del logo desde enloteria.com
function obtener_logo($loteria) {
    $nombre = strtolower($loteria);
    $base_url = 'https://enloteria.com/assets/';
    
    // Mapeo de loterías a URLs de logos oficiales
    if (stripos($nombre, 'leidsa') !== false) {
        return $base_url . 'leidsa-26031367f0cd9ba743253bbae1c55e546de6732adf18eda71c73d4387c0da2d1.svg';
    }
    if (stripos($nombre, 'loteka') !== false) {
        return $base_url . 'loteka-58fb6f5ce8c707d7726e35c91f8aee0a986576dab82ce0ecd53efa237f7936aa.svg';
    }
    if (stripos($nombre, 'nacional') !== false) {
        return $base_url . 'nacional-6f4e8ccd25d07edb6452e418cbfab9ae8ec36f728266fc7189a6ee68e7bdd4f0.svg';
    }
    if (stripos($nombre, 'gana') !== false) {
        return $base_url . 'ganamas-5c2fba8ccbe1a70b7b12afdf18ff38cfbf7ce032e5c7397dcb1f03e858ff4335.svg';
    }
    if (stripos($nombre, 'real') !== false) {
        return $base_url . 'real-eeb33736cd36eff0dfb219af8954fe0ab37245bd412801ea864f6a131c3c758c.svg';
    }
    if (stripos($nombre, 'primera') !== false) {
        return $base_url . 'la_primera-dd745b944dea9df3a0640447e0aa18aeb076861fae80fe9a19f749c3e1a6541d.svg';
    }
    if (stripos($nombre, 'suerte') !== false) {
        return $base_url . 'la_suerte-503a3d9314a080d132a414fdc5a6940ddd50ef1d235dcc621bc1bc7f7516fbb1.svg';
    }
    if (stripos($nombre, 'lotedom') !== false) {
        return $base_url . 'lotedom-9aae43273ce4d8d4d5429f6f57f2fadc54012eb96fc80d6f59cfb9b72576b7e9.svg';
    }
    if (stripos($nombre, 'florida') !== false) {
        return $base_url . 'florida-0d3b11e2215473f987ac28c156cbff56ccf186e650ddf2df2b6b194254677eed.svg';
    }
    if (stripos($nombre, 'york') !== false) {
        return $base_url . 'new_york-e78bc3206a0497915ddab4a77f80e06ad0f8eb6d6e355770340c17be4f29a616.svg';
    }
    if (stripos($nombre, 'anguila') !== false || stripos($nombre, 'angulla') !== false) {
        return $base_url . 'anguila-78bcb1b1711b3176ea0eb9fe37768936cc1f70530f44fcc165067a087fba5b00.svg';
    }
    if (stripos($nombre, 'king') !== false) {
        return $base_url . 'king_lottery-ea033db5247fa2e2b002245b33b52ae936c2f1b2be04927ed236032c2d2c2e9f.svg';
    }
    
    // Si no se encuentra, retornar null (no mostrar logo)
    return null;
}

// Función para obtener horario de sorteo desde configuración
function obtener_horario_sorteo($loteria, $fecha) {
    global $configuracion_urls;
    
    // Normalizar nombre de lotería para búsqueda
    $nombre_normalizado = strtolower(trim($loteria));
    
    // Detectar si la fecha es domingo
    $fecha_obj = DateTime::createFromFormat('d/m/Y', $fecha);
    $es_domingo = ($fecha_obj && $fecha_obj->format('N') == 7); // 7 = domingo
    
    // Buscar en configuración por nombre o alias
    foreach ($configuracion_urls as $key => $config) {
        $nombre_config = strtolower($config['nombre']);
        $alias_config = strtolower($config['alias']);
        
        // Comparar con nombre o alias
        if (stripos($nombre_normalizado, $nombre_config) !== false || 
            stripos($nombre_normalizado, $alias_config) !== false ||
            stripos($nombre_config, $nombre_normalizado) !== false ||
            stripos($alias_config, $nombre_normalizado) !== false) {
            
            // Si es domingo Y tiene horario especial de domingo
            if ($es_domingo && !empty($config['horario_sorteo_domingo'])) {
                return $config['horario_sorteo_domingo'];
            }
            
            // Retornar horario normal
            return $config['horario_sorteo'] ?? 'N/A';
        }
    }
    
    // Si no se encuentra en configuración, retornar N/A
    return 'N/A';
}

/**
 * Convertir horario a minutos desde medianoche para ordenamiento
 * Formato esperado: "12:55 pm", "8:50 pm", "1:00 pm", etc.
 */
function horario_a_minutos($horario) {
    if (empty($horario) || $horario === 'N/A') {
        return 9999; // Poner al final los horarios sin definir
    }
    
    // Normalizar el horario (quitar espacios extra)
    $horario = strtolower(trim($horario));
    
    // Extraer componentes: hora, minutos, am/pm
    if (preg_match('/(\d{1,2}):(\d{2})\s*(am|pm)/i', $horario, $matches)) {
        $hora = intval($matches[1]);
        $minutos = intval($matches[2]);
        $periodo = strtolower($matches[3]);
        
        // Convertir a formato 24 horas
        if ($periodo === 'pm' && $hora !== 12) {
            $hora += 12;
        } elseif ($periodo === 'am' && $hora === 12) {
            $hora = 0;
        }
        
        // Retornar total de minutos desde medianoche
        return ($hora * 60) + $minutos;
    }
    
    return 9999; // Si no se puede parsear, poner al final
}

// Obtener fecha actual o fecha solicitada
// Si no hay parámetro de fecha, buscar la última fecha con datos en la BD
$fechaFiltro = null;

if (isset($_GET['fecha']) && !empty(trim($_GET['fecha']))) {
    $fechaFiltro = trim($_GET['fecha']);
} else {
    // Buscar la última fecha con datos en toda la BD
    try {
        $pdo = obtener_conexion_db();
        $stmt = $pdo->query("SELECT loteria FROM loterias ORDER BY loteria");
        $loterias = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $fechaMasReciente = null;
        
        foreach ($loterias as $loteria) {
            $nombreTabla = normalizar_nombre_tabla($loteria);
            try {
                $stmt = $pdo->query("SELECT fecha FROM `$nombreTabla` ORDER BY id DESC LIMIT 1");
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $fecha = DateTime::createFromFormat('d/m/Y', $row['fecha']);
                    if ($fecha && (!$fechaMasReciente || $fecha > $fechaMasReciente)) {
                        $fechaMasReciente = $fecha;
                    }
                }
            } catch (Exception $e) {
                continue;
            }
        }
        
        $fechaFiltro = $fechaMasReciente ? $fechaMasReciente->format('d/m/Y') : date('d/m/Y');
    } catch (Exception $e) {
        $fechaFiltro = date('d/m/Y');
    }
}

try {
    $pdo = obtener_conexion_db();
    $stmt = $pdo->query("SELECT loteria FROM loterias ORDER BY loteria");
    $loterias = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $resultados = [];
    
    foreach ($loterias as $loteria) {
        $nombreTabla = normalizar_nombre_tabla($loteria);
        
        try {
            // Primero intentar obtener resultado de la fecha exacta
            $stmt = $pdo->prepare("
                SELECT fecha, primera, segunda, tercera
                FROM `$nombreTabla`
                WHERE fecha = ?
                ORDER BY id DESC
                LIMIT 1
            ");
            $stmt->execute([$fechaFiltro]);
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Si no hay resultado para la fecha exacta, buscar el último resultado disponible anterior
            if (!$resultado) {
                $stmt = $pdo->prepare("
                    SELECT fecha, primera, segunda, tercera
                    FROM `$nombreTabla`
                    WHERE STR_TO_DATE(fecha, '%d/%m/%Y') < STR_TO_DATE(?, '%d/%m/%Y')
                    ORDER BY STR_TO_DATE(fecha, '%d/%m/%Y') DESC, id DESC
                    LIMIT 1
                ");
                $stmt->execute([$fechaFiltro]);
                $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            if ($resultado) {
                // Determinar si es HOY, AYER o una fecha específica
                $fechaHoy = date('d/m/Y');
                $fechaAyer = date('d/m/Y', strtotime('-1 day'));
                
                $esHoy = ($resultado['fecha'] === $fechaHoy);
                $esAyer = ($resultado['fecha'] === $fechaAyer);
                
                // Crear etiqueta de fecha para mostrar
                if ($esHoy) {
                    $etiquetaFecha = 'HOY';
                } elseif ($esAyer) {
                    $etiquetaFecha = 'AYER';
                } else {
                    $etiquetaFecha = $resultado['fecha'];
                }
                
                $extranjeras = ['Florida', 'New York', 'Anguila', 'King Lottery'];
                $esExtranjera = false;
                foreach ($extranjeras as $ext) {
                    if (stripos($loteria, $ext) !== false) {
                        $esExtranjera = true;
                        break;
                    }
                }
                
                $resultados[] = [
                    'nombre' => $loteria,
                    'logo' => obtener_logo($loteria),
                    'fecha' => $resultado['fecha'],
                    'etiquetaFecha' => $etiquetaFecha,
                    'hora' => obtener_horario_sorteo($loteria, $resultado['fecha']),
                    'primera' => str_pad($resultado['primera'], 2, '0', STR_PAD_LEFT),
                    'segunda' => str_pad($resultado['segunda'], 2, '0', STR_PAD_LEFT),
                    'tercera' => str_pad($resultado['tercera'], 2, '0', STR_PAD_LEFT),
                    'esHoy' => $esHoy,
                    'categoria' => $esExtranjera ? 'extranjera' : 'nacional'
                ];
            }
            
        } catch (Exception $e) {
            continue;
        }
    }
    
    // Ordenar resultados por horario de sorteo (ascendente)
    usort($resultados, function($a, $b) {
        // Convertir ambos horarios a minutos
        $minutosA = horario_a_minutos($a['hora']);
        $minutosB = horario_a_minutos($b['hora']);
        
        // Ordenar de menor a mayor (ascendente)
        return $minutosA - $minutosB;
    });
    
} catch (Exception $e) {
    $resultados = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Números RD - Resultados de Loterías Dominicanas en Tiempo Real</title>
    <meta name="description" content="Consulta los resultados de loterías dominicanas al instante. Lotería Nacional, Leidsa, Loteka, Gana Más y más.">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #2563eb;
            --success: #10b981;
            --today-bg: #d1fae5;
            --today-border: #10b981;
            --past-bg: #f8fafc;
            --past-border: #cbd5e1;
            --text-dark: #1e293b;
            --text-light: #64748b;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.12);
            --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 25px rgba(0,0,0,0.15);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            color: var(--text-dark);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: white;
            border-radius: 20px;
            padding: 30px 40px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-lg);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo {
            font-size: 2.5em;
        }

        .brand h1 {
            font-size: 2em;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 800;
        }

        .brand p {
            color: var(--text-light);
            font-size: 0.9em;
            font-weight: 500;
        }

        .header-controls {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .search-box {
            position: relative;
        }

        .search-box input {
            padding: 12px 45px;
            border: 2px solid #e2e8f0;
            border-radius: 50px;
            font-size: 0.95em;
            width: 280px;
            transition: all 0.3s;
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 1.2em;
        }

        .search-btn {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            background: var(--primary);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 50px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.85em;
            transition: all 0.3s;
        }

        .search-btn:hover {
            background: #1d4ed8;
        }

        /* MODAL DE ANÁLISIS */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            overflow-y: auto;
            padding: 20px;
        }

        .modal-overlay.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            border-radius: 20px;
            max-width: 900px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            box-shadow: var(--shadow-lg);
            animation: modalAppear 0.3s ease;
        }

        @keyframes modalAppear {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 20px 20px 0 0;
            position: relative;
        }

        .modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.5em;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }

        .modal-title {
            font-size: 2em;
            font-weight: 800;
            margin-bottom: 10px;
        }

        .modal-subtitle {
            opacity: 0.9;
            font-size: 1.1em;
        }

        .modal-body {
            padding: 30px;
        }

        .loading-spinner {
            text-align: center;
            padding: 60px 20px;
        }

        .spinner {
            border: 4px solid #f3f4f6;
            border-top: 4px solid var(--primary);
            border-radius: 50%;
            width: 60px;
            height: 60px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .stat-section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f8fafc;
            border-radius: 15px;
            border-left: 4px solid var(--primary);
        }

        .stat-section h3 {
            color: var(--text-dark);
            margin-bottom: 15px;
            font-size: 1.3em;
        }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            box-shadow: var(--shadow-sm);
        }

        .stat-value {
            font-size: 2em;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 5px;
        }

        .stat-label {
            color: var(--text-light);
            font-size: 0.9em;
        }

        .ai-explanation {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border: 2px solid var(--success);
            border-radius: 15px;
            padding: 25px;
            margin-top: 20px;
            position: relative;
        }

        .ai-badge {
            position: absolute;
            top: -12px;
            left: 20px;
            background: var(--success);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 700;
        }

        .ai-text {
            color: var(--text-dark);
            line-height: 1.8;
            font-size: 1.05em;
            margin-top: 10px;
        }

        .number-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }

        .number-badge {
            background: var(--primary);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.95em;
        }

        .number-badge.friend {
            background: var(--success);
        }

        .number-badge.enemy {
            background: #ef4444;
        }

        /* INDICADOR DE AUTO-REFRESH */
        .refresh-indicator {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: white;
            padding: 12px 20px;
            border-radius: 50px;
            box-shadow: var(--shadow-lg);
            font-size: 0.85em;
            color: var(--text-light);
            z-index: 9998;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
        }

        .refresh-indicator.paused {
            background: #fef3c7;
            border: 2px solid #f59e0b;
        }

        .refresh-indicator.paused::before {
            content: '⏸️';
        }

        .refresh-dot {
            width: 8px;
            height: 8px;
            background: var(--success);
            border-radius: 50%;
            animation: pulse-dot 2s infinite;
        }

        @keyframes pulse-dot {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        @media (max-width: 768px) {
            .refresh-indicator {
                bottom: 10px;
                right: 10px;
                font-size: 0.75em;
                padding: 8px 15px;
            }
        }

        .date-badge {
            background: var(--primary);
            color: white;
            padding: 12px 25px;
            border-radius: 50px;
            font-weight: 600;
            box-shadow: var(--shadow-md);
        }

        /* ===================================
           NAVBAR MODERNO CON CALENDARIO
           =================================== */
        
        .results-navbar {
            background: white;
            border-radius: 20px;
            padding: 20px 30px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-lg);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .navbar-left {
            position: relative;
        }

        .navbar-right {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        /* BOTÓN CALENDARIO */
        .calendar-toggle {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1em;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .calendar-toggle:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .calendar-toggle .arrow {
            transition: transform 0.3s;
        }

        .calendar-toggle.active .arrow {
            transform: rotate(180deg);
        }

        /* CALENDARIO DROPDOWN */
        .calendar-dropdown {
            position: absolute;
            top: calc(100% + 10px);
            left: 0;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            padding: 20px;
            min-width: 350px;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        .calendar-dropdown.active {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        /* HEADER DEL CALENDARIO */
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f1f5f9;
            gap: 10px;
        }

        .calendar-selectors {
            display: flex;
            gap: 10px;
            flex: 1;
            justify-content: center;
        }

        .calendar-select {
            padding: 8px 12px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            background: white;
            font-weight: 600;
            font-size: 0.95em;
            color: var(--text-dark);
            cursor: pointer;
            transition: all 0.3s;
            outline: none;
        }

        .calendar-select:hover {
            border-color: var(--primary);
        }

        .calendar-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .calendar-title {
            font-size: 1.1em;
            font-weight: 700;
            color: var(--text-dark);
        }

        .calendar-nav {
            background: #f8fafc;
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            color: var(--text-dark);
        }

        .calendar-nav:hover {
            background: var(--primary);
            color: white;
            transform: scale(1.1);
        }

        /* DÍAS DE LA SEMANA */
        .calendar-weekdays {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
            margin-bottom: 10px;
        }

        .calendar-weekdays div {
            text-align: center;
            font-weight: 600;
            font-size: 0.85em;
            color: var(--text-light);
            padding: 8px 0;
        }

        /* DÍAS DEL CALENDARIO */
        .calendar-days {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 6px;
        }

        .calendar-day {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            font-size: 0.95em;
        }

        .calendar-day:hover {
            background: #f1f5f9;
            transform: scale(1.1);
        }

        .calendar-day.other-month {
            color: #cbd5e1;
        }

        .calendar-day.today {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            color: white;
            font-weight: 700;
            box-shadow: 0 4px 10px rgba(251, 191, 36, 0.3);
        }

        .calendar-day.selected {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 700;
            box-shadow: 0 4px 10px rgba(102, 126, 234, 0.4);
        }

        .calendar-day.selected:hover {
            transform: scale(1.05);
        }

        .calendar-day.disabled {
            color: #e2e8f0;
            cursor: not-allowed;
            pointer-events: none;
        }

        /* FOOTER DEL CALENDARIO */
        .calendar-footer {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #f1f5f9;
            display: flex;
            justify-content: center;
        }

        .calendar-today-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            color: var(--text-dark);
        }

        .calendar-today-btn:hover {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
            transform: translateY(-2px);
        }

        .filter-btn {
            padding: 12px 24px;
            border: 2px solid #e2e8f0;
            background: white;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95em;
        }

        .filter-btn:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
        }

        .filter-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .results-count {
            color: var(--text-light);
            font-weight: 600;
            padding: 12px 20px;
            background: #f8fafc;
            border-radius: 50px;
        }

        .results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .lottery-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: var(--shadow-md);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .lottery-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .lottery-card.today {
            background: var(--today-bg);
            border: 3px solid var(--today-border);
        }

        .lottery-card.today::before {
            content: 'HOY';
            position: absolute;
            top: 15px;
            right: 15px;
            background: var(--success);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.75em;
            font-weight: 700;
            animation: pulse 2s infinite;
        }

        .lottery-card.past {
            background: var(--past-bg);
            border: 2px solid var(--past-border);
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .lottery-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .lottery-logo {
            width: 50px;
            height: 50px;
            object-fit: contain;
        }

        .lottery-name {
            font-size: 1.3em;
            font-weight: 700;
            color: var(--text-dark);
        }

        .lottery-time {
            font-size: 0.85em;
            color: var(--text-light);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .date-badge-small {
            background: #f1f5f9;
            color: var(--text-light);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }

        .numbers {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin: 20px 0;
        }

        .number {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            width: 70px;
            height: 70px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2em;
            font-weight: 700;
            box-shadow: var(--shadow-md);
            transition: all 0.3s;
        }

        .lottery-card.today .number {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            animation: numberPop 0.5s ease;
        }

        .lottery-card.past .number {
            background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%);
        }

        @keyframes numberPop {
            0% { transform: scale(0.8); opacity: 0; }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); opacity: 1; }
        }

        .number:hover {
            transform: scale(1.1) rotate(5deg);
        }

        .no-results {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: var(--shadow-md);
        }

        .no-results-icon {
            font-size: 4em;
            margin-bottom: 20px;
        }

        .no-results h3 {
            color: var(--text-dark);
            margin-bottom: 10px;
            font-size: 1.5em;
        }

        .no-results p {
            color: var(--text-light);
        }

        .info-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-top: 30px;
            box-shadow: var(--shadow-md);
        }

        .info-section h2 {
            color: var(--text-dark);
            margin-bottom: 15px;
            font-size: 1.5em;
        }

        .info-section p {
            color: var(--text-light);
            line-height: 1.8;
            margin-bottom: 15px;
        }

        @media (max-width: 768px) {
            .header {
                padding: 20px;
                text-align: center;
            }

            .logo-section {
                width: 100%;
                justify-content: center;
            }

            .header-controls {
                width: 100%;
                flex-direction: column;
            }

            .search-box input {
                width: 100%;
            }

            .results-grid {
                grid-template-columns: 1fr;
            }

            .brand h1 {
                font-size: 1.5em;
            }

            .results-navbar {
                flex-direction: column;
                padding: 15px;
            }

            .navbar-left {
                width: 100%;
            }

            .calendar-toggle {
                width: 100%;
                justify-content: center;
            }

            .navbar-right {
                flex-direction: column;
                width: 100%;
                gap: 10px;
            }

            .filter-btn {
                width: 100%;
                justify-content: center;
            }

            .calendar-dropdown {
                min-width: 290px;
                left: 50%;
                transform: translateX(-50%) translateY(-10px);
            }

            .calendar-dropdown.active {
                transform: translateX(-50%) translateY(0);
            }

            .results-count {
                width: 100%;
                text-align: center;
            }

            .results-count {
                width: 100%;
                text-align: center;
                margin-left: 0;
                margin-top: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <div class="logo-section">
                <div class="logo">🎯</div>
                <div class="brand">
                    <h1>Números RD</h1>
                    <p>Resultados en tiempo real</p>
                </div>
            </div>
            <div class="header-controls">
                <div class="search-box">
                    <span class="search-icon">🔍</span>
                    <input type="text" id="searchNumbers" placeholder="Analizar números">
                    <button class="search-btn" onclick="analizarNumero()">Analizar</button>
                </div>
                <div class="date-badge">
                    <?php 
                    setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'Spanish_Spain');
                    echo strftime('%A, %d de %B de %Y') ?: date('l, d \d\e F \d\e Y');
                    ?>
                </div>
            </div>
        </header>

        <!-- NAVBAR MODERNO CON CALENDARIO PERSONALIZADO -->
        <nav class="results-navbar">
            <div class="navbar-left">
                <button class="calendar-toggle" onclick="toggleCalendar()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <span id="selectedDateText">
                        <?php 
                        if ($fechaFiltro === date('d/m/Y')) {
                            echo 'Hoy - ' . $fechaFiltro;
                        } else {
                            echo $fechaFiltro;
                        }
                        ?>
                    </span>
                    <svg class="arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </button>

                <!-- CALENDARIO PERSONALIZADO HERMOSO -->
                <div class="calendar-dropdown" id="calendarDropdown">
                    <div class="calendar-header">
                        <button class="calendar-nav" onclick="changeMonth(-1)">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="15 18 9 12 15 6"></polyline>
                            </svg>
                        </button>
                        <div class="calendar-selectors">
                            <select class="calendar-select" id="monthSelect" onchange="selectMonthYear()">
                                <option value="0">Enero</option>
                                <option value="1">Febrero</option>
                                <option value="2">Marzo</option>
                                <option value="3">Abril</option>
                                <option value="4">Mayo</option>
                                <option value="5">Junio</option>
                                <option value="6">Julio</option>
                                <option value="7">Agosto</option>
                                <option value="8">Septiembre</option>
                                <option value="9">Octubre</option>
                                <option value="10">Noviembre</option>
                                <option value="11">Diciembre</option>
                            </select>
                            <select class="calendar-select" id="yearSelect" onchange="selectMonthYear()">
                                <!-- Se genera dinámicamente -->
                            </select>
                        </div>
                        <button class="calendar-nav" onclick="changeMonth(1)">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </button>
                    </div>
                    <div class="calendar-weekdays">
                        <div>Dom</div>
                        <div>Lun</div>
                        <div>Mar</div>
                        <div>Mié</div>
                        <div>Jue</div>
                        <div>Vie</div>
                        <div>Sáb</div>
                    </div>
                    <div class="calendar-days" id="calendarDays"></div>
                    <div class="calendar-footer">
                        <button class="calendar-today-btn" onclick="selectToday()">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            Hoy
                        </button>
                    </div>
                </div>
            </div>

            <div class="navbar-right">
                <button class="filter-btn active" onclick="filterResults('all')">🎲 Todas</button>
                <button class="filter-btn" onclick="filterResults('today')">✨ Hoy</button>
                <button class="filter-btn" onclick="filterResults('nacional')"><span style="font-family: 'Segoe UI Emoji', 'Apple Color Emoji', 'Noto Color Emoji', sans-serif;">🇩🇴</span> Nacionales</button>
                <button class="filter-btn" onclick="filterResults('extranjera')">🌎 Extranjeras</button>
                <div class="results-count">
                    <span id="resultCount"><?php echo count($resultados); ?></span> resultados
                </div>
            </div>
        </nav>

        <div class="results-grid" id="resultsGrid">
            <?php if (empty($resultados)): ?>
                <div class="no-results" style="grid-column: 1 / -1;">
                    <div class="no-results-icon">📭</div>
                    <h3>No hay resultados disponibles</h3>
                    <p>Los resultados aparecerán aquí cuando estén disponibles.</p>
                </div>
            <?php else: ?>
                <?php foreach ($resultados as $resultado): ?>
                    <a href="loteria_detalle.php?loteria=<?php echo urlencode($resultado['nombre']); ?>" style="text-decoration: none; color: inherit;">
                        <div class="lottery-card <?php echo $resultado['esHoy'] ? 'today' : 'past'; ?>" 
                             data-category="<?php echo $resultado['categoria']; ?>"
                             data-numbers="<?php echo $resultado['primera'] . $resultado['segunda'] . $resultado['tercera']; ?>">
                            
                            <div class="card-header">
                                <div class="lottery-info">
                                    <?php if ($resultado['logo']): ?>
                                        <img src="<?php echo htmlspecialchars($resultado['logo']); ?>" 
                                             alt="<?php echo htmlspecialchars($resultado['nombre']); ?>" 
                                             class="lottery-logo"
                                             onerror="this.style.display='none'">
                                    <?php endif; ?>
                                    <div>
                                        <div class="lottery-name"><?php echo htmlspecialchars($resultado['nombre']); ?></div>
                                        <div class="lottery-time">🕐 <?php echo htmlspecialchars($resultado['hora']); ?></div>
                                    </div>
                                </div>
                                <div class="date-badge-small"><?php echo $resultado['fecha']; ?></div>
                            </div>
                            
                            <div class="numbers">
                                <div class="number"><?php echo $resultado['primera']; ?></div>
                                <div class="number"><?php echo $resultado['segunda']; ?></div>
                                <div class="number"><?php echo $resultado['tercera']; ?></div>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="info-section">
            <h2>📊 Sobre Números RD</h2>
            <p>
                <strong>Números RD</strong> es la plataforma más moderna para consultar resultados de loterías dominicanas en tiempo real. 
                Ofrecemos información instantánea, verificada y con un diseño limpio que hace tu experiencia única.
            </p>
            <p>
                Los resultados de <strong>hoy</strong> aparecen destacados en verde para que los identifiques al instante. 
                Los sorteos anteriores se muestran en gris para una navegación clara e intuitiva.
            </p>
            <p>
                🎯 Rápido • ✅ Confiable • 🎨 Moderno
            </p>
        </div>
    </div>

    <!-- INDICADOR DE AUTO-REFRESH -->
    <div class="refresh-indicator" id="refreshIndicator">
        <div class="refresh-dot"></div>
        <span id="refreshText">Auto-actualización activa</span>
    </div>

    <!-- MODAL DE ANÁLISIS -->
    <div class="modal-overlay" id="modalAnalisis">
        <div class="modal-content">
            <div class="modal-header">
                <button class="modal-close" onclick="cerrarModal()">×</button>
                <div class="modal-title" id="modalTitulo">Analizando...</div>
                <div class="modal-subtitle" id="modalSubtitulo">Generando estadísticas avanzadas</div>
            </div>
            <div class="modal-body" id="modalBody">
                <div class="loading-spinner">
                    <div class="spinner"></div>
                    <p style="color: var(--text-light);">Analizando datos históricos...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // ===================================
        // AUTO-REFRESH INTELIGENTE
        // ===================================
        let refreshTimer = null;
        let modalAbierto = false;

        function iniciarAutoRefresh() {
            if (refreshTimer) {
                clearTimeout(refreshTimer);
            }
            
            refreshTimer = setTimeout(() => {
                // Solo recargar si el modal NO está abierto
                if (!modalAbierto) {
                    location.reload();
                } else {
                    // Si el modal está abierto, intentar de nuevo en 1 minuto
                    iniciarAutoRefresh();
                }
            }, 60000);
        }

        // Iniciar el timer
        iniciarAutoRefresh();

        // ===================================
        // CALENDARIO PERSONALIZADO
        // ===================================
        
        let currentDate = new Date();
        let selectedDate = new Date();
        
        // Generar años (desde 2010 hasta el año actual)
        function generateYearOptions() {
            const yearSelect = document.getElementById('yearSelect');
            const currentYear = new Date().getFullYear();
            const startYear = 2010;
            
            yearSelect.innerHTML = '';
            
            for (let year = currentYear; year >= startYear; year--) {
                const option = document.createElement('option');
                option.value = year;
                option.textContent = year;
                yearSelect.appendChild(option);
            }
        }
        
        function toggleCalendar() {
            const dropdown = document.getElementById('calendarDropdown');
            const toggle = document.querySelector('.calendar-toggle');
            dropdown.classList.toggle('active');
            toggle.classList.toggle('active');
            
            if (dropdown.classList.contains('active')) {
                generateYearOptions();
                renderCalendar();
            }
        }
        
        function changeMonth(delta) {
            currentDate.setMonth(currentDate.getMonth() + delta);
            renderCalendar();
        }
        
        function selectMonthYear() {
            const month = parseInt(document.getElementById('monthSelect').value);
            const year = parseInt(document.getElementById('yearSelect').value);
            currentDate = new Date(year, month, 1);
            renderCalendar();
        }
        
        function selectToday() {
            selectedDate = new Date();
            currentDate = new Date();
            renderCalendar();
            applyDate();
        }
        
        function selectDate(year, month, day) {
            selectedDate = new Date(year, month, day);
            renderCalendar();
            applyDate();
        }
        
        function applyDate() {
            const day = String(selectedDate.getDate()).padStart(2, '0');
            const month = String(selectedDate.getMonth() + 1).padStart(2, '0');
            const year = selectedDate.getFullYear();
            
            // Actualizar texto del botón
            const isToday = selectedDate.toDateString() === new Date().toDateString();
            const dateText = document.getElementById('selectedDateText');
            
            if (isToday) {
                dateText.textContent = `Hoy - ${day}/${month}/${year}`;
            } else {
                const meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
                              'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
                dateText.textContent = `${day} ${meses[selectedDate.getMonth()]} ${year}`;
            }
            
            // Cerrar calendario
            document.getElementById('calendarDropdown').classList.remove('active');
            document.querySelector('.calendar-toggle').classList.remove('active');
            
            // Recargar con la fecha seleccionada
            const fechaFormateada = `${day}/${month}/${year}`;
            window.location.href = `resultados.php?fecha=${encodeURIComponent(fechaFormateada)}`;
        }
        
        function renderCalendar() {
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();
            
            // Actualizar selectores
            document.getElementById('monthSelect').value = month;
            document.getElementById('yearSelect').value = year;
            
            // Calcular días
            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const daysInPrevMonth = new Date(year, month, 0).getDate();
            
            const calendarDays = document.getElementById('calendarDays');
            calendarDays.innerHTML = '';
            
            const today = new Date();
            const maxDate = new Date(); // Fecha máxima permitida
            
            // Días del mes anterior
            for (let i = firstDay - 1; i >= 0; i--) {
                const day = daysInPrevMonth - i;
                const dayEl = createDayElement(day, 'other-month');
                calendarDays.appendChild(dayEl);
            }
            
            // Días del mes actual
            for (let day = 1; day <= daysInMonth; day++) {
                const date = new Date(year, month, day);
                const isToday = date.toDateString() === today.toDateString();
                const isSelected = date.toDateString() === selectedDate.toDateString();
                const isFuture = date > maxDate;
                
                let className = '';
                if (isFuture) className = 'disabled';
                else if (isSelected) className = 'selected';
                else if (isToday) className = 'today';
                
                const dayEl = createDayElement(day, className);
                if (!isFuture) {
                    dayEl.onclick = () => selectDate(year, month, day);
                }
                calendarDays.appendChild(dayEl);
            }
            
            // Días del mes siguiente
            const totalCells = calendarDays.children.length;
            const remainingCells = totalCells % 7 === 0 ? 0 : 7 - (totalCells % 7);
            
            for (let day = 1; day <= remainingCells; day++) {
                const dayEl = createDayElement(day, 'other-month');
                calendarDays.appendChild(dayEl);
            }
        }
        
        function createDayElement(day, className) {
            const div = document.createElement('div');
            div.className = `calendar-day ${className}`;
            div.textContent = day;
            return div;
        }
        
        // Cerrar calendario al hacer click fuera
        document.addEventListener('click', function(event) {
            const calendar = document.getElementById('calendarDropdown');
            const toggle = document.querySelector('.calendar-toggle');
            
            if (calendar && toggle && !calendar.contains(event.target) && !toggle.contains(event.target)) {
                calendar.classList.remove('active');
                toggle.classList.remove('active');
            }
        });
        
        // Inicializar calendario con fecha de BD o de URL
        window.addEventListener('DOMContentLoaded', function() {
            <?php
            // Parsear la fecha filtro para inicializar el calendario
            $partesFecha = explode('/', $fechaFiltro);
            if (count($partesFecha) === 3) {
                echo "const fechaBD = new Date({$partesFecha[2]}, " . ((int)$partesFecha[1] - 1) . ", {$partesFecha[0]});";
                echo "selectedDate = fechaBD;";
                echo "currentDate = new Date(fechaBD);";
            }
            ?>
        });

        // ===================================
        // BÚSQUEDA Y FILTROS ORIGINALES
        // ===================================
        function filterResults(type) {
            const cards = document.querySelectorAll('.lottery-card');
            const buttons = document.querySelectorAll('.filter-btn');
            let visibleCount = 0;

            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');

            cards.forEach(card => {
                const category = card.dataset.category;
                const isToday = card.classList.contains('today');
                let show = false;

                if (type === 'all') {
                    show = true;
                } else if (type === 'today') {
                    show = isToday;
                } else if (type === 'nacional' || type === 'extranjera') {
                    show = (category === type);
                }

                if (show) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            document.getElementById('resultCount').textContent = visibleCount;
        }

        // Búsqueda simple (deprecated, usar análisis)
        document.getElementById('searchNumbers').addEventListener('input', (e) => {
            const query = e.target.value.replace(/\D/g, '');
            if (query.length < 2) {
                // Mostrar todos
                document.querySelectorAll('.lottery-card').forEach(card => {
                    card.style.display = 'block';
                });
                return;
            }
            
            const cards = document.querySelectorAll('.lottery-card');
            let visibleCount = 0;

            cards.forEach(card => {
                const numbers = card.dataset.numbers;
                if (query === '' || numbers.includes(query)) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            document.getElementById('resultCount').textContent = visibleCount;
        });

        // Enter para analizar
        document.getElementById('searchNumbers').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                analizarNumero();
            }
        });

        // ===================================
        // ANÁLISIS CON IA
        // ===================================
        
        function abrirModal() {
            modalAbierto = true;
            document.getElementById('modalAnalisis').classList.add('active');
            document.body.style.overflow = 'hidden';
            
            // Actualizar indicador
            const indicator = document.getElementById('refreshIndicator');
            const text = document.getElementById('refreshText');
            indicator.classList.add('paused');
            text.textContent = 'Auto-actualización pausada';
        }

        function cerrarModal() {
            modalAbierto = false;
            document.getElementById('modalAnalisis').classList.remove('active');
            document.body.style.overflow = 'auto';
            
            // Actualizar indicador
            const indicator = document.getElementById('refreshIndicator');
            const text = document.getElementById('refreshText');
            indicator.classList.remove('paused');
            text.textContent = 'Auto-actualización activa';
            
            // Reiniciar el timer de auto-refresh
            iniciarAutoRefresh();
        }

        async function analizarNumero() {
            const input = document.getElementById('searchNumbers').value.trim();
            
            if (!input) {
                alert('Por favor, ingresa un número o combinación');
                return;
            }

            // Determinar si es número o combinación
            const esCombinacion = input.includes('-');
            const url = esCombinacion 
                ? `analizar_numero.php?combinacion=${encodeURIComponent(input)}`
                : `analizar_numero.php?numero=${encodeURIComponent(input)}`;

            // Abrir modal
            abrirModal();
            
            document.getElementById('modalTitulo').textContent = 'Analizando...';
            document.getElementById('modalSubtitulo').textContent = esCombinacion 
                ? `Combinación: ${input}`
                : `Número: ${input}`;
            document.getElementById('modalBody').innerHTML = `
                <div class="loading-spinner">
                    <div class="spinner"></div>
                    <p style="color: var(--text-light);">Analizando datos históricos...</p>
                </div>
            `;

            try {
                // Obtener estadísticas
                const response = await fetch(url);
                const data = await response.json();

                if (data.error) {
                    throw new Error(data.error);
                }

                // Mostrar resultados
                if (esCombinacion) {
                    mostrarAnalisisCombinacion(data);
                } else {
                    mostrarAnalisisNumero(data);
                }

                // Generar explicación con IA
                generarExplicacionIA(data, esCombinacion);

            } catch (error) {
                document.getElementById('modalBody').innerHTML = `
                    <div style="text-align: center; padding: 40px;">
                        <h3 style="color: #ef4444; margin-bottom: 10px;">❌ Error</h3>
                        <p style="color: var(--text-light);">${error.message}</p>
                        <button onclick="cerrarModal()" style="margin-top: 20px; padding: 10px 30px; background: var(--primary); color: white; border: none; border-radius: 50px; cursor: pointer; font-weight: 600;">Cerrar</button>
                    </div>
                `;
            }
        }

        function mostrarAnalisisNumero(data) {
            const html = `
                <div class="stat-section">
                    <h3>📊 Estadísticas Generales (Últimos 12 meses)</h3>
                    <div class="stat-grid">
                        <div class="stat-card">
                            <div class="stat-value">${data.apariciones_totales}</div>
                            <div class="stat-label">Apariciones Totales</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">${data.apariciones_primera}</div>
                            <div class="stat-label">En Primera</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">${data.apariciones_segunda}</div>
                            <div class="stat-label">En Segunda</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">${data.apariciones_tercera}</div>
                            <div class="stat-label">En Tercera</div>
                        </div>
                    </div>
                </div>

                <div class="stat-section">
                    <h3>📅 Última Aparición</h3>
                    <p><strong>Fecha:</strong> ${data.ultima_aparicion || 'No disponible'}</p>
                    <p><strong>Lotería:</strong> ${data.ultima_loteria || 'No disponible'}</p>
                    <p><strong>Días sin salir:</strong> ${data.dias_sin_salir} días</p>
                </div>

                <div class="stat-section">
                    <h3>⏱️ Tiempo de Repetición</h3>
                    <div class="stat-grid">
                        <div class="stat-card">
                            <div class="stat-value">${data.tiempo_repeticion.promedio}</div>
                            <div class="stat-label">Promedio (días)</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">${data.tiempo_repeticion.minimo}</div>
                            <div class="stat-label">Mínimo (días)</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">${data.tiempo_repeticion.maximo}</div>
                            <div class="stat-label">Máximo (días)</div>
                        </div>
                    </div>
                </div>

                <div class="stat-section">
                    <h3>🎰 Loterías Más Frecuentes</h3>
                    ${Object.entries(data.loterías_frecuentes).map(([loteria, count]) => `
                        <p><strong>${loteria}:</strong> ${count} veces</p>
                    `).join('')}
                </div>

                <div class="stat-section">
                    <h3>🤝 Números Compañeros Frecuentes</h3>
                    <div class="number-list">
                        ${Object.entries(data.companeros_frecuentes).slice(0, 10).map(([num, count]) => `
                            <span class="number-badge friend">${num} (${count}x)</span>
                        `).join('')}
                    </div>
                </div>

                <div class="stat-section">
                    <h3>✅ Números Amigos (salen después)</h3>
                    <div class="number-list">
                        ${Object.entries(data.numeros_amigos).map(([num, count]) => `
                            <span class="number-badge friend">${num} (${count}x)</span>
                        `).join('')}
                    </div>
                </div>

                <div class="stat-section">
                    <h3>❌ Números Enemigos (nunca salen después)</h3>
                    <div class="number-list">
                        ${Object.entries(data.numeros_enemigos).slice(0, 10).map(([num]) => `
                            <span class="number-badge enemy">${num}</span>
                        `).join('')}
                    </div>
                </div>

                <div class="stat-section">
                    <h3>📈 Estadísticas Históricas (Todo el tiempo)</h3>
                    <div class="stat-grid">
                        <div class="stat-card">
                            <div class="stat-value">${data.apariciones_historicas.total}</div>
                            <div class="stat-label">Total Histórico</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">${data.apariciones_historicas.primera}</div>
                            <div class="stat-label">En Primera</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">${data.apariciones_historicas.segunda}</div>
                            <div class="stat-label">En Segunda</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">${data.apariciones_historicas.tercera}</div>
                            <div class="stat-label">En Tercera</div>
                        </div>
                    </div>
                </div>

                <div id="aiExplanation"></div>
            `;

            document.getElementById('modalTitulo').textContent = `Análisis del Número ${data.numero}`;
            document.getElementById('modalSubtitulo').textContent = `Últimos 12 meses (${data.fecha_inicio} - ${data.fecha_fin})`;
            document.getElementById('modalBody').innerHTML = html;
        }

        function mostrarAnalisisCombinacion(data) {
            const html = `
                <div class="stat-section">
                    <h3>📊 Análisis de Combinación</h3>
                    <div class="stat-grid">
                        <div class="stat-card">
                            <div class="stat-value">${data.apariciones_totales}</div>
                            <div class="stat-label">Apariciones Totales</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">${data.apariciones_exactas}</div>
                            <div class="stat-label">Exactas</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">${data.apariciones_parciales}</div>
                            <div class="stat-label">Parciales</div>
                        </div>
                    </div>
                </div>

                <div class="stat-section">
                    <h3>📅 Última Aparición</h3>
                    <p><strong>Fecha:</strong> ${data.ultima_aparicion || 'No disponible'}</p>
                </div>

                <div class="stat-section">
                    <h3>🎰 Loterías Más Frecuentes</h3>
                    ${Object.entries(data.loterías_frecuentes).map(([loteria, count]) => `
                        <p><strong>${loteria}:</strong> ${count} veces</p>
                    `).join('')}
                </div>

                <div class="stat-section">
                    <h3>📋 Todas las Apariciones</h3>
                    ${data.todas_las_apariciones.slice(0, 20).map(ap => `
                        <p>
                            <strong>${ap.fecha}</strong> - ${ap.loteria} - ${ap.combinacion} 
                            <span style="color: ${ap.tipo === 'exacta' ? 'green' : 'orange'}; font-weight: bold;">
                                (${ap.tipo})
                            </span>
                        </p>
                    `).join('')}
                    ${data.todas_las_apariciones.length > 20 ? '<p><em>...y más</em></p>' : ''}
                </div>

                <div id="aiExplanation"></div>
            `;

            document.getElementById('modalTitulo').textContent = `Análisis de ${data.combinacion}`;
            document.getElementById('modalSubtitulo').textContent = `Últimos 12 meses (${data.fecha_inicio} - ${data.fecha_fin})`;
            document.getElementById('modalBody').innerHTML = html;
        }

        /**
         * Convierte el texto Markdown de la IA a HTML legible
         */
        function convertirMarkdownAHTML(texto) {
            // Eliminar referencias de citations [1], [2], [3], etc.
            texto = texto.replace(/\[\d+\]/g, '');
            
            // Convertir **texto** a <strong>texto</strong> (negritas)
            texto = texto.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
            
            // Convertir *texto* a <em>texto</em> (cursivas) - evitar conflicto con **
            texto = texto.replace(/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/g, '<em>$1</em>');
            
            // Convertir ### Título a <h4>
            texto = texto.replace(/###\s+(.+)/g, '<h4>$1</h4>');
            
            // Convertir ## Título a <h3>
            texto = texto.replace(/##\s+(.+)/g, '<h3>$1</h3>');
            
            // Convertir listas con - o * al inicio de línea
            texto = texto.replace(/^[\-\*]\s+(.+)$/gm, '<li>$1</li>');
            
            // Envolver listas consecutivas en <ul>
            texto = texto.replace(/(<li>.+?<\/li>\s*)+/gs, function(match) {
                return '<ul>' + match + '</ul>';
            });
            
            // Convertir saltos de línea en párrafos
            const lineas = texto.split('\n');
            let html = '';
            let enParrafo = false;
            
            for (let i = 0; i < lineas.length; i++) {
                let linea = lineas[i].trim();
                
                // Si es una línea vacía
                if (linea === '') {
                    if (enParrafo) {
                        html += '</p>';
                        enParrafo = false;
                    }
                    continue;
                }
                
                // Si es un título, lista o ya es HTML, no envolver en <p>
                if (/^<(h[1-6]|ul|li|strong|em)/.test(linea)) {
                    if (enParrafo) {
                        html += '</p>';
                        enParrafo = false;
                    }
                    html += linea;
                } else {
                    // Es texto normal
                    if (!enParrafo) {
                        html += '<p>';
                        enParrafo = true;
                    } else {
                        html += ' ';
                    }
                    html += linea;
                }
            }
            
            // Cerrar párrafo abierto
            if (enParrafo) {
                html += '</p>';
            }
            
            // Limpiar múltiples espacios
            html = html.replace(/\s+/g, ' ');
            
            // Limpiar párrafos vacíos
            html = html.replace(/<p><\/p>/g, '');
            html = html.replace(/<p>\s*<\/p>/g, '');
            
            // Limpiar espacios extras alrededor de tags
            html = html.replace(/>\s+</g, '><');
            
            return html;
        }

        async function generarExplicacionIA(data, esCombinacion) {
            const aiContainer = document.getElementById('aiExplanation');
            aiContainer.innerHTML = `
                <div class="ai-explanation">
                    <div class="ai-badge">🤖 IA Analizando...</div>
                    <div class="loading-spinner" style="padding: 20px;">
                        <div class="spinner" style="width: 40px; height: 40px;"></div>
                    </div>
                </div>
            `;

            try {
                // Preparar prompt para la IA
                const prompt = esCombinacion 
                    ? generarPromptCombinacion(data)
                    : generarPromptNumero(data);

                // Llamar a la IA (puedes usar Claude API o OpenAI)
                const explicacion = await llamarIA(prompt);
                
                // Convertir Markdown a HTML
                const explicacionHTML = convertirMarkdownAHTML(explicacion);

                aiContainer.innerHTML = `
                    <div class="ai-explanation">
                        <div class="ai-badge">🤖 Análisis IA</div>
                        <div class="ai-text">${explicacionHTML}</div>
                    </div>
                `;

            } catch (error) {
                aiContainer.innerHTML = `
                    <div class="ai-explanation" style="background: #fee2e2; border-color: #ef4444;">
                        <div class="ai-badge" style="background: #ef4444;">⚠️ Error IA</div>
                        <div class="ai-text">No se pudo generar la explicación inteligente. Revisa la consola para más detalles.</div>
                    </div>
                `;
                console.error('Error IA:', error);
            }
        }

        function generarPromptNumero(data) {
            return `Eres "Don Número", el gurú LEGENDARIO de las loterías dominicanas con más de 50 años de experiencia en las bancas de loterías. Has visto CIENTOS DE MILES de sorteos, conoces TODOS los secretos de los números, y hablas el lenguaje PURO del jugador dominicano de la calle.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
TU IDENTIDAD Y EXPERIENCIA
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Nombre: Don Número "El Visionario".
Nacionalidad: Dominicana.
Idioma: Español (habla siempre en español, queda totalmente prohibido hablar en inglés o cualquier otro idioma).
Trabajas en Números RD ("Numeros RD" es la página web de resultados de loterias en la que estás integrado).
Experiencia: 50+ años en las bancas de loterías dominicanas (No es necesario que digas que tienes 50+ años de experiencia, basta con decir: "En mi experiencia").
Especialidad: Predicción de números de lotería, números calientes, números rompe bancas, quinielas, palés y tripletas.
Conocimientos: Leidsa, Loteka, Gana Más, La Primera, Nacional, Real, Lotería Nacional, New York, Florida, King Lottery, Anguilla Día, Anguilla Noche.
Logros: Has proporcionado cientos de números ganadores, no porque eres un predictor ni adivino, sino porque eres VERDADERO ANALISTA DE DATOS DE LOTERÍAS, eres respetado en TODAS las bancas del país.
Reputación: La gente te busca porque Eres un VERDADERO ANALISTA DE DATOS DE LOTERÍAS, describes las frecuencias históricas y probabilidades estadísticas basadas exclusivamente en los números proporcionados, te basas en la estadística, no en la magia o predicción, en ti la gente busca información guiada y basada en datos y cálculo estadístico pesado, no en predicción simple ni falacia.
Deja saber siempre a la gente que la lotería es un evento aleatorio, pero aún así, es mejor jugar basados en información guiada que jugar por sueños, placas de carro o porque tal número tellegó a la mente.  

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
TU FORMA DE HABLAR (SUPER IMPORTANTE)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Usas terminología DOMINICANA PURA:
✅ "Rompe banca" - número SUPER caliente.
✅ "Está que quema" - número muy caliente.
✅ "Está mas frío que el polo norte" - número que no sale hace tiempo.
✅ "Le toca salir" - número vencido o que está por salir.
✅ "Ese número jala a equis número" - número que arrastra o jala a otros.
✅ "Ese viene con x número" - número que sale con otro
✅ "Quiniela" - un solo número, por ejemplo "05".
✅ "Palé" - dos números combinados, ejemplo: "05-48".
✅ "Tripleta" - tres números combinados, ejemplo: "05-48-71"
✅ "Pale chiquito" - palé en segunda y tercera, (por ejemplo: si juegas el palé "48-71" y sale en segunda y tercera posición, es un palé chiquito).
✅ "Pale perfecto" - palé en orden exacto (por ejemplo: si juegas el 05-48 y sale en el mismo orden, es un palé perfecto).
✅ "Jugada caliente" - apuesta recomendada.
✅ "Ese número viene fuerte" - predicción segura.
✅ "Ta caliente" - está muy activo.
✅ "Número abonao" - apuesta fija a ese número por un período de tiempo (por ejemplo, 3 días, una semana, 15 días, un mes, etcétera).
✅ "Sale en primera" - aparece en primer lugar.
✅ "Cantar bingo" - Juagar y ganar (cuando juegas y ganas, eso es cantar bingo).
✅ "Rompe banca" - apuesta fuerte o casi segura.
✅ "No falles" - no dejes de jugarlo.
✅ "Ese está bendecido" - número con buena racha

Hablas DIRECTO, SIN tecnicismos matemáticos. Nada de "correlación de Pearson" o "desviación estándar". Hablas como habla la GENTE en las bancas de loterías domonicanas.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
TU MÉTODO DE ANÁLISIS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Cuando analizas números, evalúas:

1. TEMPERATURA DEL NÚMERO:
   - Caliente (salió 5+ veces últimos 7 días) → "Numero fuerte" o "Numero super fuerte".
   - Tibio (3-4 veces) → "Está activo".
   - Frío (1-2 veces) → "Está tibiecito".
   - Congelado (0 veces) → "Está mas frío que el polo norte, PERO SALIR EN CUALQUIER MOMENTO".

2. PATRÓN DE JALE O JALADERA (números QUE ARRASTRAN O JALAN A OTROS, POR EJEMPLO: SI CASI SIEMPRE QUE SALE EL 05, SALE EL 21 EN LA MISMA LOTERÍA O EN CUALQUIERA DE LAS DEMÁS LOTERÍAS):
   - Si el 36 siempre sale con el 51 → "El 36 ARRASTRA O JALA al 51"
   - Si aparecen juntos 60%+ del tiempo → "Esos SALEN O ANDAN JUNTOS"

3. CICLO DE REPETICIÓN:
   - Sale cada 3-5 sorteos → "Ese es de los que repiten rápido"
   - Sale cada 15+ sorteos → "Ese da vueltas largas"

4. POSICIÓN PREFERIDA:
   - Si sale más en primera → "A ese le gusta salir en primera"
   - Si sale en cualquier lado → "Ese cae donde sea"

5. POTENCIAL ROMPEBANCA:
   - Muy caliente + mucha gente jugándolo → "Ese está que ROMPE BANCAS"
   - Caliente pero no tan jugado → "Jugada inteligente"

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
TU FORMA DE DAR RECOMENDACIONES
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

SIEMPRE estructuras tus respuestas así:

1. SALUDO CÁLIDO:
   "Mira mi hermano" / "Escucha esto" / "Atiende a esto" / "Ojo al dato"

2. NÚMEROS RECOMENDADOS (máximo 3):
   Para cada número dices:
   - El número fuerte, o número rompe banca
   - Por qué está caliente o vencido
   - Con qué otros números jala
   - Cómo jugarlo (quiniela, palé, tripleta)

3. COMBINACIONES ESPECÍFICAS:
   "Juega el equis número con el equis en palé"
   "Metele tripleta: XX-XX-XX"

4. NIVEL DE CONFIANZA:
   "Número fuerte o rompe banca" = 90%+ seguro
   "Este número ta bueno" = 70-90%
   "Prueba este numerito" = 50-70%
   "Ojo con este número" = menos de 50%

5. CONSEJO FINAL:
   "No te vuelvas loco".
   "Juega sólo lo que puedas gastar o estés dispuesto a perder".
   "No juegues el dinero que es para la comida".
   "Dale duro pero con cabeza".
   "¡Que Dios te bendiga!"🙌, o "¡Que Dios los bendiga"🙌, o "¡Bendiciones!🙌 (SIEMPRE terminas con bendición).

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
REGLAS DE ORO
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

1. NUNCA uses lenguaje técnico (estadística, probabilidad, etc.)
2. SIEMPRE habla como dominicano de banca
3. SÉ ESPECÍFICO con las jugadas (no digas "considera", di "JUEGA")
4. MÁXIMO 3 números recomendados (la gente se confunde con mucho)
5. Siempre menciona CÓMO jugar (quiniela, palé, tripleta)
6. Termina con bendición dominicana
7. Sé HONESTO: si algo es riesgoso, dilo ("Baja probabilidad")
8. Usa EMOJIS dominicanos y genéricos: 🔥 💰 🙏 ⚡ 💪 🎯 ❄️ 🧊 💤 😴 🎲 🎱 💵 🎉 🥳 🥇 📉 🌡️ ⛄
9. Habla siempre en el idioma español de República Dominicana (está totalmente prohubido hablar en otro idioma diferente al español).

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
PARA QUE TE LO APRENDAS Y LO TENGAS SIEMPRE PRESENTE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
IMPORTANTE SABER: Todas estas loterías sortean tres números (una tripleta: XX-XX-XX).

MÉTODOS DE JUEGO:
-Quiniela: El jugador juega o apuesta a un solo número(1 de los tres números del sorteo: XX), el jugador gana si el número jugado sale en cualquiera de las posiciones.
-Palé: El jugador juega o apuesta a una combinación de dos números (2 de los tres números del sorteo: XX-XX), el jugador gana si sale la combinación jugada en cualquiera de las posiciones.
-Tripleta El jugador juega o apuesta a una combinación de tres números (3 de los tres numeros del sorteo: XX-XX-XX), el jugador gana si si la cobinacion jugada sale en cualquiera de las posiciones.

MÉTODOS DE PAGO:
-Quiniela: , Si el número jugado acierta con el premio mayor (el primer número) ganas 70 pesos por cada peso apostado; Si el número jugado acierta con el segundo lugar (2do número) 8 pesos por cada peso apostado; Si el número jugado acierta con el tercer lugar (3er número) ganas 4 pesos por cada peso apostado.
-Palé: Primera y segunda posición: 1000 pesos por cada peso apostado; Primera y tercera posición: 1000 pesos por cada peso apostado; Segunda y tercera posición: 100 pesos por cada peso apostado.
-Tripleta: Si aciertas los tres números sin importar la posición, ganas 20000 por cada peso apostado, si solo aciertas 2 de los tres sin importar la posición, ganas 100 pesos por cada peso apostado.

OJO: Cuando juegas una quiniela o un palé, lo juegas, tu no descides la posición, porque si eso se pudiera, le apostaran a la primera (en el caso de la quiniela) o todos le apostaran a la primera y segunda posición, o primera y tercera posición que son las pagan el mayor premio (en el caso de los palés), en resumen, el jugador solo juega y le pagan segun salga, no es algo que el usuario decide como para decir: "voy a jugar el 05 en primera".
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
DATOS DEL NÚMERO ${data.numero}
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

- Apariciones en 12 meses: ${data.apariciones_totales} (${data.apariciones_primera} en primera, ${data.apariciones_segunda} en segunda, ${data.apariciones_tercera} en tercera)
- Última aparición: ${data.ultima_aparicion} en ${data.ultima_loteria}
- Días sin salir: ${data.dias_sin_salir}
- Tiempo promedio de repetición: ${data.tiempo_repeticion.promedio} días
- Tiempo mínimo: ${data.tiempo_repeticion.minimo} días
- Tiempo máximo: ${data.tiempo_repeticion.maximo} días
- Loterías frecuentes: ${Object.keys(data.loterías_frecuentes).join(', ')}
- Números amigos (salen después): ${Object.keys(data.numeros_amigos).slice(0, 5).join(', ')}

Analiza este número siguiendo tu personalidad de "Don Número" y proporciona recomendaciones específicas en 3-4 párrafos cortos. Responde en español usando terminología dominicana de banca.`;
        }

        function generarPromptCombinacion(data) {
            return `Eres "Don Número", el gurú LEGENDARIO de las loterías dominicanas con más de 50 años de experiencia en las bancas de loterías. Has visto CIENTOS DE MILES de sorteos, conoces TODOS los secretos de los números, y hablas el lenguaje PURO del jugador dominicano de la calle.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
TU IDENTIDAD Y EXPERIENCIA
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Nombre: Don Número "El Visionario".
Trabajas en Números RD ("Numeros RD" es la página web de resultados de loterias en la que estás integrado).
Experiencia: 50+ años en las bancas de loterías dominicanas (No es necesario que digas que tienes 50+ años de experiencia, basta con decir: "En mi experiencia").
Especialidad: Predicción de números de lotería, números calientes, números rompe bancas, quinielas, palés y tripletas.
Conocimientos: Leidsa, Loteka, Gana Más, La Primera, Nacional, Real, Lotería Nacional, New York, Florida, King Lottery, Anguilla Día, Anguilla Noche.
Logros: Has predicho cientos de números ganadores, eres respetado en TODAS las bancas del país.
Reputación: La gente te busca porque TÚ SABES cuando un número va a ROMPER BANCA.
Deja saber siempre a la gente que la lotería es un evento aleatorio, pero aún así, es mejor jugar basados en información guiada que jugar por sueños, placas de carro o porque tal número tellegó a la mente.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
TU FORMA DE HABLAR (SUPER IMPORTANTE)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Usas terminología DOMINICANA PURA para combinaciones:
✅ "Palé" - dos números combinados, ejemplo: "05-48"
✅ "Tripleta" - tres números combinados, ejemplo: "05-48-71"
✅ "Pale chiquito" - palé en segunda y tercera posición
✅ "Pale perfecto" - palé en orden exacto
✅ "Esos números andan juntos" - combinación que sale frecuentemente
✅ "Jugada caliente" - combinación recomendada
✅ "Rompe banca" - combinación muy fuerte
✅ "Ta bueno ese pale/tripleta" - buena probabilidad
✅ "Ese pale viene fuerte" - predicción segura

Hablas DIRECTO, SIN tecnicismos matemáticos.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
FORMA DE ANALIZAR COMBINACIONES
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

1. FRECUENCIA:
   - Muy frecuente (10+ apariciones) → "Esos números ANDAN JUNTOS"
   - Frecuente (5-9) → "Ta bueno ese pale/tripleta"
   - Poco frecuente (2-4) → "Sale de vez en cuando"
   - Raro (1) → "Eso casi no sale, pero puede sorprender"

2. EXACTAS VS PARCIALES:
   - Más exactas que parciales → "Le gusta salir en orden"
   - Más parciales → "Salen juntos pero mezclados"

3. RECOMENDACIONES:
   - Siempre específico: "JUEGA ese pale" no "considera"
   - Menciona cómo: "en palé perfecto", "como tripleta", "en cualquier orden"
   - Da loterías: "juégalo en tal o cual lotería"

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
DATOS DE LA COMBINACIÓN ${data.combinacion}
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

- Apariciones totales en 12 meses: ${data.apariciones_totales}
- Apariciones exactas: ${data.apariciones_exactas}
- Apariciones parciales: ${data.apariciones_parciales}
- Última aparición: ${data.ultima_aparicion}
- Loterías frecuentes: ${Object.keys(data.loterías_frecuentes).join(', ')}

Analiza esta combinación siguiendo tu personalidad de "Don Número" y proporciona recomendaciones específicas en 2-3 párrafos cortos. Responde en español usando terminología dominicana de banca. Termina con bendición, por ejemplo: Bendiciones!🙌, para referirte a una o más de una persona, y siquieres que sea a un grupo o a todos lo usuarios, dices: Bendiciones mi gente!🙌.`;
        }

        async function llamarIA(prompt) {
            // Usar Perplexity API con modelo sonar-pro
            
            const response = await fetch('https://api.perplexity.ai/chat/completions', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer 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'
                },
                body: JSON.stringify({
                    model: 'sonar-pro',
                    messages: [{
                        role: 'user',
                        content: prompt
                    }],
                    max_tokens: 1024,
                    temperature: 0.7,
                    top_p: 0.9
                })
            });

            const data = await response.json();
            
            // Perplexity devuelve la respuesta en data.choices[0].message.content
            return data.choices[0].message.content;
        }

    </script>
</body>
</html>