<?php
require_once __DIR__ . '/includes/Database.php';

$db = new Database();

try {
    echo "🔍 Probando conexión a Supabase...\n";
    // Intentamos listar la tabla (esto fallará si la tabla no existe, lo cual es útil)
    $response = $db->fetch('lottery_results', ['limit' => 1]);
    echo "✅ Conexión exitosa. La tabla 'lottery_results' ya existe.\n";
}
catch (Exception $e) {
    if (strpos($e->getMessage(), '404') !== false || strpos($e->getMessage(), 'relation "public.lottery_results" does not exist') !== false) {
        echo "⚠️  Conexión exitosa, Pero parece que la tabla 'lottery_results' AÚN NO ha sido creada.\n";
        echo "👉 Por favor, ejecuta el SQL en el editor de Supabase.\n";
    }
    else {
        echo "❌ Error de conexión: " . $e->getMessage() . "\n";
    }
}
