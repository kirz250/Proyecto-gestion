<?php
// 1. Forzamos la misma ruta global para encontrar la cookie exacta que queremos borrar
ini_set('session.cookie_path', '/'); 
session_start();

// 2. Limpiamos todas las variables de sesión en memoria
$_SESSION = array();

// 3. Destruimos la cookie física en el navegador del usuario si existe
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Destruimos la sesión en el servidor
session_destroy(); 

// 5. Redireccionamos de vuelta al formulario de acceso
header("Location: /iniciar sesion/login.php");
exit();
?>