<?php
/**
 * @OA\Info(
 *     title="EcoPoints API",
 *     version="1.0.0",
 *     description="API para el sistema de puntos ecológicos EcoPoints",
 *     @OA\Contact(
 *         email="soporte@ecopoints.hvd.lat",
 *         name="Soporte EcoPoints"
 *     )
 * )
 * 
 * @OA\Server(
 *     url="https://ecopoints.hvd.lat",
 *     description="Servidor de producción"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 * 
 * @OA\Tag(
 *     name="Autenticación",
 *     description="Endpoints para registro, login y gestión de contraseñas"
 * )
 * @OA\Tag(
 *     name="Usuario",
 *     description="Endpoints relacionados con el usuario autenticado"
 * )
 * @OA\Tag(
 *     name="Códigos QR",
 *     description="Endpoints para generar y validar códigos QR de reciclaje"
 * )
 * @OA\Tag(
 *     name="Convenios",
 *     description="Endpoints para listar y canjear convenios"
 * )
 * @OA\Tag(
 *     name="Convenios - Admin",
 *     description="Endpoints administrativos para gestionar convenios"
 * )
 * @OA\Tag(
 *     name="Empresas - Admin",
 *     description="Endpoints administrativos para gestionar empresas"
 * )
 * @OA\Tag(
 *     name="Historial",
 *     description="Endpoints para consultar historial de transacciones y canjes"
 * )
 * @OA\Tag(
 *     name="Canjes",
 *     description="Endpoints para realizar canjes de puntos"
 * )
 */