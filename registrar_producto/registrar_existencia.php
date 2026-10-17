<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "conexion_anadir.php";

if (isset($_POST['btnstock'])) {
    // Como los datos vienen de un formulario múltiple con [], estas variables son ARRAYS
    $ids_productos = $_POST['id_producto'];
    $cantidades = $_POST['cantidad'];
    $fechas_caducidad = $_POST['fecha_caducidad'];
    
    $usuario_activo = isset($_SESSION['usuario']) ? mysqli_real_escape_string($conexion, $_SESSION['usuario']) : 'sistema';

    // Iniciamos una transacción SQL para asegurar la integridad de los datos
    mysqli_begin_transaction($conexion);

    try {
        // Recorremos la lista de productos enviados en el modal usando su índice ($index)
        foreach ($ids_productos as $index => $id_raw) {
            $id_producto = intval($id_raw);
            
            // Si la fila está vacía o no se seleccionó un producto válido, la saltamos de forma segura
            if ($id_producto <= 0) {
                continue; 
            }
            
            // 🔍 VERIFICACIÓN 1: ¿Existe realmente este ID en la tabla maestra de productos?
            $buscar_producto = $conexion->query("SELECT id_producto FROM productos WHERE id_producto = $id_producto");
            if ($buscar_producto->num_rows === 0) {
                throw new Exception("Estás intentando añadir stock al ID de producto ($id_producto), pero ese ID NO existe en tu base de datos.");
            }
            
            // Sanitizamos los valores de cantidad y fecha correspondientes a esta fila
            $cantidad_a_anadir = floatval($cantidades[$index]);
            $fecha_input = isset($fechas_caducidad[$index]) ? $fechas_caducidad[$index] : '';
            $fecha_caducidad = mysqli_real_escape_string($conexion, $fecha_input);

            // 🔍 VERIFICACIÓN 2: ¿El producto ya cuenta con un registro en la tabla stock?
            $comprobar_stock = $conexion->query("SELECT id_producto FROM stock WHERE id_producto = $id_producto");

            if ($comprobar_stock->num_rows > 0) {
                // SI YA EXISTE EN EL STOCK: Se actualiza sumando la nueva cantidad
                $sql_accion = "UPDATE stock 
                               SET cantidad_actual = cantidad_actual + $cantidad_a_anadir, 
                                   fecha_caducidad = '$fecha_caducidad' 
                               WHERE id_producto = $id_producto";
            } else {
                // SI NO EXISTE EN EL STOCK (Caso: Auyama, Manzana, etc.): Se crea el registro por primera vez
                // Le asignamos por defecto un stock_minimo de 1.00
                $sql_accion = "INSERT INTO stock (id_producto, cantidad_actual, stock_minimo, fecha_caducidad) 
                               VALUES ($id_producto, $cantidad_a_anadir, 1.00, '$fecha_caducidad')";
            }
            
            // Ejecutamos la acción determinada (INSERT o UPDATE)
            if (!$conexion->query($sql_accion)) {
                throw new Exception("Error al procesar el inventario en la tabla stock para el ID: $id_producto.");
            }

            // 3. REGISTRAR EL MOVIMIENTO EN EL HISTORIAL (Para reporte.php)
            $cantidad_positiva = abs($cantidad_a_anadir);
            $tipo_movimiento = "INGRESO"; 

            $sql_historial = "INSERT INTO historial_movimientos (id_producto, tipo_movimiento, cantidad, usuario, fecha) 
                              VALUES ($id_producto, '$tipo_movimiento', $cantidad_positiva, '$usuario_activo', NOW())";
            
            if (!$conexion->query($sql_historial)) {
                throw new Exception("Error al guardar en el historial para el ID de producto: $id_producto.");
            }
        }

        // Si todo el bucle se completó con éxito, confirmamos los cambios en la base de datos
        mysqli_commit($conexion);

        // Guardamos el mensaje de éxito para que SweetAlert2 lo renderice en stock.php
        $_SESSION['mensaje_exito'] = "Los insumos han sido registrados y añadidos al stock correctamente.";

        // Redirección segura
        header("Location: stock.php");
        exit();

    } catch (Exception $e) {
        // En caso de cualquier error, cancelamos todas las operaciones pendientes de este envío
        mysqli_rollback($conexion);
        die("Error Grave: " . $e->getMessage() . " Revisa tu formulario HTML o Base de datos.");
    }
}
?>