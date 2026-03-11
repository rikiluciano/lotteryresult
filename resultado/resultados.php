<?php
/**
 * NÚMEROS RD - Resultados Diarios
 * Versión Ultra-Premium con integración Supabase
 */

require_once 'includes/Database.php';

// Función para obtener URL del logo (centralizada aquí para consistencia)
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
        'king' => 'king_lottery-ea033db5247fa2e2b002245b33b52ae936c2f1b2be04927ed236032c2d2c2e9f.svg'
    ];

    foreach ($logos as $key => $svg) {
        if (stripos($nombre, $key) !== false) return $base_url . $svg;
    }
    return null;
}

// Inicializar Supabase
$db = new Database();

// Obtener fecha de filtro (formato YYYY-MM-DD para Supabase)
$fechaFiltro = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');

// Si la fecha viene en formato d/m/Y (desde el calendario), convertirla
if (strpos($fechaFiltro, '/') !== false) {
    $dateObj = DateTime::createFromFormat('d/m/Y', $fechaFiltro);
    $fechaFiltro = $dateObj->format('Y-m-d');
}

try {
    // Obtener resultados de la tabla lottery_results filtrando por fecha
    $params = [
        'select' => '*',
        'fecha' => 'eq.' . $fechaFiltro,
        'order' => 'fecha.desc'
    ];
    
    $rawResults = $db->fetch('lottery_results', $params);
    $resultados = [];

    // Definir loterías dominicanas para categorizar
    $nacionalesNames = ['Gana Mas', 'Nacional', 'Leidsa', 'Real', 'Loteka', 'La Primera', 'La Suerte', 'LoteDom'];

    foreach ($rawResults as $row) {
        $nombre = $row['loteria'];
        $esNacional = false;
        foreach ($nacionalesNames as $nacional) {
            if (stripos($nombre, $nacional) !== false) {
                $esNacional = true;
                break;
            }
        }

        $resultados[] = [
            'nombre' => $nombre,
            'logo' => obtener_logo($nombre),
            'fecha' => $row['fecha'],
            'primera' => str_pad($row['primera'], 2, '0', STR_PAD_LEFT),
            'segunda' => str_pad($row['segunda'], 2, '0', STR_PAD_LEFT),
            'tercera' => str_pad($row['tercera'], 2, '0', STR_PAD_LEFT),
            'esHoy' => ($row['fecha'] === date('Y-m-d')),
            'categoria' => $esNacional ? 'nacional' : 'extranjera'
        ];
    }

} catch (Exception $e) {
    $resultados = [];
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados Diarios - FreqTable Premium</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Estilos Premium -->
    <link rel="stylesheet" href="css/estilos.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/theme-premium.css?v=<?php echo time(); ?>">
    
    <style>
        .results-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: var(--spacing-lg);
        }

        .results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .result-card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 20px;
            border: 1px solid var(--border-color);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            gap: 15px;
            box-shadow: 0 4px 15px var(--shadow-light);
            backdrop-filter: blur(10px);
        }

        .result-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px var(--shadow-medium);
            border-color: var(--primary-color);
        }

        .result-card .header {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .result-card .logo-img {
            width: 45px;
            height: 45px;
            object-fit: contain;
            background: white;
            border-radius: 8px;
            padding: 5px;
        }

        .result-card .name {
            font-weight: 700;
            color: var(--text-dark);
            font-size: 1.1rem;
        }

        .result-card .numbers {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 10px 0;
        }

        .result-card .ball {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.2rem;
            color: white;
            box-shadow: 0 4px 10px var(--shadow-light);
        }

        .ball-1 { background: linear-gradient(135deg, #6366f1, #4f46e5); }
        .ball-2 { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
        .ball-3 { background: linear-gradient(135deg, #ec4899, #db2777); }

        .date-filter {
            margin-bottom: 30px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            background: var(--card-bg);
            padding: 15px;
            border-radius: 50px;
            border: 1px solid var(--border-color);
            width: fit-content;
            margin-left: auto;
            margin-right: auto;
            box-shadow: 0 4px 15px var(--shadow-light);
        }

        .date-input {
            padding: 8px 15px;
            border-radius: 20px;
            border: 1px solid var(--border-color);
            background: var(--background-base);
            color: var(--text-dark);
            font-family: inherit;
            outline: none;
        }

        .no-data {
            text-align: center;
            padding: 50px;
            background: var(--card-bg);
            border-radius: 20px;
            grid-column: 1 / -1;
            color: var(--text-muted);
        }

        [data-theme="dark"] .result-card .logo-img {
            filter: brightness(0.9);
        }
    </style>
</head>
<body class="theme-premium">
    <header>
        <div class="header-container">
            <div class="logo-info-group">
                <a href="../index.html" class="logo">
                    <i class="bi bi-bar-chart-fill" style="margin-right: 8px; font-size: 1.2rem;"></i>
                    FreqTable
                </a>
                <button class="btn-info" onclick="document.body.classList.toggle('info-modal-open')">
                    <span class="info-icon">i</span>
                </button>
            </div>
            
            <nav id="mainNav">
                <button class="nav-toggle" id="navToggle" aria-label="Abrir menú">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <ul class="nav-menu" id="navMenu">
                    <li><a href="../index.html"><i class="bi bi-house-door"></i> Inicio</a></li>
                    <li><a href="resultados.php" class="active"><i class="bi bi-calendar-check"></i> Resultados</a></li>
                    <li><a href="#"><i class="bi bi-calculator"></i> Calculadora</a></li>
                    <li class="theme-switch-li">
                        <button id="themeToggle" class="theme-toggle-btn">
                            <i class="bi bi-moon-stars-fill sun-icon"></i>
                            <i class="bi bi-sun-fill moon-icon"></i>
                        </button>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="results-container">
        <div class="header-title-section" style="text-align: center; margin-bottom: 40px;">
            <h1 class="title" style="font-size: 2.5rem;">Resultados Diarios</h1>
            <p style="color: var(--text-muted);">Consulta los sorteos más recientes desde nuestra red unificada.</p>
        </div>

        <form action="resultados.php" method="GET" class="date-filter">
            <span style="font-weight: 600; color: var(--text-dark);">Filtrar por fecha:</span>
            <input type="date" name="fecha" class="date-input" value="<?php echo $fechaFiltro; ?>" onchange="this.form.submit()">
            <i class="bi bi-calendar-event" style="color: var(--primary-color);"></i>
        </form>

        <div class="results-grid">
            <?php if (empty($resultados)): ?>
                <div class="no-data">
                    <i class="bi bi-inbox" style="font-size: 3rem; display: block; margin-bottom: 15px;"></i>
                    <h3>No hay resultados para esta fecha</h3>
                    <p>Intenta seleccionar otro día en el filtro superior.</p>
                </div>
            <?php else: ?>
                <?php foreach ($resultados as $res): ?>
                    <div class="result-card" data-category="<?php echo $res['categoria']; ?>">
                        <div class="header">
                            <?php if ($res['logo']): ?>
                                <img src="<?php echo $res['logo']; ?>" alt="Logo" class="logo-img">
                            <?php else: ?>
                                <div class="logo-img" style="display:flex;align-items:center;justify-content:center;background:var(--primary-color);color:white;">
                                    <i class="bi bi-trophy"></i>
                                </div>
                            <?php endif; ?>
                            <span class="name"><?php echo $res['nombre']; ?></span>
                        </div>
                        
                        <div class="numbers">
                            <div class="ball ball-1"><?php echo $res['primera']; ?></div>
                            <div class="ball ball-2"><?php echo $res['segunda']; ?></div>
                            <div class="ball ball-3"><?php echo $res['tercera']; ?></div>
                        </div>
                        
                        <div style="font-size: 0.85rem; color: var(--text-muted); text-align: center; border-top: 1px solid var(--border-color); padding-top: 10px;">
                            <i class="bi bi-clock"></i> Sorteo del <?php echo date('d/m/Y', strtotime($res['fecha'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer se carga dinámicamente -->
    <div id="footer-container"></div>

    <script src="js/script.js"></script>
    <script>
        // Cargar Footer Dinámico
        fetch('footer/rlabs-footer.html?v=' + new Date().getTime())
            .then(res => res.text())
            .then(html => {
                document.getElementById('footer-container').innerHTML = html;
            });
            
        // Toggle Menú
        const navToggle = document.getElementById('navToggle');
        const navMenu = document.getElementById('navMenu');
        if (navToggle) {
            navToggle.addEventListener('click', () => {
                navToggle.classList.toggle('active');
                navMenu.classList.toggle('active');
            });
        }
    </script>
</body>
</html>