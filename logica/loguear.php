<?php
require '../Conexion.php';
session_start();

if(isset($_POST['Usuario']) && isset($_POST['Clave'])) {
    $Usuario = $_POST['Usuario'];
    $Clave = $_POST['Clave'];

    error_log("Usuario: $Usuario, Clave: $Clave");
    
    if ($conexion->connect_error) {
        die("Error de conexión: " . $conexion->connect_error);
    }
    
    $q = "SELECT COUNT(*) as contar FROM Usuarios WHERE Usuario = '$Usuario' AND clave = '$Clave'";
    error_log("Consulta SQL: " . $q);
    
    $consulta = mysqli_query($conexion, $q);
    
    if($consulta) {
        $array = mysqli_fetch_array($consulta);
        
        if($array['contar'] > 0) {
            $_SESSION['username'] = $Usuario;
            header("location: ../paginaprincipal.php");
            exit();
        } else {
            echo "Datos Incorrectos - Usuario o contraseña no válidos";
            echo "<br><a href='../login.php'>Volver al login</a>";
        }
    } else {
        echo "Error en la consulta SQL: " . mysqli_error($conexion);
        echo "<br>Consulta: " . $q;
    }
} else {
    echo "No se recibieron datos del formulario";
    header("refresh:2; url=../login.php");
    exit();
}
?>