<?php
$host = "localhost";
$username = "root";
$password = "";
$db = "iniciar sesion"; // Si puedes, cámbiala a iniciar_sesion en tu base de datos

// 1. Creamos la conexión en estilo Orientado a Objetos
$Conexion = new mysqli($host, $username, $password, $db);

// 2. Validamos si hubo un error de conexión
if ($Conexion->connect_error) {
    die("Error de conexión con MySQLi: " . $Conexion->connect_error);
}

// 3. Corregido: Ajustamos el charset usando el método del objeto $Conexion
$Conexion->set_charset("utf8");
?>