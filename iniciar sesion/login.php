<?php 
// 📌 NUEVA CORRECCIÓN: Evita que espacios en blanco o textos ocultos en conexion.php rompan el redireccionamiento
ob_start(); 

// 1. Configuración de sesión GLOBAL
ini_set('session.cookie_path', '/'); 
session_start();


// 2. SEGURIDAD: Redirección si ya está logueado
if (isset($_SESSION["usuario"]) && isset($_SESSION["rol"])) {
    header("Location: ../inicio.php");
    exit();
}

// 3. DEFINICIÓN DE RUTA RAÍZ DINÁMICA
$ruta_raiz = dirname(__DIR__); 

if (file_exists($ruta_raiz . "/conexion.php")) {
    include($ruta_raiz . "/conexion.php"); 
    include($ruta_raiz . "/registrar.php"); 
} else {
    include("conexion.php");
    include("registrar.php");
}

$error_login = ""; 

if (isset($_POST["boton"])) {
    // 📌 LIMITACIÓN DE CARACTERES (PHP): Recorta el input a un máximo de 20 caracteres
    $usuario = isset($_POST["Usuario"]) ? substr($_POST["Usuario"], 0, 20) : null; 
    $contrasena = isset($_POST["Contraseña"]) ? substr($_POST["Contraseña"], 0, 20) : null;

    if ($usuario && $contrasena) {
        if (isset($Conexion)) {
            $Conexion->set_charset("utf8");

            // 📌 CORRECCIÓN: Ajustamos las mayúsculas exactas según tu phpMyAdmin
            $sql = "SELECT Id, Usuario, Clave, rol, estado FROM usuarios WHERE Usuario = ?";
            
            if ($stmt = $Conexion->prepare($sql)) {
                $stmt->bind_param("s", $usuario);
                $stmt->execute();
                $resultado = $stmt->get_result();

                if ($resultado->num_rows === 1) {
                    $datos = $resultado->fetch_assoc();
                    
                    // 📌 CORRECCIÓN: Clave con "C" mayúscula
                    if ($contrasena === $datos["Clave"]) { 
                        
                        if ((int)$datos["estado"] === 1) {
                            
                            // 📌 CORRECCIÓN: Id y Usuario con sus respectivas mayúsculas
                            $_SESSION["id_usuario"] = $datos["Id"];
                            $_SESSION["usuario"]    = $datos["Usuario"];
                            $_SESSION["rol"]        = $datos["rol"]; 

                            session_write_close();

                            // Redirección directa hacia la raíz
                            header("Location: ../inicio.php");
                            exit();

                        } else {
                            $error_login = "Tu cuenta está inactiva (Estado 0). Contacta al administrador.";
                        }
                    } else {
                        $error_login = "Contraseña incorrecta.";
                    }
                } else {
                    $error_login = "El usuario no existe.";
                }
                $stmt->close();
            }
        } else {
            $error_login = "Error crítico: No se encontró la variable \$Conexion.";
        }
    }
}

if (isset($_POST["boton_registro"])) { // Cambia al nombre de tu botón de registro
    // 📌 LIMITACIÓN DE CARACTERES (PHP): Recorta a un máximo de 20 caracteres para el registro
    $usuario = isset($_POST["Usuario"]) ? substr($_POST["Usuario"], 0, 20) : null;
    $correo = $_POST["correo"] ?? null;
    $cedula = isset($_POST["cedula"]) ? substr($_POST["cedula"], 0, 20) : null;
    
    // 📌 PASO 1: Capturar la contraseña REAL del formulario y limitarla a 20 caracteres
    $contrasena_plana = isset($_POST["Contraseña"]) ? substr($_POST["Contraseña"], 0, 20) : null; 

    if ($usuario && $contrasena_plana) {
        if (isset($Conexion)) {
            
            // 📌 PASO 2: Encriptar la contraseña REAL ingresada
            $contrasena_encriptada = password_hash($contrasena_plana, PASSWORD_DEFAULT);

            // 📌 PASO 3: Insertar la variable encriptada en la consulta
            $sql = "INSERT INTO usuarios (Usuario, Clave, correo, cedula, estado, rol) VALUES (?, ?, ?, ?, 0, 'usuario')";
            
            if ($stmt = $Conexion->prepare($sql)) {
                // Se vincula la contraseña encriptada ($contrasena_encriptada)
                $stmt->bind_param("ssss", $usuario, $contrasena_encriptada, $correo, $cedula);
                
                if ($stmt->execute()) {
                    // Redireccionar al login con éxito
                    header("Location: login.php?registro=exitoso");
                    exit();
                } else {
                    echo "Error al registrar el usuario.";
                }
                $stmt->close();
            }
        }
    }
}
?>

<?php
// login.php
$mensaje_exito = isset($_GET['registro']) && $_GET['registro'] == 'exitoso';
?>

<?php if ($mensaje_exito): ?>
    <div class="alert alert-success alert-dismissible fade show text-dark shadow-sm position-fixed top-0 start-50 translate-middle-x mt-3" style="z-index: 1060;" role="alert">
        <i class="fa-solid fa-circle-check me-2 text-success"></i> 
        <strong>¡Registro exitoso!</strong> Tu cuenta ha sido creada y está pendiente de activación.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PAE</title>
    
    <link rel="shortcut icon" href="data:image/svg+xml,%3csvg%20xmlns='http://www.w3.org/2000/svg'%20viewBox='0%200%2033%2034'%20fill-rule='evenodd'%20stroke-linejoin='round'%20stroke-miterlimit='2'%20xmlns:v='https://vecta.io/nano'%3e%3cpath%20d='M3%2027.472c0%204.409%206.18%205.552%2013.5%205.552%207.281%200%2013.5-1.103%2013.5-5.513s-6.179-5.552-13.5-5.552c-7.281%200-13.5%201.103-13.5%205.513z'%20fill='%230d6efd'%20fill-rule='nonzero'/%3e%3ccircle%20cx='16.5'%20cy='8.8'%20r='8.8'%20fill='%23198754'/%3e%3c/svg%3e" type="image/x-icon">

    <link href="../startbootstrap-sb-admin-gh-pages/css/styles.css" rel="stylesheet" />
    <link href="../fontawesome-free-7.2.0-web/css/all.min.css" rel="stylesheet" />
    
    <style>
        /* 📌 Body transparente para que se muestre el contenedor del fondo */
        body {
            background: transparent !important;
        }

        .form-floating .form-control:focus {
            border-color: #212529;
            box-shadow: 0 0 0 0.25rem rgba(33, 37, 41, 0.15);
        }
        
        .btn-login-hover {
            transition: all 0.2s ease-in-out;
        }
        .btn-login-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(33, 37, 41, 0.3) !important;
        }

        /* 📌 CAPA INTERNA DE LA IMAGEN DE FONDO (Apuntando a la carpeta de al lado con ../) */
        .login-bg-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background-image: url('../photo_5037406883823684671_y.jpg'); 
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            z-index: -2;
        }

        /* 📌 CAPA DE DIFUMINADO EQUILIBRADA (No tapa por completo la foto) */
        .blur-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: linear-gradient(145deg, rgba(30, 34, 41, 0.45) 0%, rgba(17, 20, 24, 0.6) 100%);
            backdrop-filter: blur(2px); 
            -webkit-backdrop-filter: blur(8px);
            z-index: -1;
        }

        .text-shadow-hd {
            text-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
        }
        
        .text-shadow-sm {
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.4);
        }

        /* 📌 Animación suave de la Tarjeta del Formulario */
        .floating-card-smooth {
            animation: floatCard 6s ease-in-out infinite alternate;
            display: block;
        }

        @keyframes floatCard {
            0% { transform: translateY(0px); box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2); }
            100% { transform: translateY(-8px); box-shadow: 0 25px 40px rgba(0, 0, 0, 0.35); }
        }

        /* 📌 ESTILOS NUEVOS PARA EL BOTÓN OJO REACCIONANDO A FORM-FLOATING */
        .password-container {
            position: relative;
        }

        /* Ajuste de espacio para que la contraseña escrita no choque con el icono del ojo */
        .password-container #inputContrasena {
            padding-right: 50px;
        }

        /* Posicionamiento del botón de alternar */
        .toggle-password-btn {
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            background: none;
            border: none;
            padding: 5px;
            color: #6c757d;
            cursor: pointer;
            z-index: 10;
            transition: color 0.2s ease-in-out;
        }

        .toggle-password-btn:hover {
            color: #212529;
        }
    </style>
</head>

<body style="min-height: 100vh; overflow-x: hidden;">

    <div class="login-bg-container"></div>
    <div class="blur-overlay"></div>

    <div class="container d-flex align-items-center justify-content-center" style="min-height: 100vh;">
        <div class="row w-100 justify-content-center">
            
            <div class="col-12 col-md-8 col-lg-5 floating-card-smooth">
                <div class="card border-0 shadow-lg rounded-4 bg-white overflow-hidden">
                    <div class="card-body p-4 p-md-5">
                        
                        <div class="mb-4 text-center">
                            <h3 class="fw-bold text-primary mb-1">
                                <i class="fa-solid fa-wheat-awn text-success me-2"></i> PAE
                            </h3>
                            <h4 class="fw-bold text-dark mt-3 mb-1">Ingresar al Sistema</h4>
                            <p class="text-muted small mb-0">Bienvenido al Sistema de Gestión de Alimentos e Inventario.</p>
                        </div>

                        <?php if (!empty($error_login)): ?>
                            <div class="alert alert-danger d-flex align-items-center small py-2 px-3 mb-4 border-0 shadow-sm" role="alert">
                                <i class="fa-solid fa-triangle-exclamation me-2 text-danger"></i>
                                <div class="text-dark"><?php echo $error_login; ?></div>
                            </div>
                        <?php endif; ?>

                       <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                            <div class="form-floating mb-3 text-dark">
                                <!-- 📌 HTML: Se agrega maxlength="20" para impedir escribir más de 20 caracteres -->
                                <input class="form-control" id="inputUsuario" name="Usuario" type="text" placeholder="Nombre de usuario.." maxlength="20" required />
                                <label for="inputUsuario"><i class="fa-solid fa-user me-2 text-primary opacity-75"></i>Usuario</label>
                            </div>
                            
                            <div class="form-floating mb-4 text-dark password-container">
                                <!-- 📌 HTML: Se agrega maxlength="20" -->
                                <input class="form-control" id="inputContrasena" name="Contraseña" type="password" placeholder="Contraseña" maxlength="20" required />
                                <label for="inputContrasena"><i class="fa-solid fa-lock me-2 text-primary opacity-75"></i>Contraseña</label>
                                <button type="button" class="toggle-password-btn" id="togglePasswordBtn" aria-label="Mostrar u ocultar contraseña">
                                    <i class="fa-solid fa-eye" id="togglePasswordIcon"></i>
                                </button>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button class="btn btn-primary btn-lg shadow btn-login-hover fw-bold py-3" name="boton" type="submit">
                                    Iniciar Sesión <i class="fa-solid fa-arrow-right-to-bracket ms-2 small"></i>
                                </button>
                            </div>
                        </form>

                        <div class="text-center mt-4 small">
                            <p class="mb-2 text-muted">
                                ¿No tiene una cuenta? 
                                <a href="registrar_cuenta.php" class="text-decoration-none fw-semibold border-bottom border-primary border-opacity-25 pb-1 text-primary">
                                    Registrarse aquí
                                </a>
                            </p>
                            <p class="mb-2 text-muted">
                                ¿Olvidaste tu contraseña? 
                                <a href="/iniciar sesion/cambiar_contrasena.php" class="text-decoration-none fw-semibold border-bottom border-primary border-opacity-25 pb-1 text-primary">
                                    Cambiar contraseña
                                </a>
                            </p>
                            <p class="mb-0">
                                <a href="#" class="text-decoration-none text-success fw-bold" data-bs-toggle="modal" data-bs-target="#modalInformacion">
                                    <i class="fa-solid fa-circle-info me-1"></i> Ver más sobre el sistema
                                </a>
                            </p>
                        </div>

                    </div>
                </div>
            </div>
            
        </div>
    </div>

    <div class="modal fade" id="modalInformacion" tabindex="-1" aria-labelledby="modalInformacionLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold" id="modalInformacionLabel">
                        <i class="fa-solid fa-wheat-awn text-success me-2"></i> Características del Sistema PAE
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-dark">
                    <p class="text-muted small mb-4">A continuación se detallan los módulos integrales con los que cuenta la plataforma para optimizar la gestión alimentaria:</p>
                    
                    <div class="d-flex align-items-start mb-4">
                        <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-3 me-3">
                            <i class="fa-solid fa-boxes-stacked fs-5"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1">Control de Stock</h6>
                            <p class="small text-muted mb-0">Supervise niveles exactos de existencias, reciba alertas tempranas ante escasez de insumos y administre entradas/salidas de almacén.</p>
                        </div>
                    </div>

                    <div class="d-flex align-items-start mb-4">
                        <div class="bg-success bg-opacity-10 text-success p-2 rounded-3 me-3">
                            <i class="fa-solid fa-kitchen-set fs-5"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1">Gestión de Menús</h6>
                            <p class="small text-muted mb-0">Planificación estructurada y balanceada de raciones alimenticias según el stock real disponible en las bodegas.</p>
                        </div>
                    </div>

                    <div class="d-flex align-items-start">
                        <div class="bg-warning bg-opacity-10 text-warning p-2 rounded-3 me-3">
                            <i class="fa-solid fa-calculator fs-5"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1">Descuento Automatizado</h6>
                            <p class="small text-muted mb-0">El sistema realiza la deducción lógica e inmediata del inventario de manera automática tras procesar las porciones cocinadas.</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-secondary w-100 fw-bold py-2" data-bs-modal="modal" data-bs-dismiss="modal">Entendido</button>
                </div>
            </div>
        </div>
    </div>

    <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="./login_validacion.js"></script>
    <script>
    window.onpageshow = function(event) {
        if (event.persisted || (window.performance && window.performance.navigation.type === 2)) {
            window.location.reload();
        }
    };

    // 📌 SCRIPT INTERACTIVO: Permite mostrar/ocultar la clave alternando el icono de FontAwesome
    document.addEventListener('DOMContentLoaded', function() {
        const passwordInput = document.getElementById('inputContrasena');
        const toggleBtn = document.getElementById('togglePasswordBtn');
        const toggleIcon = document.getElementById('togglePasswordIcon');

        toggleBtn.addEventListener('click', function() {
            // Alternar tipo de input
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        });
    });
    </script>
</body>
</html>