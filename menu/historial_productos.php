<?php
// 1. INICIAR SESIÓN GLOBAL
ini_set('session.cookie_path', '/'); 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. GUARDIÁN DE SEGURIDAD
include($_SERVER['DOCUMENT_ROOT'] . "/iniciar sesion/verificar_admin.php"); 

include '../iniciar sesion/conexion.php';
$Conexion->select_db("productos");

// --- LÓGICA DE FILTROS ---
$donde = " WHERE 1=1 "; 

if (!empty($_GET['buscar_producto'])) {
    $busqueda = mysqli_real_escape_string($Conexion, $_GET['buscar_producto']);
    $donde .= " AND p.nombre LIKE '%$busqueda%' ";
}

if (!empty($_GET['fecha_filtro'])) {
    $fecha = mysqli_real_escape_string($Conexion, $_GET['fecha_filtro']);
    $donde .= " AND DATE(h.fecha) = '$fecha' ";
}

// Filtro especializado al presionar una receta desde el historial (Margen de tolerancia por segundos)
if (!empty($_GET['fecha_cocina']) && !empty($_GET['responsable'])) {
    $responsable = mysqli_real_escape_string($Conexion, $_GET['responsable']);
    
    $fecha_cruda = strtotime($_GET['fecha_cocina']);
    if ($fecha_cruda !== false) {
        // Creamos un rango de tiempo seguro: 1 minuto antes y 1 minuto después del registro
        $fecha_inicio = date('Y-m-d H:i:00', strtotime('-1 minute', $fecha_cruda));
        $fecha_fin    = date('Y-m-d H:i:59', strtotime('+1 minute', $fecha_cruda));
        
        // Filtramos usando BETWEEN para capturar las variaciones de segundos de los inserts del bucle
        $donde .= " AND h.usuario = '$responsable' AND h.fecha BETWEEN '$fecha_inicio' AND '$fecha_fin' ";
    }
}

// Procesar inserción si viene un array de ingredientes desde el modal (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ingredientes']) && is_array($_POST['ingredientes'])) {
    foreach ($_POST['ingredientes'] as $ingrediente) {
        $id_prod = intval($ingrediente['id']);
        $cantidad = floatval($ingrediente['cantidad']);
        $usuario = mysqli_real_escape_string($Conexion, $_SESSION['usuario']);

        // 1. Descontar del Stock
        $sql_update = "UPDATE stock SET cantidad = cantidad - $cantidad WHERE id_producto = $id_prod";
        $Conexion->query($sql_update);

        // 2. Registrar en Historial (CORREGIDO: 'tipo' cambiado por 'tipo_movimiento')
        $sql_historial = "INSERT INTO historial_movimientos (id_producto, tipo_movimiento, cantidad, usuario, fecha) 
                          VALUES ($id_prod, 'EGRESO', $cantidad, '$usuario', NOW())";
        $Conexion->query($sql_historial);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Historial de Productos - PAE</title>
    <link href="/startbootstrap-sb-admin-gh-pages/css/styles.css" rel="stylesheet" />
    <script src="/fontawesome-free-7.2.0-web/js/all.js"></script>
    <style>
        .filter-section { background: #f8f9fa; border-radius: 10px; border-left: 5px solid #212529; }
        .table-container { border-radius: 10px; overflow: hidden; }
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
                                <a class="nav-link" href="/menu/crear_receta.php">Añadir receta</a>
                                <a class="nav-link" href="/menu/lista_recetas.php">Cocinar</a>
                            </nav>
                        </div>
                        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapsePages" aria-expanded="false" aria-controls="collapsePages">
                            <div class="sb-nav-link-icon"><i class="fas fa-book-open"></i></div>
                            Reportes
                            <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                        </a>
                        <div class="collapse" id="collapsePages" aria-labelledby="headingTwo" data-bs-parent="#sidenavAccordion">
                            <nav class="sb-sidenav-menu-nested nav">
                                <a class="nav-link" href="/menu/historial.php">Historial de movimientos</a>
                                <a class="nav-link" href="register.html">Reporte 2 (Register)</a>
                                <a class="nav-link" href="password.html">Reporte 3 (Password)</a>
                            </nav>
                        </div>
                        <div class="sb-sidenav-menu-heading">Insumos</div>
                        <a class="nav-link" href="/registrar_producto/productos.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-chart-area"></i></div>
                            Productos
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
            <h1 class="mt-4">Historial de Movimientos</h1>
            
            <?php if (!empty($_GET['fecha_cocina'])): ?>
                <div class="alert alert-info alert-dismissible fade show mt-3" role="alert">
                    <i class="fas fa-info-circle me-2"></i> Mostrando insumos descontados en la fecha y hora seleccionada.
                    <a href="historial_productos.php" class="btn btn-sm btn-secondary float-end" style="margin-top: -3px;">Ver todo el historial</a>
                </div>
            <?php endif; ?>

            <ol class="breadcrumb mb-4 mt-2">
                <li class="breadcrumb-item"><a href="/inicio.php">Inicio</a></li>
                <li class="breadcrumb-item active">Buscador de registros</li>
            </ol>

            <div class="card mb-4 filter-section shadow-sm">
                <div class="card-body p-4">
                    <form method="GET" action="historial_productos.php">
                        <div class="row g-3 alignment-items-end">
                            <div class="col-md-5">
                                <label class="form-label fw-bold small text-uppercase text-muted">Nombre del Producto</label>
                                <input type="text" name="buscar_producto" class="form-control" placeholder="Buscar insumo o producto..." value="<?php echo isset($_GET['buscar_producto']) ? htmlspecialchars($_GET['buscar_producto']) : ''; ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase text-muted">Filtrar por Fecha</label>
                                <input type="date" name="fecha_filtro" class="form-control" value="<?php echo isset($_GET['fecha_filtro']) ? htmlspecialchars($_GET['fecha_filtro']) : ''; ?>">
                            </div>
                            <div class="col-md-3 d-flex align-items-end gap-2" style="padding-bottom: 1px;">
                                <button type="submit" class="btn btn-dark w-100"><i class="fas fa-filter me-1"></i> Filtrar</button>
                                <a href="historial_productos.php" class="btn btn-outline-secondary w-100">Limpiar</a>
                            </div>
                        </div>
                    </form>
                    <div class="mt-3 d-flex align-items-center gap-2">
                        <span class="small fw-bold text-muted text-uppercase">Accesos Rápidos:</span>
                        <a href="historial_productos.php?fecha_filtro=<?php echo date('Y-m-d'); ?>" class="btn btn-sm btn-outline-primary py-0 px-2">Hoy</a>
                        <a href="historial_productos.php?fecha_filtro=<?php echo date('Y-m-d', strtotime('-1 day')); ?>" class="btn btn-sm btn-outline-primary py-0 px-2">Ayer</a>
                    </div>
                </div>
            </div>

            <div class="card mb-4 shadow-sm table-container">
                <div class="card-header bg-dark text-white">
                    <span><i class="fas fa-history me-1"></i> Registros Encontrados</span>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Producto</th>
                                <th>Acción / Tipo</th>
                                <th>Cantidad</th>
                                <th>Responsable</th>
                                <th>Fecha y Hora</th>
                            </tr>
                        </thead>
                        <tbody>
                    <?php
// Corregido: Eliminamos p.unidad que no existe en tu tabla productos
$sql = "SELECT h.*, p.nombre, p.categoria 
        FROM historial_movimientos h 
        JOIN productos p ON h.id_producto = p.id_producto 
        $donde ORDER BY h.fecha DESC";

$resultado = $Conexion->query($sql);

if ($resultado && $resultado->num_rows > 0) {
    while ($row = $resultado->fetch_assoc()) {
        $fecha_format = date('d/m/Y h:i A', strtotime($row['fecha']));
        $tipo_movimiento = isset($row['tipo_movimiento']) ? $row['tipo_movimiento'] : '';
        
        // Colocamos la unidad de forma dinámica según la categoría (ej: Granos/Cereales -> kg)
        $categoria = isset($row['categoria']) ? strtolower($row['categoria']) : '';
        $unidad = '';
        if (strpos($categoria, 'granos') !== false || strpos($categoria, 'cereales') !== false) {
            $unidad = ' kg';
        } elseif (strpos($categoria, 'liquido') !== false || strpos($categoria, 'aceite') !== false) {
            $unidad = ' L';
        } elseif (!empty($categoria)) {
            $unidad = ' und'; // Por defecto para las demás categorías
        }
        
        // Obtenemos el valor numérico puro
        $cantidad_absoluta = abs(floatval($row['cantidad']));

        // Si el tipo de movimiento contiene "Cocina" o la cantidad es menor que 0
        if (strpos($tipo_movimiento, 'Cocina') !== false || floatval($row['cantidad']) < 0) {
            $tipo_badge = 'bg-danger';
            
            // Si el tipo de movimiento ya trae el nombre de la receta ("Cocina (arroz y aceite)"), lo dejamos
            $texto_accion = (!empty($tipo_movimiento)) ? $tipo_movimiento : 'Cocinado';
            
            // Signo MENOS (-) para el egreso de cocina junto a su unidad
            $signo_cantidad = '- ' . $cantidad_absoluta . $unidad;
        } else {
            // De lo contrario, es una entrada o edición manual
            $tipo_badge = 'bg-success';
            $texto_accion = 'Añadido';
            
            // Signo MÁS (+) para la entrada de mercancía junto a su unidad
            $signo_cantidad = '+ ' . $cantidad_absoluta . $unidad;
        }
        
        echo "<tr>
                <td><strong>" . htmlspecialchars($row['nombre']) . "</strong></td>
                <td><span class='badge $tipo_badge'>" . htmlspecialchars($texto_accion) . "</span></td>
                <td class='fw-bold'>" . $signo_cantidad . "</td>
                <td><i class='fas fa-user-circle me-1 text-muted'></i>" . htmlspecialchars($row['usuario']) . "</td>
                <td>" . $fecha_format . "</td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='5' class='text-center py-5 text-muted'>No hay movimientos registrados para este filtro.</td></tr>";
}
?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/startbootstrap-sb-admin-gh-pages/js/scripts.js"></script>
</body>
</html>