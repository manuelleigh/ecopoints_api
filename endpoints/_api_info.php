<?php
/**
 * @OA\OpenApi(
 *     @OA\Info(
 *         title="EcoPoints API",
 *         version="1.0.0",
 *         description="API para el sistema de puntos ecológicos EcoPoints. Permite gestionar usuarios, canjes, QR y transacciones."
 *     ),
 *     @OA\Server(
 *         url="https://ecopoints.hvd.lat",
 *         description="Servidor de producción"
 *     ),
 *     @OA\Server(
 *         url="http://localhost/ecopoints-api",
 *         description="Servidor local de desarrollo"
 *     ),
 *     @OA\SecurityScheme(
 *         securityScheme="bearerAuth",
 *         type="http",
 *         scheme="bearer",
 *         bearerFormat="JWT",
 *         description="Autenticación mediante token JWT en el encabezado Authorization"
 *     )
 * )
 */
