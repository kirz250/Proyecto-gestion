<?php
// 1. INICIAR SESIÓN GLOBAL
ini_set('session.cookie_path', '/'); 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. GUARDIÁN DE SEGURIDAD CENTRALIZADO (Corregido con ../ para salir de la carpeta)
include(dirname(__DIR__) . "/iniciar sesion/verificar_admin.php"); 

// 3. INCLUIR CONEXIÓN PRINCIPAL (Corregido con ../ para salir de la carpeta)
require_once(dirname(__DIR__) . "/iniciar sesion/conexion.php");

if (!isset($Conexion)) {
    die("Error: No se pudo establecer la conexión con la base de datos.");
}

$Conexion->set_charset("utf8");

// 4. LÓGICA PARA CAMBIAR EL ESTADO (ACTIVAR / DESACTIVAR)
if (isset($_GET['accion']) && isset($_GET['id'])) {
    $id_user = (int)$_GET['id'];
    $nuevo_estado = ($_GET['accion'] === 'activar') ? 1 : 0;

    if ($id_user === (int)$_SESSION['id_usuario'] && $nuevo_estado === 0) {
        echo "<script>alert('¡Error! No puedes desactivar tu propia cuenta.'); window.location='gestionar_usuarios.php';</script>";
        exit();
    }

    $stmt = $Conexion->prepare("UPDATE usuarios SET estado = ? WHERE Id = ?");
    $stmt->bind_param("ii", $nuevo_estado, $id_user);
    if ($stmt->execute()) {
        header("Location: gestionar_usuarios.php");
        exit();
    }
    $stmt->close();
}

// 5. LÓGICA PARA ACTUALIZAR CONTRASEÑA DESDE EL PANEL (Usa la columna 'clave')
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_clave'])) {
    $id_user = (int)$_POST['id_usuario_clave'];
    $nueva_clave = trim($_POST['nueva_contrasena']);

    if (!empty($nueva_clave)) {
        $clave_final = $nueva_clave; 

        $stmt = $Conexion->prepare("UPDATE usuarios SET clave = ? WHERE Id = ?");
        $stmt->bind_param("si", $clave_final, $id_user);
        
        if ($stmt->execute()) {
            echo "<script>alert('Contraseña actualizada con éxito.'); window.location='gestionar_usuarios.php';</script>";
            exit();
        } else {
            echo "<script>alert('Error al actualizar la contraseña.');</script>";
        }
        $stmt->close();
    }
}

// 6. OBTENER TODOS LOS USUARIOS (Se incluye 'clave' para poder visualizarla al interactuar)
$resultado = $Conexion->query("SELECT Id, Usuario, clave, rol, estado FROM usuarios ORDER BY Id ASC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Gestionar Usuarios - PAE</title>
    <link href="../startbootstrap-sb-admin-gh-pages/css/styles.css" rel="stylesheet" />
    <link href="../css/styles.css" rel="stylesheet" />
    <script src="../fontawesome-free-7.2.0-web/js/all.js"></script>

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
        <a class="navbar-brand ps-3" href="../inicio.php">Nueva Toledo</a>
        <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!"><i class="fas fa-bars"></i></button>
        <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0"></form>
        <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="text-white me-2 small"><?php echo htmlspecialchars($_SESSION['usuario']); ?></span>
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
                        <a class="nav-link" href="inicio.php">
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
                        <a class="nav-link active" href="#" data-bs-toggle="collapse" data-bs-target="#collapsePages" aria-expanded="false" aria-controls="collapsePages">
                            <div class="sb-nav-link-icon"><i class="fas fa-book-open"></i></div>
                            Reportes
                            <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                        </a>

                        <div class="collapse" id="collapsePages" aria-labelledby="headingTwo" data-bs-parent="#sidenavAccordion">
                            <nav class="sb-sidenav-menu-nested nav">
                                <a class="nav-link" href="/registrar_producto/reporte.php">Reportes</a>
                                <a class="nav-link active" href="/registrar_producto/gestionar_usuarios.php">Gestionar usuarios</a>
                            </nav>
                        </div>
                        <div class="sb-sidenav-menu-heading">Insumos</div>
                        <a class="nav-link" href="productos.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-chart-area"></i></div>
                            Catálogo de Insumos
                        </a>
                        <a class="nav-link" href="stock.php">
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
                            <h1 class="mb-0">Gestión de Usuarios</h1>
                            <!-- BOTÓN INTEGRADO DE AYUDA -->
                            <button type="button" class="btn btn-warning text-dark fw-bold rounded-pill shadow-sm px-3 py-1 btn-ayuda-destacado" data-bs-toggle="modal" data-bs-target="#modalGuiaUsuarios">
                                <i class="fas fa-circle-question fa-bounce text-dark me-1"></i> ¿Cómo gestionar usuarios?
                            </button>
                        </div>
                    </div>

                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="../inicio.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Usuarios</li>
                    </ol>

                    <div class="card mb-4 shadow-sm">
                        <div class="card-header bg-dark text-white">
                            <i class="fas fa-users me-1"></i> Control de Accesos y Seguridad
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped align-middle text-center">
                                    <thead class="table-secondary">
                                        <tr>
                                            <th>ID</th>
                                            <th>Nombre de Usuario</th>
                                            <th>Contraseña</th>
                                            <th>Rol asignado</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($resultado && $resultado->num_rows > 0): ?>
                                            <?php while($user = $resultado->fetch_object()): ?>
                                                <tr>
                                                    <td><?= $user->Id ?></td>
                                                    <td class="fw-bold text-start ps-3"><?= htmlspecialchars($user->Usuario) ?></td>
                                                    <td>
                                                        <div class="d-inline-flex align-items-center justify-content-center">
                                                            <code class="text-muted me-2" id="txt_clave_<?= $user->Id ?>" data-real="<?= htmlspecialchars($user->clave) ?>">********</code>
                                                            
                                                            <button class="btn btn-sm btn-link text-secondary p-0 me-2" type="button" onclick="toggleFilaClave(<?= $user->Id ?>)">
                                                                <i class="fas fa-eye" id="ojo_fila_<?= $user->Id ?>"></i>
                                                            </button>
                                                            
                                                            <span class="text-muted me-2">|</span>
                                                            
                                                            <button class="btn btn-sm btn-link text-decoration-none p-0" 
                                                                    onclick="abrirModalClave(<?= $user->Id ?>, '<?= htmlspecialchars($user->Usuario) ?>')">
                                                                <i class="fas fa-key"></i> Cambiar
                                                            </button>
                                                        </div>
                                                    </td>
                                                    <td><span class="badge bg-info text-dark"><?= htmlspecialchars($user->rol) ?></span></td>
                                                    <td>
                                                        <?php if ((int)$user->estado === 1): ?>
                                                            <span class="badge bg-success"><i class="fas fa-check-circle"></i> Activo</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger"><i class="fas fa-times-circle"></i> Inactivo</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ((int)$user->estado === 1): ?>
                                                            <a href="gestionar_usuarios.php?accion=desactivar&id=<?= $user->Id ?>" 
                                                               class="btn btn-sm btn-outline-danger"
                                                               onclick="return confirm('¿Seguro que deseas desactivar a el usuario <?= htmlspecialchars($user->Usuario) ?>?')">
                                                                <i class="fas fa-user-slash"></i> Desactivar
                                                            </a>
                                                        <?php else: ?>
                                                            <a href="gestionar_usuarios.php?accion=activar&id=<?= $user->Id ?>" 
                                                               class="btn btn-sm btn-success">
                                                                <i class="fas fa-user-check"></i> Activar
                                                            </a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center text-muted">No hay usuarios registrados.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            
            <footer class="py-4 bg-light mt-auto">
                <div class="container-fluid px-4 text-center text-muted small">
                    PAE Software &copy; 2026
                </div>
            </footer>
        </div>
    </div>

    <!-- MODAL GUÍA DE USO PARA LA GESTIÓN DE USUARIOS -->
    <div class="modal fade" id="modalGuiaUsuarios" tabindex="-1" aria-labelledby="modalGuiaUsuariosLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="modalGuiaUsuariosLabel">
                        <i class="fas fa-book-open me-2"></i>Guía: Gestión de Usuarios
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">
                        Administra las credenciales, estados de acceso y niveles de permisos de los usuarios del sistema PAE:
                    </p>

                    <ol class="list-group list-group-numbered list-group-flush mb-0">
                        <li class="list-group-item d-flex align-items-start border-0 px-0 py-2">
                            <div class="ms-2 me-auto">
                                <div class="fw-bold text-dark">Visualización Segura de Contraseñas</div>
                                <span class="text-secondary small">Haz clic en el icono del ojo <i class="fas fa-eye text-secondary"></i> para revelar temporalmente la contraseña almacenada de una fila específica.</span>
                            </div>
                        </li>
                        <li class="list-group-item d-flex align-items-start border-0 px-0 py-2">
                            <div class="ms-2 me-auto">
                                <div class="fw-bold text-dark">Cambiar Contraseña de un Usuario</div>
                                <span class="text-secondary small">Presiona el enlace <b>"Cambiar"</b> <i class="fas fa-key text-primary"></i> para desplegar el modal de actualización directa de contraseña sin abandonar la vista.</span>
                            </div>
                        </li>
                        <li class="list-group-item d-flex align-items-start border-0 px-0 py-2">
                            <div class="ms-2 me-auto">
                                <div class="fw-bold text-dark">Activar o Desactivar Accesos</div>
                                <span class="text-secondary small">Utiliza los botones de acción <i class="fas fa-user-slash text-danger"></i> <b>Desactivar</b> o <i class="fas fa-user-check text-success"></i> <b>Activar</b> para denegar o permitir el ingreso al sistema de forma inmediata.</span>
                            </div>
                        </li>
                        <li class="list-group-item d-flex align-items-start border-0 px-0 py-2">
                            <div class="ms-2 me-auto">
                                <div class="fw-bold text-dark">Protección de Cuenta Actual</div>
                                <span class="text-secondary small">Por reglas de seguridad centralizadas, el sistema impedirá que desactives tu propia sesión activa.</span>
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

    <!-- MODAL DE CAMBIO DE CONTRASEÑA -->
    <div class="modal fade" id="modalContrasena" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form action="gestionar_usuarios.php" method="POST" class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title"><i class="fas fa-lock me-2"></i>Cambiar Contraseña</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_usuario_clave" id="id_usuario_clave">
                    <p>Vas a cambiar la clave del usuario: <strong id="nombre_usuario_modal" class="text-primary"></strong></p>
                    <div class="mb-3">
                        <label for="nueva_contrasena" class="form-label">Nueva Contraseña</label>
                        <div class="input-group">
                            <input type="password" class="form-control" name="nueva_contrasena" id="nueva_contrasena" required minlength="4" placeholder="Escribe la nueva clave...">
                            <button class="btn btn-outline-secondary" type="button" id="btnToggleClave" onclick="toggleVisibilidadClave()">
                                <i class="fas fa-eye" id="iconoOjo"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="cambiar_clave" class="btn btn-primary">Guardar Nueva Clave</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../startbootstrap-sb-admin-gh-pages/js/scripts.js"></script>
    <script>
        function abrirModalClave(id, usuario) {
            document.getElementById('id_usuario_clave').value = id;
            document.getElementById('nombre_usuario_modal').innerText = usuario;
            
            const inputClave = document.getElementById('nueva_contrasena');
            const iconoOjo = document.getElementById('iconoOjo');
            inputClave.value = '';
            inputClave.type = 'password';
            iconoOjo.className = 'fas fa-eye';
            
            var miModal = new bootstrap.Modal(document.getElementById('modalContrasena'));
            miModal.show();
        }

        // Función para ocultar/mostrar la contraseña dentro del Modal
        function toggleVisibilidadClave() {
            const inputClave = document.getElementById('nueva_contrasena');
            const iconoOjo = document.getElementById('iconoOjo');

            if (inputClave.type === 'password') {
                inputClave.type = 'text';
                iconoOjo.className = 'fas fa-eye-slash';
            } else {
                inputClave.type = 'password';
                iconoOjo.className = 'fas fa-eye';
            }
        }

        // Función para revelar u ocultar la clave real reemplazando el texto de los asteriscos
        function toggleFilaClave(id) {
            const componenteTexto = document.getElementById('txt_clave_' + id);
            const iconoOjo = document.getElementById('ojo_fila_' + id);
            const claveReal = componenteTexto.getAttribute('data-real');

            if (componenteTexto.innerText === '********') {
                componenteTexto.innerText = claveReal;
                componenteTexto.classList.remove('text-muted');
                componenteTexto.classList.add('fw-bold', 'text-dark');
                iconoOjo.className = 'fas fa-eye-slash';
            } else {
                componenteTexto.innerText = '********';
                componenteTexto.classList.remove('fw-bold', 'text-dark');
                componenteTexto.classList.add('text-muted');
                iconoOjo.className = 'fas fa-eye';
            }
        }
    </script>
</body>
</html>