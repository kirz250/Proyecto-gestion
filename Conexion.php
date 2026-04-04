<?php
$host = "localhost";
$username = "root";
$password = "";
$db = "iniciar sesion";

$conexion = new mysqli($host, $username, $password, $db);
if ($conexion->connect_error) {
    die("Error de conexión con MySQLi: " . $conexion->connect_error);
}
?>