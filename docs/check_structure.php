<?php
echo "<h2>🔍 Verificando estructura de archivos endpoints</h2>";

$files = glob(__DIR__ . '/../endpoints/*.php');
foreach ($files as $file) {
    $filename = basename($file);
    echo "<h3>Archivo: $filename</h3>";
    
    $content = file_get_contents($file);
    $lines = explode("\n", $content);
    
    // Mostrar primeras 10 líneas
    echo "<pre>Primeras 10 líneas:\n";
    for ($i = 0; $i < min(10, count($lines)); $i++) {
        echo ($i + 1) . ": " . htmlspecialchars($lines[$i]) . "\n";
    }
    echo "</pre>";
    
    // Buscar anotaciones Swagger
    if (preg_match_all('/@OA\\\\(\w+)/', $content, $matches)) {
        echo "✅ Anotaciones encontradas: " . implode(', ', array_unique($matches[1])) . "<br>";
    } else {
        echo "❌ NO se encontraron anotaciones @OA<br>";
    }
    echo "<hr>";
}
?>