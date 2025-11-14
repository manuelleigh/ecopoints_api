<?php
header('Content-Type: application/json');

$openapi = [
    'openapi' => '3.0.0',
    'info' => [
        'title' => 'EcoPoints API',
        'version' => '1.0.0',
        'description' => 'API para el sistema de puntos ecológicos EcoPoints',
        'contact' => [
            'email' => 'soporte@ecopoints.hvd.lat',
            'name' => 'Soporte EcoPoints'
        ]
    ],
    'servers' => [
        [
            'url' => 'https://ecopoints.hvd.lat',
            'description' => 'Servidor de producción'
        ]
    ],
    'paths' => [
        '/api/logeoUsuario' => [
            'post' => [
                'tags' => ['Autenticación'],
                'summary' => 'Iniciar sesión de usuario',
                'description' => 'Autentica un usuario con email y contraseña, devuelve información del usuario y token JWT para autenticación en api protegidos',
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'required' => ['email', 'password'],
                                'properties' => [
                                    'email' => [
                                        'type' => 'string',
                                        'format' => 'email',
                                        'example' => 'usuario@ejemplo.com'
                                    ],
                                    'password' => [
                                        'type' => 'string',
                                        'format' => 'password', 
                                        'example' => 'miContraseñaSegura123'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Login exitoso',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'usuario' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'id' => ['type' => 'integer', 'example' => 1],
                                                'nombre' => ['type' => 'string', 'example' => 'Juan Pérez'],
                                                'correo' => ['type' => 'string', 'example' => 'juan@ejemplo.com']
                                            ]
                                        ],
                                        'token' => [
                                            'type' => 'string', 
                                            'example' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...'
                                        ],
                                        'mensaje' => [
                                            'type' => 'string',
                                            'example' => 'Inicio de sesión exitoso'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    '400' => [
                        'description' => 'Datos incompletos',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'error' => ['type' => 'string', 'example' => 'Datos incompletos']
                                    ]
                                ]
                            ]
                        ]
                    ],
                    '401' => [
                        'description' => 'Credenciales inválidas',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object', 
                                    'properties' => [
                                        'error' => ['type' => 'string', 'example' => 'Credenciales inválidas']
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ],
        
        '/api/registrarUsuario' => [
            'post' => [
                'tags' => ['Autenticación'],
                'summary' => 'Registrar nuevo usuario',
                'description' => 'Crea una nueva cuenta de usuario en el sistema EcoPoints',
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'required' => ['nombre', 'email', 'password'],
                                'properties' => [
                                    'nombre' => [
                                        'type' => 'string',
                                        'example' => 'Juan Pérez'
                                    ],
                                    'email' => [
                                        'type' => 'string',
                                        'format' => 'email',
                                        'example' => 'juan@ejemplo.com'
                                    ],
                                    'password' => [
                                        'type' => 'string',
                                        'format' => 'password',
                                        'example' => 'miContraseñaSegura123'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Usuario registrado exitosamente',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'mensaje' => ['type' => 'string', 'example' => 'Usuario registrado con éxito']
                                    ]
                                ]
                            ]
                        ]
                    ],
                    '400' => [
                        'description' => 'Datos incompletos',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'error' => ['type' => 'string', 'example' => 'Datos incompletos']
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ],
        
        '/api/obtenerPuntos' => [
            'get' => [
                'tags' => ['Usuario'],
                'summary' => 'Obtener puntos del usuario',
                'description' => 'Obtiene la cantidad de puntos actuales del usuario autenticado. Requiere autenticación Bearer Token.',
                'security' => [['bearerAuth' => []]],
                'responses' => [
                    '200' => [
                        'description' => 'Puntos obtenidos exitosamente',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'puntos' => ['type' => 'integer', 'example' => 1500]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    '401' => [
                        'description' => 'No autorizado',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'error' => ['type' => 'string', 'example' => 'Token inválido o expirado']
                                    ]
                                ]
                            ]
                        ]
                    ],
                    '404' => [
                        'description' => 'Usuario no encontrado',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'error' => ['type' => 'string', 'example' => 'Usuario no encontrado']
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ],
        
        '/api/validarQR' => [
            'post' => [
                'tags' => ['Códigos QR'],
                'summary' => 'Validar código QR de reciclaje',
                'description' => 'Valida un código QR generado por el sistema y asigna los puntos correspondientes al usuario autenticado. Requiere autenticación Bearer Token.',
                'security' => [['bearerAuth' => []]],
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'required' => ['codigo_qr'],
                                'properties' => [
                                    'codigo_qr' => [
                                        'type' => 'string',
                                        'example' => 'QR123ABC456DEF'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'responses' => [
                    '200' => [
                        'description' => 'QR validado exitosamente',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'mensaje' => ['type' => 'string', 'example' => 'Canje exitoso'],
                                        'usuario' => ['type' => 'string', 'example' => 'Juan Pérez'],
                                        'puntos_obtenidos' => ['type' => 'integer', 'example' => 25]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    '400' => [
                        'description' => 'Datos incompletos',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'error' => ['type' => 'string', 'example' => 'Datos incompletos']
                                    ]
                                ]
                            ]
                        ]
                    ],
                    '401' => [
                        'description' => 'No autorizado',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'error' => ['type' => 'string', 'example' => 'Token inválido o expirado']
                                    ]
                                ]
                            ]
                        ]
                    ],
                    '404' => [
                        'description' => 'Código QR no encontrado',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'error' => ['type' => 'string', 'example' => 'Código QR inválido']
                                    ]
                                ]
                            ]
                        ]
                    ],
                    '409' => [
                        'description' => 'Código QR ya utilizado',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'error' => ['type' => 'string', 'example' => 'El código ya fue canjeado o expiró']
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ],
        
        '/api/listarConvenios' => [
            'get' => [
                'tags' => ['Convenios'],
                'summary' => 'Listar convenios disponibles',
                'description' => 'Obtiene la lista de todos los convenios activos disponibles para canje. Requiere autenticación Bearer Token.',
                'security' => [['bearerAuth' => []]],
                'responses' => [
                    '200' => [
                        'description' => 'Lista de convenios obtenida exitosamente',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'id' => ['type' => 'integer', 'example' => 1],
                                            'titulo' => ['type' => 'string', 'example' => '20% de descuento en productos ecológicos'],
                                            'descripcion' => ['type' => 'string', 'example' => 'Descuento especial en toda la tienda online'],
                                            'puntos_requeridos' => ['type' => 'integer', 'example' => 500],
                                            'stock' => ['type' => 'integer', 'example' => 25],
                                            'empresa' => ['type' => 'string', 'example' => 'EcoStore'],
                                            'imagen_url' => ['type' => 'string', 'nullable' => true, 'example' => 'https://ecopoints.hvd.lat/data/img/convenio_1.jpg'],
                                            'logo_url' => ['type' => 'string', 'nullable' => true, 'example' => 'https://ecopoints.hvd.lat/data/logos/ecostore.png'],
                                            'descuento' => ['type' => 'integer', 'example' => 20]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    '401' => [
                        'description' => 'No autorizado',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'error' => ['type' => 'string', 'example' => 'Token inválido o expirado']
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ],
        
        '/api/canjearPuntos' => [
            'post' => [
                'tags' => ['Canjes'],
                'summary' => 'Canjear puntos por convenio',
                'description' => 'Permite al usuario canjear sus puntos por un convenio disponible. Requiere autenticación Bearer Token.',
                'security' => [['bearerAuth' => []]],
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'required' => ['convenio_id'],
                                'properties' => [
                                    'convenio_id' => [
                                        'type' => 'integer',
                                        'example' => 1
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Canje realizado exitosamente',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'mensaje' => ['type' => 'string', 'example' => 'Canje realizado con éxito'],
                                        'codigo_entrega' => ['type' => 'string', 'example' => 'DESC2024ABC123'],
                                        'puntos_restantes' => ['type' => 'integer', 'example' => 850]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    '400' => [
                        'description' => 'Solicitud incorrecta',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'error' => ['type' => 'string', 'example' => 'Puntos insuficientes para este canje']
                                    ]
                                ]
                            ]
                        ]
                    ],
                    '401' => [
                        'description' => 'No autorizado',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'error' => ['type' => 'string', 'example' => 'Token inválido o expirado']
                                    ]
                                ]
                            ]
                        ]
                    ],
                    '404' => [
                        'description' => 'Recurso no encontrado',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'error' => ['type' => 'string', 'example' => 'Convenio no disponible o inactivo']
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]
        // Puedes agregar más api aquí según necesites
    ],
    'components' => [
        'securitySchemes' => [
            'bearerAuth' => [
                'type' => 'http',
                'scheme' => 'bearer',
                'bearerFormat' => 'JWT'
            ]
        ]
    ],
    'tags' => [
        ['name' => 'Autenticación', 'description' => 'api para registro, login y gestión de contraseñas'],
        ['name' => 'Usuario', 'description' => 'api relacionados con el usuario autenticado'],
        ['name' => 'Códigos QR', 'description' => 'api para generar y validar códigos QR de reciclaje'],
        ['name' => 'Convenios', 'description' => 'api para listar y canjear convenios'],
        ['name' => 'Canjes', 'description' => 'api para realizar canjes de puntos']
    ]
];

echo json_encode($openapi, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>