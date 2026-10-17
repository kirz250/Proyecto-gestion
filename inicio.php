<?php
// 1. INICIAR SESIÓN GLOBAL (Estricta y primera línea real del archivo)
ini_set('session.cookie_path', '/'); 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. GUARDIÁN DE SEGURIDAD CENTRALIZADO (Antes de cualquier salida de texto o HTML)
include(__DIR__ . "/iniciar sesion/verificar_admin.php");

// Asegura que $conexion exista
include(__DIR__ . "/registrar_producto/conexion_anadir.php"); 

// ==========================================
// DEFINICIÓN DE CLASES (MODELO DE NEGOCIO)
// ==========================================

class Producto {
    private $db;
    public $id_producto;
    public $nombre;
    public $id_categoria;
    public $estado;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Obtiene todos los productos activos de la base de datos
     */
    public function obtenerTodosActivos() {
        $query = "SELECT p.*, c.nombre_categoria, c.unidad_predeterminada 
                  FROM productos.productos p 
                  LEFT JOIN productos.categorias_maestras c ON p.id_categoria = c.id_categoria 
                  WHERE p.estado = 1 OR p.estado IS NULL";
        return $this->db->query($query);
    }
}

class Stock {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Obtiene el conteo de productos con existencias físicas
     */
    public function obtenerConteoExistencias() {
        $q = $this->db->query("SELECT COUNT(*) AS total FROM productos.stock WHERE cantidad_actual > 0");
        $r = $q->fetch_assoc();
        return $r['total'] ?? 0;
    }

    /**
     * Obtiene el conteo de productos en riesgo (vencimiento de 8 a 29 días)
     */
    public function obtenerConteoEnRiesgo() {
        $q = $this->db->query("SELECT COUNT(*) AS total 
                                FROM productos.stock 
                                WHERE fecha_caducidad > DATE_ADD(CURDATE(), INTERVAL 7 DAY) 
                                AND fecha_caducidad <= DATE_ADD(CURDATE(), INTERVAL 29 DAY)
                                AND cantidad_actual > 0");
        $r = $q->fetch_assoc();
        return $r['total'] ?? 0;
    }

    /**
     * Obtiene el conteo de productos en excelente estado (30 o más días para vencer)
     */
    public function obtenerConteoExcelente() {
        $q = $this->db->query("SELECT COUNT(DISTINCT s.id_producto) AS total 
                               FROM productos.stock s 
                               WHERE DATEDIFF(s.fecha_caducidad, CURDATE()) >= 30
                               AND s.cantidad_actual > 0");
        $r = $q->fetch_assoc();
        return $r['total'] ?? 0;
    }

    /**
     * Obtiene el conteo de productos caducados, agotados o por vencer en menos de 7 días
     */
    public function obtenerConteoCaducadosOAgotados() {
        $q = $this->db->query("SELECT COUNT(*) AS total FROM productos.stock 
                               WHERE fecha_caducidad <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) 
                               OR cantidad_actual = 0");
        $r = $q->fetch_assoc();
        return $r['total'] ?? 0;
    }

    /**
     * Obtiene el listado de insumos críticos de stock mínimo ordenados por gravedad de manera general
     */
    public function obtenerAlertasStockCritico() {
        $query = "SELECT p.nombre, s.cantidad_actual, s.stock_minimo, c.unidad_predeterminada,
                  (s.stock_minimo - s.cantidad_actual) AS gravedad
                  FROM productos.stock s 
                  INNER JOIN productos.productos p ON s.id_producto = p.id_producto
                  LEFT JOIN productos.categorias_maestras c ON p.id_categoria = c.id_categoria
                  WHERE s.cantidad_actual <= s.stock_minimo
                  ORDER BY gravedad DESC";
        $resultado = $this->db->query($query);
        $alertas = [];
        if ($resultado) {
            while ($fila = $resultado->fetch_assoc()) {
                $alertas[] = $fila;
            }
        }
        return $alertas;
    }

    /**
     * Obtiene el conteo por semáforo de vencimiento menor a 7 días (Color Rojo)
     */
    public function obtenerConteoRojoVencimiento() {
        $q = $this->db->query("SELECT COUNT(*) as total FROM productos.stock 
                                WHERE fecha_caducidad <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) OR cantidad_actual = 0");
        $r = $q->fetch_assoc();
        return $r['total'] ?? 0;
    }

    /**
     * Obtiene el conteo por semáforo de vencimiento entre 8 y 29 días (Color Amarillo)
     */
    public function obtenerConteoAmarilloVencimiento() {
        $q = $this->db->query("SELECT COUNT(*) as total FROM productos.stock 
                                WHERE fecha_caducidad BETWEEN DATE_ADD(CURDATE(), INTERVAL 8 DAY) AND DATE_ADD(CURDATE(), INTERVAL 29 DAY)
                                AND cantidad_actual > 0");
        $r = $q->fetch_assoc();
        return $r['total'] ?? 0;
    }
}

// ==========================================
// INSTANCIACIÓN DE OBJETOS Y PROCESAMIENTO
// ==========================================

if (isset($conexion)) {
    $productoModel = new Producto($conexion);
    $stockModel = new Stock($conexion);

    // Obtención de conteos de las tarjetas
    $total_existencia = $stockModel->obtenerConteoExistencias();
    $total_riesgo     = $stockModel->obtenerConteoEnRiesgo();
    $total_bueno      = $stockModel->obtenerConteoExcelente();
    $total_caducados  = $stockModel->obtenerConteoCaducadosOAgotados();

    // Alertas de Stock Bajo ordenados por Gravedad
    $todos_los_bajos = $stockModel->obtenerAlertasStockCritico();

    // Top 5 de Gravedad de Stock para la gráfica
    $datos_grafica_top5 = array_slice($todos_los_bajos, 0, 5);

    $nombres_bajo_chart       = array_column($datos_grafica_top5, 'nombre');
    $cantidades_bajo_chart    = array_column($datos_grafica_top5, 'cantidad_actual');
    $stock_minimos_bajo_chart = array_column($datos_grafica_top5, 'stock_minimo');
    $unidades_bajo_chart      = array_column($datos_grafica_top5, 'unidad_predeterminada');

    // Datos para gráfica de pastel (Semáforo de Vencimiento)
    $conteo_rojo     = $stockModel->obtenerConteoRojoVencimiento();
    $conteo_amarillo = $stockModel->obtenerConteoAmarilloVencimiento();

} else {
    $total_existencia = $total_riesgo = $total_bueno = $total_caducados = 0;
    $nombres_bajo_chart = $cantidades_bajo_chart = $stock_minimos_bajo_chart = $unidades_bajo_chart = [];
    $todos_los_bajos = [];
    $conteo_rojo = $conteo_amarillo = 0;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Dashboard - PAE</title>
    <link href="startbootstrap-sb-admin-gh-pages/css/styles.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <script src="fontawesome-free-7.2.0-web/js/all.js"></script>
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
        <a class="navbar-brand ps-3" href="inicio.php">Nueva Toledo</a>
        <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!"><i class="fas fa-bars"></i></button>
        
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
                        <a class="nav-link active" href="inicio.php">
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
                                <a class="nav-link" href="menu/crear_receta.php">Crear nueva receta</a>
                                <a class="nav-link" href="menu/lista_recetas.php">Planificar menú</a>
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
                        <a class="nav-link" href="registrar_producto/productos.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-chart-area"></i></div>
                            Catálogo de Insumos
                        </a>
                        <a class="nav-link" href="registrar_producto/stock.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-table"></i></div>
                            Stock
                        </a>
                    </div>
                </div>
                
                <div class="sb-sidenav-footer">
                    <div class="small">Iniciado como:</div>
                    <span class="text-white fw-bold"><?php echo htmlspecialchars($_SESSION['usuario']); ?></span>
                    <div class="small text-muted" style="font-size: 0.75rem; text-transform: uppercase;">
                        Rol: <?php echo htmlspecialchars($_SESSION['role'] ?? $_SESSION['rol'] ?? 'Usuario'); ?>
                    </div>
                </div>
            </nav>
        </div>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <div class="d-flex align-items-center justify-content-between mt-4 mb-2">
                        <div class="d-flex align-items-center gap-3">
                            <h1 class="mb-0">U.E "Nueva Toledo"</h1>
                            <!-- BOTÓN MISMOS ESTILOS Y ANIMACIÓN -->
                            <button type="button" class="btn btn-warning text-dark fw-bold rounded-pill shadow-sm px-3 py-1 btn-ayuda-destacado" data-bs-toggle="modal" data-bs-target="#modalGuiaDashboard">
                                <i class="fas fa-circle-question fa-bounce text-dark me-1"></i> ¿Cómo se usa el sistema?
                            </button>
                        </div>
                    </div>

                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item active">PAE — ¡Bienvenido de vuelta, <strong><?php echo htmlspecialchars($_SESSION['usuario']); ?></strong>!</li>
                    </ol>

                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card bg-primary text-white h-100 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="text-white-50 small text-uppercase fw-bold">Insumos en existencia</div>
                                            <h2 class="display-6 fw-bold mb-0"><?php echo $total_existencia; ?></h2>
                                        </div>
                                        <div class="fs-1 text-white-50"><i class="fas fa-boxes"></i></div>
                                    </div>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between bg-dark bg-opacity-10">
                                   <a class="small text-white stretched-link text-decoration-none" href="#" data-bs-toggle="modal" data-bs-target="#modalExistencia">Detalles</a>
                                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card bg-warning text-white h-100 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="text-white-50 small text-uppercase fw-bold">Insumos en riesgo</div>
                                            <h2 class="display-6 fw-bold mb-0"><?php echo $total_riesgo; ?></h2>
                                        </div>
                                        <div class="fs-1 text-white-50"><i class="fas fa-exclamation-triangle"></i></div>
                                    </div>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between bg-dark bg-opacity-10">
                                   <a class="small text-white stretched-link text-decoration-none" href="#" data-bs-toggle="modal" data-bs-target="#modalRiesgo">Detalles</a>
                                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card bg-success text-white h-100 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="text-white-50 small text-uppercase fw-bold">Insumos en excelente estado</div>
                                            <h2 class="display-6 fw-bold mb-0"><?php echo $total_bueno; ?></h2>
                                        </div>
                                        <div class="fs-1 text-white-50"><i class="fas fa-check-circle"></i></div>
                                    </div>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between bg-dark bg-opacity-10">
                                    <a class="small text-white stretched-link text-decoration-none" href="#" data-bs-toggle="modal" data-bs-target="#modalExcelente">Detalles</a>
                                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card bg-danger text-white h-100 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="text-white-50 small text-uppercase fw-bold">Insumos caducados</div>
                                            <h2 class="display-6 fw-bold mb-0"><?php echo $total_caducados; ?></h2>
                                        </div>
                                        <div class="fs-1 text-white-50"><i class="fas fa-calendar-times"></i></div>
                                    </div>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between bg-dark bg-opacity-10">
                                    <a class="small text-white stretched-link text-decoration-none" href="#" data-bs-toggle="modal" data-bs-target="#modalCaducado">Detalles</a>
                                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-xl-6">
                            <div class="card mb-4">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-chart-bar me-1"></i> Top 5 Críticos de Stock</span>
                                    <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalTodosBajos">
                                        <i class="fas fa-list-ol"></i> Ver alertas completas (<?= count($todos_los_bajos) ?>)
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div style="position: relative; height: 350px; width: 100%;">
                                        <canvas id="chartStockMin"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <i class="fas fa-chart-pie me-1"></i> Alerta de Vencimiento
                                </div>
                                <div class="card-body">
                                    <div style="position: relative; height: 350px; width: 100%;">
                                        <canvas id="chartVencimiento"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-table me-1"></i> Vista Previa de Catálogo
                        </div>
                        <div class="card-body">
                            <table id="miTabla" class="table">
                                <thead style="background-color: #2848b1; color: white;">
                                    <tr>
                                        <th>#</th> 
                                        <th>ID</th> 
                                        <th>NOMBRE</th> 
                                        <th>CATEGORIA</th> 
                                        <th>UNIDAD</th> 
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    if (isset($productoModel)) {
                                        $sql_tabla = $productoModel->obtenerTodosActivos();
                                        if ($sql_tabla) {
                                            $contador_filas = 1;
                                            while($datos = $sql_tabla->fetch_object()){ ?>
                                            <tr>
                                                <th><?= $contador_filas++ ?></th> 
                                                <td><?= htmlspecialchars($datos->id_producto) ?></td> 
                                                <td><?= htmlspecialchars($datos->nombre) ?></td> 
                                                <td><?= htmlspecialchars($datos->nombre_categoria ?? 'Sin Categoría') ?></td> 
                                                <td><?= htmlspecialchars($datos->unidad_predeterminada ?? '-') ?></td> 
                                            </tr>
                                            <?php }
                                        }
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
                        <div class="text-muted">Copyright &copy; Nueva Toledo 2026</div>
                        <div>
                            <a href="#">Privacy Policy</a>
                            &middot;
                            <a href="#">Terms &amp; Conditions</a>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- MODAL GUÍA GENERAL DE ESTRUCTURA Y FLUJO DEL SISTEMA PAE -->
    <div class="modal fade" id="modalGuiaDashboard" tabindex="-1" aria-labelledby="modalGuiaDashboardLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="modalGuiaDashboardLabel">
                        <i class="fas fa-compass me-2"></i>Guía de Uso del Sistema PAE
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body p-4" style="max-height: 75vh; overflow-y: auto;">
                    
                    <!-- 1. RUTA Y ORDEN DE USO CON ENLACES DIRECTOS -->
                    <div class="mb-4">
                        <h6 class="fw-bold text-primary mb-3"><i class="fas fa-route me-1"></i> 1. Flujo de Trabajo Recomendado (Haz clic para ir al módulo)</h6>
                        <div class="list-group">
                            <a href="registrar_producto/productos.php" class="list-group-item list-group-item-action border-1 rounded mb-2 shadow-sm">
                                <div class="d-flex w-100 justify-content-between align-items-center">
                                    <h6 class="mb-1 text-primary fw-bold"><i class="fas fa-boxes me-2"></i>Paso 1: Catálogo de Insumos</h6>
                                    <i class="fas fa-arrow-right text-muted"></i>
                                </div>
                                <p class="mb-1 text-muted small">Registra de primero los alimentos o insumos básicos (ej. Harina, Aceite, Pollo) asociándolos a su categoría y unidad de medida fija.</p>
                            </a>

                            <a href="registrar_producto/stock.php" class="list-group-item list-group-item-action border-1 rounded mb-2 shadow-sm">
                                <div class="d-flex w-100 justify-content-between align-items-center">
                                    <h6 class="mb-1 text-primary fw-bold"><i class="fas fa-table me-2"></i>Paso 2: Stock / Inventario</h6>
                                    <i class="fas fa-arrow-right text-muted"></i>
                                </div>
                                <p class="mb-1 text-muted small">Registra la entrada física de las cantidades que ingresan al almacén de la institución, asignando la fecha de vencimiento y el inventario mínimo requerido.</p>
                            </a>

                            <a href="menu/crear_receta.php" class="list-group-item list-group-item-action border-1 rounded mb-2 shadow-sm">
                                <div class="d-flex w-100 justify-content-between align-items-center">
                                    <h6 class="mb-1 text-primary fw-bold"><i class="fas fa-utensils me-2"></i>Paso 3: Recetario y Planificación de Menú</h6>
                                    <i class="fas fa-arrow-right text-muted"></i>
                                </div>
                                <p class="mb-1 text-muted small">Crea los platillos combinando los alimentos previamente registrados en el catálogo. Luego, calcula el total de insumos necesarios ingresando la cantidad de personas a atender.</p>
                            </a>

                            <a href="/registrar_producto/reporte.php" class="list-group-item list-group-item-action border-1 rounded shadow-sm">
                                <div class="d-flex w-100 justify-content-between align-items-center">
                                    <h6 class="mb-1 text-primary fw-bold"><i class="fas fa-file-invoice me-2"></i>Paso 4: Reportes y Gestión de Usuarios</h6>
                                    <i class="fas fa-arrow-right text-muted"></i>
                                </div>
                                <p class="mb-1 text-muted small">Genera reportes para impresión sobre las existencias y movimientos, o administra las cuentas de usuario y claves del personal autorizado.</p>
                            </a>
                        </div>
                    </div>

                    <hr>

                    <!-- 2. INTERPRETAR GRÁFICAS Y TARJETAS (CON APERTURA DE MODALES) -->
                    <div>
                        <h6 class="fw-bold text-primary mb-3"><i class="fas fa-chart-line me-1"></i> 2. Interpretación e Interacción de Indicadores</h6>
                        <ul class="list-group list-group-flush small">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-chart-bar text-danger me-2"></i><b>Top 5 Críticos de Stock:</b> Muestra los alimentos con deficiencia de stock.</span>
                                <button class="btn btn-sm btn-outline-danger" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalTodosBajos">Ver Alertas</button>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-chart-pie text-warning me-2"></i><b>Alerta de Vencimiento:</b> Compara insumos caducados frente a riesgo moderado.</span>
                                <button class="btn btn-sm btn-outline-warning text-dark" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalRiesgo">Ver Riesgo</button>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-clone text-info me-2"></i><b>Tarjetas de Resumen:</b> Consulta el listado completo en existencia.</span>
                                <button class="btn btn-sm btn-outline-primary" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalExistencia">Ver Existencias</button>
                            </li>
                        </ul>
                    </div>

                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm px-4 rounded-pill" data-bs-dismiss="modal">Entendido</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL EXISTENCIAS -->
    <div class="modal fade" id="modalExistencia" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg"> 
            <div class="modal-content shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title d-flex align-items-center"><i class="fas fa-boxes me-2"></i> Insumos en Existencia</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Nombre del Insumo</th>
                                    <th class="text-center">Existencia Actual</th>
                                    <th class="text-center pe-4">Stock Mínimo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if (isset($conexion)) {
                                    $query = "SELECT p.nombre, s.cantidad_actual, s.stock_minimo, c.unidad_predeterminada 
                                              FROM productos.stock s 
                                              INNER JOIN productos.productos p ON s.id_producto = p.id_producto 
                                              LEFT JOIN productos.categorias_maestras c ON p.id_categoria = c.id_categoria
                                              WHERE s.cantidad_actual > 0";
                                    $resultado = $conexion->query($query);
                                    if ($resultado && $resultado->num_rows > 0) {
                                        while($fila = $resultado->fetch_assoc()) {
                                            $u = htmlspecialchars($fila['unidad_predeterminada'] ?? '');
                                            echo "<tr>
                                                    <td class='ps-4 fw-semibold'>" . htmlspecialchars($fila['nombre']) . "</td>
                                                    <td class='text-center'><span class='badge bg-primary px-3 py-2 fs-7'>{$fila['cantidad_actual']} {$u}</span></td>
                                                    <td class='text-center text-muted pe-4'><strong>{$fila['stock_minimo']}</strong> {$u}</td>
                                                  </tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='3' class='text-center text-muted py-4'>No hay productos registrados en existencia.</td></tr>";
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL RIESGO -->
    <div class="modal fade" id="modalRiesgo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content shadow-lg">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title d-flex align-items-center"><i class="fas fa-exclamation-triangle me-2"></i> Insumos en Riesgo (8-29 días)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Producto</th>
                                    <th class="text-center pe-4">Días restantes para vencer</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if (isset($conexion)) {
                                    $query = "SELECT p.nombre, DATEDIFF(s.fecha_caducidad, CURDATE()) AS dias_restantes 
                                              FROM productos.stock s 
                                              INNER JOIN productos.productos p ON s.id_producto = p.id_producto 
                                              WHERE DATEDIFF(s.fecha_caducidad, CURDATE()) BETWEEN 8 AND 29
                                              AND s.cantidad_actual > 0";
                                    $resultado = $conexion->query($query);
                                    if ($resultado && $resultado->num_rows > 0) {
                                        while($fila = $resultado->fetch_assoc()) {
                                            echo "<tr>
                                                    <td class='ps-4 fw-semibold'>" . htmlspecialchars($fila['nombre']) . "</td>
                                                    <td class='text-center pe-4'><span class='badge bg-warning text-dark px-3 py-2 fs-7'><i class='fas fa-clock me-1'></i> {$fila['dias_restantes']} días</span></td>
                                                  </tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='2' class='text-center text-muted py-4'>No hay productos en riesgo intermedio de vencimiento.</td></tr>";
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL EXCELENTE -->
    <div class="modal fade" id="modalExcelente" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content shadow-lg">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title d-flex align-items-center"><i class="fas fa-check-circle me-2"></i> Insumos en Excelente Estado (&ge; 30 días)</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Producto</th>
                                    <th class="text-center pe-4">Días restantes óptimos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if (isset($conexion)) {
                                    $query = "SELECT p.nombre, DATEDIFF(s.fecha_caducidad, CURDATE()) AS dias_restantes 
                                              FROM productos.stock s 
                                              INNER JOIN productos.productos p ON s.id_producto = p.id_producto 
                                              WHERE DATEDIFF(s.fecha_caducidad, CURDATE()) >= 30
                                              AND s.cantidad_actual > 0";
                                    $resultado = $conexion->query($query);
                                    if ($resultado && $resultado->num_rows > 0) {
                                        while($fila = $resultado->fetch_assoc()) {
                                            echo "<tr>
                                                    <td class='ps-4 fw-semibold'>" . htmlspecialchars($fila['nombre']) . "</td>
                                                    <td class='text-center pe-4'><span class='badge bg-success px-3 py-2 fs-7'><i class='fas fa-shield-alt me-1'></i> {$fila['dias_restantes']} días</span></td>
                                                  </tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='2' class='text-center text-muted py-4'>No hay productos registrados en estado óptimo.</td></tr>";
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL CADUCADOS -->
    <div class="modal fade" id="modalCaducado" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content shadow-lg">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title d-flex align-items-center"><i class="fas fa-calendar-times me-2"></i> Insumos Caducados o Agotados</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Producto</th>
                                    <th class="text-center">Existencia</th>
                                    <th class="text-center pe-4">Estado / Vencimiento</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if (isset($conexion)) {
                                    $query = "SELECT p.nombre, s.cantidad_actual, s.fecha_caducidad, c.unidad_predeterminada 
                                              FROM productos.stock s 
                                              INNER JOIN productos.productos p ON s.id_producto = p.id_producto 
                                              LEFT JOIN productos.categorias_maestras c ON p.id_categoria = c.id_categoria
                                              WHERE s.fecha_caducidad <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) OR s.cantidad_actual = 0
                                              ORDER BY s.fecha_caducidad ASC";
                                    $resultado = $conexion->query($query);
                                    if ($resultado && $resultado->num_rows > 0) {
                                        while($fila = $resultado->fetch_assoc()) {
                                            $badge_estado = "";
                                            
                                            if ($fila['cantidad_actual'] == 0) {
                                                $badge_estado = "<span class='badge bg-dark text-white px-2 py-1 fs-8'>AGOTADO</span>";
                                            } else {
                                                $fecha_cad = strtotime($fila['fecha_caducidad']);
                                                $fecha_hoy = strtotime(date('Y-m-d'));
                                                
                                                if ($fecha_cad > $fecha_hoy) {
                                                    $badge_estado = "<span class='badge bg-warning text-dark px-2 py-1 fs-8'>POR VENCER (" . htmlspecialchars($fila['fecha_caducidad']) . ")</span>";
                                                } else {
                                                    $badge_estado = "<span class='badge bg-danger text-white px-2 py-1 fs-8'>CADUCADO (" . htmlspecialchars($fila['fecha_caducidad']) . ")</span>";
                                                }
                                            }
                                            
                                            $u = htmlspecialchars($fila['unidad_predeterminada'] ?? '');

                                            echo "<tr>
                                                    <td class='ps-4 fw-semibold'>" . htmlspecialchars($fila['nombre']) . "</td>
                                                    <td class='text-center fw-bold'>{$fila['cantidad_actual']} <span class='text-muted fw-normal'>{$u}</span></td>
                                                    <td class='text-center pe-4'>{$badge_estado}</td>
                                                  </tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='3' class='text-center text-muted py-4'>No hay productos caducados o agotados.</td></tr>";
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL ALERTAS COMPLETAS -->
    <div class="modal fade" id="modalTodosBajos" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content shadow-lg">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title"><i class="fas fa-list-ol me-2"></i> Reporte General de Alertas (Stock Bajo o Crítico)</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Producto</th>
                                <th class="text-center">Existencia Actual</th>
                                <th class="text-center">Stock Mínimo</th>
                                <th class="text-center pe-4">Gravedad del Faltante</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($todos_los_bajos) > 0): ?>
                                <?php foreach ($todos_los_bajos as $item): 
                                    $u = htmlspecialchars($item['unidad_predeterminada'] ?? ''); ?>
                                    <tr>
                                        <td class="ps-4 fw-semibold"><?= htmlspecialchars($item['nombre']) ?></td>
                                        <td class="text-center text-danger"><strong><?= $item['cantidad_actual'] ?></strong> <?= $u ?></td>
                                        <td class="text-center text-muted"><?= $item['stock_minimo'] ?> <?= $u ?></td>
                                        <td class="text-center pe-4">
                                            <span class="badge bg-danger px-2 py-1 fs-8">
                                                Faltan <?= number_format($item['gravedad'], 2) ?> <?= $u ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center text-muted py-4">Excelente: ¡No hay alertas de stock bajo actualmente!</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="startbootstrap-sb-admin-gh-pages/js/scripts.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        const nombresBajos = <?php echo json_encode($nombres_bajo_chart); ?>;
        const cantidadesBajos = <?php echo json_encode($cantidades_bajo_chart); ?>;
        const minimosBajos = <?php echo json_encode($stock_minimos_bajo_chart); ?>;
        const unidadesBajos = <?php echo json_encode($unidades_bajo_chart); ?>;

        // 1. Gráfica de Barras para el Top 5 Crítico de Stock
        const ctxStock = document.getElementById('chartStockMin').getContext('2d');
        const chartStock = new Chart(ctxStock, {
            type: 'bar',
            data: {
                labels: nombresBajos,
                datasets: [
                    {
                        label: 'Cantidad Actual',
                        data: cantidadesBajos,
                        backgroundColor: 'rgba(220, 53, 69, 0.75)',
                        borderColor: 'rgba(220, 53, 69, 1)',
                        borderWidth: 1
                    },
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                let index = context.dataIndex;
                                let unit = unidadesBajos[index] ? ' ' + unidadesBajos[index] : '';
                                if (label) {
                                    label += ': ';
                                }
                                label += context.parsed.y + unit;
                                return label;
                            }
                        }
                    }
                }
            }
        });

       // 2. Gráfica de Pastel para el Semáforo de Vencimientos
        const ctxVencimiento = document.getElementById('chartVencimiento').getContext('2d');
        const chartVencimiento = new Chart(ctxVencimiento, {
            type: 'pie',
            data: {
                labels: ['Caducados o Agotados', 'Riesgo Moderado (8 a 29 días)'],
                datasets: [{
                    data: [<?= $total_caducados ?>, <?= $conteo_amarillo ?>],
                    backgroundColor: ['#dc3545', '#ffc107'],
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                onClick: (event, elements) => {
                    if (elements && elements.length > 0) {
                        const index = elements[0].index; 
                        
                        let modalId = '';
                        if (index === 0) {
                            modalId = 'modalCaducado';
                        } else if (index === 1) {
                            modalId = 'modalRiesgo';
                        }

                        if (modalId) {
                            const modalElement = document.getElementById(modalId);
                            if (modalElement) {
                                const modalBootstrap = new bootstrap.Modal(modalElement);
                                modalBootstrap.show();
                            }
                        }
                    }
                },
                onHover: (event, chartElement) => {
                    event.native.target.style.cursor = chartElement.length ? 'pointer' : 'default';
                }
            }
        });
    </script>
</body>
</html>