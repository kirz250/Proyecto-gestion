<?php
ob_start();
ini_set('session.cookie_path', '/');
session_start();

// Importar la conexión (Verificando la raíz de tu proyecto2026)
$ruta_raiz = dirname(__DIR__);
if (file_exists($ruta_raiz . "/conexion.php")) {
    include($ruta_raiz . "/conexion.php");
} else {
    include("conexion.php"); // Por si está en la misma carpeta
}

// Asegurar que la conexión use UTF-8
if (isset($Conexion)) {
    $Conexion->set_charset("utf8");
}

$error = "";
$exito = "";
$paso = 1; // Controla las vistas (1: Buscar usuario, 2: Preguntas, 3: Nueva clave, 4: Fin)

// Inicializar variables de sesión temporales para la recuperación
if (!isset($_SESSION['recuperar_usuario_id'])) {
    $_SESSION['recuperar_usuario_id'] = null;
    $_SESSION['respuestas_correctas'] = [];
}

/* ==========================================================================
   PASO 1: VERIFICAR USUARIO O CORREO Y TRAER SUS RESPUESTAS REGISTRADAS
   ========================================================================== */
if (isset($_POST['btn_buscar_usuario'])) {
    $identificador = trim($_POST['identificador'] ?? '');

    if (!empty($identificador)) {
        // Buscamos el ID del usuario primero
        $sql_user = "SELECT Id, Usuario FROM usuarios WHERE Usuario = ? OR correo = ?";
        if ($stmt_user = $Conexion->prepare($sql_user)) {
            $stmt_user->bind_param("ss", $identificador, $identificador);
            $stmt_user->execute();
            $res_user = $stmt_user->get_result();

            if ($res_user->num_rows === 1) {
                $user_data = $res_user->fetch_assoc();
                $id_usuario = $user_data['Id'];

                // Ahora buscamos sus respuestas en la tabla 'usuario_respuestas'
                // Traemos el id_pregunta_cat para saber a cuál corresponde cada respuesta
                $sql_resp = "SELECT id_pregunta_cat, respuesta FROM usuario_respuestas WHERE id_usuario = ?";
                if ($stmt_resp = $Conexion->prepare($sql_resp)) {
                    $stmt_resp->bind_param("i", $id_usuario);
                    $stmt_resp->execute();
                    $res_resp = $stmt_resp->get_result();

                    if ($res_resp->num_rows > 0) {
                        $_SESSION['recuperar_usuario_id'] = $id_usuario;
                        $_SESSION['respuestas_correctas'] = [];

                        // Guardamos las respuestas esperadas usando el ID de la pregunta como clave
                        while ($row = $res_resp->fetch_assoc()) {
                            $_SESSION['respuestas_correctas'][$row['id_pregunta_cat']] = $row['respuesta'];
                        }

                        $paso = 2; // Avanzamos al formulario de preguntas
                    } else {
                        $error = "El usuario existe, pero no tiene preguntas de seguridad registradas.";
                    }
                    $stmt_resp->close();
                }
            } else {
                $error = "Usuario o correo electrónico no encontrado.";
            }
            $stmt_user->close();
        } else {
            $error = "Error en la base de datos: " . $Conexion->error;
        }
    } else {
        $error = "Por favor, introduzca su usuario o correo electrónico.";
    }
}

/* ==========================================================================
   PASO 2: VALIDAR RESPUESTAS DINÁMICAS (MÍNIMO 2 DE 3)
   ========================================================================== */
if (isset($_POST['btn_verificar_preguntas'])) {
    $paso = 2; 
    
    // Capturamos las respuestas enviadas asociándolas al ID correspondiente en el catálogo
    // 1 -> Color, 2 -> Película, 3 -> Mascota (según tus INSERTS en preguntas_catalogo)
    $respuestas_usuario = [
        1 => trim($_POST['resp_color'] ?? ''),
        2 => trim($_POST['resp_pelicula'] ?? ''),
        3 => trim($_POST['resp_mascota'] ?? '')
    ];

    $aciertos = 0;
    $total_preguntas_configuradas = 0;

    // Evaluamos dinámicamente las respuestas almacenadas en la sesión
    foreach ($_SESSION['respuestas_correctas'] as $id_pregunta => $respuesta_correcta) {
        $total_preguntas_configuradas++;
        if (isset($respuestas_usuario[$id_pregunta]) && !empty($respuestas_usuario[$id_pregunta])) {
            if (strtolower($respuestas_usuario[$id_pregunta]) === strtolower($respuesta_correcta)) {
                $aciertos++;
            }
        }
    }

    // Si acertó al menos 2, o si tiene menos de 2 configuradas pero las acertó todas
    if ($aciertos >= 2 || ($total_preguntas_configuradas < 2 && $aciertos == $total_preguntas_configuradas && $aciertos > 0)) {
        $paso = 3; // ¡Validación exitosa!
    } else {
        $error = "Respuestas incorrectas. Debe acertar al menos 2 de las preguntas para continuar.";
    }
}

/* ==========================================================================
   PASO 3: ACTUALIZAR LA CONTRASEÑA
   ========================================================================== */
if (isset($_POST['btn_actualizar_clave'])) {
    $paso = 3; 
    
    $nueva_clave = $_POST['nueva_clave'] ?? '';
    $confirmar_clave = $_POST['confirmar_clave'] ?? '';
    $id_usuario = $_SESSION['recuperar_usuario_id'];

    if (!empty($nueva_clave) && !empty($confirmar_clave)) {
        if ($nueva_clave === $confirmar_clave) {
            
            $sql_update = "UPDATE usuarios SET Clave = ? WHERE Id = ?";
            if ($stmt_update = $Conexion->prepare($sql_update)) {
                $stmt_update->bind_param("si", $nueva_clave, $id_usuario);
                
                if ($stmt_update->execute()) {
                    $exito = "¡Contraseña renovada con éxito!";
                    
                    unset($_SESSION['recuperar_usuario_id']);
                    unset($_SESSION['respuestas_correctas']);
                    
                    $paso = 4; 
                } else {
                    $error = "Error al actualizar la contraseña en el sistema.";
                }
                $stmt_update->close();
            }
        } else {
            $error = "Las contraseñas introducidas no coinciden.";
        }
    } else {
        $error = "Por favor, complete ambos campos de contraseña.";
    }
}

// Si cancela o decide dejarla como está
if (isset($_POST['btn_cancelar'])) {
    unset($_SESSION['recuperar_usuario_id']);
    unset($_SESSION['respuestas_correctas']);
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - PAE</title>
    
    <link rel="shortcut icon" href="data:image/svg+xml,%3csvg%20xmlns='http://www.w3.org/2000/svg'%20viewBox='0%200%2033%2034'%20fill-rule='evenodd'%20stroke-linejoin='round'%20stroke-miterlimit='2'%20xmlns:v='https://vecta.io/nano'%3e%3cpath%20d='M3%2027.472c0%204.409%206.18%205.552%2013.5%205.552%207.281%200%2013.5-1.103%2013.5-5.513s-6.179-5.552-13.5-5.552c-7.281%200-13.5%201.103-13.5%205.513z'%20fill='%230d6efd'%20fill-rule='nonzero'/%3e%3ccircle%20cx='16.5'%20cy='8.8'%20r='8.8'%20fill='%23198754'/%3e%3c/svg%3e" type="image/x-icon">

    <link href="../startbootstrap-sb-admin-gh-pages/css/styles.css" rel="stylesheet" />
    <link href="../fontawesome-free-7.2.0-web/css/all.min.css" rel="stylesheet" />
    <style>
        .form-floating .form-control:focus {
            border-color: #212529;
            box-shadow: 0 0 0 0.25rem rgba(33, 37, 41, 0.15);
        }
        .btn-hover-effect {
            transition: all 0.2s ease-in-out;
        }
        .btn-hover-effect:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15) !important;
        }
    </style>
</head>

<body class="bg-light" style="min-height: 100vh;">

    <div class="container d-flex align-items-center justify-content-center" style="min-height: 100vh;">
        <div class="row w-100 justify-content-center">
            <div class="col-12 col-md-8 col-lg-5">
                <div class="card border-0 shadow-lg rounded-4 bg-white overflow-hidden">
                    <div class="card-body p-4 p-md-5">

                        <div class="mb-4 text-center">
                            <h3 class="fw-bold text-primary mb-1">
                                <i class="fa-solid fa-shield-halved text-success me-2"></i> Módulo PAE
                            </h3>
                            <h4 class="fw-bold text-dark mt-3 mb-1">Recuperación de Cuenta</h4>
                            <p class="text-muted small mb-0">Verifique su identidad mediante sus preguntas secretas.</p>
                        </div>

                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger d-flex align-items-center small py-2 px-3 mb-4 border-0 shadow-sm" role="alert">
                                <i class="fa-solid fa-triangle-exclamation me-2"></i>
                                <div><?php echo $error; ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($exito)): ?>
                            <div class="alert alert-success d-flex align-items-center small py-2 px-3 mb-4 border-0 shadow-sm" role="alert">
                                <i class="fa-solid fa-circle-check me-2"></i>
                                <div><?php echo $exito; ?></div>
                            </div>
                        <?php endif; ?>


                        <?php if ($paso === 1): ?>
                            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                                <p class="text-muted small text-center mb-4">Ingrese su nombre de usuario o correo electrónico para iniciar la comprobación.</p>
                                
                                <div class="form-floating mb-4 text-dark">
                                    <input class="form-control" id="inputIdentificador" name="identificador" type="text" placeholder="Usuario o Correo..." required />
                                    <label for="inputIdentificador"><i class="fa-solid fa-user me-2 text-primary opacity-75"></i>Usuario o Correo</label>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button class="btn btn-primary btn-lg shadow btn-hover-effect fw-bold py-3" name="btn_buscar_usuario" type="submit">
                                        Validar Usuario <i class="fa-solid fa-arrow-right ms-2 small"></i>
                                    </button>
                                    <a href="login.php" class="btn btn-link link-secondary text-decoration-none small mt-2">Regresar al Login</a>
                                </div>
                            </form>
                        <?php endif; ?>


                        <?php if ($paso === 2): ?>
                            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                                <div class="alert alert-warning py-2 px-3 small border-0 mb-4 text-dark">
                                    <i class="fa-solid fa-key me-1 text-primary"></i> Sistema: Responda correctamente al menos <b>2 de las 3 preguntas</b>.
                                </div>

                                <div class="form-floating mb-3 text-dark">
                                    <input class="form-control" id="resp_color" name="resp_color" type="text" placeholder="Respuesta" required autocomplete="off" />
                                    <label for="resp_color"><i class="fa-solid fa-palette me-2 text-success opacity-75"></i>¿Cuál es tu color favorito?</label>
                                </div>

                                <div class="form-floating mb-3 text-dark">
                                    <input class="form-control" id="resp_pelicula" name="resp_pelicula" type="text" placeholder="Respuesta" required autocomplete="off" />
                                    <label for="resp_pelicula"><i class="fa-solid fa-film me-2 text-success opacity-75"></i>¿Cuál es tu película favorita?</label>
                                </div>

                                <div class="form-floating mb-4 text-dark">
                                    <input class="form-control" id="resp_mascota" name="resp_mascota" type="text" placeholder="Respuesta" required autocomplete="off" />
                                    <label for="resp_mascota"><i class="fa-solid fa-dog me-2 text-success opacity-75"></i>¿Nombre de tu primera mascota?</label>
                                </div>

                                <div class="d-grid gap-2">
                                    <button class="btn btn-success btn-lg shadow btn-hover-effect fw-bold py-3" name="btn_verificar_preguntas" type="submit">
                                        Comprobar Respuestas <i class="fa-solid fa-user-shield ms-2 small"></i>
                                    </button>
                                    <a href="login.php" class="btn btn-light border text-muted fw-bold py-2 mt-1 w-100 text-decoration-none">
    Dejar como está y salir
</a>
                                </div>
                            </form>
                        <?php endif; ?>


                        <?php if ($paso === 3): ?>
                            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                                <div class="alert alert-success py-2 px-3 small border-0 mb-4">
                                    <i class="fa-solid fa-unlock me-1"></i> Acceso concedido. Puede cambiar su contraseña o dejarla intacta.
                                </div>

                               <div class="form-floating mb-3 text-dark position-relative">
    <input class="form-control" id="nueva_clave" name="nueva_clave" type="password" placeholder="Nueva Contraseña" required />
    <label for="nueva_clave"><i class="fa-solid fa-lock me-2 text-primary opacity-75"></i>Nueva Contraseña</label>
    <i class="fa-solid fa-eye password-toggle" onclick="togglePass('nueva_clave')"></i>
</div>

<div class="form-floating mb-4 text-dark position-relative">
    <input class="form-control" id="confirmar_clave" name="confirmar_clave" type="password" placeholder="Confirmar Contraseña" required />
    <label for="confirmar_clave"><i class="fa-solid fa-check-double me-2 text-primary opacity-75"></i>Confirmar Contraseña</label>
    <i class="fa-solid fa-eye password-toggle" onclick="togglePass('confirmar_clave')"></i>
</div>
                                <div class="d-grid gap-2">
                                    <button class="btn btn-primary btn-lg shadow btn-hover-effect fw-bold py-3" name="btn_actualizar_clave" type="submit">
                                        Renovar Contraseña <i class="fa-solid fa-save ms-2 small"></i>
                                    </button>
                                    
                                    <button class="btn btn-outline-secondary btn-md fw-bold py-2" 
        type="button" 
        onclick="window.location.href='login.php';">
    Dejar como está (Conservar actual)
</button>
                                </div>
                            </form>
                        <?php endif; ?>


                        <?php if ($paso === 4): ?>
                            <div class="text-center py-3">
                                <i class="fa-solid fa-circle-check text-success fa-3x mb-3 animate__animated animate__bounceIn"></i>
                                <p class="text-dark fw-semibold">¡Cambio completado con éxito!</p>
                                <div class="d-grid gap-2 mt-4">
                                    <a href="login.php" class="btn btn-primary btn-lg fw-bold shadow py-3">
                                        Iniciar Sesión <i class="fa-solid fa-right-to-bracket ms-2"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1055;">
    <div id="errorToast" class="toast align-items-center text-white bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <i class="fa-solid fa-triangle-exclamation me-2"></i> <strong>Error:</strong> Las contraseñas no coinciden.
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>


<style>
    /* 1. Reservamos espacio a la derecha para que el texto no toque el ojo */
    .form-control {
        padding-right: 45px !important; 
    }

    /* 2. Posicionamos el ojo dentro del input */
    .password-toggle {
        position: absolute;
        right: 15px;      /* Distancia desde el borde derecho */
        top: 50%;         /* Centrado vertical */
        transform: translateY(-50%);
        cursor: pointer;
        z-index: 10;
        color: #6c757d;
        transition: color 0.2s;
    }

    .password-toggle:hover {
        color: #0d6efd;
    }
</style>

    <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
    // Función para mostrar el ojito
    function togglePass(id) {
        const input = document.getElementById(id);
        input.type = (input.type === "password") ? "text" : "password";
    }

    // Validación elegante
    const form = document.querySelector('form');
    form.addEventListener('submit', function(event) {
        const pass1 = document.getElementById('nueva_clave').value;
        const pass2 = document.getElementById('confirmar_clave').value;

        if (pass1 !== pass2) {
            event.preventDefault(); // Detiene el envío del formulario
            
            // Activamos el Toast de forma bonita
            const toastEl = document.getElementById('errorToast');
            const toast = new bootstrap.Toast(toastEl);
            toast.show();
        }
    });
</script>

</body>
</html>