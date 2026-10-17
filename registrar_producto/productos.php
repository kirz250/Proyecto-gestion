<?php 
// 1. INICIAR SESIÓN GLOBAL (Misma configuración exacta que en el login)
ini_set('session.cookie_path', '/'); 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. GUARDIÁN DE SEGURIDAD CENTRALIZADO (Ruta absoluta universal)
include($_SERVER['DOCUMENT_ROOT'] . "/iniciar sesion/verificar_admin.php"); 

// 3. CONEXIÓN A LA BASE DE DATOS
include "conexion_anadir.php";

// =========================================================================
// 🔥 PROCESAR LAS ACCIONES DE LA CATEGORÍA AQUÍ MISMO (EVITA EL ERROR 404)
// =========================================================================

// ACCIÓN A: MODIFICAR NOMBRE
if (isset($_POST['btn_modificar_cat'])) {
    $id_categoria = intval($_POST['id_categoria']);
    $nuevo_nombre = $conexion->real_escape_string($_POST['nuevo_nombre_categoria']);

    if (!empty($id_categoria) && !empty($nuevo_nombre)) {
        $sql_update_cat = $conexion->query("UPDATE categorias_maestras SET nombre_categoria = '$nuevo_nombre' WHERE id_categoria = $id_categoria");
        
        if ($sql_update_cat) {
            header("Location: productos.php?cat_msg=success");
            exit();
        } else {
            header("Location: productos.php?cat_msg=error");
            exit();
        }
    }
}

/// ACCIÓN B: ELIMINAR CATEGORÍA
if (isset($_POST['btn_eliminar_cat'])) {
    $id_categoria_eliminar = intval($_POST['id_categoria']);
    $opcion_limbo = $_POST['opcion_limbo'] ?? ''; // 'redireccionar', 'crear_nueva' o 'validar_existencia'

    if (!empty($id_categoria_eliminar)) {
        
        if ($opcion_limbo === 'validar_existencia') {
            // Opción 3: Verificar si cuenta con insumos registrados o no
            $check_insumos = $conexion->query("SELECT COUNT(*) AS total FROM productos WHERE id_categoria = $id_categoria_eliminar");
            $row_ins = $check_insumos->fetch_assoc();
            
            if ($row_ins['total'] > 0) {
                // Si cuenta con insumos registrados, deniega la eliminación directa por seguridad
                header("Location: productos.php?cat_msg=error");
                exit();
            }
            // Si no cuenta con insumos registrados, procede a eliminarla sin problemas abajo.
            
        } elseif ($opcion_limbo === 'redireccionar') {
            // Obtener el ID de la categoría destino seleccionada
            $id_categoria_destino = intval($_POST['id_categoria_destino'] ?? 0);
            
            if ($id_categoria_destino > 0 && $id_categoria_destino !== $id_categoria_eliminar) {
                // Mover los productos a la categoría elegida antes de eliminar
                $conexion->query("UPDATE productos SET id_categoria = $id_categoria_destino WHERE id_categoria = $id_categoria_eliminar");
            }
            
        } elseif ($opcion_limbo === 'crear_nueva') {
            // Obtener el nombre de la nueva categoría y su unidad predeterminada
            $nuevo_nombre_cat = $conexion->real_escape_string(trim($_POST['nueva_cat_limbo_nombre']));
            $nueva_unidad_cat = $conexion->real_escape_string(trim($_POST['nueva_cat_limbo_unidad'] ?? 'UN'));

            if (!empty($nuevo_nombre_cat)) {
                // 1. Crear la nueva categoría maestra
                $conexion->query("INSERT INTO categorias_maestras (nombre_categoria, unidad_predeterminada) VALUES ('$nuevo_nombre_cat', '$nueva_unidad_cat')");
                $id_categoria_destino = $conexion->insert_id;

                if ($id_categoria_destino > 0) {
                    // 2. Mover todos los productos asociados
                    $conexion->query("UPDATE productos SET id_categoria = $id_categoria_destino WHERE id_categoria = $id_categoria_eliminar");
                }
            }
        }

        // Por último, eliminar de forma segura la categoría vieja
        $sql_delete_cat = $conexion->query("DELETE FROM categorias_maestras WHERE id_categoria = $id_categoria_eliminar");
        
        if ($sql_delete_cat) {
            header("Location: productos.php?cat_msg=del_success");
            exit();
        } else {
            header("Location: productos.php?cat_msg=error");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Inventario - Stock y Nutrición</title>
    <link href="../DataTables/datatables.min.css" rel="stylesheet" />
    <link href="../startbootstrap-sb-admin-gh-pages/css/styles.css" rel="stylesheet" />
    <script src="../fontawesome-free-7.2.0-web/js/all.min.js" crossorigin="anonymous"></script>

    <style>
        .datatable-table thead th, #datatablesSimple thead th {
            background-color: #3168bb !important; 
            color: #ffffff !important;            
            font-family: "Nunito", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
            font-size: 1.05rem !important;        
            font-weight: 700 !important;
            text-transform: uppercase;
            vertical-align: middle !important;
            padding: 12px 10px !important;       
        }
        .datatable-table tbody td, #datatablesSimple tbody td {
            vertical-align: middle !important;
            font-family: "Nunito", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
            font-size: 1rem !important;           
            padding: 14px 10px !important;       
        }
        .datatable-wrapper {
            font-family: "Nunito", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
            font-size: 1rem !important;
        }
        .datatable-sorter::before, .datatable-sorter::after {
            border-top-color: #ffffff !important;
            border-bottom-color: #ffffff !important;
        }
        .form-check-input:checked {
            background-color: #198754 !important;
            border-color: #198754 !important;
        }
        .fila-inhabilitada {
            background-color: #f2f2f2 !important;
            color: #8c8c8c;
        }
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
                        <div class="sb-sidenav-menu-heading">Inicio</div>
                        <a class="nav-link" href="/inicio.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                            Inicio
                        </a>
                        <div class="sb-sidenav-menu-heading">Operación Diaria</div>
                        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseLayouts" aria-expanded="false" aria-controls="collapseLayouts">
                            <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>
                            Recetario
                            <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                        </a>
                        <div class="collapse" id="collapseLayouts" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                            <nav class="sb-sidenav-menu-nested nav">
                                <a class="nav-link" href="/menu/crear_receta.php">Crear nueva receta</a>
                                <a class="nav-link" href="/menu/lista_recetas.php">Planificar Menú</a>
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
                        <div class="sb-sidenav-menu-heading">Insumos</div>
                        <a class="nav-link active" href="/registrar_producto/productos.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-chart-area"></i></div>
                          Catálogo de Insumos
                        </a>
                        <a class="nav-link" href="/registrar_producto/stock.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-table"></i></div>
                            Stock
                        </a>
                    </div>
                </div>
                <div class="sb-sidenav-footer">
                    <div class="small">Iniciado como:</div>
                    <span class="text-white fw-bold"><?php echo htmlspecialchars($_SESSION['usuario']); ?></span>
                </div>
            </nav>
        </div>

        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <div class="d-flex align-items-center justify-content-between mt-4 mb-2">
                        <div class="d-flex align-items-center gap-3">
                            <h1 class="mb-0">Catálogo de Insumos</h1>
                            <!-- BOTÓN INTEGRADO MISMO ESTILO Y ANIMACIÓN -->
                            <button type="button" class="btn btn-warning text-dark fw-bold rounded-pill shadow-sm px-3 py-1 btn-ayuda-destacado" data-bs-toggle="modal" data-bs-target="#modalGuiaInsumos">
                                <i class="fas fa-circle-question fa-bounce text-dark me-1"></i> ¿Cómo registrar un insumo?
                            </button>
                        </div>
                    </div>

                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="/inicio.php">Inicio</a></li>
                        <li class="breadcrumb-item active">Insumos</li>
                    </ol>

                    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <strong>¡Éxito!</strong> El insumo ha sido eliminado definitivamente del inventario.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php elseif (isset($_GET['msg']) && $_GET['msg'] == 'error'): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>¡Error!</strong> No se pudo procesar la eliminación del insumo.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_GET['cat_msg']) && $_GET['cat_msg'] == 'success'): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <strong>¡Éxito!</strong> La categoría ha sido corregida en todo el sistema.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php elseif (isset($_GET['cat_msg']) && $_GET['cat_msg'] == 'del_success'): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <strong>¡Éxito!</strong> La categoría ha sido eliminada.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php elseif (isset($_GET['cat_msg']) && $_GET['cat_msg'] == 'error'): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>¡Error!</strong> No se pudo procesar la solicitud sobre la categoría (Es posible que tenga insumos registrados y se seleccionó una opción incompatible).
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="card mb-4">
                        <div class="card-header d-flex align-items-center gap-2">
                            <i class="fas fa-table me-1"></i>
                            <span>Vista Previa de Insumos</span>
                            
                            <button type="button" class="btn btn-warning btn-sm ms-auto fw-bold" data-bs-toggle="modal" data-bs-target="#modalEditarCategoria">
                                <i class="fas fa-edit"></i> Corregir / Eliminar Categoría
                            </button>

                            <div class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAnadirProducto"
                                 style="box-shadow: 4px 0px 0px 0px #154360; font-weight: bold; cursor: pointer;">
                                <i class="fas fa-plus"></i> Nuevo Insumo!
                            </div>
                        </div>
                        <div class="card-body">
                            <table id="datatablesSimple" class="table table-bordered">
                                <thead style="background-color: #2848b1; color: white;">
                                    <tr>
                                        <th>ID</th> 
                                        <th>NOMBRE</th> 
                                        <th>CATEGORÍA</th> 
                                        <th>UNIDAD</th>
                                        <th class="text-center">ESTADO</th>
                                        <th class="text-center">ACCIONES</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
$sql = $conexion->query("SELECT p.id_producto, 
                               p.nombre, 
                               IFNULL(c.nombre_categoria, 'Sin Categoría') AS categoria, 
                               IFNULL(c.unidad_predeterminada, 'UN') AS unidad_medida,
                               IFNULL(s.cantidad_actual, 0) AS existencia
                        FROM productos p
                        LEFT JOIN categorias_maestras c ON p.id_categoria = c.id_categoria
                        LEFT JOIN stock s ON p.id_producto = s.id_producto");

if($sql) {
    while($datos = $sql->fetch_object()){ 
        $claseFila = ($datos->existencia <= 0) ? 'fila-inhabilitada' : '';
    ?>
    <tr class="<?= $claseFila ?>" id="fila-prod-<?= $datos->id_producto ?>">
        <td><?= $datos->id_producto ?></td> 
        <td><?= $datos->nombre ?></td> 
        <td><?= $datos->categoria ?></td> 
        <td><?= $datos->unidad_medida ?></td> 
        <td class="text-center">
            <div class="form-check form-switch d-inline-block">
                <input class="form-check-input" type="checkbox" role="switch" 
                       disabled <?= $datos->existencia > 0 ? 'checked' : '' ?>>
            </div>
        </td>
        <td class="text-center">
            <a href="eliminar_insumo_registro.php?id=<?= $datos->id_producto ?>" 
               class="btn btn-danger btn-sm" 
               onclick="return confirmarEliminacion('<?= htmlspecialchars($datos->nombre) ?>');"
               title="Eliminar definitivamente del sistema">
                <i class="fas fa-trash-alt"></i>
            </a>
        </td>
    </tr>
    <?php } 
} ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>

            <footer class="py-4 bg-light mt-auto">
                <div class="container-fluid px-4">
                    <div class="d-flex align-items-center justify-content-between small">
                        <div class="text-muted">Copyright &copy; Nutrición 2026</div>
                        <div>
                            <a href="#">Privacidad</a>
                            &middot;
                            <a href="#">Términos</a>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- MODAL GUÍA DE USO PARA EL REGISTRO DE INSUMOS -->
    <div class="modal fade" id="modalGuiaInsumos" tabindex="-1" aria-labelledby="modalGuiaInsumosLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="modalGuiaInsumosLabel">
                        <i class="fas fa-book-open me-2"></i>Guía: Registro de Insumos
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">
                        Sigue estos pasos para dar de alta un nuevo alimento o insumo en el catálogo del sistema:
                    </p>

                    <ol class="list-group list-group-numbered list-group-flush mb-0">
                        <li class="list-group-item d-flex align-items-start border-0 px-0 py-2">
                            <div class="ms-2 me-auto">
                                <div class="fw-bold text-dark">Haz clic en "Nuevo Insumo!"</div>
                                <span class="text-secondary small">Presiona el botón azul ubicado arriba a la derecha de la tabla.</span>
                            </div>
                        </li>
                        <li class="list-group-item d-flex align-items-start border-0 px-0 py-2">
                            <div class="ms-2 me-auto">
                                <div class="fw-bold text-dark">Nombre del Producto</div>
                                <span class="text-secondary small">Escribe el nombre del alimento (ej. <i>Queso Blanco</i>). Puedes autocompletar si ya existe.</span>
                            </div>
                        </li>
                        <li class="list-group-item d-flex align-items-start border-0 px-0 py-2">
                            <div class="ms-2 me-auto">
                                <div class="fw-bold text-dark">Categoría y Unidad de Medida</div>
                                <span class="text-secondary small">Selecciona el grupo alimenticio. La unidad (KG, LT, UN) se asignará automáticamente según la categoría.</span>
                            </div>
                        </li>
                        <li class="list-group-item d-flex align-items-start border-0 px-0 py-2">
                            <div class="ms-2 me-auto">
                                <div class="fw-bold text-dark">Stock Mínimo</div>
                                <span class="text-secondary small">Define la cantidad mínima deseada en inventario para recibir alertas cuando las existencias estén bajas.</span>
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
    
    <div class="modal fade" id="modalAnadirProducto" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(lightblue, white);">
                    <h5 class="modal-title mx-auto fw-bold" id="modalLabel">REGISTRAR ENTRADA DE INSUMO</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form action="anadir_producto_validacion.php" method="post">
                        <div class="mb-3">
                            <label class="form-label fw-bold">NOMBRE DEL PRODUCTO</label>
                            <input type="text" class="form-control" name="nombre" placeholder="Ej: Queso Blanco" list="sugerenciasProductos" required autocomplete="off">
                            <datalist id="sugerenciasProductos">
                                <?php
                                $query_sug = $conexion->query("SELECT DISTINCT nombre FROM productos ORDER BY nombre ASC");
                                if($query_sug) {
                                    while($sug = $query_sug->fetch_assoc()) {
                                        echo "<option value='".htmlspecialchars($sug['nombre'])."'>";
                                    }
                                }
                                ?>
                            </datalist>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="categoria" class="form-label fw-bold">CATEGORÍA</label>
                                <select id="categoria" name="categoria" class="form-select" onchange="actualizarUnidad()" required>
                                    <option value="">Seleccione...</option>
                                    <option value="Granos/Cereales">Granos/Cereales</option>
                                    <option value="Proteínas Animales">Proteínas Animales</option>
                                    <option value="Lacteos">Lácteos</option>
                                    <option value="Individuales">Individuales</option>
                                    <option value="Grasas/Aceites">Grasas/Aceites</option>
                                    <option value="Frutas/Hortalizas">Frutas/Hortalizas</option>
                                    <?php
                                    $consulta = $conexion->query("SELECT DISTINCT nombre_categoria FROM categorias_maestras WHERE nombre_categoria NOT IN ('Granos/Cereales', 'Proteinas Animales', 'Lacteos', 'Individuales', 'Grasas/Aceites', 'Frutas/Hortalizas')");
                                    while ($cat = $consulta->fetch_assoc()) {
                                        echo "<option value='{$cat['nombre_categoria']}'>{$cat['nombre_categoria']}</option>";
                                    }
                                    ?>
                                    <option value="otra">Otra categoría...</option>
                                </select>
                                <input type="text" id="input_otra_categoria" name="otra_categoria" class="form-control mt-2" placeholder="Ej: Refrescos" style="display:none;" oninput="capitalizarTexto(this)">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="decay_medida" class="form-label fw-bold">UNIDAD DE MEDIDA</label>
                                <input type="text" id="unidad_medida" name="unidad" class="form-control" placeholder="Ej: KG" maxlength="2" oninput="this.value = this.value.toUpperCase()" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">STOCK MÍNIMO</label>
                                <input type="number" step="0.01" class="form-control" name="minimo" value="1.00" required>
                            </div>
                        </div>
                        
                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-primary px-4" name="btnregistrar">Guardar en Inventario</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

   <!-- MODAL EDITAR / ELIMINAR CATEGORÍAS (CON CONTROL DE LIMBO) -->
    <div class="modal fade" id="modalEditarCategoria" tabindex="-1" aria-labelledby="modalCatLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title mx-auto fw-bold" id="modalCatLabel"><i class="fas fa-edit"></i> GESTIONAR CATEGORÍAS</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form action="productos.php" method="post" id="formGestionCategorias">
                        
                        <!-- 1. SELECCIONAR CATEGORÍA OBJETIVO -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">SELECCIONAR LA CATEGORÍA A GESTIONAR</label>
                            <select name="id_categoria" id="id_categoria_select" class="form-select border-primary" onchange="actualizarDestinosDisponibles()" required>
                                <option value="">Seleccione...</option>
                                <?php
                                $query_cat_mod = $conexion->query("SELECT id_categoria, nombre_categoria FROM categorias_maestras ORDER BY nombre_categoria ASC");
                                while($cm = $query_cat_mod->fetch_assoc()) {
                                    echo "<option value='{$cm['id_categoria']}'>".htmlspecialchars($cm['nombre_categoria'])."</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <!-- OPCIÓN A: MODIFICAR NOMBRE -->
                        <div class="border p-3 rounded mb-4 bg-light shadow-sm">
                            <h6 class="fw-bold text-dark mb-2"><i class="fas fa-pen small"></i> Opción A: Cambiar Nombre</h6>
                            <div class="mb-2">
                                <input type="text" class="form-control" name="nuevo_nombre_categoria" id="nuevo_nombre_categoria" placeholder="Nuevo nombre correcto" oninput="capitalizarTexto(this)">
                            </div>
                            <button type="submit" class="btn btn-dark btn-sm w-100 fw-bold" name="btn_modificar_cat" onclick="return validarModificacion();">
                                Actualizar Nombre
                            </button>
                        </div>

                        <!-- OPCIÓN B: ELIMINAR CATEGORÍA (ZONA DE SEGURIDAD) -->
                        <div class="border p-3 rounded border-danger bg-light shadow-sm">
                            <h6 class="fw-bold text-danger mb-3"><i class="fas fa-exclamation-triangle small"></i> Opción B: Eliminar Categoría</h6>
                            
                            <p class="small text-muted mb-3">Para evitar que los insumos queden en el limbo sin categoría asociada, elija una de las siguientes opciones:</p>
                            
                            <!-- Select de decisión -->
                            <div class="mb-3">
                                <label class="form-label small fw-bold">¿Qué desea hacer con sus insumos actuales?</label>
                                <select class="form-select form-select-sm" name="opcion_limbo" id="opcion_limbo" onchange="toggleOpcionesLimbo()" required>
                                    <option value="">Seleccione una opción de resguardo...</option>
                                    <option value="redireccionar">Opción N° 1: Redireccionarlos a otra categoría existente</option>
                                    <option value="crear_nueva">Opción N° 2: Crear una nueva categoría para moverlos</option>
                                    <option value="validar_existencia">Opción N° 3: Eliminar solo si NO cuenta con insumos registrados</option>
                                </select>
                            </div>

                            <!-- CONTENEDOR DINÁMICO 1: REDIRECCIONAR A OTRA CATEGORÍA -->
                            <div id="seccion_redireccionar" class="mb-3 border p-2 bg-white rounded" style="display: none;">
                                <label class="form-label small fw-bold text-primary">Seleccionar Categoría de Destino</label>
                                <select name="id_categoria_destino" id="id_categoria_destino" class="form-select form-select-sm">
                                    <option value="">Seleccione destino...</option>
                                    <!-- Se llena dinámicamente vía JS para excluir la categoría que se va a borrar -->
                                </select>
                            </div>

                            <!-- CONTENEDOR DINÁMICO 2: CREAR UNA NUEVA CATEGORÍA -->
                            <div id="seccion_crear_nueva" class="mb-3 border p-2 bg-white rounded" style="display: none;">
                                <label class="form-label small fw-bold text-success">Datos de la Nueva Categoría (Máx. 20 letras)</label>
                                <div class="mb-2">
                                    <input type="text" class="form-control form-control-sm" name="nueva_cat_limbo_nombre" id="nueva_cat_limbo_nombre" placeholder="Nombre (Ej: Charcutería)" maxlength="20" oninput="validarSoloLetrasYEspacios(this)">
                                </div>
                                <div>
                                    <input type="text" class="form-control form-control-sm" name="nueva_cat_limbo_unidad" id="nueva_cat_limbo_unidad" placeholder="Unidad Predeterminada (Ej: KG)" maxlength="2" oninput="this.value = this.value.toUpperCase()">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-danger btn-sm w-100 fw-bold mt-2" name="btn_eliminar_cat" onclick="return validarEliminarCategoria();">
                                <i class="fas fa-trash-alt"></i> Procesar y Eliminar Categoría
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="../bootstrap/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="../startbootstrap-sb-admin-gh-pages/js/scripts.js"></script>
    <script src="../DataTables/simple-datatables.min.js" crossorigin="anonymous"></script>
    
    <script>
    window.addEventListener('DOMContentLoaded', event => {
        const datatablesSimple = document.getElementById('datatablesSimple');
        if (datatablesSimple) {
            fetch('../DataTables/es-ES.Json')
                .then(response => response.json())
                .then(traduccion => {
                    new simpleDatatables.DataTable(datatablesSimple, {
                        labels: traduccion,
                        perPage: 5,
                        searchable: true,
                        fixedHeight: false
                    });
                })
                .catch(error => {
                    new simpleDatatables.DataTable(datatablesSimple);
                });
        }
    });

    function confirmarEliminacion(nombreProducto) {
        return confirm(`¿Está totalmente seguro de que desea ELIMINAR el producto "${nombreProducto}" de forma DEFINITIVA?\n\nEsta acción no se puede deshacer y borrará permanentemente sus existencias en el stock.`);
    }

    // VALIDACIONES EXCLUSIVAS DEL MODAL DE CATEGORÍAS
    function validarModificacion() {
        const select = document.getElementById('id_categoria_select').value;
        const nuevoNombre = document.getElementById('nuevo_nombre_categoria').value.trim();
        if(!select) { alert('Debe seleccionar una categoría primero.'); return false; }
        if(!nuevoNombre) { alert('Debe escribir el nuevo nombre para procesar el cambio.'); return false; }
        return true;
    }

    function confirmarEliminarCategoria() {
        const select = document.getElementById('id_categoria_select');
        if(!select.value) { alert('Debe seleccionar una categoría primero.'); return false; }
        
        const nombreCat = select.options[select.selectedIndex].text;
        return confirm(`¿Está completamente seguro de borrar la categoría "${nombreCat}"?\n\nLos insumos registrados bajo esta categoría pasarán a ser generales.`);
    }

    <?php
        $res = $conexion->query("SELECT nombre_categoria, unidad_predeterminada FROM categorias_maestras");
        $data = [];
        while ($fila = $res->fetch_assoc()) {
            $data[$fila['nombre_categoria']] = $fila['unidad_predeterminada'];
        }
    ?>
    const mapaUnidadesAprendidas = <?php echo json_encode($data); ?>;

    function capitalizarTexto(input) {
        if (input.value.length === 0) return;
        let palabras = input.value.toLowerCase().split(' ');
        for (let i = 0; i < palabras.length; i++) {
            palabras[i] = palabras[i].charAt(0).toUpperCase() + palabras[i].slice(1);
        }
        input.value = palabras.join(' ');
    }

    // Función restrictiva para permitir solo letras y espacios uno por uno, con límite de 20 caracteres y formato capitalizado
    function validarSoloLetrasYEspacios(input) {
        // Permitir solo letras (incluyendo acentos y eñe) y espacios
        let valorLimpio = input.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '');
        
        // Aplicar límite máximo de 20 caracteres
        if (valorLimpio.length > 20) {
            valorLimpio = valorLimpio.substring(0, 20);
        }
        
        input.value = valorLimpio;
        capitalizarTexto(input);
    }

    function actualizarUnidad() {
        const selectCat = document.getElementById('categoria');
        const inputOtra = document.getElementById('input_otra_categoria');
        const inputUnidad = document.getElementById('unidad_medida');
        
        const mapaFijo = {
            "Granos/Cereales": "KG",
            "Lacteos": "LT",
            "Grasas/Aceites": "LT",
            "Proteínas Animales": "KG",
            "Frutas/Hortalizas": "UN",
            "Individuales": "UN"
        };

        if (selectCat.value === "otra") {
            inputOtra.style.display = "block";
            inputUnidad.value = ""; 
            inputUnidad.readOnly = false;
        } else {
            inputOtra.style.display = "none";
            let unidad = mapaFijo[selectCat.value] || mapaUnidadesAprendidas[selectCat.value] || "";
            inputUnidad.value = unidad;
            inputUnidad.readOnly = true;
        }
    }


    // Mostrar/Ocultar campos dinámicos según decisión sobre el limbo
    function toggleOpcionesLimbo() {
        const opcion = document.getElementById('opcion_limbo').value;
        const divRedireccionar = document.getElementById('seccion_redireccionar');
        const divCrearNueva = document.getElementById('seccion_crear_nueva');

        // Resetear visibilidades
        divRedireccionar.style.display = 'none';
        divCrearNueva.style.display = 'none';

        // Requerimientos dinámicos para no romper validación nativa de HTML5
        document.getElementById('id_categoria_destino').required = false;
        document.getElementById('nueva_cat_limbo_nombre').required = false;

        if (opcion === 'redireccionar') {
            divRedireccionar.style.display = 'block';
            document.getElementById('id_categoria_destino').required = true;
        } else if (opcion === 'crear_nueva') {
            divCrearNueva.style.display = 'block';
            document.getElementById('nueva_cat_limbo_nombre').required = true;
        }
    }

    // Excluir la categoría que se va a borrar del listado de destinos posibles de redirección
    function actualizarDestinosDisponibles() {
        const selectOrigen = document.getElementById('id_categoria_select');
        const selectDestino = document.getElementById('id_categoria_destino');
        const origenVal = selectOrigen.value;

        // Limpiar select de destinos
        selectDestino.innerHTML = '<option value="">Seleccione destino...</option>';

        // Recorrer y clonar las opciones del select de origen que no coincidan con el que se va a eliminar
        Array.from(selectOrigen.options).forEach(option => {
            if (option.value !== "" && option.value !== origenVal) {
                const opt = document.createElement('option');
                opt.value = option.value;
                opt.textContent = option.textContent;
                selectDestino.appendChild(opt);
            }
        });
    }

    // Validación estricta antes de procesar el botón de borrar categoría
    function validarEliminarCategoria() {
        const selectOrigen = document.getElementById('id_categoria_select');
        if (!selectOrigen.value) {
            alert('Debe seleccionar la categoría que desea eliminar primero.');
            return false;
        }

        const opcion = document.getElementById('opcion_limbo').value;
        if (!opcion) {
            alert('Por favor, elija una de las opciones para resguardar los insumos antes de eliminar.');
            return false;
        }

        if (opcion === 'redireccionar') {
            const destino = document.getElementById('id_categoria_destino').value;
            if (!destino) {
                alert('Debe elegir una categoría destino para mover los insumos.');
                return false;
            }
        } else if (opcion === 'crear_nueva') {
            const nuevoNombre = document.getElementById('nueva_cat_limbo_nombre').value.trim();
            if (!nuevoNombre) {
                alert('Debe escribir el nombre para la nueva categoría.');
                return false;
            }
        }

        const nombreCat = selectOrigen.options[selectOrigen.selectedIndex].text;
        return confirm(`¿Está totalmente seguro de procesar la eliminación de la categoría "${nombreCat}"?`);
    }
    </script>
</body>
</html>