<?php
// 1. REGULACIÓN DE SESIONES (Garantiza que no choque si ya inició en la raíz)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_path', '/'); 
    session_start();
}

// 2. GUARDIÁN DE SEGURIDAD Y CONEXIÓN ÚNICA (Se removió el include duplicado)
include($_SERVER['DOCUMENT_ROOT'] . "/iniciar sesion/verificar_admin.php"); 
include "conexion_anadir.php";

// 3. PROCESAMIENTO DE LA ELIMINACIÓN LÓGICA DEL STOCK
if (!empty($_GET["id"])) {
    // Aseguramos que el ID de stock sea un entero para evitar inyecciones SQL
    $id_s = intval($_GET["id"]);

    // Verificamos que el registro de stock realmente exista antes de operar
    $consulta = $conexion->query("SELECT id_producto FROM stock WHERE id_stock = $id_s");
    $datos = $consulta->fetch_assoc();

    if ($datos) {
        // ELIMINACIÓN LÓGICA DEL STOCK: 
        // Solo borramos la disponibilidad/existencia actual en la cocina diaria.
        // NO tocamos la tabla 'productos' ni la tabla 'receta_detalles'.
        $sql_stock = $conexion->query("DELETE FROM stock WHERE id_stock = $id_s");

        if ($sql_stock) {
            // Redirigimos de vuelta a stock.php indicando éxito
            header("Location: stock.php?mensaje=eliminado");
            exit(); 
        } else {
            // En vez de un echo que rompe los headers, redirigimos pasando el error por URL
            header("Location: stock.php?mensaje=error");
            exit();
        }
    } else {
        // Si el registro no existe, redirigimos informando la situación
        header("Location: stock.php?mensaje=not_found");
        exit();
    }
} else {
    // Si entraron al archivo sin un ID válido por GET, regresamos al stock de forma segura
    header("Location: stock.php");
    exit();
}
?>