<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoPoints API Documentation</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@3/swagger-ui.css" />
    <style>
        html {
            box-sizing: border-box;
            overflow: -moz-scrollbars-vertical;
            overflow-y: scroll;
        }
        *,
        *:before,
        *:after {
            box-sizing: inherit;
        }
        body {
            margin: 0;
            background: #fafafa;
        }
        #swagger-ui {
            padding: 20px 0;
        }
        .swagger-ui .topbar {
            background: #2E7D32;
            padding: 10px 0;
        }
        .swagger-ui .topbar .download-url-wrapper {
            display: none;
        }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>

    <script src="https://unpkg.com/swagger-ui-dist@3/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@3/swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = function() {
            // Configuración de Swagger UI
            const ui = SwaggerUIBundle({
                url: 'generate.php', // Tu generador de OpenAPI
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "StandaloneLayout",
                defaultModelsExpandDepth: -1, // Oculta los modelos por defecto
                docExpansion: 'none', // Colapsa todos los endpoints inicialmente
                filter: true, // Habilita la búsqueda/filtro
                tagsSorter: 'alpha', // Ordena tags alfabéticamente
                operationsSorter: 'alpha', // Ordena operaciones alfabéticamente
                validatorUrl: null, // Desactiva validación externa
                onComplete: function() {
                    // Personalizaciones adicionales cuando se carga
                    console.log('EcoPoints API Documentation loaded successfully');
                }
            });

            // Agregar botón personalizado para autenticación
            window.ui = ui;
        }
    </script>
</body>
</html>