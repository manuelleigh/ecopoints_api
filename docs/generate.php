<?php
require_once(__DIR__ . '/../vendor/autoload.php');

header('Content-Type: application/json');

try {
    // Escanear solo los endpoints
    $openapi = \OpenApi\Generator::scan([__DIR__ . '/../endpoints']);
    
    // Si no tiene info, agregarla manualmente
    if (!$openapi->info) {
        $openapi->info = new \OpenApi\Annotations\Info([
            'title' => 'EcoPoints API',
            'version' => '1.0.0',
            'description' => 'API para el sistema de puntos ecológicos EcoPoints'
        ]);
    }
    
    if (!$openapi->servers) {
        $openapi->servers = [new \OpenApi\Annotations\Server([
            'url' => 'https://ecopoints.hvd.lat',
            'description' => 'Servidor de producción'
        ])];
    }
    
    if (!$openapi->components) {
        $openapi->components = new \OpenApi\Annotations\Components([]);
    }
    
    if (!$openapi->components->securitySchemes) {
        $openapi->components->securitySchemes = [
            'bearerAuth' => new \OpenApi\Annotations\SecurityScheme([
                'type' => 'http',
                'scheme' => 'bearer',
                'bearerFormat' => 'JWT'
            ])
        ];
    }

    echo $openapi->toJson();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?>