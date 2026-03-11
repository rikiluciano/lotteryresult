<?php
/**
 * NÚMEROS RD - Resultados Diarios
 * Versión Ultra-Premium con integración Supabase
 */

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
        'angulla' => 'anguila-78bcb1b1711b3176ea0eb9fe37768936cc1f70530f44fcc165067a087fba5b00.svg',
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
$fechaFiltroURL = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');
$fechaFiltroQuery = $fechaFiltroURL;

// Normalizar formato para consulta
if (strpos($fechaFiltroQuery, '/') !== false) {
    $dateObj = DateTime::createFromFormat('d/m/Y', $fechaFiltroQuery);
    if ($dateObj) $fechaFiltroQuery = $dateObj->format('Y-m-d');
}

try {
    // Si queremos ver todos los resultados de un día, a veces Supabase 
    // puede tener loterías que aún no han jugado hoy.
    // Para depurar, pediremos los últimos 50 resultados ordenados por fecha
    // y filtraremos los que correspondan al día.
    $params = [
        'select' => '*',
        'order' => 'fecha.desc',
        'limit' => 50
    ];
    
    $rawResults = $db->fetch('lottery_results', $params);
    $resultados = [];

    // Definir loterías dominicanas para categorizar
    $nacionalesNames = ['Gana Mas', 'Nacional', 'Leidsa', 'Real', 'Loteka', 'La Primera', 'La Suerte', 'LoteDom'];

    foreach ($rawResults as $row) {
        // Solo mostrar los que coincidan con la fecha seleccionada
        if ($row['fecha'] !== $fechaFiltroQuery) continue;

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
    
    // Si no hay resultados para hoy, quizás el usuario quiere ver los últimos disponibles
    // pero respetaremos su filtro. Si está vacío, avisaremos.

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
    <link rel="stylesheet" href="footer/rlabs-footer.css?v=<?php echo time(); ?>">
    
    <style>
        .results-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .result-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 24px;
            border: 1px solid var(--border-color);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            gap: 18px;
            box-shadow: 0 4px 15px var(--shadow-light);
            backdrop-filter: blur(12px);
        }

        .result-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 30px var(--shadow-medium);
            border-color: var(--primary-color);
        }

        .result-card .header {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .result-card .logo-img {
            width: 50px;
            height: 50px;
            object-fit: contain;
            background: white;
            border-radius: 12px;
            padding: 6px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .result-card .name {
            font-weight: 800;
            color: var(--text-dark);
            font-size: 1.2rem;
            letter-spacing: -0.5px;
        }

        .result-card .numbers {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin: 15px 0;
        }

        .result-card .ball {
            width: 50px;
            height: 50px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.4rem;
            color: white;
            box-shadow: 0 5px 12px var(--shadow-light);
            transition: transform 0.2s;
        }
        
        .result-card:hover .ball {
            transform: scale(1.05);
        }

        .ball-1 { background: linear-gradient(135deg, #6366f1, #4f46e5); }
        .ball-2 { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
        .ball-3 { background: linear-gradient(135deg, #ec4899, #db2777); }

        .date-filter-box {
            margin-bottom: 40px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }

        .filter-form {
            background: var(--card-bg);
            padding: 10px 25px;
            border-radius: 50px;
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 8px 25px var(--shadow-light);
            backdrop-filter: blur(10px);
        }

        .date-input {
            padding: 10px 18px;
            border-radius: 25px;
            border: 1px solid var(--border-color);
            background: var(--background-base);
            color: var(--text-dark);
            font-family: inherit;
            font-weight: 600;
            outline: none;
            cursor: pointer;
            transition: border-color 0.3s;
        }
        
        .date-input:focus {
            border-color: var(--primary-color);
        }

        .no-data {
            text-align: center;
            padding: 80px 40px;
            background: var(--card-bg);
            border-radius: 24px;
            grid-column: 1 / -1;
            border: 2px dashed var(--border-color);
        }

        .title-gradient {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 900;
        }

        @media (max-width: 768px) {
            .results-grid {
                grid-template-columns: 1fr;
            }
            .filter-form {
                flex-direction: column;
                border-radius: 20px;
                padding: 20px;
                width: 100%;
            }
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
                <button id="themeToggle" class="btn-theme" title="Cambiar Tema">
                    🌙
                </button>
                
                <!-- BOTÓN ESTRATÉGICO DE RESULTADOS (ACTIVO) -->
                <a href="resultados.php" class="btn-direct-results" style="filter: brightness(1.2); border-color: white;">
                    <i class="bi bi-calendar-check-fill"></i>
                    <span class="btn-text">Resultados</span>
                    <span class="live-dot"></span>
                </a>
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
                    <li><a href="calculadora.html"><i class="bi bi-calculator"></i> Calculadora</a></li>
                    <li><a href="../index.html#ayuda"><i class="bi bi-question-circle"></i> Ayuda</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="results-container">
        <div style="text-align: center; margin-bottom: 50px;">
            <h1 class="title-gradient" style="font-size: 3rem; margin-bottom: 10px;">Resultados Diarios</h1>
            <p style="color: var(--text-muted); font-size: 1.1rem;">Información oficial y verificada en tiempo real.</p>
        </div>

        <div class="date-filter-box">
            <form action="resultados.php" method="GET" class="filter-form">
                <span style="font-weight: 700; color: var(--text-dark);"><i class="bi bi-funnel"></i> Filtrar por fecha:</span>
                <input type="date" name="fecha" class="date-input" value="<?php echo $fechaFiltroQuery; ?>" onchange="this.form.submit()">
            </form>
            <?php if ($fechaFiltroQuery === date('Y-m-d')): ?>
                <span class="badge" style="background: var(--success-color); color: white; padding: 5px 12px; border-radius: 12px; font-size: 0.8rem; font-weight: 700;">
                    <i class="bi bi-record-fill" style="animation: pulseLive 1s infinite;"></i> EN VIVO - HOY
                </span>
            <?php endif; ?>
        </div>

        <div class="results-grid">
            <?php if (empty($resultados)): ?>
                <div class="no-data">
                    <i class="bi bi-search" style="font-size: 4rem; color: var(--border-color); display: block; margin-bottom: 20px;"></i>
                    <h3 style="color: var(--text-dark);">Sin resultados encontrados</h3>
                    <p style="color: var(--text-muted);">No hay sorteos registrados para el día <strong><?php echo date('d/m/Y', strtotime($fechaFiltroQuery)); ?></strong> todavía.</p>
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
                        
                        <div style="font-size: 0.9rem; color: var(--text-muted); text-align: center; border-top: 1px solid var(--border-color); padding-top: 15px; font-weight: 500;">
                            <i class="bi bi-calendar3"></i> Sorteo del <?php echo date('d/m/Y', strtotime($res['fecha'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer Dinámico -->
    <div id="rlabs-footer-container"></div>

    <script src="js/script.js"></script>
    <script>
        // Cargar Footer Dinámico con CSS (Asegurando que rlabs-footer.css esté en el head)
        fetch('footer/rlabs-footer.html?v=' + new Date().getTime())
            .then(res => res.text())
            .then(html => {
                document.getElementById('rlabs-footer-container').innerHTML = html;
            });
            
        // Toggle Menú Móvil
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