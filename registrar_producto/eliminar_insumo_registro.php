<?php
// 1. INICIAR SESIÓN GLOBAL
ini_set('session.cookie_path', '/'); 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. GUARDIÁN DE SEGURIDAD CENTRALIZADO
include($_SERVER['DOCUMENT_ROOT'] . "/iniciar sesion/verificar_admin.php"); 

// 3. CONEXIÓN A LA BASE DE DATOS
include "conexion_anadir.php";

$id_producto = intval($_GET['id'] ?? 0);

if ($id_producto > 0) {
    // Desactivamos temporalmente la verificación de claves foráneas
    // Esto permite que el producto se "desplome" por completo del sistema sin importar las dependencias
    $conexion->query("SET FOREIGN_KEY_CHECKS = 0");

    // 1. Lo eliminamos físicamente de la tabla stock (limpia el inventario relacionado)
    $stmt_stock = $conexion->prepare("DELETE FROM stock WHERE id_producto = ?");
    $stmt_stock->bind_param("i", $id_producto);
    $stmt_stock->execute();

    // 2. Lo eliminamos físicamente del catálogo maestro de productos
    $stmt_prod = $conexion->prepare("DELETE FROM productos WHERE id_producto = ?");
    $stmt_prod->bind_param("i", $id_producto);
    
    if ($stmt_prod->execute()) {
        // Volvemos a activar las restricciones antes de redireccionar
        $conexion->query("SET FOREIGN_KEY_CHECKS = 1");
        header("Location: productos.php?msg=deleted");
        exit;
    } else {
        $conexion->query("SET FOREIGN_KEY_CHECKS = 1");
        header("Location: productos.php?msg=error");
        exit;
    }
} else {
    header("Location: productos.php");
    exit;
}
?>