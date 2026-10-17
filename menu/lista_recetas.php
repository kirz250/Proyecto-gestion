<?php 
// 1. INICIAR SESIÓN GLOBAL (Misma configuración exacta que en el login)
ini_set('session.cookie_path', '/'); 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. GUARDIÁN DE SEGURIDAD CENTRALIZADO (Ruta absoluta universal)
include($_SERVER['DOCUMENT_ROOT'] . "/iniciar sesion/verificar_admin.php"); 
?>

<?php if (isset($_GET['exito']) && $_GET['exito'] == 1): ?>
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
        <div class="d-flex align-items-center">
            <i class="fas fa-circle-check fs-4 me-2"></i>
            <div>
                <strong>¡Éxito Total!</strong> La receta y todos sus ingredientes se han registrado correctamente en el sistema.
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php
// 1. Conexión a la base de datos
include '../iniciar sesion/conexion.php'; 

// --- BLOQUE DE PROCESAMIENTO AJAX (POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    $Conexion->select_db("productos");

    // --- ACCIÓN A: BORRAR RECETA ---
    if (isset($_POST['borrar_id_receta'])) {
        $id = mysqli_real_escape_string($Conexion, $_POST['borrar_id_receta']);
        
        mysqli_begin_transaction($Conexion);
        
        try {
            $Conexion->query("DELETE FROM receta_detalles WHERE id_receta = '$id'");
            $Conexion->query("DELETE FROM historial_cocina WHERE id_receta = '$id'");
            
            if ($Conexion->query("DELETE FROM recetas_comida WHERE id_receta = '$id'")) {
                mysqli_commit($Conexion);
                echo json_encode(["status" => "success"]);
            } else {
                throw new Exception($Conexion->error);
            }
        } catch (Exception $e) {
            mysqli_rollback($Conexion);
            echo json_encode([
                "status" => "error", 
                "message" => "Error de base de datos: " . $e->getMessage()
            ]);
        }
        exit;
    }

    // --- ACCIÓN B: ELABORAR RECETA CON PORCIONES Y AJUSTES ---
    if (isset($_POST['ajax_id_receta']) && isset($_POST['ajustes_manuales'])) {
        $id_receta = mysqli_real_escape_string($Conexion, $_POST['ajax_id_receta']);
        $porciones = (int)$_POST['porciones'];
        $ajustes = json_decode($_POST['ajustes_manuales'], true);

        // Validación estricta en Backend de que las porciones sean MÍNIMO 500
        if ($porciones < 500) {
            echo json_encode(["status" => "error", "message" => "El mínimo de porciones a elaborar debe ser 500 estudiantes."]);
            exit;
        }

        if (!$ajustes) {
            echo json_encode(["status" => "error", "message" => "Datos de ajuste no recibidos"]);
            exit;
        }

        mysqli_begin_transaction($Conexion);
        $todo_bien = true;
        $error_msg = "";

        foreach ($ajustes as $ajuste) {
            $nombre_inv = mysqli_real_escape_string($Conexion, $ajuste['nombre']);
            $cantidad_a_descontar = floatval($ajuste['cantidad']);

            $sql_stock = "SELECT s.cantidad_actual, p.id_producto 
                          FROM stock s 
                          JOIN productos p ON s.id_producto = p.id_producto 
                          WHERE p.nombre = '$nombre_inv' FOR UPDATE";
            $res_stock = $Conexion->query($sql_stock);
            $row_stock = $res_stock->fetch_assoc();

            if (!$row_stock || $row_stock['cantidad_actual'] < $cantidad_a_descontar) {
                $todo_bien = false;
                $error_msg = "Stock insuficiente para: " . $nombre_inv . " (Disponible: " . ($row_stock ? $row_stock['cantidad_actual'] : 0) . ", Requerido: " . $cantidad_a_descontar . ")";
                break;
            }

            $id_p = $row_stock['id_producto'];
            $sql_update = "UPDATE stock SET cantidad_actual = cantidad_actual - $cantidad_a_descontar WHERE id_producto = '$id_p'";
            if (!$Conexion->query($sql_update)) {
                $todo_bien = false;
                $error_msg = "Error al actualizar stock de " . $nombre_inv;
                break;
            }
        }

        if ($todo_bien) {
            $sql_historial_cocina = "INSERT INTO historial_cocina (id_receta, fecha_elaboracion, hora_elaboracion, cantidad_personas) 
                                     VALUES ('$id_receta', CURDATE(), CURTIME(), $porciones)";
            $Conexion->query($sql_historial_cocina);

            $usuario_activo = mysqli_real_escape_string($Conexion, $_SESSION['usuario'] ?? 'Usuario');
            $tipo_accion_receta = "EGRESO_RECETA"; 

            foreach ($ajustes as $ajuste) {
                $nombre_inv = mysqli_real_escape_string($Conexion, $ajuste['nombre']);
                $cantidad_a_descontar = floatval($ajuste['cantidad']);

                $res_prod = $Conexion->query("SELECT id_producto FROM productos WHERE nombre = '$nombre_inv' LIMIT 1");
                $row_prod = $res_prod->fetch_assoc();
                
                if ($row_prod) {
                    $id_p = $row_prod['id_producto'];
                    $cantidad_negativa = -1 * abs($cantidad_a_descontar);
                    
                    $sql_movimiento = "INSERT INTO historial_movimientos (id_producto, tipo_movimiento, cantidad, usuario, fecha) 
                                       VALUES ('$id_p', '$tipo_accion_receta', $cantidad_negativa, '$usuario_activo', NOW())";
                    $Conexion->query($sql_movimiento);
                }
            }

            mysqli_commit($Conexion);
            echo json_encode(["status" => "success", "message" => "¡Preparación registrada con éxito! Redirigiendo al historial..."]);

        } else {
            mysqli_rollback($Conexion);
            echo json_encode(["status" => "error", "message" => $error_msg]);
        }
        exit;
    }

    // --- ACCIÓN C: ACTUALIZAR RECETA DESDE EL MODAL ---
    if (isset($_POST['editar_id_receta']) && isset($_POST['nuevo_nombre_plato'])) {
        $id_receta = mysqli_real_escape_string($Conexion, $_POST['editar_id_receta']);
        $nuevo_nombre = mysqli_real_escape_string($Conexion, $_POST['nuevo_nombre_plato']);
        $ingredientes_editados = json_decode($_POST['ingredientes_editados'], true);

        mysqli_begin_transaction($Conexion);
        
        $sql_up_receta = "UPDATE recetas_comida SET nombre_plato = '$nuevo_nombre' WHERE id_receta = '$id_receta'";
        if($Conexion->query($sql_up_receta)) {
            
            $Conexion->query("DELETE FROM receta_detalles WHERE id_receta = '$id_receta'");
            
            foreach ($ingredientes_editados as $ing) {
                $nombre_ing = mysqli_real_escape_string($Conexion, $ing['nombre']);
                $nueva_cant = floatval($ing['cantidadBase']);
                
                $res_p_id = $Conexion->query("SELECT id_producto FROM productos WHERE nombre = '$nombre_ing' LIMIT 1");
                $row_p_id = $res_p_id->fetch_assoc();
                
                if ($row_p_id) {
                    $id_prod = $row_p_id['id_producto'];
                    $sql_ins_det = "INSERT INTO receta_detalles (id_receta, id_producto, cantidad_necesaria) VALUES ('$id_receta', '$id_prod', $nueva_cant)";
                    $Conexion->query($sql_ins_det);
                }
            }
            
            mysqli_commit($Conexion);
            echo json_encode(["status" => "success", "message" => "Receta e insumos reestructurados con éxito."]);
        } else {
            mysqli_rollback($Conexion);
            echo json_encode(["status" => "error", "message" => "No se pudieron guardar las modificaciones."]);
        }
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Gestión de Recetas - Nueva Toledo</title>
    <link href="/startbootstrap-sb-admin-gh-pages/css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    <style>
    .btn-ayuda-destacado {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .btn-ayuda-destacado:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(255, 193, 7, 0.4) !important;
    }
    </style>
</head>

<body class="sb-nav-fixed">
    <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
        <a class="navbar-brand ps-3" href="/inicio.php">Nueva Toledo</a>
        <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle"><i class="fas fa-bars"></i></button>
        <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0"></form>
        <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
            <li class="nav-item dropdown">
                 <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="text-white me-2 small"><?php echo htmlspecialchars($_SESSION['usuario']); ?></span>
                    <i class="fas fa-user fa-fw"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                    <li><a class="dropdown-item" href="#!">Ajustes</a></li>
                    <li><a class="dropdown-item" href="#!">Activity Log</a></li>
                    <li><hr class="dropdown-divider" /></li>
                    <li><a class="dropdown-item" href="/iniciar sesion/logout.php">Cerrar sesión</a></li>
                </ul>
            </li>
        </ul>
    </nav>

    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
                <div class="sb-sidenav-menu">
                    <div class="nav">
                        <div class="sb-sidenav-menu-heading text-white-50">INICIO</div>
                        <a class="nav-link" href="/inicio.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                            Inicio
                        </a>
                        <div class="sb-sidenav-menu-heading">Operación Diaria</div>
                        <a class="nav-link active" href="#" data-bs-toggle="collapse" data-bs-target="#collapseLayouts" aria-expanded="true" aria-controls="collapseLayouts">
                            <div class="sb-nav-link-icon"><i class="fas fa-utensils"></i></div>
                            Recetario
                            <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                        </a>
                        <div class="collapse show" id="collapseLayouts" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                            <nav class="sb-sidenav-menu-nested nav">
                                <a class="nav-link" href="crear_receta.php">Crear nueva receta</a>
                                <a class="nav-link active" href="lista_recetas.php">Planificar menú</a>
                            </nav>
                        </div>
                        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapsePages" aria-expanded="false" aria-controls="collapsePages">
                            <div class="sb-nav-link-icon"><i class="fas fa-book-open"></i></div>
                            Reportes
                            <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                        </a>
                        <div class="collapse" id="collapsePages" aria-labelledby="headingTwo" data-bs-parent="#sidenavAccordion">
                              <nav class="sb-sidenav-menu-nested nav">
                                <a class="nav-link" href="/registrar_producto/reporte.php">Reportes</a>
                                <a class="nav-link" href="/registrar_producto/gestionar_usuarios.php">Gestionar usuarios</a>
                            </nav>
                        </div>
                        <div class="sb-sidenav-menu-heading text-white-50">Insumos</div>
                        <a class="nav-link" href="/registrar_producto/productos.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-box"></i></div>
                            Catálogo de Insumos
                        </a>
                        <a class="nav-link" href="/registrar_producto/stock.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-boxes-stacked"></i></div>
                            Stock
                        </a>
                    </div>
                </div>
                <div class="sb-sidenav-footer text-white-50">
                    <div class="small">Iniciado como:</div>
                     <span class="text-white fw-bold"><?php echo htmlspecialchars($_SESSION['usuario']); ?></span>
                </div>
            </nav>
        </div>

        <div id="layoutSidenav_content">
            <main id="contenedor-principal">
                <div class="container-fluid px-4">
                    <div class="d-flex align-items-center justify-content-between mt-4 mb-4">
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h1 class="h3 mb-1 text-gray-800">Administración de Recetas</h1>
                                <button type="button" class="btn btn-warning text-dark fw-bold rounded-pill shadow-sm px-3 py-1 btn-ayuda-destacado" data-bs-toggle="modal" data-bs-target="#modalGuiaAdministracion">
                                    <i class="fas fa-circle-question fa-bounce text-dark me-1"></i> ¿Cómo se usa este campo?
                                </button>
                            </div>
                            <p class="text-muted small mb-0">Listado general de platos e insumos de la Unidad Educativa.</p>
                        </div>
                        <a href="crear_receta.php" class="btn btn-primary shadow-sm">
                            <i class="fas fa-plus me-1"></i> Añadir Nueva Receta
                        </a>
                    </div>

                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-dark text-white py-3">
                            <i class="fas fa-table me-1 text-warning"></i> Base de Datos de Recetas Registradas
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="tablaRecetas" class="table table-striped table-hover align-middle w-100">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 5%">ID</th>
                                            <th style="width: 25%">Nombre del Plato</th>
                                            <th style="width: 40%">Ingredientes Conlleva</th>
                                            <th style="width: 12%" class="text-center">Habilitada</th>
                                            <th style="width: 18%" class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if (isset($Conexion) && $Conexion != null) {
                                            $Conexion->select_db("productos");
                                            $sql = "SELECT * FROM recetas_comida";
                                            $resultado = $Conexion->query($sql);

                                            while ($receta = $resultado->fetch_assoc()):
                                                $id_receta = (int)$receta['id_receta'];
                                                $nombre_plato = htmlspecialchars($receta['nombre_plato']);
                                        ?>
                                        <tr>
                                            <td class="fw-bold"><?php echo $id_receta; ?></td>
                                            <td>
                                                <span class="fw-bold text-dark id-nombre-plato">
                                                    <i class="fas fa-bowl-rice text-muted me-2"></i><?php echo $nombre_plato; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-wrap gap-1 max-badges-container">
                                                    <?php
                                                    $sql_det_v = "SELECT p.nombre, c.unidad_predeterminada AS unidad_medida, rd.cantidad_necesaria, IFNULL(s.cantidad_actual, 0) as stock_disponible 
                                                                  FROM receta_detalles rd 
                                                                  JOIN productos p ON rd.id_producto = p.id_producto 
                                                                  LEFT JOIN categorias_maestras c ON p.id_categoria = c.id_categoria
                                                                  LEFT JOIN stock s ON p.id_producto = s.id_producto
                                                                  WHERE rd.id_receta = $id_receta";
                                                                  
                                                    $res_det_v = $Conexion->query($sql_det_v);
                                                    while ($det = $res_det_v->fetch_assoc()):
                                                    ?>
                                                        <span class='badge bg-light text-dark border p-2 rounded-pill small badge-ingrediente-item' 
                                                              data-stock="<?php echo floatval($det['stock_disponible']); ?>"
                                                              data-raw-name="<?php echo htmlspecialchars($det['nombre']); ?>"
                                                              data-raw-cant="<?php echo floatval($det['cantidad_necesaria']); ?>"
                                                              data-raw-unit="<?php echo htmlspecialchars($det['unidad_medida']); ?>">
                                                            <?php echo htmlspecialchars($det['nombre']); ?>: 
                                                            <strong class='text-primary'>
                                                                <?php echo floatval($det['cantidad_necesaria']); ?> 
                                                                <small class="text-muted fw-normal"><?php echo htmlspecialchars($det['unidad_medida']); ?></small>
                                                            </strong>
                                                        </span>
                                                    <?php endwhile; ?>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="form-check form-switch d-inline-block">
                                                    <input class="form-check-input style-switch-radius" type="checkbox" role="switch" checked 
                                                           style="cursor: pointer; width: 2.5em; height: 1.25em;"
                                                           onclick="toggleEstadoReceta(<?php echo $id_receta; ?>, '<?php echo addslashes($nombre_plato); ?>', this)">
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group shadow-sm" role="group">
                                                    <button type="button" class="btn btn-sm btn-success btn-elaborar" title="Elaborar Plato (Descontar)"
                                                            onclick="ejecutarCocina(<?php echo $id_receta; ?>, '<?php echo addslashes($nombre_plato); ?>', this)">
                                                        <i class="fas fa-fire-burner"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-warning" title="Modificar Receta en Modal"
                                                            onclick="abrirModalEditar(<?php echo $id_receta; ?>, this)">
                                                        <i class="fas fa-edit text-dark"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-danger" title="Eliminar definitivamente"
                                                            onclick="eliminarReceta(<?php echo $id_receta; ?>, '<?php echo addslashes($nombre_plato); ?>')">
                                                        <i class="fas fa-trash-can"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile; } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- MODAL GUÍA DE USO -->
    <div class="modal fade" id="modalGuiaAdministracion" tabindex="-1" aria-labelledby="modalGuiaAdministracionLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="modalGuiaAdministracionLabel">
                        <i class="fas fa-book-open me-2"></i>Guía: Administración de Recetas
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">
                        En este módulo puedes gestionar las preparaciones del menú y procesar la salida de insumos del almacén:
                    </p>

                    <ol class="list-group list-group-numbered list-group-flush mb-0">
                        <li class="list-group-item d-flex align-items-start border-0 px-0 py-2">
                            <div class="ms-2 me-auto">
                                <div class="fw-bold text-dark">Elaborar Plato (Botón Verde <i class="fas fa-fire-burner text-success"></i>)</div>
                                <span class="text-secondary small">Calcula el consumo de insumos partiendo de la matrícula promedio (mínimo 500 alumnos) sin límite máximo superior, y los descuenta del stock actual.</span>
                            </div>
                        </li>
                        <li class="list-group-item d-flex align-items-start border-0 px-0 py-2">
                            <div class="ms-2 me-auto">
                                <div class="fw-bold text-dark">Editar Receta (Botón Amarillo <i class="fas fa-edit text-warning"></i>)</div>
                                <span class="text-secondary small">Permite modificar el nombre del plato, agregar insumos o consultar la ración individual calculada por estudiante en gramos/mililitros.</span>
                            </div>
                        </li>
                        <li class="list-group-item d-flex align-items-start border-0 px-0 py-2">
                            <div class="ms-2 me-auto">
                                <div class="fw-bold text-dark">Habilitar / Inactivar</div>
                                <span class="text-secondary small">Utiliza el interruptor central para pausar temporalmente preparaciones que no estén disponibles en el menú.</span>
                            </div>
                        </li>
                        <li class="list-group-item d-flex align-items-start border-0 px-0 py-2">
                            <div class="ms-2 me-auto">
                                <div class="fw-bold text-dark">Eliminar Receta (Botón Rojo <i class="fas fa-trash-can text-danger"></i>)</div>
                                <span class="text-secondary small">Elimina definitivamente el platillo y sus vinculaciones de insumos de la base de datos.</span>
                            </div>
                        </li>
                    </ol>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm px-4 rounded-pill" data-bs-dismiss="modal">Entendido</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL EDITAR RECETA CON EXPLICACIÓN TÉCNICA DE RACIÓN -->
    <div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content shadow border-0">
                <div class="modal-header bg-warning text-dark shadow-sm">
                    <h5 class="modal-title fw-bold"><i class="fas fa-edit me-2"></i>Modificar Datos de la Receta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Nombre de la Receta / Plato Base:</label>
                        <input type="text" id="editNombrePlato" class="form-control form-control-lg border-warning shadow-sm fw-bold text-secondary">
                    </div>

                    <div class="card bg-light border-0 p-3 mb-3 shadow-sm">
                        <label class="form-label fw-bold text-dark"><i class="fas fa-cart-plus me-1 text-success"></i> Añadir Insumo Adicional a esta receta:</label>
                        <div class="row g-2">
                            <div class="col-md-8">
                                <select id="selectNuevoInsumoEditor" class="form-select border-success">
                                    <option value="" disabled selected>-- Seleccione un Producto / Insumo --</option>
                                    <?php 
                                    $res_todos_p = $Conexion->query("SELECT p.nombre, c.unidad_predeterminada, IFNULL(s.cantidad_actual, 0) as stock 
                                                                     FROM productos p 
                                                                     LEFT JOIN categorias_maestras c ON p.id_categoria = c.id_categoria
                                                                     LEFT JOIN stock s ON p.id_producto = s.id_producto ORDER BY p.nombre ASC");
                                    while($prod_b = $res_todos_p->fetch_assoc()):
                                    ?>
                                        <option value="<?php echo htmlspecialchars($prod_b['nombre']); ?>" 
                                                data-unit="<?php echo htmlspecialchars($prod_b['unidad_predeterminada']); ?>" 
                                                data-stock="<?php echo floatval($prod_b['stock']); ?>">
                                            <?php echo htmlspecialchars($prod_b['nombre']); ?> (Stock Disp: <?php echo floatval($prod_b['stock'])." ".$prod_b['unidad_predeterminada']; ?>)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="button" class="btn btn-success w-100" onclick="agregarInsumoEnEditor()"><i class="fas fa-plus"></i> Añadir</button>
                            </div>
                        </div>
                    </div>

                    <!-- EXPLICACIÓN TÉCNICA REPORTE DE MEDICIÓN POR RACIÓN -->
                    <div class="alert alert-warning py-2 px-3 mb-3 small border-0 shadow-sm text-dark">
                        <i class="fas fa-scale-balanced me-1 fw-bold"></i> <b>Criterio de medición por ración individual:</b>
                        <span class="d-block text-muted mt-1">
                            • <b>Cárnicos (Pollo):</b> ~200g por ración cruda con hueso.<br>
                            • <b>Granos y Cereales (Arroz/Pasta/Caraotas):</b> ~100g por estudiante.<br>
                            • <b>Harina / Polvo / Líquidos (Arepa/Chicha/Leche/Aceite):</b> ~50g / 12.5ml por estudiante.
                        </span>
                    </div>

                    <h6 class="text-muted fw-bold mb-3"><i class="fas fa-list me-1"></i> Lista de Insumos Vinculados:</h6>
                    <div class="table-responsive rounded-3">
                        <table class="table table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Insumo</th>
                                    <th class="text-center" style="width: 140px;">Stock Almacén</th>
                                    <th style="width: 290px;">
                                        Ración por Estudiante (Calculada)
                                        <i class="fas fa-circle-info text-primary ms-1" title="Muestra la porción individual exacta en gramos (g) o mililitros (ml) dividiendo la base entera entre 80 estudiantes."></i>
                                    </th>
                                    <th class="text-center" style="width: 50px;">Quitar</th>
                                </tr>
                            </thead>
                            <tbody id="listaIngredientesEdit"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button id="btnConfirmarEdicion" class="btn btn-warning px-5 fw-bold shadow-sm text-dark" onclick="guardarCambiosReceta()">Guardar Cambios</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEliminar" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white"><h5><i class="fas fa-triangle-exclamation me-2"></i>Eliminar Receta</h5></div>
                <div class="modal-body text-center py-4">¿Estás seguro de borrar a <strong id="nombreRecetaBorrar"></strong>?</div>
                <div class="modal-footer bg-light border-0">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button id="btnConfirmarBorrado" class="btn btn-danger" onclick="confirmarBorrado()">Sí, eliminar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

  <script>
    let idTemporal = null;
    let modalE, modalM;

    document.addEventListener("DOMContentLoaded", function() {
        $('#tablaRecetas').DataTable({
            language: { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
            pageLength: 10,
            responsive: true,
            columnDefs: [{ orderable: false, targets: [2, 3, 4] }]
        });

        modalE = new bootstrap.Modal(document.getElementById('modalEliminar'));
        modalM = new bootstrap.Modal(document.getElementById('modalEditar'));
    });

    function toggleEstadoReceta(id, nombre, checkbox) {
        let estado = checkbox.checked ? "Habilitada" : "Inactivada/Pausada";
        const fila = checkbox.closest('tr');
        const btnElaborar = fila.querySelector('.btn-elaborar');

        Swal.fire({
            title: '¿Modificar disponibilidad?',
            text: `La receta "${nombre}" pasará al estado: ${estado}`,
            icon: checkbox.checked ? 'info' : 'warning',
            showCancelButton: true,
            confirmButtonColor: '#4e73df',
            cancelButtonColor: '#858796',
            confirmButtonText: 'Sí, cambiar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (!result.isConfirmed) {
                checkbox.checked = !checkbox.checked;
            } else {
                if (btnElaborar) { btnElaborar.disabled = !checkbox.checked; }
                Swal.fire('¡Actualizado!', `La receta se ha modificado correctamente.`, 'success');
            }
        });
    }

    function eliminarReceta(id, nombre) {
        Swal.fire({
            title: '¿Estás completamente seguro?',
            text: `Vas a eliminar definitivamente la receta "${nombre}". Se borrará también su historial de preparación asociado. ¡Esta acción no se puede deshacer!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-trash-can me-1"></i> Sí, eliminar receta',
            cancelButtonText: 'Cancelar',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return $.ajax({
                    url: window.location.pathname,
                    type: 'POST',
                    dataType: 'json',
                    data: { borrar_id_receta: id }
                }).then(response => {
                    if (response.status !== 'success') {
                        throw new Error(response.message || 'No se pudo procesar la eliminación.');
                    }
                    return response;
                }).catch(error => {
                    Swal.showValidationMessage(`Error al eliminar: ${error.message}`);
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: '¡Eliminado!',
                    text: 'La receta ha sido removida del sistema exitosamente.',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            }
        });
    }

    function confirmarBorrado() {
        if(idTemporal) {
            enviarPeticion({ borrar_id_receta: idTemporal });
        }
    }

    function enviarPeticion(datos) {
        $.ajax({
            url: window.location.pathname,
            type: 'POST',
            data: datos,
            success: function(response) {
                if(response.status === 'success') {
                    location.reload();
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            }
        });
    }
    
    function abrirModalEditar(id, boton) {
        idTemporal = id;
        const fila = boton.closest('tr');
        const nombreActual = fila.querySelector('.id-nombre-plato').innerText.trim();
        
        document.getElementById('editNombrePlato').value = nombreActual;
        document.getElementById('selectNuevoInsumoEditor').selectedIndex = 0;
        const contenedorInsumos = document.getElementById('listaIngredientesEdit');
        contenedorInsumos.innerHTML = '';

        const badges = fila.querySelectorAll('.badge-ingrediente-item');
        badges.forEach(badge => {
            const nombreIng = badge.getAttribute('data-raw-name');
            const cantTotalBase80 = parseFloat(badge.getAttribute('data-raw-cant'));
            const unit = badge.getAttribute('data-raw-unit');
            const stockActual = badge.getAttribute('data-stock');

            insertarFilaEnEditor(nombreIng, stockActual, cantTotalBase80, unit);
        });

        if(modalM) modalM.show();
    }

    // Muestra la ración por alumno convertida a gramos (g) o mililitros (ml)
    function insertarFilaEnEditor(nombre, stock, cantTotalBase, unidad) {
        const contenedorInsumos = document.getElementById('listaIngredientesEdit');
        
        if (contenedorInsumos.querySelector(`input[data-nombre="${nombre}"]`)) {
            Swal.fire('Atención', 'Este insumo ya se encuentra listado en la receta.', 'warning');
            return;
        }

        const cantOriginal = parseFloat(cantTotalBase) || 0;
        let racionKgL = cantOriginal > 0 ? (cantOriginal / 80) : 0;
        
        let valorMostrar = racionKgL;
        let etiquetaUnidad = unidad;

        const unidadUpper = unidad.toUpperCase().trim();

        // Conversión técnica de milimétrica visual (Kg -> g | Lt -> ml)
        if (unidadUpper === 'KG' || unidadUpper === 'KILOGRAMO' || unidadUpper === 'KILOGRAMOS') {
            valorMostrar = racionKgL * 1000; // Ej: 0.05 Kg = 50 g
            etiquetaUnidad = 'g / est.';
        } else if (unidadUpper === 'LT' || unidadUpper === 'L' || unidadUpper === 'LITRO' || unidadUpper === 'LITROS') {
            valorMostrar = racionKgL * 1000; // Ej: 0.0125 L = 12.5 ml
            etiquetaUnidad = 'ml / est.';
        } else {
            etiquetaUnidad = unidad + ' / est.';
        }

        // Formato limpio: elimina ceros decimales innecesarios (ej: 200 en lugar de 200.000)
        const valorFormateado = (valorMostrar % 1 === 0) ? valorMostrar.toFixed(0) : valorMostrar.toFixed(1);

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="fw-bold text-secondary">${nombre}</td>
            <td class="text-center text-dark font-monospace">${stock} <small class="text-muted">${unidad}</small></td>
            <td>
                <div class="input-group">
                    <input type="text" 
                           class="form-control text-end input-edit-cant bg-light fw-bold text-primary" 
                           data-nombre="${nombre}" 
                           data-cant-total="${cantOriginal}" 
                           value="${valorFormateado}" 
                           readonly 
                           title="Campo no editable. Muestra la ración exacta por estudiante.">
                    <span class="input-group-text bg-light text-muted small fw-bold">${etiquetaUnidad}</span>
                </div>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger rounded-circle" onclick="this.closest('tr').remove()">
                    <i class="fas fa-times"></i>
                </button>
            </td>
        `;
        contenedorInsumos.appendChild(tr);
    }

    function agregarInsumoEnEditor() {
        const select = document.getElementById('selectNuevoInsumoEditor');
        const opcion = select.options[select.selectedIndex];
        if(!opcion.value) {
            Swal.fire('Error', 'Selecciona un insumo válido.', 'error');
            return;
        }
        const nombre = opcion.value;
        const unidad = opcion.getAttribute('data-unit');
        const stock = opcion.getAttribute('data-stock');
        
        // Si se agrega un insumo adicional manualmente, asignamos 4 como base guía
        insertarFilaEnEditor(nombre, stock, 4, unidad);
    }

    function guardarCambiosReceta() {
        const nuevoNombre = document.getElementById('editNombrePlato').value.trim();
        if(!nuevoNombre) {
            Swal.fire('Error', 'El nombre de la receta es obligatorio.', 'error');
            return;
        }

        const filas = document.querySelectorAll('#listaIngredientesEdit tr');
        const ingredientes = [];
        let validar = true;

        filas.forEach(fila => {
            const input = fila.querySelector('.input-edit-cant');
            const nombre = input.getAttribute('data-nombre');
            const cantTotal = parseFloat(input.getAttribute('data-cant-total'));

            if(isNaN(cantTotal) || cantTotal <= 0) {
                validar = false;
            } else {
                ingredientes.push({ nombre: nombre, cantidadBase: cantTotal });
            }
        });

        if(!validar || ingredientes.length === 0) {
            Swal.fire('Atención', 'Asegúrate de tener al menos un ingrediente válido en la receta.', 'warning');
            return;
        }

        $.ajax({
            url: window.location.pathname,
            type: 'POST',
            data: {
                editar_id_receta: idTemporal,
                nuevo_nombre_plato: nuevoNombre,
                ingredientes_editados: JSON.stringify(ingredientes)
            },
            success: function(response) {
                if(response.status === 'success') {
                    if(modalM) modalM.hide();
                    Swal.fire({
                        title: '¡Guardado!',
                        text: response.message,
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            }
        });
    }

    // --- FUNCIÓN UNIFICADA CON MÍNIMO OBLIGATORIO DE 500 ESTUDIANTES Y SIN LÍMITE MÁXIMO ---
    function ejecutarCocina(id, nombrePlato, boton) {
        const fila = boton.closest('tr');
        const badges = fila.querySelectorAll('.badge-ingrediente-item');
        
        let tablaHTML = `
            <div class="table-responsive mt-3">
                <table class="table table-sm table-bordered align-middle text-start" style="font-size:0.85rem;">
                    <thead class="table-light text-center">
                        <tr>
                            <th>Insumo</th>
                            <th>Stock Act.</th>
                            <th style="width: 32%;">Consumo Real</th>
                            <th>Restante</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-insumos-body">
        `;

        // Valor base de arranque: 500 alumnos (mínimo promedio)
        const porcionesIniciales = 500;

        badges.forEach((badge, index) => {
            const nombreIng = badge.getAttribute('data-raw-name');
            const cantBase = parseFloat(badge.getAttribute('data-raw-cant'));
            const unit = badge.getAttribute('data-raw-unit');
            const stockDisp = parseFloat(badge.getAttribute('data-stock'));

            // cantBase representa la ración para 80 personas, por tanto (cantBase / 80) es por alumno
            const racionUnitaria = cantBase / 80;
            const totalDescontarTeorico = racionUnitaria * porcionesIniciales;
            const stockRestanteTeorico = stockDisp - totalDescontarTeorico;

            tablaHTML += `
                <tr id="fila-insumo-${index}" data-cant-base="${racionUnitaria}" data-stock-disp="${stockDisp}" data-unidad="${unit}" data-nombre="${nombreIng}">
                    <td class="fw-bold">
                        ${nombreIng}
                        <div class="text-muted theoretical-label" id="teorico-label-${index}" style="font-size: 0.75rem;">
                            Teórico: ${totalDescontarTeorico.toFixed(3)} ${unit}
                        </div>
                    </td>
                    <td class="text-end font-monospace text-secondary">${stockDisp.toFixed(3)} <small>${unit}</small></td>
                    <td>
                        <div class="input-group input-group-sm">
                            <input type="number" step="any" min="0" 
                                   max="${totalDescontarTeorico}" 
                                   id="input-real-${index}"
                                   class="form-control text-end input-ajuste-real font-monospace" 
                                   value="${totalDescontarTeorico.toFixed(3)}"
                                   oninput="validarCambioManual(${index})">
                            <span class="input-group-text bg-light" style="font-size: 0.7rem; padding: 0.25rem 0.5rem;">${unit}</span>
                        </div>
                    </td>
                    <td class="text-end font-monospace fw-bold cell-stock-restante" id="restante-${index}">
                        ${stockRestanteTeorico.toFixed(3)} <small class="text-muted fw-normal">${unit}</small>
                    </td>
                </tr>
            `;
        });

        tablaHTML += `</tbody></table></div>`;

        Swal.fire({
            title: `Elaborar: ${nombrePlato}`,
            html: `
                <div class="mb-3 text-start">
                    <label class="form-label fw-bold text-dark">1. Ingresa la Matrícula a Servir (Mínimo obligatorio: 500 estudiantes):</label>
                    <input type="number" id="swal-porciones" class="form-control form-control-lg text-center fw-bold text-primary" min="500" value="500" oninput="recalcularPorcionesGlobales(this.value)">
                    <div class="form-text small text-muted text-center mt-1">El valor parte de 500 como promedio base y puede incrementarse según sea necesario.</div>
                </div>
                <div class="text-start mb-2">
                    <label class="form-label fw-bold text-dark">2. Consulta de Stock y Ajuste de Insumos:</label>
                    <p class="text-muted small mb-1">Puedes modificar el "Consumo Real" si usaste menos de la ración teórica (solo se permite restar).</p>
                </div>
                ${tablaHTML}
                <div id="contenedor-alerta-stock" class="mt-2"></div>
            `,
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-fire-burner me-1"></i> Descontar y Procesar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            showLoaderOnConfirm: true,
            didOpen: () => {
                evaluarStocksGlobales();
            },
            preConfirm: () => {
                const porciones = parseInt(document.getElementById('swal-porciones').value);
                if (isNaN(porciones) || porciones < 500) {
                    Swal.showValidationMessage('El mínimo de porciones a elaborar debe ser de 500 estudiantes.');
                    return false;
                }

                let ajustesManuales = [];
                let validacionInputs = true;
                let mensajeError = "";

                const filas = document.querySelectorAll('#tabla-insumos-body tr');
                filas.forEach((fila, index) => {
                    const nombre = fila.getAttribute('data-nombre');
                    const stockDisp = parseFloat(fila.getAttribute('data-stock-disp'));
                    const cantBase = parseFloat(fila.getAttribute('data-cant-base'));
                    
                    const input = document.getElementById(`input-real-${index}`);
                    const valorIngresado = parseFloat(input.value);
                    const valorTeorico = cantBase * porciones;

                    if (isNaN(valorIngresado) || valorIngresado < 0) {
                        validacionInputs = false;
                        mensajeError = `La cantidad para ${nombre} no es válida.`;
                    } else if (valorIngresado > valorTeorico) {
                        validacionInputs = false;
                        mensajeError = `No puedes consumir más del teórico (${valorTeorico.toFixed(3)}) para el insumo: ${nombre}.`;
                    } else if (valorIngresado > stockDisp) {
                        validacionInputs = false;
                        mensajeError = `El consumo de ${nombre} supera el stock disponible. Redúcelo más.`;
                    } else {
                        ajustesManuales.push({
                            nombre: nombre,
                            cantidad: valorIngresado
                        });
                    }
                });

                if (!validacionInputs) {
                    Swal.showValidationMessage(mensajeError);
                    return false;
                }

                return $.ajax({
                    url: window.location.pathname,
                    type: 'POST',
                    data: {
                        ajax_id_receta: id,
                        porciones: porciones,
                        ajustes_manuales: JSON.stringify(ajustesManuales)
                    }
                }).then(response => {
                    if (response.status !== 'success') {
                        throw new Error(response.message || 'Error desconocido');
                    }
                    return response;
                }).catch(error => {
                    Swal.showValidationMessage(`Error en la solicitud: ${error.message}`);
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((finalResult) => {
            if (finalResult.isConfirmed && finalResult.value) {
                Swal.fire({
                    title: '¡Operación Exitosa!',
                    text: finalResult.value.message,
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = '/registrar_producto/reporte.php';
                });
            }
        });
    }

    // --- RECALCULO CON MÍNIMO 500 Y SIN LÍMITE SUPERIOR ---
    function recalcularPorcionesGlobales(porcionesStr) {
        let porciones = parseInt(porcionesStr);
        
        // Bloquea cualquier número por debajo de 500, pero permite cualquier número superior sin límite
        if (isNaN(porciones) || porciones < 500) {
            porciones = 500;
        }

        const filas = document.querySelectorAll('#tabla-insumos-body tr');
        filas.forEach((fila, index) => {
            const cantBase = parseFloat(fila.getAttribute('data-cant-base'));
            const stockDisp = parseFloat(fila.getAttribute('data-stock-disp'));
            const unidad = fila.getAttribute('data-unidad');

            const nuevoTeorico = cantBase * porciones;

            const inputReal = document.getElementById(`input-real-${index}`);
            inputReal.setAttribute('max', nuevoTeorico);
            inputReal.value = nuevoTeorico.toFixed(3);

            document.getElementById(`teorico-label-${index}`).innerText = `Teórico: ${nuevoTeorico.toFixed(3)} ${unidad}`;

            const stockRestante = stockDisp - nuevoTeorico;
            const celdaRestante = document.getElementById(`restante-${index}`);
            celdaRestante.innerHTML = `${stockRestante.toFixed(3)} <small class="text-muted fw-normal">${unidad}</small>`;

            colorearFilaYRestante(index, stockRestante);
        });

        evaluarStocksGlobales();
    }

    function validarCambioManual(index) {
        const fila = document.getElementById(`fila-insumo-${index}`);
        const cantBase = parseFloat(fila.getAttribute('data-cant-base'));
        const stockDisp = parseFloat(fila.getAttribute('data-stock-disp'));
        const unidad = fila.getAttribute('data-unidad');

        let porciones = parseInt(document.getElementById('swal-porciones').value);
        if (isNaN(porciones) || porciones < 500) {
            porciones = 500;
        }
        
        const valorTeorico = cantBase * porciones;

        const inputReal = document.getElementById(`input-real-${index}`);
        let valorIngresado = parseFloat(inputReal.value);

        if (valorIngresado > valorTeorico) {
            inputReal.value = valorTeorico.toFixed(3);
            valorIngresado = valorTeorico;

            Swal.showValidationMessage(`Ajuste limitado: El máximo teórico permitido para estas porciones es ${valorTeorico.toFixed(3)} ${unidad}.`);
            setTimeout(() => {
                const errorLabel = document.getElementById('swal2-validation-message');
                if (errorLabel) errorLabel.style.display = 'none';
            }, 3000);
        }

        if (isNaN(valorIngresado) || valorIngresado < 0) {
            valorIngresado = 0;
        }

        const stockRestante = stockDisp - valorIngresado;
        const celdaRestante = document.getElementById(`restante-${index}`);
        celdaRestante.innerHTML = `${stockRestante.toFixed(3)} <small class="text-muted fw-normal">${unidad}</small>`;

        colorearFilaYRestante(index, stockRestante);
        evaluarStocksGlobales();
    }

    function colorearFilaYRestante(index, stockRestante) {
        const fila = document.getElementById(`fila-insumo-${index}`);
        const celdaRestante = document.getElementById(`restante-${index}`);

        if (stockRestante < 0) {
            fila.classList.add('table-danger');
            celdaRestante.classList.remove('text-success');
            celdaRestante.classList.add('text-danger');
        } else {
            fila.classList.remove('table-danger');
            celdaRestante.classList.remove('text-danger');
            celdaRestante.classList.add('text-success');
        }
    }

    function evaluarStocksGlobales() {
        let hayInsuficientes = false;
        const filas = document.querySelectorAll('#tabla-insumos-body tr');

        filas.forEach((fila, index) => {
            const stockDisp = parseFloat(fila.getAttribute('data-stock-disp'));
            const inputReal = document.getElementById(`input-real-${index}`);
            const valorIngresado = parseFloat(inputReal.value) || 0;

            if (stockDisp - valorIngresado < 0) {
                hayInsuficientes = true;
            }
        });

        const alertDiv = document.getElementById('contenedor-alerta-stock');
        if (hayInsuficientes) {
            alertDiv.innerHTML = `
                <div class="alert alert-danger border-0 small mb-0 py-2">
                    <i class="fas fa-triangle-exclamation me-1"></i> 
                    <strong>Stock Insuficiente:</strong> Ajusta (resta) manualmente las cantidades marcadas en rojo para no exceder tu stock actual.
                </div>`;
        } else {
            alertDiv.innerHTML = '';
        }
    }
  </script>
</body>
</html>