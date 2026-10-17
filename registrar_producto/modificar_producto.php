<?php
include "conexion_anadir.php"; 

if (empty($_GET["id"])) {
    header("Location: stock.php");
    exit();
}

$id_s = intval($_GET["id"]);

// 1. CONSULTA CORREGIDA: Se añade INNER JOIN a categorias_maestras y se extraen los campos reales
$query_prod = $conexion->query("SELECT s.*, 
                                       p.nombre, 
                                       c.nombre_categoria AS categoria, 
                                       c.unidad_predeterminada AS unidad_medida 
                                FROM stock s
                                INNER JOIN productos p ON s.id_producto = p.id_producto
                                INNER JOIN categorias_maestras c ON p.id_categoria = c.id_categoria
                                WHERE s.id_stock = $id_s");

// 2. Verificamos que la consulta haya funcionado
$datos = ($query_prod) ? $query_prod->fetch_assoc() : null;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Existencia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(to bottom, #d1e7ff, #ffffff); min-height: 100vh; padding-top: 50px; }
    </style>
</head>
<body>

    <form class="col-11 col-md-5 p-4 m-auto shadow bg-white rounded" method="POST">
        <h3 class="text-center text-primary fw-bold mb-4">MODIFICAR REGISTRO</h3>

        <?php
        // Incluimos el controlador DESPUÉS de tener los datos listos
        include "modificar_controlador.php";

        if ($datos) { ?>
            <input type="hidden" name="id_stock" value="<?= $id_s ?>">
            <input type="hidden" name="id_producto" value="<?= $datos['id_producto'] ?>">
            
            <div class="mb-3">
                <label class="form-label fw-bold">Nombre del Producto</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($datos['nombre']) ?>" readonly style="background-color: #e9ecef;">
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Categoría</label>
                    <select id="categoria" name="categoria" class="form-select" onchange="actualizarUnidad()" required>
                        <option value="">Seleccione...</option>
                        
                        <?php
                        // Nombre de la categoría actual del producto obtenido de la BD
                        $cat_guardada = trim($datos['categoria']);

                        // Mapeo preciso de los IDs reales de tus categorías fijas
                        $categorias_fijas = [
                            "Frutas/Hortalizas" => ["id" => 1, "texto" => "Frutas/Hortalizas"],
                            "Granos/Cereales" => ["id" => 6, "texto" => "Granos/Cereales"],
                            "Proteínas Animales" => ["id" => 9, "texto" => "Proteínas Animales"],
                            "Lácteos" => ["id" => 11, "texto" => "Lácteos"],
                            "Grasas/Aceites" => ["id" => 12, "texto" => "Grasas/Aceites"]
                        ];

                        foreach ($categorias_fijas as $nombre_bd => $info) {
                            $selected = (strcasecmp($cat_guardada, $nombre_bd) == 0) ? "selected" : "";
                            echo "<option value='{$info['id']}' data-nombre='{$nombre_bd}' $selected>{$info['texto']}</option>";
                        }

                        // Se consulta el resto de categorías asegurando traer id_categoria
                        $consulta = $conexion->query("SELECT id_categoria, nombre_categoria FROM categorias_maestras 
                                                     WHERE nombre_categoria NOT IN ('Granos/Cereales', 'Proteínas Animales', 'Lácteos', 'Grasas/Aceites', 'Frutas/Hortalizas')");
                        
                        while ($cat = $consulta->fetch_assoc()) {
                            $id_cat = $cat['id_categoria'];
                            $val_cat = $cat['nombre_categoria'];
                            $selected = (strcasecmp($cat_guardada, $val_cat) == 0) ? "selected" : "";
                            echo "<option value='{$id_cat}' data-nombre='{$val_cat}' $selected>{$val_cat}</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Unidad de Medida</label>
                    <input type="text" name="unidad" id="unidad_medida" class="form-control" 
                           value="<?= htmlspecialchars($datos['unidad_medida']) ?>" readonly>
                </div>
            </div>

           <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label text-success fw-bold">Cantidad</label>
                    <input type="number" step="0.01" min="0" class="form-control" name="cantidad" value="<?= $datos['cantidad_actual'] ?>" required oninput="validarPositivo(this)">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Mínimo Requerido</label>
                    <input type="number" step="0.01" min="0" class="form-control" name="minimo" value="<?= $datos['stock_minimo'] ?>" required oninput="validarPositivo(this)">
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold">Fecha de Caducidad</label>
                <input type="date" 
                       class="form-control" 
                       name="fecha_caducidad" 
                       value="<?= $datos['fecha_caducidad'] ?>" 
                       min="<?= date('Y-m-d') ?>">
            </div>

            <div class="text-center">
                <a href="stock.php" class="btn btn-secondary px-4">Cancelar</a>
                <button type="submit" class="btn btn-primary px-5" name="btnmodificar" value="ok">Guardar Todo</button>
            </div>

        <?php } else {
            echo "<div class='alert alert-danger'>No se encontró información para el registro #$id_s</div>";
        } ?>
    </form>

    <script>
    function actualizarUnidad() {
        const selectCat = document.getElementById('categoria');
        const inputUnidad = document.getElementById('unidad_medida');
        
        // Obtenemos el elemento option activo
        const optionSeleccionada = selectCat.options[selectCat.selectedIndex];
        // Extraemos el texto identificador de la categoría guardado en 'data-nombre'
        const catSeleccionada = optionSeleccionada ? optionSeleccionada.getAttribute('data-nombre') : '';

        const mapaUnidades = {
            "Granos/Cereales": "KG",
            "Lácteos": "LT",
            "Grasas/Aceites": "LT",
            "Proteínas Animales": "KG",
            "Frutas/Hortalizas": "GR"
        };

        if (mapaUnidades[catSeleccionada]) {
            inputUnidad.value = mapaUnidades[catSeleccionada];
            inputUnidad.readOnly = true;
        } else {
            inputUnidad.readOnly = false;
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const select = document.getElementById('categoria');
        if(select.value !== "") {
            actualizarUnidad();
        }
    });

    function validarPositivo(input) {
        if (input.value < 0 || input.value === "") {
            input.value = 0;
        }
    }
</script>
</body>
</html>