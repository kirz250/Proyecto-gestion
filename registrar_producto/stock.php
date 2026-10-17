<?php 
// 1. INICIAR SESIÓN GLOBAL (Misma configuración exacta que en el login)
ini_set('session.cookie_path', '/'); 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. GUARDIÁN DE SEGURIDAD CENTRALIZADO (Ruta absoluta universal)
include($_SERVER['DOCUMENT_ROOT'] . "/iniciar sesion/verificar_admin.php"); 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>PAE - Stock y Nutrición</title>
    <link href="../startbootstrap-sb-admin-gh-pages/css/style.min.css" rel="stylesheet" />
    <link href="../startbootstrap-sb-admin-gh-pages/css/styles.css" rel="stylesheet" />
    <script src="../fontawesome-free-7.2.0-web/js/all.min.js" crossorigin="anonymous"></script>

<style>
    /* 1. Agrandamos la cabecera oscura */
    .datatable-table thead th, #datatablesSimple thead th {
        background-color: #212529 !important; 
        color: #ffffff !important;            
        font-family: "Nunito", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
        font-size: 1.05rem !important;        
        font-weight: 700 !important;
        text-transform: uppercase;
        vertical-align: middle !important;
        padding: 12px 10px !important;       
    }

    /* 2. Agrandamos el contenido de las celdas y filas */
    .datatable-table tbody td, #datatablesSimple tbody td {
        vertical-align: middle !important;
        font-family: "Nunito", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
        font-size: 1rem !important;           
        padding: 14px 10px !important;       
    }

    /* 3. Agrandamos también los textos del buscador y de la paginación */
    .datatable-wrapper {
        font-family: "Nunito", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
        font-size: 1rem !important;
    }
    
    .datatable-sorter::before, .datatable-sorter::after {
        border-top-color: #ffffff !important;
        border-bottom-color: #ffffff !important;
    }

    .datatable-table tbody td .text-danger,
    #datatablesSimple tbody td .text-danger,
    .text-danger {
        color: #dc3545 !important; 
    }
    .datatable-table tbody td .text-danger i,
    #datatablesSimple tbody td .fa-exclamation-circle {
        color: #dc3545 !important;
        font-size: 1.1rem !important;
        display: inline-block !important;
        vertical-align: middle !important;
    }

    .datatable-table tbody td .text-warning,
    #datatablesSimple tbody td .text-warning,
    .text-warning {
        color: #ffc107 !important; 
    }
    .datatable-table tbody td .text-warning i,
    #datatablesSimple tbody td .fa-exclamation-triangle {
        color: #ffc107 !important;
        font-size: 1.1rem !important;
        display: inline-block !important;
        vertical-align: middle !important;
    }

    .me-1 {
        margin-right: 0.25rem !important;
    }

    /* Estilo estético para separar las filas de insumos en el modal */
    .insumo-row {
        position: relative;
        background-color: #f8f9fa;
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
                        <div class="sb-sidenav-menu-heading">INICIO</div>
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
                        <a class="nav-link" href="/registrar_producto/productos.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-chart-area"></i></div>
                            Catálogo de Insumos
                        </a>
                        <a class="nav-link active" href="/registrar_producto/stock.php">
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
                            <h1 class="mb-0">Gestión de Stock</h1>
                            <!-- BOTÓN INTEGRADO DE AYUDA -->
                            <button type="button" class="btn btn-warning text-dark fw-bold rounded-pill shadow-sm px-3 py-1 btn-ayuda-destacado" data-bs-toggle="modal" data-bs-target="#modalGuiaStock">
                                <i class="fas fa-circle-question fa-bounce text-dark me-1"></i> ¿Cómo usar el stock?
                            </button>
                        </div>
                    </div>

                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="/inicio.php">Inicio</a></li>
                        <li class="breadcrumb-item active">Stock</li>
                    </ol>

                    <!-- 📌 CONTROLADOR INCORPORADO DIRECTAMENTE EN LA PARTE SUPERIOR DE LA VISTA -->
                    <?php 
                    include "conexion_anadir.php";
                    include "modificar_controlador.php"; // Procesará el POST de edición aquí mismo
                    include "anadir_producto_validacion.php";
                    ?>

                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div><i class="fas fa-table me-1"></i> Insumos en Existencia</div>
                            <div class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalEntradaStock"
                                 style="box-shadow: 4px 0px 0px 0px #154360; font-weight: bold; cursor: pointer;">
                                <i class="fas fa-plus"></i> Registrar Insumos en Stock!
                            </div>
                        </div>

                        <div class="card-body">
                            <table id="datatablesSimple" class="table table-striped table-bordered">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th> 
                                        <th>PRODUCTO</th> 
                                        <th>CATEGORÍA</th> 
                                        <th>EXISTENCIA</th> 
                                        <th>UNIDAD</th> 
                                        <th>MÍNIMO</th> 
                                        <th>VENCIMIENTO</th> 
                                        <th>ACCIONES</th> 
                                    </tr>
                                </thead>
                                <tbody>
                                   <?php 
$sql = $conexion->query("SELECT s.id_stock, 
                                s.id_producto,
                                p.nombre, 
                                c.id_categoria,
                                c.nombre_categoria AS categoria, 
                                c.unidad_predeterminada AS unidad_medida, 
                                s.cantidad_actual, 
                                s.stock_minimo, 
                                s.fecha_caducidad
                         FROM stock s 
                         INNER JOIN productos p ON s.id_producto = p.id_producto
                         INNER JOIN categorias_maestras c ON p.id_categoria = c.id_categoria");

if (!$sql) { 
    die("Error en SQL: " . $conexion->error); 
}

// Traemos la unidad_predeterminada mediante INNER JOIN para inyectarla en el select del modal de Entrada
$productos_opciones = [];
$query_p = $conexion->query("SELECT p.id_producto, p.nombre, c.unidad_predeterminada 
                             FROM productos p 
                             INNER JOIN categorias_maestras c ON p.id_categoria = c.id_categoria");
while($p_row = $query_p->fetch_object()) {
    $productos_opciones[] = $p_row;
}

while($datos = $sql->fetch_object()){ 
    $clase_vencimiento = '';
    
    if (!empty($datos->fecha_caducidad) && $datos->fecha_caducidad != '0000-00-00') {
        $fecha_actual = new DateTime();
        $fecha_vence = new DateTime($datos->fecha_caducidad);
        $diferencia = $fecha_actual->diff($fecha_vence);
        $dias_restantes = (int)$diferencia->format('%r%a');

        if ($dias_restantes <= 7) {
            $clase_vencimiento = 'text-danger fw-bold';
        } elseif ($dias_restantes > 30) {
            $clase_vencimiento = 'text-success';
        } else {
            $clase_vencimiento = 'text-warning fw-bold';
        }
    }
    
    $punto_advertencia = $datos->stock_minimo * 1.30; 
    $clase_stock = '';
    $icono_stock = '';

    if ($datos->cantidad_actual <= $datos->stock_minimo) {
        $clase_stock = 'text-danger fw-bold';
        $icono_stock = '<i class="fas fa-exclamation-circle me-1"></i>';
    } elseif ($datos->cantidad_actual <= $punto_advertencia) {
        $clase_stock = 'text-warning fw-bold';
        $icono_stock = '<i class="fas fa-exclamation-triangle me-1"></i>';
    }
?>
                                    <tr>
                                        <td><?= $datos->id_stock ?></td> 
                                        <td><strong><?= $datos->nombre ?></strong></td> 
                                        <td><?= $datos->categoria ?></td> 
                                        <td class="<?= $clase_stock ?>">
                                            <?= $icono_stock ?>
                                            <?= $datos->cantidad_actual ?>
                                        </td>
                                        <td><?= $datos->unidad_medida ?></td> 
                                        <td><?= $datos->stock_minimo ?></td> 
                                        <td>
                                            <span class="<?= $clase_vencimiento ?>">
                                                <?= $datos->fecha_caducidad ?>
                                            </span>
                                        </td>
                                        <td>
                                            <!-- 📌 BOTÓN MODIFICADO PARA COMPORTAMIENTO MODAL SIN REDIRECCIÓN -->
                                            <button type="button" 
                                                    class="btn btn-warning btn-sm btn-abrir-editar"
                                                    data-id-stock="<?= $datos->id_stock ?>"
                                                    data-id-producto="<?= $datos->id_producto ?>"
                                                    data-nombre="<?= htmlspecialchars($datos->nombre) ?>"
                                                    data-categoria="<?= htmlspecialchars($datos->categoria) ?>"
                                                    data-unidad="<?= htmlspecialchars($datos->unidad_medida) ?>"
                                                    data-cantidad="<?= $datos->cantidad_actual ?>"
                                                    data-minimo="<?= $datos->stock_minimo ?>"
                                                    data-vence="<?= $datos->fecha_caducidad ?>">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <a onclick="return eliminar()" href="eliminar.php?id=<?= $datos->id_stock ?>" class="btn btn-small btn-danger"><i class="fa-solid fa-trash"></i></a>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
            
            <footer class="py-4 bg-light mt-auto">
                <div class="container-fluid px-4">
                    <div class="d-flex align-items-center justify-content-between small">
                        <div class="text-muted">Copyright &copy; Sistema Nutrición 2026</div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- MODAL GUÍA DE USO PARA LA GESTIÓN DE STOCK -->
    <div class="modal fade" id="modalGuiaStock" tabindex="-1" aria-labelledby="modalGuiaStockLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="modalGuiaStockLabel">
                        <i class="fas fa-book-open me-2"></i>Guía: Gestión de Stock
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">
                        Administra el inventario en existencia, registra nuevas entradas físicas y monitorea caducidades:
                    </p>

                    <ol class="list-group list-group-numbered list-group-flush mb-0">
                        <li class="list-group-item d-flex align-items-start border-0 px-0 py-2">
                            <div class="ms-2 me-auto">
                                <div class="fw-bold text-dark">Registrar Insumos en Stock</div>
                                <span class="text-secondary small">Haz clic en el botón azul <b>"Registrar Insumos en Stock!"</b>. Los productos mostrados provienen del <i>Catálogo de Insumos</i>. Puedes registrar hasta 5 insumos simultáneamente.</span>
                            </div>
                        </li>
                        <li class="list-group-item d-flex align-items-start border-0 px-0 py-2">
                            <div class="ms-2 me-auto">
                                <div class="fw-bold text-dark">Interpretación de Alertas de Existencia</div>
                                <span class="text-secondary small">
                                    • <i class="fas fa-exclamation-circle text-danger"></i> <b>Rojo:</b> La existencia es menor o igual al mínimo estipulado.<br>
                                    • <i class="fas fa-exclamation-triangle text-warning"></i> <b>Naranja:</b> Existencia cercana al límite crítico (hasta 30% por encima del mínimo).
                                </span>
                            </div>
                        </li>
                        <li class="list-group-item d-flex align-items-start border-0 px-0 py-2">
                            <div class="ms-2 me-auto">
                                <div class="fw-bold text-dark">Interpretación de Vencimiento</div>
                                <span class="text-secondary small">
                                    • <b>Verde:</b> Más de 30 días vigentes.<br>
                                    • <b>Naranja/Amarillo:</b> Entre 8 y 30 días restantes.<br>
                                    • <b>Rojo:</b> Vence en 7 días o menos.
                                </span>
                            </div>
                        </li>
                        <li class="list-group-item d-flex align-items-start border-0 px-0 py-2">
                            <div class="ms-2 me-auto">
                                <div class="fw-bold text-dark">Acciones de Edición y Eliminación</div>
                                <span class="text-secondary small">Usa el botón de lápiz <i class="fa-solid fa-pen-to-square text-warning"></i> para actualizar parámetros de un registro en un modal emergente o la papelera <i class="fa-solid fa-trash text-danger"></i> para darlo de baja.</span>
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

    <!-- MODAL 1: REGISTRAR ENTRADA DE STOCK -->
    <div class="modal fade" id="modalEntradaStock" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg"> <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(to right, #d1e7ff, white);">
                <h5 class="modal-title mx-auto fw-bold">Registrar Insumos en Stock</h5>
                <button type="button" class="btn-close ms-0" data-bs-dismiss="modal"></button>
            </div>
            
            <div class="modal-body p-4">
                <form action="registrar_existencia.php" method="post">
                    
                    <div id="contenedor-insumos">
                        <div class="insumo-row border rounded p-3 mb-3 shadow-sm">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-secondary número-insumo">Insumo #1</span>
                            </div>
                            <div class="row">
                                <div class="col-md-5 mb-2">
                                    <label class="form-label fw-bold small">PRODUCTO</label>
                                    <select name="id_producto[]" class="form-select selector-producto" required>
                                        <option value="" data-unidad="">Seleccione...</option>
                                        <?php foreach($productos_opciones as $prod): ?>
                                            <option value="<?= $prod->id_producto ?>" data-unidad="<?= $prod->unidad_predeterminada ?>"><?= htmlspecialchars($prod->nombre) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label class="form-label fw-bold small">CANTIDAD</label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" class="form-control" name="cantidad[]" placeholder="0.00" required>
                                        <span class="input-group-text badge-unidad" style="min-width: 45px; justify-content: center; font-weight: bold; background-color: #e9ecef;">--</span>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label fw-bold small">FECHA VENCIMIENTO</label>
                                    <input type="date" class="form-control" name="fecha_caducidad[]" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3 text-start">
                        <button type="button" id="btn-añadir-fila" class="btn btn-outline-primary btn-sm fw-bold">
                            <i class="fas fa-plus"></i> Añadir otro insumo
                        </button>
                        <small class="text-muted ms-2" id="alerta-maximo">(Máximo 5 items)</small>
                    </div>
                    
                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-success px-5 fw-bold" name="btnstock">Procesar Entrada de Stock</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- 📌 MODAL 2: EDITAR EXISTENCIA (NUEVO - DESPLEGABLE) -->
<div class="modal fade" id="modalEditarStock" tabindex="-1" aria-labelledby="modalEditarLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <div class="modal-header bg-warning">
                <h5 class="modal-title mx-auto fw-bold text-dark" id="modalEditarLabel"><i class="fa-solid fa-pen-to-square me-2"></i>MODIFICAR REGISTRO</h5>
                <button type="button" class="btn-close ms-0" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                
                <!-- Alerta dinámica integrada en el modal para evitar popups de localhost -->
                <div id="alerta-error-editar" class="alert alert-danger py-2 px-3 mb-3 small" style="display: none;">
                    <i class="fas fa-exclamation-circle me-1"></i> <span id="alerta-error-texto-editar"></span>
                </div>

                <!-- Envía el formulario a la misma vista stock.php -->
                <form action="stock.php" method="POST" onsubmit="return validarFechaVencimiento();">
                    
                    <input type="hidden" name="id_stock" id="edit_id_stock">
                    <input type="hidden" name="id_producto" id="edit_id_producto">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Nombre del Producto</label>
                        <input type="text" name="nombre" id="edit_nombre" class="form-control" readonly style="background-color: #e9ecef; font-weight: bold;">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small">Categoría</label>
                            <select id="edit_categoria" name="categoria" class="form-select" onchange="actualizarUnidadEdicion()" required>
                                <option value="">Seleccione...</option>
                                <?php
                                // Cargamos las opciones dinámicamente tal como en tu archivo anterior
                                $categorias_fijas = [
                                    "Frutas/Hortalizas" => ["id" => 1, "texto" => "Frutas/Hortalizas"],
                                    "Granos/Cereales" => ["id" => 6, "texto" => "Granos/Cereales"],
                                    "Proteínas Animales" => ["id" => 9, "texto" => "Proteínas Animales"],
                                    "Lácteos" => ["id" => 11, "texto" => "Lácteos"],
                                    "Grasas/Aceites" => ["id" => 12, "texto" => "Grasas/Aceites"]
                                ];

                                foreach ($categorias_fijas as $nombre_bd => $info) {
                                    echo "<option value='{$info['id']}' data-nombre='{$nombre_bd}'>{$info['texto']}</option>";
                                }

                                $consulta_op = $conexion->query("SELECT id_categoria, nombre_categoria FROM categorias_maestras 
                                                             WHERE nombre_categoria NOT IN ('Granos/Cereales', 'Proteínas Animales', 'Lácteos', 'Grasas/Aceites', 'Frutas/Hortalizas')");
                                
                                while ($cat = $consulta_op->fetch_assoc()) {
                                    echo "<option value='{$cat['id_categoria']}' data-nombre='{$cat['nombre_categoria']}'>{$cat['nombre_categoria']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small">Unidad de Medida</label>
                            <input type="text" name="unidad" id="edit_unidad_medida" class="form-control" readonly style="background-color: #e9ecef; font-weight: bold;">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-success fw-bold small">Cantidad</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="cantidad" id="edit_cantidad" required oninput="validarPositivo(this)">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small">Mínimo Requerido</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="minimo" id="edit_minimo" required oninput="validarPositivo(this)">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold small">Fecha de Caducidad</label>
                        <!-- Forzamos la fecha mínima directamente en el frontend usando la fecha de hoy -->
                        <input type="date" class="form-control" name="fecha_caducidad" id="edit_fecha_caducidad" min="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="text-center">
                        <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary px-5 fw-bold" name="btnmodificar" value="ok">Guardar Todo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

 <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
 <script src="../DataTables/simple-datatables.min.js"></script>

<script>
    function eliminar() {
        return confirm("¿Estás seguro de que deseas eliminar este registro del inventario?");
    }

    window.addEventListener('DOMContentLoaded', event => {
        const datatablesSimple = document.getElementById('datatablesSimple');
        if (datatablesSimple) {
            const configuracionTabla = {
                perPage: 5,
                searchable: true,
                fixedHeight: false,
                layout: {
                    top: "{search}", 
                    bottom: "{select}{info}{pager}" 
                }
            };

            fetch('../DataTables/es-ES.Json')
                .then(response => {
                    if (!response.ok) throw new Error('Error de ruta JSON');
                    return response.json();
                })
                .then(traduccion => {
                    configuracionTabla.labels = traduccion;
                    new simpleDatatables.DataTable(datatablesSimple, configuracionTabla);
                })
                .catch(error => {
                    console.warn("Cargando diseño de respaldo por seguridad:", error);
                    new simpleDatatables.DataTable(datatablesSimple, configuracionTabla);
                });
        }
    });

    // 🔄 FUNCIÓN ACTUALIZADORA: Extrae de forma segura la unidad de la opción seleccionada (Modal de Entrada)
    function actualizarUnidadDeFila(selectElement) {
        const fila = selectElement.closest('.insumo-row');
        if (!fila) return;

        const badgeUnidad = fila.querySelector('.badge-unidad');
        if (!badgeUnidad) return;

        if (selectElement.selectedIndex >= 0) {
            const optionSeleccionada = selectElement.options[selectElement.selectedIndex];
            const unidad = optionSeleccionada.getAttribute('data-unidad');
            
            if (unidad && unidad.trim() !== '') {
                badgeUnidad.textContent = unidad;
            } else {
                badgeUnidad.textContent = '--';
            }
        } else {
            badgeUnidad.textContent = '--';
        }
    }

    document.getElementById('contenedor-insumos').addEventListener('change', function(e) {
        if (e.target && e.target.classList.contains('selector-producto')) {
            actualizarUnidadDeFila(e.target);
        }
    });

    // LÓGICA JAVASCRIPT DINÁMICA: Añadir filas (Máximo 5)
    document.getElementById('btn-añadir-fila').addEventListener('click', function() {
        const contenedor = document.getElementById('contenedor-insumos');
        const filasActuales = contenedor.getElementsByClassName('insumo-row').length;

        if (filasActuales >= 5) {
            alert('Has alcanzado el límite máximo de 5 insumos permitidos a la vez.');
            return;
        }

        const primeraFila = contenedor.getElementsByClassName('insumo-row')[0];
        const nuevaFila = primeraFila.cloneNode(true);

        const inputs = nuevaFila.querySelectorAll('input');
        inputs.forEach(input => input.value = '');

        const selectClonado = nuevaFila.querySelector('.selector-producto');
        if (selectClonado) {
            selectClonado.selectedIndex = 0; 
        }

        const badgeClonado = nuevaFila.querySelector('.badge-unidad');
        if (badgeClonado) {
            badgeClonado.textContent = '--';
        }

        const nuevoNumero = filasActuales + 1;
        nuevaFila.querySelector('.número-insumo').textContent = 'Insumo #' + nuevoNumero;

        const headerFila = nuevaFila.querySelector('.d-flex');
        const btnRemover = document.createElement('button');
        btnRemover.type = 'button';
        btnRemover.className = 'btn-close text-danger small';
        btnRemover.style.fontSize = '0.8rem';
        btnRemover.title = 'Remover este insumo';
        btnRemover.addEventListener('click', function() {
            nuevaFila.remove();
            renumerarInsumos();
        });
        headerFila.appendChild(btnRemover);

        contenedor.appendChild(nuevaFila);
        renumerarInsumos();
    });

    function renumerarInsumos() {
        const contenedor = document.getElementById('contenedor-insumos');
        const filas = contenedor.getElementsByClassName('insumo-row');
        for (let i = 0; i < filas.length; i++) {
            filas[i].querySelector('.número-insumo').textContent = 'Insumo #' + (i + 1);
        }
        document.getElementById('btn-añadir-fila').disabled = (filas.length >= 5);
    }

    // 📌 LÓGICA EXCLUSIVA DEL MODAL DE EDICIÓN (SIN REDIRECCIÓN)
    document.addEventListener('click', function(e) {
        const boton = e.target.closest('.btn-abrir-editar');
        if (boton) {
            e.preventDefault();

            // Ocultamos alertas dinámicas viejas
            document.getElementById('alerta-error-editar').style.display = 'none';

            // Extraemos toda la data del botón de la fila clickeada
            const idStock = boton.getAttribute('data-id-stock');
            const idProducto = boton.getAttribute('data-id-producto');
            const nombre = boton.getAttribute('data-nombre');
            const categoriaNombre = boton.getAttribute('data-categoria');
            const unidad = boton.getAttribute('data-unidad');
            const cantidad = boton.getAttribute('data-cantidad');
            const minimo = boton.getAttribute('data-minimo');
            const vence = boton.getAttribute('data-vence');

            // Inyectamos valores a los inputs del modal
            document.getElementById('edit_id_stock').value = idStock;
            document.getElementById('edit_id_producto').value = idProducto;
            document.getElementById('edit_nombre').value = nombre;
            document.getElementById('edit_unidad_medida').value = unidad;
            document.getElementById('edit_cantidad').value = cantidad;
            document.getElementById('edit_minimo').value = minimo;
            document.getElementById('edit_fecha_caducidad').value = (vence && vence !== '0000-00-00') ? vence : '';

            // Autoseleccionar la categoría correcta dentro del select
            const selectCat = document.getElementById('edit_categoria');
            for (let option of selectCat.options) {
                if (option.getAttribute('data-nombre') === categoriaNombre) {
                    option.selected = true;
                    break;
                }
            }

            // Desplegar el modal usando Bootstrap nativo
            const modalEditar = new bootstrap.Modal(document.getElementById('modalEditarStock'));
            modalEditar.show();
        }
    });

    // Cambiar dinámicamente la unidad de medida según la categoría seleccionada en el modal de edición
    function actualizarUnidadEdicion() {
        const selectCat = document.getElementById('edit_categoria');
        const inputUnidad = document.getElementById('edit_unidad_medida');
        
        const optionSeleccionada = selectCat.options[selectCat.selectedIndex];
        const catSeleccionada = optionSeleccionada ? optionSeleccionada.getAttribute('data-nombre') : '';

        const mapaUnidades = {
            "Granos/Cereales": "KG",
            "Lácteos": "LT",
            "Grasas/Aceites": "LT",
            "Proteínas Animales": "KG",
            "Frutas/Hortalizas": "UN"
        };

        if (mapaUnidades[catSeleccionada]) {
            inputUnidad.value = mapaUnidades[catSeleccionada];
        } else {
            inputUnidad.value = ''; // Si es una categoría externa que no tenemos registrada localmente
        }
    }

    // Validación estética contra errores de fecha de vencimiento (previene el popup de localhost:3000)
    function validarFechaVencimiento() {
        const inputFec = document.getElementById('edit_fecha_caducidad').value;
        const contenedorAlerta = document.getElementById('alerta-error-editar');
        const textoAlerta = document.getElementById('alerta-error-texto-editar');

        if (inputFec) {
            const fechaHoy = new Date().toISOString().split('T')[0];
            if (inputFec < fechaHoy) {
                textoAlerta.textContent = 'La fecha de caducidad no puede ser anterior al día de hoy.';
                contenedorAlerta.style.display = 'block';
                return false; // Bloquea el envío del formulario
            }
        }
        return true;
    }

    function validarPositivo(input) {
        if (input.value < 0 || input.value === "") {
            input.value = 0;
        }
    }
</script>
</body>
</html>