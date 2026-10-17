<?php
// 1. Conexión a la base de datos
$conexion = mysqli_connect("localhost", "root", "", "productos");

if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}

// --- BLOQUE DE PROCESAMIENTO AJAX ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ajax_id_receta'])) {
    header('Content-Type: application/json');
    $id_receta = mysqli_real_escape_string($conexion, $_POST['ajax_id_receta']);

    $sql_plato = "SELECT nombre_plato FROM recetas_comida WHERE id_receta = '$id_receta' LIMIT 1";
    $res_plato = mysqli_query($conexion, $sql_plato);
    $data_plato = mysqli_fetch_assoc($res_plato);

    if (!$data_plato) {
        echo json_encode(["status" => "error", "message" => "Receta no encontrada"]);
        exit;
    }

    $nombre_plato = $data_plato['nombre_plato'];
    $sql_det = "SELECT id_producto, cantidad_necesaria FROM receta_detalles WHERE id_receta = '$id_receta'";
    $res_det = mysqli_query($conexion, $sql_det);

    mysqli_begin_transaction($conexion);
    $todo_bien = true;
    $error_msg = "";

    while ($item = mysqli_fetch_assoc($res_det)) {
        $id_p = $item['id_producto'];
        $cant = $item['cantidad_necesaria'];

        $res_stock = mysqli_query($conexion, "SELECT cantidad_actual FROM stock WHERE id_producto = '$id_p' FOR UPDATE");
        $row = mysqli_fetch_assoc($res_stock);

        if (!$row || $row['cantidad_actual'] < $cant) {
            $todo_bien = false;
            $error_msg = "Stock insuficiente para: " . $nombre_plato;
            break;
        }

        mysqli_query($conexion, "UPDATE stock SET cantidad_actual = cantidad_actual - $cant WHERE id_producto = '$id_p'");
    }

    if ($todo_bien) {
        mysqli_query($conexion, "INSERT INTO historial_cocina (nombre_plato, fecha_elaboracion, hora_elaboracion) VALUES ('$nombre_plato', CURDATE(), CURTIME())");
        mysqli_commit($conexion);
        echo json_encode(["status" => "success", "message" => "¡$nombre_plato elaborado con éxito!"]);
    } else {
        mysqli_rollback($conexion);
        echo json_encode(["status" => "error", "message" => $error_msg]);
    }
    exit; 
}

$sql_lista = "SELECT id_receta, nombre_plato FROM recetas_comida ORDER BY nombre_plato ASC";
$resultado = mysqli_query($conexion, $sql_lista);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <title>Despacho Rápido - PAE</title>
    <link href="../css/styles.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <style>
        .list-group-item { border-left: 5px solid #0d6efd; transition: 0.3s; }
        .fade-out { opacity: 0; transition: opacity 0.5s ease; pointer-events: none; }
    </style>
</head>
<body class="bg-light">

    <div id="interfaz-app">
        <nav class="navbar navbar-dark bg-dark mb-4">
            <span class="navbar-brand mb-0 h1 ps-3">Inventario PAE - Producción</span>
        </nav>

        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card shadow-lg">
                        <div class="card-header bg-primary text-white text-center">
                            <h5 class="mb-0"><i class="fas fa-utensils me-2"></i> Ordenar Elaboración</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <?php while ($row = mysqli_fetch_assoc($resultado)): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center py-3 px-4">
                                    <div>
                                        <small class="text-primary fw-bold">ID #<?php echo $row['id_receta']; ?></small>
                                        <h6 class="mb-0"><?php echo htmlspecialchars($row['nombre_plato']); ?></h6>
                                    </div>
                                    <button type="button" 
                                            onclick="ejecutarCocina(<?php echo $row['id_receta']; ?>)" 
                                            class="btn btn-success fw-bold px-4 shadow-sm">
                                        <i class="fas fa-fire-burner me-1"></i> EJECUTAR
                                    </button>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function ejecutarCocina(idReceta) {
        Swal.fire({
            title: '¿Confirmar ejecución?',
            text: "Se descontarán los insumos del inventario.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, elaborar ahora',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                let formData = new FormData();
                formData.append('ajax_id_receta', idReceta);

                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
               .then(data => {
    if (data.status === 'success') {
        // Efecto visual opcional
        document.getElementById('interfaz-app').classList.add('fade-out');

        Swal.fire({
            title: '¡Plato Elaborado!',
            text: data.message,
            icon: 'success',
            confirmButtonText: 'Ver Historial',
            allowOutsideClick: false
        }).then((result) => {
            // Usamos ./ para asegurar que busque en la misma carpeta actual
            // Si esto falla, el navegador te mostrará el error exacto en la consola (F12)
            window.location.assign("./historial.php");
        });
    } else {
        Swal.fire('Error', data.message, 'error');
    }
})
                .catch(error => {
                    Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
                });
            }
        });
    }
    </script>
</body>
</html>