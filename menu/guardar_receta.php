<?php
include '../iniciar sesion/conexion.php';

if (!isset($Conexion) || $Conexion === null) {
    die("Error crítico: No se pudo cargar la conexión a la base de datos.");
}

$Conexion->select_db("productos");

if ($_POST) {
    // Como viene directamente del select, lo recibimos de forma estándar:
    $nombre_plato = $Conexion->real_escape_string(trim($_POST['nombre_plato']));
    
    if (empty($nombre_plato)) {
        die("Error: No se seleccionó ni se especificó ningún nombre de receta.");
    }

    $productos = $_POST['productos'];   
    $cantidades = $_POST['cantidades']; 

    $sql_receta = "INSERT INTO productos.recetas_comida (nombre_plato) VALUES ('$nombre_plato')";
    
    if ($Conexion->query($sql_receta)) {
        $id_receta_nueva = $Conexion->insert_id;

        foreach ($productos as $indice => $id_producto) {
            $id_p = $Conexion->real_escape_string($id_producto);
            $cantidad = $Conexion->real_escape_string($cantidades[$indice]);

            if (!empty($id_p)) {
                $sql_details = "INSERT INTO productos.receta_detalles (id_receta, id_producto, cantidad_necesaria) 
                                VALUES ('$id_receta_nueva', '$id_p', '$cantidad')";
                $Conexion->query($sql_details);
            }
        }

        header("Location: lista_recetas.php?exito=1");
        exit();

    } else {
        echo "Error al guardar la receta: " . $Conexion->error;
    }
}
?>