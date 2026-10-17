<?php
// 1. INICIAR SESIÓN GLOBAL (Misma configuración exacta que en el login)
ini_set('session.cookie_path', '/'); 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. GUARDIÁN DE SEGURIDAD CENTRALIZADO (Ruta absoluta universal)
include($_SERVER['DOCUMENT_ROOT'] . "/iniciar sesion/verificar_admin.php"); 

include '../iniciar sesion/conexion.php';
$Conexion->select_db("productos");

// --- LÓGICA DE FILTROS ---
$donde = " WHERE 1=1 "; 

if (!empty($_GET['buscar_plato'])) {
    $busqueda = mysqli_real_escape_string($Conexion, $_GET['buscar_plato']);
    $donde .= " AND nombre_plato LIKE '%$busqueda%' ";
}

if (!empty($_GET['fecha_filtro'])) {
    $fecha = mysqli_real_escape_string($Conexion, $_GET['fecha_filtro']);
    $donde .= " AND fecha_elaboracion = '$fecha' ";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Historial de Elaboración - PAE</title>
    <link href="/startbootstrap-sb-admin-gh-pages/css/styles.css" rel="stylesheet" />
    <script src="fontawesome-free-7.2.0-web/js/all.js"></script>
    <style>
        .filter-section { background: #f8f9fa; border-radius: 10px; border-left: 5px solid #212529; }
        .table-container { border-radius: 10px; overflow: hidden; }
        .link-plato { text-decoration: none; transition: color 0.2s ease-in-out; }
        .link-plato:hover { color: #0d6efd !important; text-decoration: underline; }
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
                        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseLayouts">
                            <div class="sb-nav-link-icon"><i class="fas fa-utensils"></i></div>
                            Recetario
                            <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                        </a>
                        <div class="collapse" id="collapseLayouts">
                            <nav class="sb-sidenav-menu-nested nav">
                                <a class="nav-link" href="crear_receta.php">Añadir receta</a>
                                <a class="nav-link" href="lista_recetas.php">Cocinar</a>
                            </nav>
                        </div>

                        <a class="nav-link" href="#" data-bs-toggle="collapse" data-bs-target="#collapseHistory">
                            <div class="sb-nav-link-icon"><i class="fas fa-history"></i></div>
                            Reportes
                            <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                        </a>
                        <div class="collapse show" id="collapseHistory">
                            <nav class="sb-sidenav-menu-nested nav">
                                <a class="nav-link active" href="historial.php">Historial de Cocina</a>
                            </nav>
                        </div>

                        <div class="sb-sidenav-menu-heading text-white-50">Insumos</div>
                        <a class="nav-link" href="/registrar_producto/productos.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-box"></i></div>
                            Productos
                        </a>
                        <a class="nav-link" href="/registrar_producto/stock.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-boxes-stacked"></i></div>
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
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="/inicio.php">Inicio</a></li>
                        <li class="breadcrumb-item active">Buscador de registros</li>
                    </ol>

                    <div class="card mb-4 filter-section shadow-sm">
                        <div class="card-body">
                            <form method="GET" action="" class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-uppercase">Nombre del Plato</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                                        <input type="text" name="buscar_plato" class="form-control" placeholder="Buscar preparation..." value="<?php echo $_GET['buscar_plato'] ?? ''; ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-uppercase">Filtrar por Fecha</label>
                                    <input type="date" name="fecha_filtro" id="inputFecha" class="form-control" value="<?php echo $_GET['fecha_filtro'] ?? ''; ?>">
                                </div>
                                <div class="col-md-5 d-flex align-items-end gap-2">
                                    <button type="submit" class="btn btn-dark w-100">
                                        <i class="fas fa-filter me-1"></i> Filtrar
                                    </button>
                                    <a href="historial.php" class="btn btn-outline-secondary w-100">
                                        <i class="fas fa-sync-alt me-1"></i> Limpiar
                                    </a>
                                </div>
                            </form>
                            <div class="mt-3">
                                <span class="small text-muted me-2 text-uppercase fw-bold">Accesos rápidos:</span>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="filtrarHoy()">Hoy</button>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="filtrarAyer()">Ayer</button>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4 shadow-sm table-container">
                        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-clock-rotate-left me-1"></i> Registros Encontrados</span>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th class="ps-3">ID</th>
                                        <th>Plato Elaborado</th>
                                        <th>Fecha</th>
                                        <th>Hora</th>
                                        <th>Beneficiarios</th> 
                                        <th>Elaborado por</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sql = "SELECT id_historial, nombre_plato, fecha_elaboracion, hora_elaboracion, cantidad_personas 
                                            FROM historial_cocina 
                                            $donde 
                                            ORDER BY id_historial DESC";
                                    $resultado = $Conexion->query($sql);
                                    
                                    if ($resultado && $resultado->num_rows > 0) {
                                        while ($row = $resultado->fetch_assoc()) {
                                            $f = date('d/m/Y', strtotime($row['fecha_elaboracion']));
                                            $h = date('h:i A', strtotime($row['hora_elaboracion']));
                                            
                                            $raciones = (int)($row['cantidad_personas'] ?? 0);

                                            if ($raciones === 0 && !empty($row['nombre_plato'])) {
                                                if (preg_match('/\(x(\d+)\s*raciones\)/i', $row['nombre_plato'], $coincidencias)) {
                                                    $raciones = (int)$coincidencias[1];
                                                }
                                            }

                                            $usuario_responsable = htmlspecialchars($_SESSION['usuario']);

                                            // MODIFICACIÓN CLAVE: Combinamos fecha_elaboracion y hora_elaboracion de forma limpia para pasarlo en la URL
                                            $fecha_cocina_param = urlencode($row['fecha_elaboracion'] . ' ' . $row['hora_elaboracion']);
                                            $responsable_param = urlencode($_SESSION['usuario']);

                                            echo "<tr>
                                                    <td class='ps-3'>#{$row['id_historial']}</td>
                                                    <td>
                                                        <a href='historial_productos.php?fecha_cocina={$fecha_cocina_param}&responsable={$responsable_param}' class='text-dark fw-bold link-plato' title='Ver desglose en historial de productos'>
                                                            <i class='fas fa-arrow-up-right-from-square me-1 text-muted small'></i> " . htmlspecialchars($row['nombre_plato']) . "
                                                        </a>
                                                    </td>
                                                    <td><i class='far fa-calendar me-2 text-muted'></i>$f</td>
                                                    <td><i class='far fa-clock me-2 text-muted'></i>$h</td>
                                                    <td><i class='fas fa-pie-chart me-2 text-success'></i><strong>{$raciones}</strong> raciones</td>
                                                    <td><span class='text-muted'><i class='fas fa-user-shield me-1 small'></i>{$usuario_responsable}</span></td>
                                                    <td><span class='badge bg-success'><i class='fas fa-check-circle me-1'></i>Listo</span></td>
                                                  </tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='7' class='text-center py-5 text-muted'>No se encontraron platos para esta búsqueda.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
            
            <footer class="py-4 bg-light mt-auto">
                <div class="container-fluid px-4">
                    <div class="d-flex align-items-center justify-content-between small">
                        <div class="text-muted">Copyright &copy; Sistema PAE 2026</div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <script>
        function filtrarHoy() {
            const hoy = new Date().toISOString().split('T')[0];
            window.location.href = `historial.php?fecha_filtro=${hoy}`;
        }

        function filtrarAyer() {
            const ayer = new Date();
            ayer.setDate(ayer.getDate() - 1);
            const fechaAyer = ayer.toISOString().split('T')[0];
            window.location.href = `historial.php?fecha_filtro=${fechaAyer}`;
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="/startbootstrap-sb-admin-gh-pages/js/scripts.js"></script>
</body>
</html>