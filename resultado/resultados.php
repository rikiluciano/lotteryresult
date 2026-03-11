<?php
/**
 * NÚMEROS RD - Resultados Diarios
 * Versión Ultra-Premium con integración Supabase
 */

// Configuración de zona horaria
date_default_timezone_set('America/Santo_Domingo');

require_once 'includes/Database.php';

// Función para obtener URL del logo
function obtener_logo($loteria) {
    $nombre = strtolower($loteria);
    $base_url = 'https://enloteria.com/assets/';
    $logos = [
        'leidsa' => 'leidsa-26031367f0cd9ba743253bbae1c55e546de6732adf18eda71c73d4387c0da2d1.svg',
        'loteka' => 'loteka-58fb6f5ce8c707d7726e35c91f8aee0a986576dab82ce0ecd53efa237f7936aa.svg',
        'nacional' => 'nacional-6f4e8ccd25d07edb6452e418cbfab9ae8ec36f728266fc7189a6ee68e7bdd4f0.svg',
        'gana' => 'ganamas-5c2fba8ccbe1a70b7b12afdf18ff38cfbf7ce032e5c7397dcb1f03e858ff4335.svg',
        'real' => 'real-eeb33736cd36eff0dfb219af8954fe0ab37245bd412801ea864f6a131c3c758c.svg',
        'primera' => 'la_primera-dd745b944dea9df3a0640447e0aa18aeb076861fae80fe9a19f749c3e1a6541d.svg',
        'suerte' => 'la_suerte-503a3d9314a080d132a414fdc5a6940ddd50ef1d235dcc621bc1bc7f7516fbb1.svg',
        'lotedom' => 'lotedom-9aae43273ce4d8d4d5429f6f57f2fadc54012eb96fc80d6f59cfb9b72576b7e9.svg',
        'florida' => 'florida-0d3b11e2215473f987ac28c156cbff56ccf186e650ddf2df2b6b194254677eed.svg',
        'york' => 'new_york-e78bc3206a0497915ddab4a77f80e06ad0f8eb6d6e355770340c17be4f29a616.svg',
        'anguila' => 'anguila-78bcb1b1711b3176ea0eb9fe37768936cc1f70530f44fcc165067a087fba5b00.svg',
        'anguilla' => 'anguila-78bcb1b1711b3176ea0eb9fe37768936cc1f70530f44fcc165067a087fba5b00.svg',
        'king' => 'king_lottery-ea033db5247fa2e2b002245b33b52ae936c2f1b2be04927ed236032c2d2c2e9f.svg'
    ];
    foreach ($logos as $key => $svg) {
        if (stripos($nombre, $key) !== false) return $base_url . $svg;
    }
    return null;
}

$db = new Database();

try {
    $rawResults = $db->fetch('lottery_results', [
        'select' => '*',
        'order' => 'fecha.desc',
        'limit' => 1000
    ]);
    
    // Determinar qué fecha mostrar
    $fechaFiltroQuery = isset($_GET['fecha']) ? $_GET['fecha'] : null;
    
    // Normalizar a Y-m-d
    if ($fechaFiltroQuery && strpos($fechaFiltroQuery, '/') !== false) {
        $dateObj = DateTime::createFromFormat('d/m/Y', $fechaFiltroQuery);
        if ($dateObj) $fechaFiltroQuery = $dateObj->format('Y-m-d');
    }

    // LÓGICA DE AUTO-FALLBACK: Si no hay fecha en URL, buscamos el día más reciente CON DATOS COMPLETOS
    if (!$fechaFiltroQuery && !empty($rawResults)) {
        $countsByDate = [];
        foreach ($rawResults as $r) {
            $f = substr($r['fecha'], 0, 10);
            if (!isset($countsByDate[$f])) $countsByDate[$f] = 0;
            $countsByDate[$f]++;
        }
        foreach ($countsByDate as $date => $count) {
            if ($count >= 10) { // Si tiene al menos 10 resultados, es un día "bueno" para mostrar
                $fechaFiltroQuery = $date;
                break;
            }
        }
        if (!$fechaFiltroQuery) $fechaFiltroQuery = substr($rawResults[0]['fecha'], 0, 10);
    } elseif (!$fechaFiltroQuery) {
        $fechaFiltroQuery = date('Y-m-d');
    }

    // Filtrado
    $resultados = [];
    $lotteriesSeen = [];
    foreach ($rawResults as $row) {
        $fechaRow = substr($row['fecha'], 0, 10);
        $nombreLoteria = trim($row['loteria']);
        if ($fechaRow === $fechaFiltroQuery && !isset($lotteriesSeen[$nombreLoteria])) {
            $resultados[] = [
                'nombre' => $nombreLoteria,
                'logo' => obtener_logo($nombreLoteria),
                'fecha' => $fechaRow,
                'primera' => str_pad($row['primera'], 2, '0', STR_PAD_LEFT),
                'segunda' => str_pad($row['segunda'], 2, '0', STR_PAD_LEFT),
                'tercera' => str_pad($row['tercera'], 2, '0', STR_PAD_LEFT)
            ];
            $lotteriesSeen[$nombreLoteria] = true;
        }
    }
    
    // Si el usuario forzó HOY y está vacío, pues sale vacío, pero por defecto entrará al día anterior completo
} catch (Exception $e) { $resultados = []; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados RD - FreqTable Premium</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Flatpickr -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/dark.css">
    
    <link rel="stylesheet" href="css/estilos.css?v=6.0">
    <link rel="stylesheet" href="css/theme-premium.css?v=6.0">
    <link rel="stylesheet" href="footer/rlabs-footer.css?v=6.0">
    
    <style>
        .results-container { max-width: 1250px; margin: 0 auto; padding: 40px 15px; }
        .results-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap: 20px; margin-top: 30px; }
        .result-card { background: var(--card-bg); border-radius: 20px; padding: 25px; border: 1px solid var(--border-color); display: flex; flex-direction: column; gap: 15px; backdrop-filter: blur(12px); box-shadow: 0 4px 15px var(--shadow-light); transition: all 0.3s ease; }
        .result-card:hover { transform: translateY(-3px); border-color: var(--primary-color); }
        .result-card .header { display: flex; align-items: center; gap: 15px; }
        .result-card .logo-container { width: 55px; height: 55px; background: white; border-radius: 10px; display: flex; align-items: center; justify-content: center; padding: 4px; }
        .result-card .logo-img { width: 100%; height: 100%; object-fit: contain; }
        .result-card .name { font-weight: 800; color: var(--text-dark); font-size: 1.15rem; flex: 1; }
        
        /* TODOS EXACTAMENTE DEL MISMO TAMAÑO SIN EXCEPCIÓN */
        .result-card .numbers { display: flex; justify-content: center; gap: 12px; margin: 10px 0; }
        .result-card .ball { 
            width: 52px !important; 
            height: 52px !important; 
            border-radius: 12px !important; 
            display: flex !important; 
            align-items: center !important; 
            justify-content: center !important; 
            font-weight: 800 !important; 
            font-size: 1.5rem !important; 
            color: white !important; 
            border: none !important;
            padding: 0 !important;
            text-align: center !important;
            box-shadow: 0 4px 8px rgba(0,0,0,0.15) !important;
        }
        
        .result-card .ball-1 { background: linear-gradient(135deg, #00ff88, #00a859) !important; box-shadow: 0 0 12px rgba(0,255,136,0.2) !important; }
        .result-card .ball-2 { background: linear-gradient(135deg, #4E54C8, #24243E) !important; }
        .result-card .ball-3 { background: linear-gradient(135deg, #ec4899, #db2777) !important; }
        
        .result-card .ball-label { font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-top: 4px; text-align: center; display: block; }

        .date-filter-box { margin-bottom: 50px; display: flex; justify-content: center; position: relative; }
        .custom-calendar-trigger {
            background: var(--card-bg);
            padding: 10px 25px;
            border-radius: 50px;
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            box-shadow: 0 8px 20px var(--shadow-light);
            transition: all 0.3s ease;
        }
        .calendar-icon { font-size: 1.2rem; color: var(--primary-color); }
        .calendar-text { font-weight: 800; color: var(--text-dark); font-size: 1rem; }
        #datePicker { position: absolute; left: 50%; top: 50%; opacity: 0; pointer-events: none; }

        .title-gradient { background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-weight: 900; }
    </style>
</head>
<body class="theme-premium">
    <header>
        <div class="header-container">
            <div class="logo-info-group" style="display: flex; align-items: center;">
                <a href="../index.html" class="logo"><i class="bi bi-bar-chart-fill" style="margin-right: 8px;"></i> FreqTable</a>
                <button class="btn-info" onclick="document.body.classList.toggle('info-modal-open')"><span class="info-icon">i</span></button>
                <button id="themeToggle" class="btn-theme">🌙</button>
                <a href="resultados.php" class="btn-direct-results"><span class="indicator-live-header"></span><i class="bi bi-calendar-check-fill"></i><span class="btn-text">Resultados</span></a>
            </div>
            <nav id="mainNav">
                <button class="nav-toggle" id="navToggle"><span></span><span></span><span></span></button>
                <ul class="nav-menu" id="navMenu">
                    <li><a href="../index.html"><i class="bi bi-house-door"></i> Inicio</a></li>
                    <li><a href="resultados.php" class="active"><i class="bi bi-calendar-check"></i> Resultados</a></li>
                    <li><a href="calculadora.html"><i class="bi bi-calculator"></i> Calculadora</a></li>
                    <li><a href="../index.html#ayuda"><i class="bi bi-question-circle"></i> Ayuda</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="results-container">
        <div style="text-align: center; margin-bottom: 50px;">
            <h1 class="title-gradient" style="font-size: 3.5rem;">Resultados RD</h1>
            <p style="color: var(--text-muted); font-size: 1.1rem;">Sorteos oficiales para el: <span style="font-weight: 800; color: var(--primary-color);"><?php echo date('d/m/Y', strtotime($fechaFiltroQuery)); ?></span></p>
        </div>

        <div class="date-filter-box">
            <div class="custom-calendar-trigger" id="openCalendar">
                <i class="bi bi-calendar3 calendar-icon"></i>
                <div class="calendar-text"><?php echo date('d / m / Y', strtotime($fechaFiltroQuery)); ?></div>
                <i class="bi bi-chevron-down" style="font-size: 0.8rem; opacity: 1;"></i>
            </div>
            <input type="text" id="datePicker">
        </div>

        <div class="results-grid">
            <?php if (empty($resultados)): ?>
                <div class="no-data"><i class="bi bi-search" style="font-size: 4rem; opacity: 0.2; margin-bottom: 20px; display: block;"></i><h3>No hay registros</h3><p>Prueba con otra fecha en el calendario.</p></div>
            <?php else: ?>
                <?php foreach ($resultados as $res): ?>
                    <div class="result-card">
                        <div class="header">
                            <div class="logo-container">
                                <img src="<?php echo $res['logo'] ?: 'https://via.placeholder.com/100?text=RD'; ?>" alt="Logo" class="logo-img">
                            </div>
                            <span class="name"><?php echo $res['nombre']; ?></span>
                        </div>
                        <div class="numbers">
                            <div>
                                <div class="ball ball-1"><?php echo $res['primera']; ?></div>
                                <span class="ball-label">1ro</span>
                            </div>
                            <div>
                                <div class="ball ball-2"><?php echo $res['segunda']; ?></div>
                                <span class="ball-label">2do</span>
                            </div>
                            <div>
                                <div class="ball ball-3"><?php echo $res['tercera']; ?></div>
                                <span class="ball-label">3ro</span>
                            </div>
                        </div>
                        <div style="font-size: 0.8rem; color: var(--text-muted); text-align: center; border-top: 1px solid var(--border-color); padding-top: 10px; font-weight: 600; opacity: 0.7;">
                            SORTEO OFICIAL RD
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <div id="rlabs-footer-container"></div>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
    <script src="js/script.js"></script>
    <script>
        fetch('footer/rlabs-footer.html?v=' + new Date().getTime()).then(res => res.text()).then(html => { document.getElementById('rlabs-footer-container').innerHTML = html; });
        const fp = flatpickr("#datePicker", {
            locale: "es",
            dateFormat: "Y-m-d",
            maxDate: "today",
            static: false,
            position: "below center",
            onChange: function(selectedDates, dateStr) {
                window.location.href = "resultados.php?fecha=" + dateStr;
            }
        });
        document.getElementById('openCalendar').addEventListener('click', () => { fp.open(); });
        const navToggle = document.getElementById('navToggle');
        const navMenu = document.getElementById('navMenu');
        if (navToggle) { navToggle.addEventListener('click', () => { navToggle.classList.toggle('active'); navMenu.classList.toggle('active'); }); }
    </script>
</body>
</html>