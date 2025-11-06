<?php
$host = "localhost";


$db_name = "ecopoints_db";
$username = "root";
$password = "";
// $db_name = "hvd_ecopoints_db";
// $username = "hvd_ecopoints";
// $password = "mleigh$2810";

try {
    $conn = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die(json_encode(["error" => "Error de conexión: " . $e->getMessage()]));
}
?>