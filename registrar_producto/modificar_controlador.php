<?php
// Asegurar que la sesión esté disponible
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!empty($_POST["btnmodificar"])) {
    $id_s = intval($_POST["id_stock"]);
    $id_p = intval($_POST["id_producto"]);
    
    $nom = isset($_POST["nombre"]) ? trim($_POST["nombre"]) : "";
    $id_cat = intval($_POST["categoria"]); 
    $can = floatval($_POST["cantidad"]);
    $min = floatval($_POST["minimo"]);
    $fec = !empty($_POST["fecha_caducidad"]) ? $_POST["fecha_caducidad"] : null;

    // VALIDACIÓN BACKEND SEGURA: Evitar que la fecha de caducidad vaya hacia atrás
    $fecha_actual = date('Y-m-d');
    if (!empty($fec) && $fec < $fecha_actual) {
        echo "<div class='alert alert-danger text-center fw-bold' style='margin: 15px;'>Error: La fecha de caducidad no puede ser anterior al día de hoy.</div>";
        return; 
    }

    // 1. Actualizar PRODUCTOS
    if (!empty($nom)) {
        $stmt1 = $conexion->prepare("UPDATE productos SET nombre=?, id_categoria=? WHERE id_producto=?");
        $stmt1->bind_param("sii", $nom, $id_cat, $id_p);
    } else {
        $stmt1 = $conexion->prepare("UPDATE productos SET id_categoria=? WHERE id_producto=?");
        $stmt1->bind_param("ii", $id_p);
    }
    $res1 = $stmt1->execute();

    // 2. Actualizar STOCK
    $stmt2 = $conexion->prepare("UPDATE stock SET cantidad_actual=?, stock_minimo=?, fecha_caducidad=? WHERE id_stock=?");
    $stmt2->bind_param("ddsi", $can, $min, $fec, $id_s);
    $res2 = $stmt2->execute();

    if ($res1 && $res2) {
        // Almacenamos el mensaje de éxito en sesión para que sea renderizado de manera limpia
        $_SESSION['mensaje_exito'] = "El registro se ha modificado exitosamente.";
        
        // Redirección instantánea para refrescar la tabla
        echo "<script>window.location='stock.php';</script>";
        exit();
    } else {
        echo "<div class='alert alert-danger text-center' style='margin: 15px;'>Error al guardar: " . $conexion->error . "</div>";
    }
}
?>