<?php
// 1. INICIAR SESIÓN GLOBAL
ini_set('session.cookie_path', '/'); 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. GUARDIÁN DE SEGURIDAD CENTRALIZADO
include(dirname(__DIR__) . "/iniciar sesion/verificar_admin.php");

// 3. CONEXIÓN A LA BASE DE DATOS
include(__DIR__ . "/conexion_anadir.php"); 

// --- CONFIGURACIÓN DE FILTROS ---
$tipo_reporte = $_POST['tipo_reporte'] ?? 'inventario';
$filtro_fecha = $_POST['filtro_fecha'] ?? 'todos';
$fecha_inicio = $_POST['fecha_inicio'] ?? '';
$fecha_fin = $_POST['fecha_fin'] ?? '';
$filtro_caducidad = $_POST['filtro_caducidad'] ?? 'todos';

// 📌 CONSTRUCCIÓN DEL TEXTO DE FECHA (Dinámico y legible)
$texto_periodo = "";
switch ($filtro_fecha) {
    case 'hoy':
        $texto_periodo = "de Hoy";
        break;
    case 'semana':
        $texto_periodo = "de los Últimos 7 Días";
        break;
    case 'mes':
        $texto_periodo = "de los Últimos 30 Días";
        break;
    case 'personalizado':
        if (!empty($fecha_inicio) && !empty($fecha_fin)) {
            $f_ini_formato = date("d/m/Y", strtotime($fecha_inicio));
            $f_fin_formato = date("d/m/Y", strtotime($fecha_fin));
            $texto_periodo = "del Periodo del " . $f_ini_formato . " al " . $f_fin_formato;
        } else {
            $texto_periodo = "de Rango Personalizado";
        }
        break;
    case 'todos':
    default:
        $texto_periodo = "Histórico General";
        break;
}

// 📌 CONSTRUCCIÓN DEL TÍTULO DINÁMICO ULTRA-ESPECÍFICO
$titulo_dinamico = "";
switch ($tipo_reporte) {
    case 'inventario':
        // Evaluamos el subtipo de caducidad seleccionado
        if ($filtro_caducidad == 'caducado') {
            $titulo_dinamico = "Reporte de Insumos Caducados " . $texto_periodo;
        } elseif ($filtro_caducidad == 'riesgo') {
            $titulo_dinamico = "Reporte de Insumos en Riesgo de Vencimiento " . $texto_periodo;
        } elseif ($filtro_caducidad == 'estable') {
            $titulo_dinamico = "Reporte de Insumos en Perfecto Estado " . $texto_periodo;
        } else {
            $titulo_dinamico = "Reporte de Almacén y Stock General " . $texto_periodo;
        }
        break;
        
    case 'platillos':
        $titulo_dinamico = "Reporte de Platillos y Recetas Elaboradas " . $texto_periodo;
        break;
        
    case 'descuentos':
        $titulo_dinamico = "Reporte de Insumos Descontados (Consumos) " . $texto_periodo;
        break;
        
    case 'ingresos':
        $titulo_dinamico = "Reporte de Productos e Insumos Ingresados " . $texto_periodo;
        break;
        
    default:
        $titulo_dinamico = "Reporte del Sistema " . $texto_periodo;
        break;
}

// Variables de condición de fecha para la base de datos
$condicion_fecha_hc = ""; 
$condicion_fecha_hm = ""; 

if ($filtro_fecha == 'personalizado' && !empty($fecha_inicio) && !empty($fecha_fin)) {
    $condicion_fecha_hc = " AND DATE(hc.fecha_elaboracion) BETWEEN '$fecha_inicio' AND '$fecha_fin'";
    $condicion_fecha_hm = " AND DATE(hm.fecha) BETWEEN '$fecha_inicio' AND '$fecha_fin'";
} elseif ($filtro_fecha == 'hoy') {
    $condicion_fecha_hc = " AND DATE(hc.fecha_elaboracion) = CURDATE()";
    $condicion_fecha_hm = " AND DATE(hm.fecha) = CURDATE()";
} elseif ($filtro_fecha == 'semana') {
    $condicion_fecha_hc = " AND hc.fecha_elaboracion >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    $condicion_fecha_hm = " AND hm.fecha >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
} elseif ($filtro_fecha == 'mes') {
    $condicion_fecha_hc = " AND hc.fecha_elaboracion >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    $condicion_fecha_hm = " AND hm.fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
}

// --- CONDICIÓN SQL DE CADUCIDAD ---
$condicion_caducidad = "";
if ($tipo_reporte == 'inventario') {
    if ($filtro_caducidad == 'caducado') {
        $condicion_caducidad = " AND (s.fecha_caducidad IS NOT NULL AND s.fecha_caducidad <= CURDATE() OR s.cantidad_actual = 0)";
    } elseif ($filtro_caducidad == 'riesgo') {
        $condicion_caducidad = " AND s.fecha_caducidad IS NOT NULL AND s.fecha_caducidad > CURDATE() AND s.fecha_caducidad <= DATE_ADD(CURDATE(), INTERVAL 29 DAY) AND s.cantidad_actual > 0";
    } elseif ($filtro_caducidad == 'estable') {
        $condicion_caducidad = " AND s.cantidad_actual > 0 AND (s.fecha_caducidad IS NULL OR DATEDIFF(s.fecha_caducidad, CURDATE()) >= 30)";
    }
}

// --- CONSULTA PARA LA GRÁFICA DE DONA ---
$sql_grafica = "SELECT 
    SUM(CASE WHEN (s.fecha_caducidad IS NOT NULL AND s.fecha_caducidad <= CURDATE()) OR s.cantidad_actual = 0 THEN 1 ELSE 0 END) as total_caducados,
    SUM(CASE WHEN s.fecha_caducidad IS NOT NULL AND s.fecha_caducidad > CURDATE() AND s.fecha_caducidad <= DATE_ADD(CURDATE(), INTERVAL 29 DAY) AND s.cantidad_actual > 0 THEN 1 ELSE 0 END) as total_riesgo,
    SUM(CASE WHEN s.cantidad_actual > 0 AND (s.fecha_caducidad IS NULL OR DATEDIFF(s.fecha_caducidad, CURDATE()) >= 30) THEN 1 ELSE 0 END) as total_estables
    FROM stock s
    INNER JOIN productos p ON s.id_producto = p.id_producto";
$res_grafica = $conexion->query($sql_grafica);
$stats = $res_grafica->fetch_assoc();

$cant_caducados = $stats['total_caducados'] ?? 0;
$cant_riesgo = $stats['total_riesgo'] ?? 0;
$cant_estables = $stats['total_estables'] ?? 0;

// --- ESTADÍSTICAS DE TURNO DE PREPARACIÓN ---
$cant_manana = 0;
$cant_tarde = 0;
if ($tipo_reporte == 'platillos') {
    $sql_turnos = "SELECT 
        SUM(CASE WHEN hc.hora_elaboracion < '13:00:00' THEN 1 ELSE 0 END) as total_manana,
        SUM(CASE WHEN hc.hora_elaboracion >= '13:00:00' THEN 1 ELSE 0 END) as total_tarde
        FROM historial_cocina hc
        WHERE 1=1" . $condicion_fecha_hc;
    $res_turnos = $conexion->query($sql_turnos);
    if ($res_turnos) {
        $stats_turnos = $res_turnos->fetch_assoc();
        $cant_manana = $stats_turnos['total_manana'] ?? 0;
        $cant_tarde = $stats_turnos['total_tarde'] ?? 0;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title><?php echo htmlspecialchars($titulo_dinamico); ?> - PAE</title>
    <link href="../startbootstrap-sb-admin-gh-pages/css/styles.css" rel="stylesheet" />
    <link href="../css/styles.css" rel="stylesheet" />
    <script src="../fontawesome-free-7.2.0-web/js/all.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        .fila-vencida { background-color: #fde8e8 !important; color: #9b1c1c !important; }
        .fila-por-vencer { background-color: #fef3c7 !important; color: #92400e !important; }
        
        .btn-ayuda-destacado {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .btn-ayuda-destacado:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 193, 7, 0.4) !important;
        }

        @media print {
            .sb-topnav, #layoutSidenav_nav, .card-filtro, .btn-print-area, .breadcrumb, .no-print, .btn-ayuda-destacado { display: none !important; }
            #layoutSidenav_content { padding: 0 !important; margin: 0 !important; left: 0 !important; }
            .container-fluid { width: 100% !important; padding: 0 !important; }
            .card { border: none !important; box-shadow: none !important; }
            .col-lg-8 { width: 100% !important; }
        }
    </style>
</head>
<body class="sb-nav-fixed">
    <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
        <a class="navbar-brand ps-3" href="../inicio.php">Nueva Toledo</a>
        <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!"><i class="fas fa-bars"></i></button>
        <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0"></form>
        <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="text-white me-2 small"><?php echo htmlspecialchars($_SESSION['usuario'] ?? 'Usuario'); ?></span>
                    <i class="fas fa-user fa-fw"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                    <li><a class="dropdown-item" href="../iniciar sesion/logout.php">Cerrar sesión</a></li>
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
                        <a class="nav-link" href="../inicio.php"><div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>Inicio</a>
                        <div class="sb-sidenav-menu-heading">Operación Diaria</div>
                        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseLayouts" aria-expanded="false" aria-controls="collapseLayouts">
                            <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>Recetario<div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                        </a>
                        <div class="collapse" id="collapseLayouts" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                            <nav class="sb-sidenav-menu-nested nav">
                                <a class="nav-link" href="../menu/crear_receta.php">Crear nueva receta</a>
                                <a class="nav-link" href="../menu/lista_recetas.php">Planificar menú</a>
                            </nav>
                        </div>
                        <a class="nav-link active" href="#" data-bs-toggle="collapse" data-bs-target="#collapsePages" aria-expanded="true" aria-controls="collapsePages">
                            <div class="sb-nav-link-icon"><i class="fas fa-book-open"></i></div>Reportes<div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                        </a>
                        <div class="collapse show" id="collapsePages" aria-labelledby="headingTwo" data-bs-parent="#sidenavAccordion">
                            <nav class="sb-sidenav-menu-nested nav">
                                <a class="nav-link active" href="reporte.php">Reportes</a>
                                <a class="nav-link" href="gestionar_usuarios.php">Gestionar usuarios</a>
                            </nav>
                        </div>
                        <div class="sb-sidenav-menu-heading">Insumos</div>
                        <a class="nav-link" href="productos.php"><div class="sb-nav-link-icon"><i class="fas fa-chart-area"></i></div>Catálogo de Insumos</a>
                        <a class="nav-link" href="stock.php"><div class="sb-nav-link-icon"><i class="fas fa-table"></i></div>Stock</a>
                    </div>
                </div>
                <div class="sb-sidenav-footer">
                    <div class="small">Iniciado como:</div>
                    <span class="text-white fw-bold"><?php echo htmlspecialchars($_SESSION['usuario'] ?? 'Usuario'); ?></span>
                </div>
            </nav>
        </div>

        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <div class="d-flex align-items-center justify-content-between mt-4 mb-2">
                        <div class="d-flex align-items-center gap-3">
                            <h1 class="mb-0"><?php echo htmlspecialchars($titulo_dinamico); ?></h1>
                            <!-- BOTÓN INTEGRADO DE AYUDA -->
                            <button type="button" class="btn btn-warning text-dark fw-bold rounded-pill shadow-sm px-3 py-1 btn-ayuda-destacado" data-bs-toggle="modal" data-bs-target="#modalGuiaReportes">
                                <i class="fas fa-circle-question fa-bounce text-dark me-1"></i> ¿Cómo usar los reportes?
                            </button>
                        </div>
                    </div>
                    
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="../inicio.php">Inicio</a></li>
                        <li class="breadcrumb-item active">Módulo de Reportes Avanzados</li>
                    </ol>

                    <!-- PANEL DE PARÁMETROS -->
                    <div class="card mb-4 shadow-sm card-filtro">
                        <div class="card-header bg-light text-dark fw-bold">
                            <i class="fas fa-filter me-1"></i> Panel de Parámetros del Reporte
                        </div>
                        <div class="card-body">
                            <form action="reporte.php" method="POST" class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">Área del Reporte:</label>
                                    <select name="tipo_reporte" id="tipo_reporte" class="form-select" onchange="toggleFiltroCaducidad()">
                                        <option value="inventario" <?php echo $tipo_reporte == 'inventario' ? 'selected' : ''; ?>>📋 Estado de Almacén y Caducidades</option>
                                        <option value="platillos" <?php echo $tipo_reporte == 'platillos' ? 'selected' : ''; ?>>🍳 Platillos y Recetas Elaboradas</option>
                                        <option value="descuentos" <?php echo $tipo_reporte == 'descuentos' ? 'selected' : ''; ?>>📉 Insumos Descontados (Consumo)</option>
                                        <option value="ingresos" <?php echo $tipo_reporte == 'ingresos' ? 'selected' : ''; ?>>📈 Productos e Insumos Ingresados</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-3" id="contenedor_filtro_caducidad">
                                    <label class="form-label fw-bold">Estado de Caducidad:</label>
                                    <select name="filtro_caducidad" class="form-select">
                                        <option value="todos" <?php echo $filtro_caducidad == 'todos' ? 'selected' : ''; ?>>Todos los insumos</option>
                                        <option value="caducado" <?php echo $filtro_caducidad == 'caducado' ? 'selected' : ''; ?>>❌ Solo Caducados</option>
                                        <option value="riesgo" <?php echo $filtro_caducidad == 'riesgo' ? 'selected' : ''; ?>>⚠️ En riesgo (Vence ≤ 29 días)</option>
                                        <option value="estable" <?php echo $filtro_caducidad == 'estable' ? 'selected' : ''; ?>>✅ En perfecto estado (Estables)</option>
                                    </select>
                                </div>

                                <div class="col-md-2" id="contenedor_filtro_fecha">
                                    <label class="form-label fw-bold">Período de Tiempo:</label>
                                    <select name="filtro_fecha" id="filtro_fecha" class="form-select" onchange="toggleRangoFechas()">
                                        <option value="todos" <?php echo $filtro_fecha == 'todos' ? 'selected' : ''; ?>>Todo el historial</option>
                                        <option value="hoy" <?php echo $filtro_fecha == 'hoy' ? 'selected' : ''; ?>>Solo lo de hoy</option>
                                        <option value="semana" <?php echo $filtro_fecha == 'semana' ? 'selected' : ''; ?>>Últimos 7 días</option>
                                        <option value="mes" <?php echo $filtro_fecha == 'mes' ? 'selected' : ''; ?>>Últimos 30 días</option>
                                        <option value="personalizado" <?php echo $filtro_fecha == 'personalizado' ? 'selected' : ''; ?>>📅 Rango personalizado</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-3 row g-2" id="seccion_rango_manual" style="display: none;">
                                    <div class="col-6">
                                        <label class="form-label small fw-bold">Desde:</label>
                                        <input type="date" name="fecha_inicio" class="form-control" value="<?php echo htmlspecialchars($fecha_inicio); ?>">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-bold">Hasta:</label>
                                        <input type="date" name="fecha_fin" class="form-control" value="<?php echo htmlspecialchars($fecha_fin); ?>">
                                    </div>
                                </div>

                                <div class="col d-flex align-items-end justify-content-end">
                                    <button type="submit" class="btn btn-primary shadow-sm px-4">
                                        <i class="fas fa-sync me-1"></i> Generar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- CONTENEDOR DEL DOCUMENTO -->
                    <div class="card mb-4 shadow-sm">
                        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-file-invoice me-1"></i> Documento Generado</span>
                            <button class="btn btn-sm btn-light btn-print-area" onclick="window.print()">
                                <i class="fas fa-print me-1"></i> Imprimir / PDF
                            </button>
                        </div>
                        <div class="card-body">
                            
                            <?php if ($tipo_reporte == 'inventario'): ?>
                                <div class="row">
                                    <div class="col-xl-8 col-lg-7 col-md-12">
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-hover align-middle mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Insumo / Producto</th>
                                                        <th>Categoría</th>
                                                        <th class="text-center">Existencia</th>
                                                        <th class="text-center">Mínimo</th>
                                                        <th class="text-center">Fecha Caducidad</th>
                                                        <th class="text-center">Estado</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $sql = "SELECT p.nombre, c.unidad_predeterminada, s.cantidad_actual, s.stock_minimo, s.fecha_caducidad, c.nombre_categoria 
                                                            FROM stock s 
                                                            INNER JOIN productos p ON s.id_producto = p.id_producto 
                                                            LEFT JOIN categorias_maestras c ON p.id_categoria = c.id_categoria 
                                                            WHERE 1=1 {$condicion_caducidad}
                                                            ORDER BY s.fecha_caducidad ASC";
                                                    $res = $conexion->query($sql);
                                                    if ($res && $res->num_rows > 0) {
                                                        while ($row = $res->fetch_assoc()) {
                                                            $clase = ""; 
                                                            $badge = ""; 
                                                            $fecha = $row['fecha_caducidad'];
                                                            $cantidad = $row['cantidad_actual'];
                                                            
                                                            if (($fecha && strtotime($fecha) <= strtotime(date('Y-m-d'))) || $cantidad == 0) {
                                                                $clase = "fila-vencida"; 
                                                                $badge = "<span class='badge bg-danger'>Caducado</span>";
                                                            } elseif ($fecha && strtotime($fecha) <= strtotime('+29 days') && $cantidad > 0) {
                                                                $clase = "fila-por-vencer"; 
                                                                $badge = "<span class='badge bg-warning text-dark'>Por vencer</span>";
                                                            } else { 
                                                                $badge = "<span class='badge bg-success'>Estable</span>"; 
                                                            }
                                                            
                                                            $fecha_formateada = $fecha ? date("d/m/Y", strtotime($fecha)) : 'Sin fecha';
                                                            $unidad = htmlspecialchars($row['unidad_predeterminada'] ?? '');
                                                            
                                                            echo "<tr class='{$clase}'>
                                                                    <td><strong>".htmlspecialchars($row['nombre'])."</strong></td>
                                                                    <td>".htmlspecialchars($row['nombre_categoria'] ?? 'Sin categoría')."</td>
                                                                    <td class='text-center fw-bold'>{$cantidad} {$unidad}</td>
                                                                    <td class='text-center text-muted'>{$row['stock_minimo']} {$unidad}</td>
                                                                    <td class='text-center'>{$fecha_formateada}</td>
                                                                    <td class='text-center'>{$badge}</td>
                                                                  </tr>";
                                                        }
                                                    } else {
                                                        echo "<tr><td colspan='6' class='text-center text-muted py-3'>No hay insumos con el filtro seleccionado.</td></tr>";
                                                    }
                                                    ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    
                                    <div class="col-xl-4 col-lg-5 col-md-12 mt-4 mt-lg-0 no-print">
                                        <div class="card h-100 border-0 bg-light">
                                            <div class="card-body d-flex flex-column align-items-center justify-content-center text-center">
                                                <h6 class="card-title text-muted fw-bold mb-3">Distribución de Caducidades</h6>
                                                <div style="position: relative; height: 230px; width: 100%;">
                                                    <canvas id="graficoCaducidad"></canvas>
                                                </div>
                                                <div class="mt-3 small text-muted">
                                                    Muestra el estado global de todos los productos registrados en el inventario.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            <?php elseif ($tipo_reporte == 'platillos'): ?>
                                <div class="row mb-4">
                                    <div class="col-md-6 mb-3 mb-md-0">
                                        <div class="card border-0 bg-primary bg-gradient text-white shadow-sm h-100">
                                            <div class="card-body py-3 d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="text-white-50 text-uppercase fw-bold small mb-1">Preparadas en la Mañana</h6>
                                                    <h3 class="fw-bold mb-0"><?= $cant_manana ?> <span class="fs-6 fw-normal text-white-50">platos</span></h3>
                                                    <span class="text-white-50" style="font-size: 0.75rem;">De 12:00 AM a 12:59 PM</span>
                                                </div>
                                                <div class="fs-2 text-white-50"><i class="fas fa-sun"></i></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card border-0 text-white shadow-sm h-100" style="background: linear-gradient(135deg, #fd7e14, #f76707);">
                                            <div class="card-body py-3 d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="text-white-50 text-uppercase fw-bold small mb-1">Preparadas en la Tarde</h6>
                                                    <h3 class="fw-bold mb-0"><?= $cant_tarde ?> <span class="fs-6 fw-normal text-white-50">platos</span></h3>
                                                    <span class="text-white-50" style="font-size: 0.75rem;">De 1:00 PM a 11:59 PM</span>
                                                </div>
                                                <div class="fs-2 text-white-50"><i class="fas fa-cloud-sun"></i></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-striped table-hover align-middle mb-0">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Platillo / Receta</th>
                                                <th class="text-center">Personas/Porciones</th>
                                                <th class="text-center">Responsable</th>
                                                <th class="text-center">Fecha de Elaboración</th>
                                                <th class="text-center">Hora</th>
                                                <th class="text-center">Turno</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $sql = "SELECT rc.nombre_plato, hc.id_receta, hc.cantidad_personas, hm.usuario, hc.fecha_elaboracion, hc.hora_elaboracion 
                                                    FROM historial_cocina hc
                                                    INNER JOIN recetas_comida rc ON hc.id_receta = rc.id_receta 
                                                    LEFT JOIN historial_movimientos hm ON DATE(hc.fecha_elaboracion) = DATE(hm.fecha) AND hm.tipo_movimiento = 'EGRESO_RECETA'
                                                    WHERE 1=1" . $condicion_fecha_hc . " 
                                                    GROUP BY hc.id_receta, hc.fecha_elaboracion, hc.hora_elaboracion
                                                    ORDER BY hc.fecha_elaboracion DESC, hc.hora_elaboracion DESC";
                                            
                                            $res = $conexion->query($sql);
                                            if ($res && $res->num_rows > 0) {
                                                while ($row = $res->fetch_assoc()) {
                                                    $responsable = !empty($row['usuario']) ? $row['usuario'] : 'No registrado';
                                                    
                                                    $hora = $row['hora_elaboracion'];
                                                    if ($hora < '13:00:00') {
                                                        $badge_turno = "<span class='badge bg-info text-dark'><i class='fas fa-sun me-1'></i> Mañana</span>";
                                                    } else {
                                                        $badge_turno = "<span class='badge bg-warning text-dark'><i class='fas fa-cloud-sun me-1'></i> Tarde</span>";
                                                    }

                                                    echo "<tr>
                                                            <td><strong>".htmlspecialchars($row['nombre_plato'])."</strong></td>
                                                            <td class='text-center fw-bold'>{$row['cantidad_personas']} porc.</td>
                                                            <td class='text-center'>".htmlspecialchars($responsable)."</td>
                                                            <td class='text-center'>".date("d/m/Y", strtotime($row['fecha_elaboracion']))."</td>
                                                            <td class='text-center'>{$hora}</td>
                                                            <td class='text-center'>{$badge_turno}</td>
                                                          </tr>";
                                                }
                                            } else { echo "<tr><td colspan='6' class='text-center text-muted py-3'>No hay registros de platos en este rango de tiempo.</td></tr>"; }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>

                            <?php elseif ($tipo_reporte == 'descuentos'): ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Insumo Consumido</th>
                                                <th class="text-center">Cantidad Gastada</th>
                                                <th>Receta / Platillo Destino</th>
                                                <th>Responsable</th>
                                                <th class="text-center">Fecha de Consumo</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $sql = "SELECT p.nombre AS producto_nombre, c.unidad_predeterminada, hm.cantidad, hm.usuario, hm.fecha, rc.nombre_plato 
                                                    FROM historial_movimientos hm
                                                    INNER JOIN productos p ON hm.id_producto = p.id_producto
                                                    LEFT JOIN categorias_maestras c ON p.id_categoria = c.id_categoria
                                                    LEFT JOIN historial_cocina hc ON DATE(hm.fecha) = DATE(hc.fecha_elaboracion)
                                                    LEFT JOIN recetas_comida rc ON hc.id_receta = rc.id_receta
                                                    WHERE hm.tipo_movimiento = 'EGRESO_RECETA'" . $condicion_fecha_hm . " 
                                                    GROUP BY hm.id_movimiento, p.id_producto 
                                                    ORDER BY hm.fecha DESC";
                                                    
                                            $res = $conexion->query($sql);
                                            if ($res && $res->num_rows > 0) {
                                                while ($row = $res->fetch_assoc()) {
                                                    $cant = abs($row['cantidad']); 
                                                    $unidad = htmlspecialchars($row['unidad_predeterminada'] ?? '');
                                                    $receta = !empty($row['nombre_plato']) ? htmlspecialchars($row['nombre_plato']) : "<span class='text-muted small'>Deducción por cocina</span>";
                                                    echo "<tr>
                                                            <td><strong class='text-danger'><i class='fas fa-arrow-down me-1'></i>".htmlspecialchars($row['producto_nombre'])."</strong></td>
                                                            <td class='text-center fw-bold'>{$cant} {$unidad}</td>
                                                            <td>{$receta}</td>
                                                            <td>".htmlspecialchars($row['usuario'])."</td>
                                                            <td class='text-center'>".date("d/m/Y H:i:s", strtotime($row['fecha']))."</td>
                                                          </tr>";
                                                }
                                            } else { echo "<tr><td colspan='5' class='text-center text-muted py-3'>No se registran deducciones en este intervalo.</td></tr>"; }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>

                            <?php elseif ($tipo_reporte == 'ingresos'): ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Insumo Ingresado</th>
                                                <th class="text-center">Cantidad Añadida</th>
                                                <th>Responsable</th>
                                                <th class="text-center">Fecha de Ingreso</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $sql = "SELECT p.nombre, c.unidad_predeterminada, hm.cantidad, hm.usuario, hm.fecha 
                                                    FROM historial_movimientos hm
                                                    INNER JOIN productos p ON hm.id_producto = p.id_producto 
                                                    LEFT JOIN categorias_maestras c ON p.id_categoria = c.id_categoria
                                                    WHERE hm.tipo_movimiento = 'INGRESO'" . $condicion_fecha_hm . " 
                                                    ORDER BY hm.fecha DESC";
                                                    
                                            $res = $conexion->query($sql);
                                            if ($res && $res->num_rows > 0) {
                                                while ($row = $res->fetch_assoc()) {
                                                    $unidad = htmlspecialchars($row['unidad_predeterminada'] ?? '');
                                                    echo "<tr>
                                                            <td><strong class='text-success'><i class='fas fa-arrow-up me-1'></i>".htmlspecialchars($row['nombre'])."</strong></td>
                                                            <td class='text-center fw-bold'>{$row['cantidad']} {$unidad}</td>
                                                            <td>".htmlspecialchars($row['usuario'])."</td>
                                                            <td class='text-center'>".date("d/m/Y H:i:s", strtotime($row['fecha']))."</td>
                                                          </tr>";
                                                }
                                            } else { echo "<tr><td colspan='4' class='text-center text-muted py-3'>No se registran entradas de insumos en este intervalo.</td></tr>"; }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>

                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <!-- MODAL GUÍA DE USO PARA EL MÓDULO DE REPORTES -->
    <div class="modal fade" id="modalGuiaReportes" tabindex="-1" aria-labelledby="modalGuiaReportesLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="modalGuiaReportesLabel">
                        <i class="fas fa-book-open me-2"></i>Guía: Módulo de Reportes
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">
                        Genera informes detallados del estado de almacén, preparaciones diarias y movimientos de inventario:
                    </p>

                    <ol class="list-group list-group-numbered list-group-flush mb-0">
                        <li class="list-group-item d-flex align-items-start border-0 px-0 py-2">
                            <div class="ms-2 me-auto">
                                <div class="fw-bold text-dark">Selecciona el Área del Reporte</div>
                                <span class="text-secondary small">Elige entre <i>Estado de Almacén</i>, <i>Platillos Elaborados</i>, <i>Insumos Descontados (Consumos)</i> o <i>Ingresos de Insumos</i>.</span>
                            </div>
                        </li>
                        <li class="list-group-item d-flex align-items-start border-0 px-0 py-2">
                            <div class="ms-2 me-auto">
                                <div class="fw-bold text-dark">Aplica Filtros de Estado y Tiempo</div>
                                <span class="text-secondary small">Filtra insumos por caducidad (caducados, en riesgo o estables) o limita las fechas (hoy, 7 días, 30 días o rango personalizado).</span>
                            </div>
                        </li>
                        <li class="list-group-item d-flex align-items-start border-0 px-0 py-2">
                            <div class="ms-2 me-auto">
                                <div class="fw-bold text-dark">Haz clic en "Generar"</div>
                                <span class="text-secondary small">Presiona el botón azul para consultar la base de datos y actualizar los títulos, métricas y gráficas dinámicas.</span>
                            </div>
                        </li>
                        <li class="list-group-item d-flex align-items-start border-0 px-0 py-2">
                            <div class="ms-2 me-auto">
                                <div class="fw-bold text-dark">Imprimir o Exportar en PDF</div>
                                <span class="text-secondary small">Utiliza el botón <b>"Imprimir / PDF"</b> en la esquina superior del documento para guardar el informe de forma física o digital.</span>
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../startbootstrap-sb-admin-gh-pages/js/scripts.js"></script>
    
    <script>
        function toggleRangoFechas() {
            var filtro = document.getElementById('filtro_fecha').value;
            var seccionRango = document.getElementById('seccion_rango_manual');
            if (filtro === 'personalizado') {
                seccionRango.style.display = 'flex';
            } else {
                seccionRango.style.display = 'none';
            }
        }
        
        function toggleFiltroCaducidad() {
            var tipoReporte = document.getElementById('tipo_reporte').value;
            var contenedorCaducidad = document.getElementById('contenedor_filtro_caducidad');
            
            if (tipoReporte === 'inventario') {
                contenedorCaducidad.style.display = 'block';
            } else {
                contenedorCaducidad.style.display = 'none';
            }
        }
        
        document.addEventListener("DOMContentLoaded", function() {
            toggleRangoFechas();
            toggleFiltroCaducidad();
            
            // --- INICIALIZACIÓN DE LA GRÁFICA DE DONA (CHART.JS) ---
            <?php if ($tipo_reporte == 'inventario'): ?>
            const ctx = document.getElementById('graficoCaducidad').getContext('2d');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Caducados', 'En Riesgo', 'Estables'],
                    datasets: [{
                        data: [
                            <?php echo (int)$cant_caducados; ?>, 
                            <?php echo (int)$cant_riesgo; ?>, 
                            <?php echo (int)$cant_estables; ?>
                        ],
                        backgroundColor: [
                            '#dc3545', // Rojo para caducados / crítico
                            '#ffc107', // Amarillo para riesgo / por vencer
                            '#198754'  // Verde para estables / excelente
                        ],
                        borderWidth: 1,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                font: {
                                    size: 11
                                }
                            }
                        }
                    }
                }
            });
            <?php endif; ?>
        });
    </script>
</body>
</html>