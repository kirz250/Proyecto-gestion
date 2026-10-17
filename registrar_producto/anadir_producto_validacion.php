<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
include "conexion_anadir.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_POST['btnregistrar'])) {
    // Si el usuario seleccionó "otra", tomamos el valor del input manual
    $categoria_input = $_POST['categoria'];
    $categoria_otra  = isset($_POST['otra_categoria']) ? trim($_POST['otra_categoria']) : '';
    $categoria_nombre = ($categoria_input === 'otra') ? $categoria_otra : $categoria_input;
    
    $nombre_nuevo = trim($_POST['nombre']); 
    $unidad       = strtoupper($_POST['unidad']); // Forzamos mayúsculas (Ej: KG)
    $minimo       = $_POST['minimo'];
    
    if (!empty($nombre_nuevo)) {
        
        // =================================================================
        // 1. VALIDACIÓN DE DUPLICADOS EXACTOS Y SIMILITUD (Evita el Fatal Error)
        // =================================================================
        
        // Convertimos a minúsculas y limpiamos espacios múltiples para comparar de manera segura
        $nombre_limpio = preg_replace('/\s+/', ' ', mb_strtolower($nombre_nuevo, 'UTF-8'));

        // Traemos todos los productos existentes para comparar
        $sql_buscar = $conexion->query("SELECT nombre FROM productos");
        if ($sql_buscar) {
            while ($reg = $sql_buscar->fetch_assoc()) {
                $nombre_bd_limpio = preg_replace('/\s+/', ' ', mb_strtolower($reg['nombre'], 'UTF-8'));
                
                // 1.1 Duplicado exacto
                if ($nombre_limpio === $nombre_bd_limpio) {
                    header("Location: productos.php?error=duplicado");
                    exit();
                }
                
                // 1.2 Validación de similitud (Singulares / Plurales / Variantes leves)
                // soundex o levenshtein ayudan a capturar palabras sumamente similares
                if (soundex($nombre_limpio) === soundex($nombre_bd_limpio) || levenshtein($nombre_limpio, $nombre_bd_limpio) <= 2) {
                    header("Location: productos.php?error=similar&existente=" . urlencode($reg['nombre']));
                    exit();
                }
            }
        }

        // =================================================================
        // 2. OBTENER O CREAR EL ID DE LA CATEGORÍA
        // =================================================================
        $id_categoria = null;

        // Intentar buscar si la categoría ya existe en categorias_maestras
        $stmt_buscar_cat = $conexion->prepare("SELECT id_categoria FROM categorias_maestras WHERE nombre_categoria = ?");
        $stmt_buscar_cat->bind_param("s", $categoria_nombre);
        $stmt_buscar_cat->execute();
        $resultado_cat = $stmt_buscar_cat->get_result();

        if ($fila_cat = $resultado_cat->fetch_assoc()) {
            $id_categoria = $fila_cat['id_categoria'];
        } else {
            // Si es nueva ("otra"), se inserta en la tabla maestra
            $stmt_ins_cat = $conexion->prepare("INSERT INTO categorias_maestras (nombre_categoria, unidad_predeterminada) VALUES (?, ?)");
            $stmt_ins_cat->bind_param("ss", $categoria_nombre, $unidad);
            $stmt_ins_cat->execute();
            $id_categoria = $conexion->insert_id;
        }

        // =================================================================
        // 3. INSERCIÓN DEL PRODUCTO
        // =================================================================
        $stmt1 = $conexion->prepare("INSERT INTO productos (nombre, id_categoria) VALUES (?, ?)");
        $stmt1->bind_param("si", $nombre_nuevo, $id_categoria);
        
        if ($stmt1->execute()) {
            $id_nuevo = $conexion->insert_id;

            // 4. INSERCIÓN EN STOCK
            $stmt2 = $conexion->prepare("INSERT INTO stock (id_producto, stock_minimo, cantidad_actual) VALUES (?, ?, 0)");
            $stmt2->bind_param("id", $id_nuevo, $minimo);
            $stmt2->execute();
            
            $_SESSION['mensaje_exito'] = "Producto '$nombre_nuevo' registrado con éxito bajo la categoría '$categoria_nombre'.";
            header("Location: stock.php");
            exit();
        }
    }
}
?>