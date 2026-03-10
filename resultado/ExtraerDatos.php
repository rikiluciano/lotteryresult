<?php
/**
 * Script PHP para procesamiento de Feed RSS con arquitectura de datos distribuida.
 * Versión: Chimera 2.0 - CORREGIDO
 * Autor: Project Chimera, Arquitecto de Software
 * Fecha: 2025-09-29
 */

// Configuración de zona horaria y directorios base
date_default_timezone_set('America/Santo_Domingo');
const BASE_DATA_DIR = 'json';
const FEED_URL_FILE = 'feed_url.txt';
const ALIAS_FILE = 'alias.json';

// --- Funciones de Utilidad y Configuración ---

function logMessage($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    echo "[$timestamp] [$level] $message\n";
}

function loadFeedUrl() {
    if (!file_exists(FEED_URL_FILE)) throw new Exception("Archivo " . FEED_URL_FILE . " no encontrado");
    $url = trim(file_get_contents(FEED_URL_FILE));
    if (empty($url)) throw new Exception("URL del feed está vacía");
    return $url;
}

function loadAliases() {
    if (!file_exists(ALIAS_FILE)) throw new Exception("Archivo " . ALIAS_FILE . " no encontrado");
    $aliases = json_decode(file_get_contents(ALIAS_FILE), true);
    if (json_last_error() !== JSON_ERROR_NONE) throw new Exception("Error al parsear " . ALIAS_FILE);
    return $aliases;
}

// --- ARQUITECTURA DE DATOS CORREGIDA ---

/**
 * CORREGIDO: Determina y asegura la ruta del archivo de conteo para una fecha dada.
 */
function getConteoFilePath($pubDate) {
    $timestamp = strtotime($pubDate);
    if ($timestamp === false) {
        throw new Exception("Fecha inválida al generar ruta de archivo: $pubDate");
    }

    $mesesNombres = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
        5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
        9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'
    ];
    
    $year = date('Y', $timestamp);
    $monthIndex = (int)date('n', $timestamp);
    $monthName = $mesesNombres[$monthIndex];

    $monthDir = BASE_DATA_DIR . "/conteo-{$year}/conteo-{$monthName}";

    if (!is_dir($monthDir)) {
        logMessage("Creando directorio: $monthDir", 'SETUP');
        if (!mkdir($monthDir, 0775, true)) {
            throw new Exception("¡CRÍTICO! No se pudo crear el directorio: $monthDir. Verifique los permisos.");
        }
    }

    return "{$monthDir}/conteo-{$monthName}.json";
}

/**
 * CORREGIDO: Carga los conteos desde un archivo específico.
 */
function loadConteos($filePath) {
    if (!file_exists($filePath)) {
        logMessage("Archivo no existe, creando nueva estructura: $filePath", 'INFO');
        return [];
    }
    
    $content = file_get_contents($filePath);
    $conteos = json_decode($content, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        logMessage("Error al parsear $filePath, iniciando con datos vacíos para este mes", 'WARNING');
        return [];
    }
    
    return $conteos;
}

/**
 * CORREGIDO: Guarda los conteos en un archivo específico.
 */
function saveConteos($conteos, $filePath) {
    $json = json_encode($conteos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        throw new Exception("¡CRÍTICO! Error al codificar JSON para $filePath");
    }
    
    $result = file_put_contents($filePath, $json);
    if ($result === false) {
        throw new Exception("¡CRÍTICO! Error al guardar en $filePath");
    }
    
    logMessage("Guardado exitoso: $filePath (" . strlen($json) . " bytes)", 'SUCCESS');
}

function getFeedContent($url) {
    $context = stream_context_create([
        'http' => [
            'timeout' => 30,
            'user_agent' => 'Mozilla/5.0 (compatible; RSS Processor)',
            'method' => 'GET',
            'header' => "Accept: application/rss+xml, application/xml, text/xml\r\n"
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ]);
    
    $content = @file_get_contents($url, false, $context);
    
    if ($content === false) {
        $httpUrl = str_replace('https://', 'http://', $url);
        logMessage("HTTPS falló, intentando con HTTP: $httpUrl", 'WARNING');
        $content = @file_get_contents($httpUrl, false, $context);
    }
    
    if ($content === false && function_exists('curl_init')) {
        logMessage("file_get_contents falló, intentando con cURL", 'WARNING');
        $content = getFeedContentWithCurl($url);
    }
    
    if ($content === false) {
        throw new Exception("Error al obtener el feed RSS de: $url. Verifique su conexión a internet.");
    }
    
    return $content;
}

function getFeedContentWithCurl($url) {
    if (!function_exists('curl_init')) {
        return false;
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; RSS Processor)');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/rss+xml, application/xml, text/xml'
    ]);
    
    $content = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($content === false || !empty($error) || $httpCode >= 400) {
        logMessage("cURL error: $error, HTTP Code: $httpCode", 'ERROR');
        return false;
    }
    
    return $content;
}

function parseFeed($content) {
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    
    if (!$dom->loadXML($content)) {
        throw new Exception("Error al parsear el XML del feed RSS");
    }
    
    $items = [];
    $entries = $dom->getElementsByTagName('item');
    
    foreach ($entries as $entry) {
        $title = $entry->getElementsByTagName('title')->item(0);
        $pubDate = $entry->getElementsByTagName('pubDate')->item(0);
        
        if ($title && $pubDate) {
            $items[] = [
                'title' => trim($title->nodeValue),
                'pubDate' => trim($pubDate->nodeValue)
            ];
        }
    }
    
    return $items;
}

/**
 * CORREGIDO: Función para extraer números del título
 */
function extractNumbers($title) {
    // Buscar patrón después de "hoy:" - números separados por guiones
    if (preg_match('/hoy:\s*(\d{2}-\d{2}-\d{2})/i', $title, $matches)) {
        $numbers = explode('-', $matches[1]);
        logMessage("Números extraídos: " . implode('-', $numbers), 'DEBUG');
        return $numbers;
    }
    logMessage("No se encontraron números en: $title", 'WARNING');
    return [];
}

/**
 * CORREGIDO: Función para extraer componentes de fecha con identificador único
 */
function extraerFechaComponentes($pubDate) {
    $timestamp = strtotime($pubDate);
    if ($timestamp === false) throw new Exception("Error al parsear fecha: $pubDate");
    
    $mesesNombres = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril', 
        5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto', 
        9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'
    ];
    $diasNombres = [
        1 => 'lunes', 2 => 'martes', 3 => 'miércoles', 4 => 'jueves', 
        5 => 'viernes', 6 => 'sábado', 0 => 'domingo'
    ];
    
    $year = date('Y', $timestamp);
    $month = $mesesNombres[(int)date('n', $timestamp)];
    $dayNum = (int)date('j', $timestamp);
    $dayName = $diasNombres[(int)date('w', $timestamp)];
    
    return [
        // CRÍTICO: Ahora incluye el año para evitar colisiones
        'dia_nombre' => "$dayName, $dayNum de $month de $year",
        'fecha_iso' => date('Y-m-d', $timestamp)
    ];
}

// --- LÓGICA DE PROCESAMIENTO CORREGIDA ---

function inicializarEstructuraLoteria($alias, &$conteos) {
    if (!isset($conteos[$alias])) {
        $conteos[$alias] = [
            'ConteoEnPrimera' => [],
            'ConteoAll' => [],
            'estadisticas' => [
                'ConteoEnPrimera' => ['general' => []],
                'ConteoAll' => ['general' => []],
                'total_sorteos' => 0,
                'fecha_primer_registro' => null,
                'fecha_ultimo_registro' => null
            ]
        ];
        logMessage("Estructura inicializada para: $alias", 'DEBUG');
    }
}

/**
 * CORREGIDO: Verifica duplicados usando fecha ISO completa
 */
function yaFueProcesado($alias, $fechaISO, $conteos) {
    if (!isset($conteos[$alias]['ConteoAll'])) {
        return false;
    }
    
    // Buscar por fecha ISO en todos los registros
    foreach ($conteos[$alias]['ConteoAll'] as $dia => $data) {
        if (isset($data['fecha_iso']) && $data['fecha_iso'] === $fechaISO) {
            logMessage("Duplicado detectado: $alias - $fechaISO", 'DEBUG');
            return true;
        }
    }
    
    return false;
}

/**
 * CORREGIDO: Calcula estadísticas para el conjunto de datos actual
 */
function calcularEstadisticas($alias, &$conteos) {
    $statsData = &$conteos[$alias]['estadisticas'];
    $statsData['ConteoEnPrimera']['general'] = [];
    $statsData['ConteoAll']['general'] = [];
    $totalSorteos = 0;
    $fechasRegistros = [];

    foreach ($conteos[$alias]['ConteoAll'] as $dia => $data) {
        $totalSorteos++;
        if (isset($data['fecha_completa'])) {
            $fechasRegistros[] = $data['fecha_completa'];
        }
        if (isset($data['numeros'])) {
            foreach ($data['numeros'] as $numero) {
                if (!isset($statsData['ConteoAll']['general'][$numero])) {
                    $statsData['ConteoAll']['general'][$numero] = 0;
                }
                $statsData['ConteoAll']['general'][$numero]++;
            }
        }
    }
    
    foreach ($conteos[$alias]['ConteoEnPrimera'] as $dia => $data) {
        if (isset($data['numeros'])) {
            foreach ($data['numeros'] as $numero) {
                if (!isset($statsData['ConteoEnPrimera']['general'][$numero])) {
                    $statsData['ConteoEnPrimera']['general'][$numero] = 0;
                }
                $statsData['ConteoEnPrimera']['general'][$numero]++;
            }
        }
    }

    $statsData['total_sorteos'] = $totalSorteos;
    if (!empty($fechasRegistros)) {
        sort($fechasRegistros);
        $statsData['fecha_primer_registro'] = date('Y-m-d', strtotime($fechasRegistros[0]));
        $statsData['fecha_ultimo_registro'] = date('Y-m-d', strtotime(end($fechasRegistros)));
    }
}

/**
 * CORREGIDO: Procesa un item y lo añade a la estructura de datos en memoria.
 */
function processItem($item, $aliases, &$conteos) {
    $title = $item['title'];
    $pubDate = $item['pubDate'];
    
    // Buscar alias coincidente
    $aliasMatch = null;
    foreach ($aliases as $pattern => $alias) {
        if (stripos($title, $pattern) !== false) {
            $aliasMatch = $alias;
            break;
        }
    }
    
    if (!$aliasMatch) {
        logMessage("No se encontró alias para: $title", 'DEBUG');
        return false;
    }
    
    // Extraer números primero
    $numbers = extractNumbers($title);
    if (empty($numbers) || count($numbers) < 3) {
        logMessage("Números insuficientes en: $title", 'WARNING');
        return false;
    }
    
    // Inicializar estructura
    inicializarEstructuraLoteria($aliasMatch, $conteos);

    try {
        $fechaComponentes = extraerFechaComponentes($pubDate);
    } catch (Exception $e) {
        logMessage($e->getMessage(), 'ERROR');
        return false;
    }

    // CRÍTICO: Verificar duplicados usando fecha ISO
    if (yaFueProcesado($aliasMatch, $fechaComponentes['fecha_iso'], $conteos)) {
        return false;
    }
    
    $dia = $fechaComponentes['dia_nombre'];
    
    logMessage("✓ Procesando $aliasMatch: " . implode('-', $numbers) . " | $dia", 'INFO');
    
    // Almacenar datos con fecha ISO para detección de duplicados
    $conteos[$aliasMatch]['ConteoEnPrimera'][$dia] = [
        'numeros' => [$numbers[0]], 
        'fecha_completa' => $pubDate,
        'fecha_iso' => $fechaComponentes['fecha_iso']
    ];
    
    $conteos[$aliasMatch]['ConteoAll'][$dia] = [
        'numeros' => $numbers, 
        'fecha_completa' => $pubDate,
        'fecha_iso' => $fechaComponentes['fecha_iso']
    ];
    
    // Recalcular estadísticas
    calcularEstadisticas($aliasMatch, $conteos);
    
    return true;
}

/**
 * FUNCIÓN PRINCIPAL CORREGIDA
 */
function main() {
    try {
        logMessage("=== Iniciando Procesamiento (Chimera 2.0 CORREGIDO) ===", 'INFO');
        
        $feedUrl = loadFeedUrl();
        $aliases = loadAliases();
        
        logMessage("Obteniendo feed desde: $feedUrl", 'INFO');
        $feedContent = getFeedContent($feedUrl);
        
        logMessage("Parseando feed...", 'INFO');
        $items = parseFeed($feedContent);
        
        logMessage("Items encontrados en el feed: " . count($items), 'INFO');

        if (empty($items)) {
            logMessage("⚠️ No se encontraron items en el feed", 'WARNING');
            return;
        }

        $conteosPorArchivo = [];
        $itemsProcesados = 0;
        $itemsOmitidos = 0;

        foreach ($items as $index => $item) {
            logMessage("Procesando item " . ($index + 1) . "/" . count($items), 'DEBUG');
            
            $pubDate = $item['pubDate'];
            
            try {
                $filePath = getConteoFilePath($pubDate);
            } catch (Exception $e) {
                logMessage("Error al obtener ruta de archivo: " . $e->getMessage(), 'ERROR');
                continue;
            }

            // Cargar datos del mes si aún no están en el buffer
            if (!isset($conteosPorArchivo[$filePath])) {
                logMessage("Cargando datos para: $filePath", 'DEBUG');
                $conteosPorArchivo[$filePath] = loadConteos($filePath);
            }

            // Procesar el item
            if (processItem($item, $aliases, $conteosPorArchivo[$filePath])) {
                $itemsProcesados++;
            } else {
                $itemsOmitidos++;
            }
        }

        logMessage("Items procesados: $itemsProcesados", 'INFO');
        logMessage("Items omitidos: $itemsOmitidos", 'INFO');

        if ($itemsProcesados > 0) {
            logMessage("Guardando archivos modificados...", 'INFO');
            $archivosGuardados = 0;
            
            foreach ($conteosPorArchivo as $filePath => $data) {
                if (!empty($data)) {
                    saveConteos($data, $filePath);
                    $archivosGuardados++;
                }
            }
            
            logMessage("✓ Archivos guardados: $archivosGuardados", 'SUCCESS');
            logMessage("=== Procesamiento completado exitosamente ===", 'SUCCESS');
        } else {
            logMessage("⚠️ No se procesaron items nuevos. No hay cambios para guardar.", 'WARNING');
        }
        
    } catch (Exception $e) {
        logMessage("ERROR CRÍTICO: " . $e->getMessage(), 'FATAL');
        logMessage("Stack trace: " . $e->getTraceAsString(), 'FATAL');
        exit(1);
    }
}

// Ejecutar
main();
?>