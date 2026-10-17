<?php 
// 1. INICIAR SESIÓN GLOBAL (Misma configuración exacta que en el login)
ini_set('session.cookie_path', '/'); 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. GUARDIÁN DE SEGURIDAD CENTRALIZADO (Ruta absoluta universal)
include($_SERVER['DOCUMENT_ROOT'] . "/iniciar sesion/verificar_admin.php"); 
?>

<?php
// Incluimos la conexión desde su carpeta y con el formato POO unificado
include '../iniciar sesion/conexion.php';

// Variables para controlar el estado del modal de notificación
$mostrar_modal = false;
$modal_tipo = ""; // "success" o "danger"
$modal_mensaje = "";

// --- PROCESAMIENTO DEL FORMULARIO CON TRANSACCIONES ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['nombre_plato'])) {
    if (isset($Conexion) && $Conexion != null) {
        $Conexion->select_db("productos");

        // Limpiamos los datos básicos
        $nombre_plato = trim($_POST['nombre_plato']);
        $productosIds = $_POST['productos'] ?? [];
        $cantidades = $_POST['cantidades'] ?? [];

        // Validaciones iniciales del lado del servidor
        if (empty($nombre_plato)) {
            $mostrar_modal = true;
            $modal_tipo = "danger";
            $modal_mensaje = "Error: El nombre del plato no puede estar completamente vacío.";
        } elseif (empty($productosIds) || empty($cantidades)) {
            $mostrar_modal = true;
            $modal_tipo = "danger";
            $modal_mensaje = "Error: Debes añadir al menos un ingrediente válido a la receta.";
        } else {
            
            // --- VALIDACIÓN DE NOMBRE DUPLICADO (Con Sentencia Preparada) ---
            $sql_verificar = "SELECT id_receta FROM productos.recetas_comida WHERE nombre_plato = ? LIMIT 1";
            $stmt_verificar = $Conexion->prepare($sql_verificar);
            
            if ($stmt_verificar) {
                $stmt_verificar->bind_param("s", $nombre_plato);
                $stmt_verificar->execute();
                $resultado_verificar = $stmt_verificar->get_result();

                if ($resultado_verificar && $resultado_verificar->num_rows > 0) {
                    $mostrar_modal = true;
                    $modal_tipo = "danger";
                    $modal_mensaje = "Error: Ya existe una receta registrada con el nombre de <b>" . htmlspecialchars($nombre_plato) . "</b>. Por favor, elige un nombre diferente.";
                } else {

                    // --- INICIO DE TRANSACCIÓN (Solo si el nombre es único) ---
                    $Conexion->begin_transaction();

                    try {
                        // 1. Insertamos la cabecera del plato
                        $sql_receta = "INSERT INTO productos.recetas_comida (nombre_plato) VALUES (?)";
                        $stmt_receta = $Conexion->prepare($sql_receta);
                        
                        if (!$stmt_receta) {
                            throw new Exception("Error al preparar la consulta del plato principal: " . $Conexion->error);
                        }
                        
                        $stmt_receta->bind_param("s", $nombre_plato);
                        if (!$stmt_receta->execute()) {
                            throw new Exception("Error al insertar el plato principal: " . $stmt_receta->error);
                        }

                        // 2. Obtenemos el ID autogenerado
                        $id_receta_nueva = $Conexion->insert_id;

                        // 3. Re-indexamos los arreglos numéricamente
                        $productosIds = array_values($productosIds);
                        $cantidades = array_values($cantidades);

                        // Preparamos la consulta del detalle
                        $sql_detalle = "INSERT INTO productos.receta_detalles (id_receta, id_producto, cantidad_necesaria) VALUES (?, ?, ?)";
                        $stmt_detalle = $Conexion->prepare($sql_detalle);
                        
                        if (!$stmt_detalle) {
                            throw new Exception("Error al preparar la consulta de ingredientes: " . $Conexion->error);
                        }

                        // 4. Recorremos dinámicamente cada insumo enviado
                        foreach ($productosIds as $indice => $id_producto) {
                            $id_p = trim($id_producto);
                            $cantidad_raw = isset($cantidades[$indice]) ? $cantidades[$indice] : 0;
                            $cantidad = floatval($cantidad_raw);

                            if (!empty($id_p) && $cantidad > 0) {
                                $stmt_detalle->bind_param("iid", $id_receta_nueva, $id_p, $cantidad);
                                
                                if (!$stmt_detalle->execute()) {
                                    throw new Exception("Error al registrar el ingrediente con ID ($id_p): " . $stmt_detalle->error);
                                }
                            }
                        }

                        $Conexion->commit();
                        
                        $mostrar_modal = true;
                        $modal_tipo = "success";
                        $modal_mensaje = "¡La receta de <b>" . htmlspecialchars($nombre_plato) . "</b> y todos sus ingredientes han sido guardados de forma exitosa!";

                    } catch (Exception $e) {
                        $Conexion->rollback();
                        $mostrar_modal = true;
                        $modal_tipo = "danger";
                        $modal_mensaje = "No se pudo procesar la receta de forma segura. Detalle: " . $e->getMessage();
                    } finally {
                        if (isset($stmt_receta)) $stmt_receta->close();
                        if (isset($stmt_detalle)) $stmt_detalle->close();
                    }
                }
                $stmt_verificar->close();
            } else {
                $mostrar_modal = true;
                $modal_tipo = "danger";
                $modal_mensaje = "Error del sistema al verificar duplicados: " . $Conexion->error;
            }
        }
    }
}

// Preparamos los productos y su stock para el formulario
if (isset($Conexion) && $Conexion != null) {
    $Conexion->select_db("productos");

    $sql = "SELECT 
                p.id_producto, 
                p.nombre, 
                c.unidad_predeterminada AS unidad_medida, 
                IFNULL(s.cantidad_actual, 0) AS stock_actual 
            FROM productos p
            INNER JOIN categorias_maestras c ON p.id_categoria = c.id_categoria
            LEFT JOIN stock s ON p.id_producto = s.id_producto";

    $resultado = $Conexion->query($sql);

    if (!$resultado) {
        die("Error en la consulta SQL: " . $Conexion->error);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Crear Receta - Stock y Nutrición</title>
    <link href="/startbootstrap-sb-admin-gh-pages/css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <style>
    .bg-azul-vivo, .bg-azul-medio { background-color: #212529 !important; }
    .bg-azul-vivo .navbar-brand { color: rgba(255, 255, 255, 0.8) !important; }
    .bg-azul-vivo .nav-link, .bg-azul-vivo .btn-link { color: rgba(255, 255, 255, 0.5) !important; }
    .bg-azul-vivo .nav-link:hover, .bg-azul-vivo .btn-link:hover { color: rgba(255, 255, 255, 0.75) !important; }
    .sb-topnav.navbar-dark { border-bottom: none; }
    
    /* Efecto hover suave para el botón de ayuda resaltado */
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
    <nav class="sb-topnav navbar navbar-expand navbar-dark bg-azul-vivo shadow-sm">
        <a class="navbar-brand ps-3 fw-bold" href="/inicio.php">Nueva Toledo</a>
        <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0 text-white" id="sidebarToggle"><i class="fas fa-bars"></i></button>
        
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
                                <a class="nav-link active" href="crear_receta.php">Crear nueva receta</a>
                                <a class="nav-link" href="lista_recetas.php">Planificar Menú</a>
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
            <main>
                <div class="container-fluid px-4">
                    
                    <!-- ENCABEZADO CON TÍTULO Y BOTÓN DE GUÍA MUCHO MÁS DESTACADO -->
                    <div class="d-flex justify-content-between align-items-center mt-4 mb-2">
                        <h1 class="h3 text-dark fw-bold mb-0">Gestión de Recetas</h1>
                        
                        <!-- BOTÓN DESTACADO EN AMARILLO VIVO CON SOMBRA Y TEXTO EN NEGRITA -->
                        <button type="button" class="btn btn-warning text-dark fw-bold rounded-pill shadow px-3 py-2 btn-ayuda-destacado" data-bs-toggle="modal" data-bs-target="#modalGuiaReceta">
                            <i class="fas fa-circle-question fa-bounce text-dark me-1"></i> ¿Cómo crear una receta?
                        </button>
                    </div>

                    <ol class="breadcrumb mb-4 bg-light p-2 rounded border">
                        <li class="breadcrumb-item"><a href="lista_recetas.php" class="text-decoration-none">Recetas</a></li>
                        <li class="breadcrumb-item active">Añadir Receta</li>
                    </ol>

                    <div id="contenedor-alertas"></div>

                    <form action="" method="POST" onsubmit="return validarAntesDeEnviar(event)">
                        
                        <div class="card mb-4 border-0 shadow-sm">
                            <div class="card-header bg-azul-vivo text-white py-3 fw-bold d-flex align-items-center justify-content-between">
                                <div>
                                    <i class="fas fa-users me-2"></i> Elaborar receta base
                                </div>
                                <span class="badge bg-warning text-dark fs-6"><i class="fas fa-calculator me-1"></i> Guía: 80 Raciones (Menú Predeterminado)</span>
                            </div>
                            <div class="card-body p-4 bg-white">
                                <div class="row align-items-center">
                                    <div class="col-md-4">
                                        <label class="form-label text-dark fw-bold mb-1">Nombre de la Receta:</label>
                                        <p class="text-muted small mb-md-0">
                                            Selecciona una receta del menú para cargar insumos predeterminados (calculados para 80 estudiantes como guía), o pulsa <b>"+ Nueva"</b> para crear una receta propia con la cantidad que desees.
                                        </p>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="input-group input-group-lg">
                                            <span class="input-group-text bg-light border-secondary-subtle"><i class="fas fa-bowl-food text-primary"></i></span>
                                            <select name="nombre_plato" id="selectReceta" class="form-select border-secondary-subtle fw-semibold" required onchange="actualizarTituloReceta()">
                                                <option value="" disabled selected>Seleccione una receta...</option>
                                                <option value="Arroz con pollo y arepa frita">Arroz con pollo y arepa frita</option>
                                                <option value="Arroz con leche">Arroz con leche</option>
                                                <option value="Arroz con pollo guisado con caraota y arepa frita">Arroz con pollo guisado con caraota y arepa frita</option>
                                                <option value="Pasta con pollo guisado y bollo">Pasta con pollo guisado y bollo</option>
                                                <option value="Chicha de arroz">Chicha de arroz</option>
                                                <option value="Chicha de espagueti">Chicha de espagueti</option>
                                                <option value="Chicha de paquete">Chicha de paquete</option>
                                            </select>
                                            <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#modalNuevaReceta">
                                                <i class="fas fa-plus me-1"></i> Nueva
                                            </button>
                                        </div>
                                        <div id="alertaNuevaReceta" class="text-success small fw-bold mt-2 d-none">
                                            <i class="fas fa-circle-check"></i> Creando nueva receta: <span id="nombreNuevaRecetaBadge"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-4 border-0 shadow-sm">
                            <div class="card-header bg-azul-medio text-white py-3 d-flex align-items-center justify-content-between">
                                <span class="fw-bold">
                                    <i class="fas fa-list-check me-2"></i> 
                                    Ingredientes para: <span id="nombrePlatilloActual" class="text-warning fw-bold">Seleccione o cree una receta</span>
                                </span>
                                <button type="button" onclick="agregarFila()" class="btn btn-light btn-sm fw-bold shadow-sm text-primary">
                                    <i class="fas fa-plus me-1 text-success"></i> Añadir Insumo
                                </button>
                            </div>

                            <div class="card-body p-4 bg-white">
                                <div class="alert alert-info py-2 px-3 mb-3 small d-flex align-items-center gap-2">
                                    <i class="fas fa-circle-info fs-5"></i>
                                    <div>
                                        <b>Nota de estandarización:</b> Las recetas predefinidas del listado están calculadas para una matrícula guía de <b>80 estudiantes</b>. Si estás creando una <b>nueva receta personalizada</b>, puedes ajustar libremente las cantidades requeridas según la necesidad real.
                                    </div>
                                </div>
                                
                                <div id="contenedor-ingredientes" class="d-flex flex-column gap-2">
                                    <div class="fila-ingrediente d-flex align-items-center gap-2 p-2 rounded bg-light border">
                                        <div class="text-muted px-2 d-none d-sm-block">
                                            <i class="fas fa-bars small"></i>
                                        </div>
                                        <div class="flex-grow-1 position-relative">
                                            <input type="text" list="lista-productos" class="form-control input-producto bg-white" placeholder="Escribe para buscar un producto..." required oninput="actualizarUnidadYStock(this)">
                                            <input type="hidden" name="productos[]" class="id-producto-oculto">
                                        </div>
                                        <div style="max-width: 220px;">
                                            <div class="input-group">
                                                <input type="number" step="1" min="1" name="cantidades[]" class="form-control text-end input-cantidad" placeholder="0" required oninput="evaluarFilaIndividual(this)">
                                                <span class="input-group-text bg-white text-muted small fw-bold badge-unidad">Und</span>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-outline-danger border-0" onclick="removerFila(this)" title="Eliminar ingrediente">
                                            <i class="fas fa-trash-can"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <datalist id="lista-productos">
                            <?php
                            if (isset($resultado) && $resultado) {
                                $resultado->data_seek(0);
                                while ($row = $resultado->fetch_assoc()) {
                                    $id = $row['id_producto'];
                                    $nombre = htmlspecialchars($row['nombre']);
                                    $unidad = htmlspecialchars($row['unidad_medida'] ?? 'Kg');
                                    $stock = htmlspecialchars($row['stock_actual'] ?? '0');
                                    echo "<option value='{$nombre}' data-id='{$id}' data-unidad='{$unidad}' data-stock='{$stock}'>Stock disponible: {$stock} {$unidad}</option>";
                                }
                            }
                            ?>
                        </datalist>
                        
                        <div class="d-flex justify-content-end gap-2 mb-5">
                            <a href="lista_recetas.php" class="btn btn-secondary px-4 shadow-sm">
                                <i class="fas fa-arrow-left me-2"></i>Volver al Menú
                            </a>
                            <button type="submit" class="btn btn-primary bg-azul-vivo px-4 shadow-sm border-0">
                                <i class="fas fa-floppy-disk me-2"></i>Guardar Receta Completa
                            </button>
                        </div>
                    </form>
                </div>
            </main>

            <footer class="py-4 bg-light mt-auto border-top">
                <div class="container-fluid px-4">
                    <div class="d-flex align-items-center justify-content-between small">
                        <div class="text-muted">Copyright &copy; Sistema Stock y Nutrición 2026</div>
                        <div>
                            <a href="#" class="text-decoration-none text-primary">Políticas</a>
                            &middot;
                            <a href="#" class="text-decoration-none text-primary">Términos</a>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- MODAL GUÍA DE AYUDA EXPLICATIVO -->
    <div class="modal fade" id="modalGuiaReceta" tabindex="-1" aria-labelledby="modalGuiaRecetaLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="modalGuiaRecetaLabel">
                        <i class="fas fa-book-open me-2"></i>Guía: Crear una Receta
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">
                        Sigue estos sencillos pasos para registrar correctamente el patrón e ingredientes de la preparación:
                    </p>

                    <ol class="list-group list-group-numbered list-group-flush mb-0">
                        <li class="list-group-item d-flex align-items-start border-0 px-0 py-2">
                            <div class="ms-2 me-auto">
                                <div class="fw-bold text-dark">Nombre del Menú</div>
                                <span class="text-secondary small">Selecciona una receta estandarizada de la lista o pulsa en <strong>"+ Nueva"</strong> para registrar una personalizada.</span>
                            </div>
                        </li>
                        <li class="list-group-item d-flex align-items-start border-0 px-0 py-2">
                            <div class="ms-2 me-auto">
                                <div class="fw-bold text-dark">Ajustar Insumos e Ingredientes</div>
                                <span class="text-secondary small">Al elegir un platillo predefinido se cargarán automáticamente los ingredientes base. Puedes agregar o eliminar productos con los botones de acción.</span>
                            </div>
                        </li>
                        <li class="list-group-item d-flex align-items-start border-0 px-0 py-2">
                            <div class="ms-2 me-auto">
                                <div class="fw-bold text-dark">Definir Cantidades Base</div>
                                <span class="text-secondary small">Indica los kilogramos, litros o empaques netos. Las recetas predeterminadas vienen calculadas para <strong>80 estudiantes como guía</strong>; para nuevas recetas puedes usar el margen o proporción que requieras.</span>
                            </div>
                        </li>
                        <li class="list-group-item d-flex align-items-start border-0 px-0 py-2">
                            <div class="ms-2 me-auto">
                                <div class="fw-bold text-dark">Guardar Receta</div>
                                <span class="text-secondary small">Haz clic en <strong>"Guardar Receta Completa"</strong> para registrar la receta y calcular automáticamente los futuros despachos.</span>
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

    <!-- MODAL NUEVA RECETA CON LÍMITE DE 40 CARACTERES Y VALIDACIÓN DE LETRAS/ESPACIOS -->
    <div class="modal fade" id="modalNuevaReceta" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Agregar Nueva Receta al Listado</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="seccionNombreReceta">
                        <label class="form-label text-dark fw-bold">Nombre del Platillo:</label>
                        <input type="text" id="nuevaRecetaInput" class="form-control" 
                               placeholder="Ej. Pabellon Criollo" 
                               maxlength="40"
                               oninput="validarNombreInput(this)">
                        <div class="form-text small text-muted d-flex justify-content-between mt-1">
                            <span>Solo letras y espacios simples.</span>
                            <span id="contadorCaracteres">0/40</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="guardarNombreReceta()">Agregar al Selector</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalNotificacion" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modalNotificacionLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header <?php echo ($modal_tipo === 'success') ? 'bg-success text-white' : 'bg-danger text-white'; ?>">
                    <h5 class="modal-title" id="modalNotificacionLabel">
                        <?php echo ($modal_tipo === 'success') ? '<i class="fas fa-circle-check me-2"></i> ¡Correcto!' : '<i class="fas fa-circle-xmark me-2"></i> Error'; ?>
                    </h5>
                </div>
                <div class="modal-body p-4 text-center">
                    <p class="fs-5 mb-0 text-dark"><?php echo $modal_mensaje; ?></p>
                </div>
                <div class="modal-footer justify-content-center border-0 pt-0">
                    <?php if ($modal_tipo === 'success'): ?>
                        <a href="lista_recetas.php" class="btn btn-success px-4">Aceptar</a>
                    <?php else: ?>
                        <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cerrar</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="/startbootstrap-sb-admin-gh-pages/js/scripts.js"></script>

    <script>
    <?php if ($mostrar_modal): ?>
        document.addEventListener("DOMContentLoaded", function() {
            var miModal = new bootstrap.Modal(document.getElementById('modalNotificacion'));
            miModal.show();
        });
    <?php endif; ?>

    // Mapeo dinámico de recetas estandarizado a un lote base de 80 raciones netas (Kg / Lt enteros)
    const recetasPredefinidas = {
        "Arroz con pollo y arepa frita": [
            { nombre: "Pollo", cantidad: 16 },
            { nombre: "Arroz", cantidad: 8 },
            { nombre: "Aji", cantidad: 4 },
            { nombre: "Pimenton", cantidad: 4 },
            { nombre: "Cebolla", cantidad: 4 },
            { nombre: "Zanahoria", cantidad: 4 },
            { nombre: "Harina de Maíz", cantidad: 4 },
            { nombre: "Aceite", cantidad: 1 }
        ],
        "Arroz con leche": [
            { nombre: "Arroz", cantidad: 4 },
            { nombre: "Azúcar", cantidad: 4 },
            { nombre: "Leche", cantidad: 4 }
        ],
        "Arroz con pollo guisado con caraota y arepa frita": [
            { nombre: "Pollo", cantidad: 16 },
            { nombre: "Arroz", cantidad: 8 },
            { nombre: "Caraota", cantidad: 8 },
            { nombre: "Aji", cantidad: 4 },
            { nombre: "Pimenton", cantidad: 4 },
            { nombre: "Cebolla", cantidad: 4 },
            { nombre: "Zanahoria", cantidad: 4 },
            { nombre: "Harina de Maíz", cantidad: 4 },
            { nombre: "Aceite", cantidad: 1 }
        ],
        "Pasta con pollo guisado y bollo": [
            { nombre: "Pollo", cantidad: 16 },
            { nombre: "Pasta", cantidad: 8 },
            { nombre: "Aji", cantidad: 4 },
            { nombre: "Pimenton", cantidad: 4 },
            { nombre: "Cebolla", cantidad: 4 },
            { nombre: "Zanahoria", cantidad: 4 },
            { nombre: "Harina de Maíz", cantidad: 4 }
        ],
        "Chicha de arroz": [
            { nombre: "Arroz", cantidad: 4 },
            { nombre: "Leche", cantidad: 4 },
            { nombre: "Azúcar", cantidad: 4 }
        ],
        "Chicha de espagueti": [
            { nombre: "Pasta", cantidad: 4 },
            { nombre: "Leche", cantidad: 4 },
            { nombre: "Azúcar", cantidad: 4 }
        ],
        "Chicha de paquete": [
            { nombre: "Chicha", cantidad: 8 },
            { nombre: "Leche", cantidad: 4 },
            { nombre: "Azúcar", cantidad: 4 }
        ]
    };

    function normalizarTexto(str) {
        return str ? str.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "").trim() : "";
    }

    function obtenerMejorCoincidenciaStock(nombreBuscado) {
        const datalist = document.getElementById('lista-productos');
        const opciones = Array.from(datalist.options);
        const buscadoNorm = normalizarTexto(nombreBuscado);

        if (!buscadoNorm || opciones.length === 0) return null;

        let encontrada = opciones.find(opt => normalizarTexto(opt.value) === buscadoNorm);
        if (encontrada) return encontrada.value;

        encontrada = opciones.find(opt => {
            const optNorm = normalizarTexto(opt.value);
            return optNorm.includes(buscadoNorm) || buscadoNorm.includes(optNorm);
        });
        if (encontrada) return encontrada.value;

        const palabras = buscadoNorm.split(' ');
        for (let p of palabras) {
            if (p.length > 2) {
                encontrada = opciones.find(opt => normalizarTexto(opt.value).includes(p));
                if (encontrada) return encontrada.value;
            }
        }

        return nombreBuscado;
    }

    function actualizarTituloReceta() {
        const select = document.getElementById('selectReceta');
        const titulo = document.getElementById('nombrePlatilloActual');
        
        if(select.value) {
            titulo.textContent = select.value;
            cargarIngredientesPredefinidos(select.value);
        } else {
            titulo.textContent = "Seleccione o cree una receta";
        }
    }

    function cargarIngredientesPredefinidos(nombreReceta) {
        if (!recetasPredefinidas[nombreReceta]) return;

        const contenedor = document.getElementById('contenedor-ingredientes');
        while(contenedor.children.length > 1) {
            contenedor.removeChild(contenedor.lastChild);
        }

        const ingredientes = recetasPredefinidas[nombreReceta];
        ingredientes.forEach((ing, index) => {
            let fila;
            if (index === 0) {
                fila = contenedor.children[0];
            } else {
                agregarFila();
                fila = contenedor.lastChild;
            }

            const inputProd = fila.querySelector('.input-producto');
            const nombreRealEnStock = obtenerMejorCoincidenciaStock(ing.nombre);
            
            inputProd.value = nombreRealEnStock;
            actualizarUnidadYStock(inputProd);

            const inputCant = fila.querySelector('.input-cantidad');
            inputCant.value = ing.cantidad;
        });
    }

    // Validación en tiempo real del modal: Solo letras, máximo 1 espacio entre palabras y 40 caracteres
    function validarNombreInput(input) {
        // 1. Eliminar cualquier carácter que NO sea letra o espacio
        let valor = input.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '');

        // 2. Reemplazar múltiples espacios consecutivos por un solo espacio
        valor = valor.replace(/\s+/g, ' ');

        input.value = valor;

        // Actualizar contador
        const contador = document.getElementById('contadorCaracteres');
        if (contador) {
            contador.textContent = `${input.value.length}/40`;
        }
    }

    function guardarNombreReceta() {
        const input = document.getElementById('nuevaRecetaInput');
        const valor = input.value.trim();

        if (valor === "") {
            alert("Por favor ingrese un nombre válido (solo letras y espacios).");
            return;
        }

        if (valor.length > 40) {
            alert("El nombre de la receta no puede superar los 40 caracteres.");
            return;
        }

        const select = document.getElementById('selectReceta');
        
        const opcionesExistentes = Array.from(select.options).map(opt => opt.value.toLowerCase());
        if (opcionesExistentes.includes(valor.toLowerCase())) {
            alert("Esa receta ya se encuentra en la lista.");
            return;
        }

        const nuevaOpcion = document.createElement('option');
        nuevaOpcion.value = valor;
        nuevaOpcion.textContent = valor;
        select.appendChild(nuevaOpcion);
        
        select.value = valor;
        
        document.getElementById('nombreNuevaRecetaBadge').textContent = valor;
        document.getElementById('alertaNuevaReceta').classList.remove('d-none');
        
        actualizarTituloReceta();
        
        const modalEl = document.getElementById('modalNuevaReceta');
        const modal = bootstrap.Modal.getInstance(modalEl);
        modal.hide();
        
        input.value = "";
        document.getElementById('contadorCaracteres').textContent = "0/40";
    }

    function actualizarUnidadYStock(input) {
        const filaActual = input.closest('.fila-ingrediente');
        const hiddenInput = filaActual.querySelector('.id-producto-oculto');
        const badgeUnidad = filaActual.querySelector('.badge-unidad');
        const datalist = document.getElementById('lista-productos');
        
        const inputValNorm = normalizarTexto(input.value);
        const opcionSeleccionada = Array.from(datalist.options).find(opt => normalizarTexto(opt.value) === inputValNorm);

        if (opcionSeleccionada) {
            const id = opcionSeleccionada.getAttribute('data-id');
            const unidad = opcionSeleccionada.getAttribute('data-unidad');

            let duplicado = false;
            const todasLasFilas = document.querySelectorAll('.fila-ingrediente');
            
            todasLasFilas.forEach(f => {
                if (f !== filaActual) {
                    const idOculto = f.querySelector('.id-producto-oculto').value;
                    if (idOculto === id) duplicado = true;
                }
            });

            if (duplicado) {
                const contenedorAlertas = document.getElementById('contenedor-alertas');
                const alerta = document.createElement('div');
                alerta.className = "alert alert-warning alert-dismissible fade show shadow-sm small";
                alerta.innerHTML = `<i class="fas fa-triangle-exclamation me-2"></i> El producto <b>${input.value}</b> ya está en la receta. <button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
                contenedorAlertas.appendChild(alerta);
                
                input.value = "";
                hiddenInput.value = "";
                badgeUnidad.textContent = "Und";
                return;
            }

            hiddenInput.value = id;         
            badgeUnidad.textContent = unidad; 
            
        } else {
            hiddenInput.value = "";
            badgeUnidad.textContent = "Und";
        }
    }

    function evaluarFilaIndividual(inputCantidad) {
        const fila = inputCantidad.closest('.fila-ingrediente');
        fila.classList.remove('bg-danger-subtle', 'border-danger', 'bg-success-subtle', 'border-success-subtle');
        fila.classList.add('bg-light');
    }

    function validarAntesDeEnviar(event) {
        const filas = document.querySelectorAll('.fila-ingrediente');
        let tieneIngredientes = false;

        filas.forEach(fila => {
            const idProducto = fila.querySelector('.id-producto-oculto').value;
            if (idProducto) tieneIngredientes = true;
        });

        if (!tieneIngredientes) {
            alert("Debes agregar al menos un ingrediente válido a la receta.");
            event.preventDefault();
            return false;
        }
        return true;
    }

    function agregarFila() {
        var contenedor = document.getElementById("contenedor-ingredientes");
        var nuevaFila = contenedor.children[0].cloneNode(true);
        
        nuevaFila.querySelector('.input-producto').value = "";
        nuevaFila.querySelector('.id-producto-oculto').value = "";
        nuevaFila.querySelector('.input-cantidad').value = "";
        nuevaFila.querySelector('.badge-unidad').textContent = "Und";
        
        nuevaFila.classList.remove('bg-danger-subtle', 'border-danger', 'bg-success-subtle', 'border-success-subtle');
        nuevaFila.classList.add('bg-light');
        
        nuevaFila.style.opacity = 0;
        contenedor.appendChild(nuevaFila);
        
        setTimeout(() => {
            nuevaFila.style.transition = "opacity 0.15s ease-in-out";
            nuevaFila.style.opacity = 1;
        }, 10);
    }

    function removerFila(boton) {
        var contenedor = document.getElementById("contenedor-ingredientes");
        if (contenedor.children.length > 1) {
            var fila = boton.closest('.fila-ingrediente');
            fila.style.opacity = 0;
            setTimeout(() => {
                fila.remove();
            }, 150);
        } else {
            alert("La receta debe tener al menos un ingrediente de base.");
        }
    }
    </script>
</body>
</html>