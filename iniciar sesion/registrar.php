<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Definición de ruta dinámica para la conexión
$ruta_raiz_reg = dirname(__DIR__);
if (file_exists($ruta_raiz_reg . "/conexion.php")) {
    include_once($ruta_raiz_reg . "/conexion.php");
} else {
    include_once("conexion.php");
}

/**
 * 📌 RESPALDO DE COMPATIBILIDAD CON JAVASCRIPT:
 * Cuando usas JavaScript mediante `.submit()` para mandar el formulario,
 * los elementos <button> no viajan en el array $_POST.
 * Si detectamos los datos del usuario, forzamos la variable 'boton_registro' 
 * para procesar la cuenta sin problemas.
 */
if (!isset($_POST["boton_registro"]) && isset($_POST["nombre_de_usuario"])) {
    $_POST["boton_registro"] = "1";
}

// 📌 LOGIC EXCLUSIVA PARA CREAR NUEVAS CUENTAS AL PULSAR EL BOTÓN
if (isset($_POST["boton_registro"])) {
    
    // Capturamos las variables usando los 'name' reales de tu formulario HTML
    $usuario = isset($_POST["nombre_de_usuario"]) ? trim($_POST["nombre_de_usuario"]) : null;
    $contrasena_plana = isset($_POST["contrasena"]) ? $_POST["contrasena"] : null;
    $correo = isset($_POST["email"]) ? trim($_POST["email"]) : null;
    $cedula = isset($_POST["cedula"]) ? trim($_POST["cedula"]) : null;

    // Campos del modal de preguntas de seguridad
    $color = isset($_POST["color_favorito"]) ? trim($_POST["color_favorito"]) : null;
    $pelicula = isset($_POST["pelicula_favorita"]) ? trim($_POST["pelicula_favorita"]) : null;
    $mascota = isset($_POST["mascota_nombre"]) ? trim($_POST["mascota_nombre"]) : null;

    // Validación estricta para evitar fallos de columnas NULL en la BD
    if (empty($color) || empty($pelicula) || empty($mascota)) {
        echo "<script>alert('Error: Debes responder a todas las preguntas de seguridad del modal.'); window.history.back();</script>";
        exit();
    }

    if (!empty($usuario) && !empty($contrasena_plana)) {
        if (isset($Conexion)) {
            $Conexion->set_charset("utf8");

            // 1. Verificar si el usuario o la cédula ya existen
            $sqlCheck = "SELECT Id FROM usuarios WHERE Usuario = ? OR cedula = ?";
            if ($stmtCheck = $Conexion->prepare($sqlCheck)) {
                $stmtCheck->bind_param("ss", $usuario, $cedula);
                $stmtCheck->execute();
                $resCheck = $stmtCheck->get_result();

                if ($resCheck->num_rows > 0) {
                    echo "<script>alert('El nombre de usuario o la cédula ya se encuentran registrados.'); window.history.back();</script>";
                    exit();
                }
                $stmtCheck->close();
            }

            // 2. Insertar el nuevo usuario en la base de datos (Contraseña en texto plano)
            $sqlInsert = "INSERT INTO usuarios (Usuario, Clave, correo, cedula, rol, estado) VALUES (?, ?, ?, ?, 'usuario', 1)";
            if ($stmtInsert = $Conexion->prepare($sqlInsert)) {
                $stmtInsert->bind_param("ssss", $usuario, $contrasena_plana, $correo, $cedula);
                
                if ($stmtInsert->execute()) {
                    $id_nuevo_usuario = $Conexion->insert_id; // Obtenemos el ID generado para vincular las respuestas

                    // 3. Guardar las 3 respuestas vinculadas al catálogo de preguntas
                    $sqlRespuestas = "INSERT INTO usuario_respuestas (id_usuario, id_pregunta_cat, respuesta) VALUES (?, ?, ?)";
                    
                    if ($stmtRespuestas = $Conexion->prepare($sqlRespuestas)) {
                        // Guardar Respuesta 1: Color favorito (ID 1)
                        $id_cat_color = 1;
                        $stmtRespuestas->bind_param("iis", $id_nuevo_usuario, $id_cat_color, $color);
                        $stmtRespuestas->execute();

                        // Guardar Respuesta 2: Película favorita (ID 2)
                        $id_cat_pelicula = 2;
                        $stmtRespuestas->bind_param("iis", $id_nuevo_usuario, $id_cat_pelicula, $pelicula);
                        $stmtRespuestas->execute();

                        // Guardar Respuesta 3: Nombre de la mascota (ID 3)
                        $id_cat_mascota = 3;
                        $stmtRespuestas->bind_param("iis", $id_nuevo_usuario, $id_cat_mascota, $mascota);
                        $stmtRespuestas->execute();

                        $stmtRespuestas->close();
                    }

                    echo "<script>alert('¡Registro exitoso! Ya puedes iniciar sesión.'); window.location='login.php?registro=exitoso';</script>";
                    exit();
                } else {
                    echo "<script>alert('Error al guardar el usuario en la base de datos.'); window.history.back();</script>";
                }
                $stmtInsert->close();
            }
        } else {
            echo "<script>alert('Error de conexión: No se localizó el objeto de conexión de Base de datos.'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('Faltan campos obligatorios para proceder con el registro.'); window.history.back();</script>";
    }
}
?>