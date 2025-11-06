<?php
function generarCodigoUnico($longitud = 10) {
    return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $longitud);
}

function respuestaJSON($data, $codigo = 200) {
    http_response_code($codigo);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function dispositivoValido($dispositivo_id) {
    $validos = ["DISP12345", "DISP67890", "DISPABCDE"];
    return in_array($dispositivo_id, $validos);
}
?>
